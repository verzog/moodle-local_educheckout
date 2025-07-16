<?php
/**
 * Moodec Gateway DPS
 *
 * @package     local
 * @subpackage  local_moodec
 * @author     Vernon Spain - Formerly Thomas Threadgold
 * @copyright  2015 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

use DomDocument;
use moodle_url;
use core\message\message;

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_once($CFG->libdir . '/filelib.php');

class gateway_dps extends gateway {

    protected $_internalGatewayURL;

    public function __construct($transaction) {
        global $CFG;

        parent::__construct($transaction);

        $this->_gatewayName = get_string('payment_dps_title', 'local_moodec');
        $this->_internalGatewayURL = new moodle_url($CFG->wwwroot . '/local/moodec/payment/dps/index.php');

        $this->_gatewayURL = get_config('local_moodec', 'payment_dps_sandbox')
            ? 'https://uat.paymentexpress.com/pxaccess/pxpay.aspx'
            : 'https://sec.paymentexpress.com/pxaccess/pxpay.aspx';
    }

    public function get_dom(string $xml): \SimpleXMLElement {
        $dom = new DomDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);
        return simplexml_import_dom($dom);
    }

    public function query(string $data): string {
        $curl = new \curl();
        return $curl->post($this->_gatewayURL, $data, ['returntransfer' => true]);
    }

    public function begin(): \SimpleXMLElement {
        global $CFG, $USER;

        $txnId = time() . $this->_transaction->get_id();
        $site = get_site();

        $xmlrequest = sprintf(
            "<GenerateRequest>
                <PxPayUserId>%s</PxPayUserId>
                <PxPayKey>%s</PxPayKey>
                <AmountInput>%.2f</AmountInput>
                <CurrencyInput>%s</CurrencyInput>
                <MerchantReference>%s</MerchantReference>
                <EmailAddress>%s</EmailAddress>
                <TxnData1>%d</TxnData1>
                <TxnData2>%s</TxnData2>
                <TxnData3>%s</TxnData3>
                <TxnType>Purchase</TxnType>
                <TxnId>%d</TxnId>
                <BillingId></BillingId>
                <EnableAddBillCard>0</EnableAddBillCard>
                <UrlSuccess>%s</UrlSuccess>
                <UrlFail>%s</UrlFail>
                <Opt></Opt>
            </GenerateRequest>",
            clean_param(get_config('local_moodec', 'payment_dps_userid'), PARAM_CLEAN),
            clean_param(get_config('local_moodec', 'payment_dps_key'), PARAM_CLEAN),
            clean_param($this->_transaction->get_cost(), PARAM_CLEAN),
            clean_param(get_config('local_moodec', 'currency'), PARAM_CLEAN),
            clean_param('Transaction #' . $this->_transaction->get_id(), PARAM_CLEAN),
            clean_param($USER->email, PARAM_CLEAN),
            clean_param($txnId, PARAM_CLEAN),
            clean_param(substr($site->shortname, 0, 50), PARAM_CLEAN),
            clean_param(substr("{$USER->lastname}, {$USER->firstname}", 0, 50), PARAM_CLEAN),
            clean_param($txnId, PARAM_CLEAN),
            new moodle_url($CFG->wwwroot . '/local/moodec/payment/dps/success.php'),
            new moodle_url($CFG->wwwroot . '/local/moodec/payment/dps/fail.php')
        );

        $this->_transaction->set_gateway(MOODEC_GATEWAY_DPS);
        $this->_transaction->set_txn_id($txnId);
        $this->_transaction->pending();

        return $this->get_dom($this->query($xmlrequest));
    }

    public function abort($data): void {
        $xmlrequest = sprintf(
            "<ProcessResponse>
                <PxPayUserId>%s</PxPayUserId>
                <PxPayKey>%s</PxPayKey>
                <Response>%s</Response>
            </ProcessResponse>",
            clean_param(get_config('local_moodec', 'payment_dps_userid'), PARAM_CLEAN),
            clean_param(get_config('local_moodec', 'payment_dps_key'), PARAM_CLEAN),
            $data
        );

        $response = $this->get_dom($this->query($xmlrequest));
        $this->send_error_to_admin("DPS transaction failed!", (array)$response);
        $this->_transaction->fail();
    }

    public function handle($data = null): bool {
        if ($this->_transaction->get_status() === \MoodecTransaction::STATUS_COMPLETE) {
            return true;
        }

        if (is_null($data)) {
            $this->_transaction->fail();
            return false;
        }

        $xmlrequest = sprintf(
            "<ProcessResponse>
                <PxPayUserId>%s</PxPayUserId>
                <PxPayKey>%s</PxPayKey>
                <Response>%s</Response>
            </ProcessResponse>",
            clean_param(get_config('local_moodec', 'payment_dps_userid'), PARAM_CLEAN),
            clean_param(get_config('local_moodec', 'payment_dps_key'), PARAM_CLEAN),
            $data
        );

        $response = $this->get_dom($this->query($xmlrequest));

        if ($response === false || $response->attributes()->valid != '1') {
            $this->send_error_to_admin("DPS transaction was not valid!", (array)$response);
            $this->_transaction->fail();
            return false;
        }

        if ($this->_transaction->get_txn_id() != $response->TxnId) {
            $this->send_error_to_admin("Transaction IDs do not match!", (array)$response);
            $this->_transaction->fail();
            return false;
        }

        if ($response->CurrencySettlement != get_config('local_moodec', 'currency')) {
            $this->send_error_to_admin("Currency mismatch: received {$response->CurrencySettlement}", (array)$response);
            $this->_transaction->fail();
            return false;
        }

        if ($response->AmountSettlement < $this->_transaction->get_cost()) {
            $this->send_error_to_admin("Amount paid too low: {$response->AmountSettlement} < {$this->_transaction->get_cost()}", (array)$response);
            $this->_transaction->fail();
            return false;
        }

        if ($response->Success == 1 && $response->ResponseText == "APPROVED") {
            if ($this->verify_transaction()) {
                $this->complete_enrolment();
                return true;
            }
        }

        $this->send_error_to_admin("Unhandled error completing DPS transaction", (array)$response);
        return false;
    }

    public function render(): string {
        return sprintf(
            '<form action="%s" method="POST" class="payment-gateway gateway--dps">
                <input type="hidden" name="id" value="%s">
                <input type="submit" name="submit" value="%s">
            </form>',
            $this->_internalGatewayURL,
            $this->_transaction->get_id(),
            get_string('button_dps_label', 'local_moodec')
        );
    }
}
