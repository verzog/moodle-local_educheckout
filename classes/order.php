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
 * Order model for the Moodec storefront.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

/**
 * Creates orders from carts and tracks their payment/delivery status.
 */
class order {
    /** @var \stdClass the order record */
    protected $record;

    /**
     * Wrap an order record.
     *
     * @param \stdClass $record a row from {local_moodec_order}
     */
    protected function __construct(\stdClass $record) {
        $this->record = $record;
    }

    /**
     * Create a pending order from a cart, computing net/tax/gross.
     *
     * @param cart $cart the source cart
     * @param int $userid the purchasing user id
     * @param string|null $country customer ISO country code for tax resolution
     * @return order
     */
    public static function create_from_cart(cart $cart, int $userid, ?string $country): order {
        global $DB;

        $rate = tax::resolve_rate($country);
        $now = time();
        $net = 0.0;
        $taxtotal = 0.0;
        $gross = 0.0;
        $lines = [];

        foreach ($cart->get_items() as $item) {
            $split = tax::split((float) $item->unitprice, $rate);
            $net += $split['net'];
            $taxtotal += $split['tax'];
            $gross += $split['gross'];
            $lines[] = (object) [
                'productid' => (int) $item->productid,
                'variationid' => (int) $item->variationid,
                'courseid' => (int) $item->courseid,
                'unitprice' => $split['net'],
                'nettax' => $split['tax'],
                'enrolled' => 0,
            ];
        }

        $order = (object) [
            'userid' => $userid,
            'cartid' => $cart->get_id(),
            'currency' => $cart->get_currency(),
            'netamount' => round($net, 2),
            'taxamount' => round($taxtotal, 2),
            'taxrate' => $rate,
            'taxinclusive' => get_config('local_moodec', 'tax_mode') === 'inclusive' ? 1 : 0,
            'amount' => round($gross, 2),
            'status' => 'pending',
            'paymentid' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $order->id = $DB->insert_record('local_moodec_order', $order);

        foreach ($lines as $line) {
            $line->orderid = $order->id;
            $DB->insert_record('local_moodec_order_item', $line);
        }

        return new self($order);
    }

    /**
     * Return the order id.
     *
     * @return int
     */
    public function get_id(): int {
        return (int) $this->record->id;
    }

    /**
     * Return the gross amount payable.
     *
     * @return float
     */
    public function get_amount(): float {
        return (float) $this->record->amount;
    }

    /**
     * Return the order currency code.
     *
     * @return string
     */
    public function get_currency(): string {
        return (string) $this->record->currency;
    }

    /**
     * Return the order line items.
     *
     * @return array records from {local_moodec_order_item}
     */
    public function get_items(): array {
        global $DB;
        return $DB->get_records('local_moodec_order_item', ['orderid' => $this->get_id()]);
    }

    /**
     * Set the status and payment id, updating the modified timestamp.
     *
     * @param string $status one of pending|paid|delivered|failed|cancelled
     * @param int|null $paymentid the core payment id, if known
     * @return void
     */
    public function set_status(string $status, ?int $paymentid = null): void {
        global $DB;

        $this->record->status = $status;
        $this->record->timemodified = time();
        if ($paymentid !== null) {
            $this->record->paymentid = $paymentid;
        }
        $DB->update_record('local_moodec_order', $this->record);
    }

    /**
     * Mark a single order item as delivered (enrolled).
     *
     * @param int $itemid the order item id
     * @return void
     */
    public function mark_item_enrolled(int $itemid): void {
        global $DB;
        $DB->set_field('local_moodec_order_item', 'enrolled', 1, ['id' => $itemid, 'orderid' => $this->get_id()]);
    }
}
