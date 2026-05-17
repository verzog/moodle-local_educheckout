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
 * Payment service provider for the Moodec storefront.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec\payment;

use local_moodec\order;

/**
 * Tells core_payment what a cart order costs and what to do once it is paid.
 */
class service_provider implements \core_payment\local\callback\service_provider {
    /**
     * Return the payable for a given order.
     *
     * @param string $paymentarea the payment area (always 'cart')
     * @param int $itemid the local_moodec_order id
     * @return \core_payment\local\entities\payable
     */
    public static function get_payable(string $paymentarea, int $itemid): \core_payment\local\entities\payable {
        global $DB;

        $record = $DB->get_record('local_moodec_order', ['id' => $itemid], '*', MUST_EXIST);
        $accountid = (int) get_config('local_moodec', 'paymentaccount');

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
     * @param int $itemid the local_moodec_order id
     * @return \moodle_url
     */
    public static function get_success_url(string $paymentarea, int $itemid): \moodle_url {
        return new \moodle_url('/local/moodec/receipt.php', ['id' => $itemid]);
    }

    /**
     * Deliver the order: enrol the buyer into each purchased course.
     *
     * @param string $paymentarea the payment area
     * @param int $itemid the local_moodec_order id
     * @param int $paymentid the core payment id
     * @param int $userid the paying user id
     * @return bool
     */
    public static function deliver_order(string $paymentarea, int $itemid, int $paymentid, int $userid): bool {
        $order = order::instance($itemid);
        $order->set_status('paid', $paymentid);
        $order->deliver($userid);
        return true;
    }
}
