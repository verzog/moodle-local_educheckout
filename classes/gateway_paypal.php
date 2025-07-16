<?php
/**
 * Moodec Gateway Paypal
 *
 * @package     local
 * @subpackage  local_moodec
 * @author     Thomas Threadgold
 * @copyright  2015 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

use moodle_url;
use stdClass;
use core\message\message;

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

class gateway_paypal extends gateway {

    public function __construct($transaction) {
        parent::__construct($transaction);

        $this->_gatewayName = get_string('payment_paypal_title', 'local_moodec');
        $this->_gatewayURL = get_config('local_moodec', 'payment_paypal_sandbox')
            ? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
            : 'https://www.paypal.com/cgi-bin/webscr';
    }

    public function handle($data = null): bool {
        global $DB, $CFG;

        $this->_transaction->set_gateway(MOODEC_GATEWAY_PAYPAL);

        if ($this->_transaction->get_status() === \MoodecTransaction::STATUS_COMPLETE) {
            return false;
        }

        if (is_null($data)) {
            $this->_transaction->fail();
            return false;
        }

        if (!empty($data->txn_id)) {
            $this->_transaction->set_txn_id($data->txn_id);
        }

        if (!in_array($data->payment_status, ['Completed', 'Pending'])) {
            foreach ($this->_transaction->get_items() as $item) {
                $product = local_moodec_get_product($item->get_product_id());
                $instance = $DB->get_record('enrol', ['courseid' => $product->get_course_id(), 'enrol' => 'moodec']);
                $this->_enrolPlugin->unenrol_user($instance, $this->_transaction->get_user_id());
            }
            $this->send_error_to_admin("Status not completed or pending. User unenrolled.", (array)$data);
            $this->_transaction->fail();
            return false;
        }

        if ($data->mc_currency != get_config('local_moodec', 'currency')) {
            $this->send_error_to_admin("Currency mismatch: {$data->mc_currency}", (array)$data);
            $this->_transaction->fail();
            return false;
        }

        if ($data->payment_status == "Pending" && $data->pending_reason != "echeck") {
            $admin = get_admin();
            $user = $DB->get_record('user', ['id' => $this->_transaction->get_user_id()]);
            $msg = new stdClass();
            $msg->component = 'local_moodec';
            $msg->name = 'local_moodec_payment';
            $msg->userfrom = $admin;
            $msg->userto = $user;
            $msg->subject = "Moodle: PayPal payment";
            $msg->fullmessage = "Your PayPal payment is pending.";
            $msg->fullmessageformat = FORMAT_PLAIN;
            $msg->fullmessagehtml = '';
            $msg->smallmessage = '';
            message_send($msg);

            $this->send_error_to_admin("Payment pending", (array)$data);
            $this->_transaction->pending();
            return false;
        }

        if (core_text::strtolower($data->business) !== core_text::strtolower(get_config('local_moodec', 'payment_paypal_email'))) {
            $this->send_error_to_admin("PayPal business email mismatch", (array)$data);
            $this->_transaction->fail();
            return false;
        }

        if ($data->mc_gross < $this->_transaction->get_cost()) {
            $this->send_error_to_admin("Amount too low: {$data->mc_gross} < {$this->_transaction->get_cost()}", (array)$data);
            $this->_transaction->fail();
            return false;
        }

        if ($this->verify_transaction()) {
            $this->complete_enrolment();
            return true;
        }

        return false;
    }

    public function render(): string {
        global $CFG;
        $html = sprintf('<form action="%s" method="POST" class="payment-gateway gateway--paypal">', $this->_gatewayURL);

        $html .= sprintf('
            <input type="hidden" name="cmd" value="_cart">
            <input type="hidden" name="charset" value="utf-8">
            <input type="hidden" name="upload" value="1">
            <input type="hidden" name="for_auction" value="false">
            <input type="hidden" name="no_note" value="1">
            <input type="hidden" name="no_shipping" value="1">
            <input type="hidden" name="business" value="%s">
            <input type="hidden" name="currency_code" value="%s">
            <input type="hidden" name="custom" value="%d">
            <input type="hidden" name="notify_url" value="%s">
            <input type="hidden" name="return" value="%s">
            <input type="hidden" name="cancel_return" value="%s">',
            get_config('local_moodec', 'payment_paypal_email'),
            get_config('local_moodec', 'currency'),
            $this->_transaction->get_id(),
            new moodle_url($CFG->wwwroot . '/local/moodec/payment/paypal/ipn.php'),
            new moodle_url($CFG->wwwroot . '/my'),
            new moodle_url($CFG->wwwroot . '/local/moodec/pages/cart.php')
        );

        $count = 1;
        foreach ($this->_transaction->get_items() as $item) {
            $product = local_moodec_get_product($item->get_product_id());
            $name = $product->get_type() === PRODUCT_TYPE_SIMPLE
                ? $product->get_fullname()
                : $product->get_fullname() . ' - ' . $product->get_variation($item->get_variation_id())->get_name();

            $html .= sprintf('<input type="hidden" name="item_name_%d" value="%s">', $count, $name);
            $html .= sprintf('<input type="hidden" name="amount_%d" value="%s">', $count, $item->get_cost());
            $count++;
        }

        $html .= sprintf('<input type="submit" name="submit" value="%s">', get_string('button_paypal_label', 'local_moodec'));
        $html .= '</form>';

        return $html;
    }
}
