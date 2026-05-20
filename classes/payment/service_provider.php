<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Payment service provider for the EduCheckout storefront.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout\payment;

use local_educheckout\order;

/**
 * Tells core_payment what a cart order costs and what to do once it is paid.
 */
class service_provider implements \core_payment\local\callback\service_provider {
    /**
     * Return the payable for a given order.
     *
     * @param string $paymentarea the payment area (always 'cart')
     * @param int $itemid the local_educheckout_order id
     * @return \core_payment\local\entities\payable
     */
    public static function get_payable(string $paymentarea, int $itemid): \core_payment\local\entities\payable {
        global $DB;

        $record = $DB->get_record('local_educheckout_order', ['id' => $itemid], '*', MUST_EXIST);
        $accountid = (int) get_config('local_educheckout', 'paymentaccount');

        if (!$accountid) {
            throw new \moodle_exception('nogateways', 'local_educheckout');
        }

        return new \core_payment\local\entities\payable(
            (float) $record->amount,
            $record->currency,
            $accountid
        );
    }

    /**
     * Return the URL the user is sent to after a successful payment.
     *
     * @param string $paymentarea the payment area
     * @param int $itemid the local_educheckout_order id
     * @return \moodle_url
     */
    public static function get_success_url(string $paymentarea, int $itemid): \moodle_url {
        return new \moodle_url('/local/educheckout/receipt.php', ['id' => $itemid]);
    }

    /**
     * Deliver the order: enrol the buyer into each purchased course.
     *
     * @param string $paymentarea the payment area
     * @param int $itemid the local_educheckout_order id
     * @param int $paymentid the core payment id
     * @param int $userid the paying user id
     * @return bool
     */
    public static function deliver_order(string $paymentarea, int $itemid, int $paymentid, int $userid): bool {
        $order = order::instance($itemid);

        if ($order->is_delivered()) {
            return true;
        }

        try {
            $order->set_status('paid', $paymentid);
            $order->deliver($userid);
        } catch (\Throwable $e) {
            $order->set_status('failed');
            throw $e;
        }

        try {
            $user = \core_user::get_user($userid, '*', MUST_EXIST);
            $receipturl = (new \moodle_url(
                '/local/educheckout/receipt.php',
                ['id' => $order->get_id()]
            ))->out(false);
            $cost = \core_payment\helper::get_cost_as_string(
                $order->get_amount(),
                $order->get_currency()
            );
            $a = (object) [
                'orderid' => $order->get_id(),
                'cost'    => $cost,
                'receipturl' => $receipturl,
            ];
            $msg = new \core\message\message();
            $msg->component         = 'local_educheckout';
            $msg->name              = 'order_receipt';
            $msg->userfrom          = \core_user::get_noreply_user();
            $msg->userto            = $user;
            $msg->subject           = get_string('email_order_receipt_subject', 'local_educheckout', $order->get_id());
            $msg->fullmessage       = get_string('email_order_receipt_body', 'local_educheckout', $a);
            $msg->fullmessageformat = FORMAT_PLAIN;
            $msg->fullmessagehtml   = '';
            $msg->smallmessage      = $msg->subject;
            $msg->notification      = 1;
            $msg->contexturl        = $receipturl;
            $msg->contexturlname    = get_string('ordersummary', 'local_educheckout');
            message_send($msg);
        } catch (\Throwable $e) {
            debugging('EduCheckout: failed to send order receipt: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return true;
    }
}
