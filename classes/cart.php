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
 * DB-backed shopping cart for the Moodec storefront.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

/**
 * Loads and mutates the current open cart for a user or guest session.
 */
class cart {
    /** @var \stdClass the cart record */
    protected $record;

    /**
     * Wrap a cart record.
     *
     * @param \stdClass $record a row from {local_moodec_cart}
     */
    protected function __construct(\stdClass $record) {
        $this->record = $record;
    }

    /**
     * Get (or create) the open cart for a user, or for a guest session key.
     *
     * @param int $userid the user id, or 0 for a guest cart
     * @param string|null $sessionkey guest session key (required when userid is 0)
     * @return cart
     */
    public static function get_open(int $userid, ?string $sessionkey = null): cart {
        global $DB;

        if ($userid > 0) {
            $conditions = ['userid' => $userid, 'status' => 'open'];
        } else {
            $conditions = ['userid' => 0, 'sessionkey' => (string) $sessionkey, 'status' => 'open'];
        }

        $record = $DB->get_record('local_moodec_cart', $conditions);
        if (!$record) {
            $now = time();
            $record = (object) [
                'userid' => $userid,
                'sessionkey' => $userid > 0 ? null : (string) $sessionkey,
                'currency' => (string) (get_config('local_moodec', 'currency') ?: 'AUD'),
                'status' => 'open',
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $record->id = $DB->insert_record('local_moodec_cart', $record);
        }

        return new self($record);
    }

    /**
     * Find an open guest cart for a session key without creating one.
     *
     * @param string|null $sessionkey the guest session key
     * @return cart|null
     */
    public static function find_guest(?string $sessionkey): ?cart {
        global $DB;

        if ($sessionkey === null || $sessionkey === '') {
            return null;
        }
        $record = $DB->get_record('local_moodec_cart', [
            'userid' => 0,
            'sessionkey' => $sessionkey,
            'status' => 'open',
        ]);
        return $record ? new self($record) : null;
    }

    /**
     * Return the cart id.
     *
     * @return int
     */
    public function get_id(): int {
        return (int) $this->record->id;
    }

    /**
     * Return the cart currency code.
     *
     * @return string
     */
    public function get_currency(): string {
        return (string) $this->record->currency;
    }

    /**
     * Return the items in this cart.
     *
     * @return array records from {local_moodec_cart_item}
     */
    public function get_items(): array {
        global $DB;
        return $DB->get_records('local_moodec_cart_item', ['cartid' => $this->get_id()]);
    }

    /**
     * Whether the cart has no items.
     *
     * @return bool
     */
    public function is_empty(): bool {
        global $DB;
        return !$DB->record_exists('local_moodec_cart_item', ['cartid' => $this->get_id()]);
    }

    /**
     * Sum of the cart line prices (pre-tax).
     *
     * @return float
     */
    public function get_total(): float {
        global $DB;
        $total = $DB->get_field_sql(
            'SELECT SUM(unitprice) FROM {local_moodec_cart_item} WHERE cartid = :cartid',
            ['cartid' => $this->get_id()]
        );
        return (float) ($total ?: 0);
    }

    /**
     * Add a product (optionally a variation) to the cart if not already present.
     *
     * @param int $productid the product id
     * @param int $variationid the variation id, or 0
     * @param int $courseid the course id to enrol into
     * @param float $unitprice the price at time of adding
     * @return bool true if added, false if it was already in the cart
     */
    public function add_item(int $productid, int $variationid, int $courseid, float $unitprice): bool {
        global $DB;

        $existing = $DB->record_exists('local_moodec_cart_item', [
            'cartid' => $this->get_id(),
            'productid' => $productid,
            'variationid' => $variationid,
        ]);
        if ($existing) {
            return false;
        }

        $DB->insert_record('local_moodec_cart_item', (object) [
            'cartid' => $this->get_id(),
            'productid' => $productid,
            'variationid' => $variationid,
            'courseid' => $courseid,
            'unitprice' => $unitprice,
            'timecreated' => time(),
        ]);
        $this->touch();
        return true;
    }

    /**
     * Remove an item from the cart.
     *
     * @param int $itemid the cart item id
     * @return void
     */
    public function remove_item(int $itemid): void {
        global $DB;
        $DB->delete_records('local_moodec_cart_item', ['id' => $itemid, 'cartid' => $this->get_id()]);
        $this->touch();
    }

    /**
     * Merge another cart's items into this one, then cancel the source cart.
     *
     * @param cart $other the cart to absorb
     * @return void
     */
    public function merge_from(cart $other): void {
        global $DB;

        if ($other->get_id() === $this->get_id()) {
            return;
        }
        foreach ($other->get_items() as $item) {
            $this->add_item(
                (int) $item->productid,
                (int) $item->variationid,
                (int) $item->courseid,
                (float) $item->unitprice
            );
        }
        $DB->delete_records('local_moodec_cart_item', ['cartid' => $other->get_id()]);
        $DB->set_field('local_moodec_cart', 'status', 'cancelled', ['id' => $other->get_id()]);
    }

    /**
     * Mark this cart as ordered (called once an order is created).
     *
     * @return void
     */
    public function mark_ordered(): void {
        global $DB;
        $DB->set_field('local_moodec_cart', 'status', 'ordered', ['id' => $this->get_id()]);
    }

    /**
     * Update the modified timestamp.
     *
     * @return void
     */
    protected function touch(): void {
        global $DB;
        $DB->set_field('local_moodec_cart', 'timemodified', time(), ['id' => $this->get_id()]);
    }
}
