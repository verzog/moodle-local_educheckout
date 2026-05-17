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
 * Product model for the Moodec storefront.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

/**
 * Read-only representation of a saleable course (product) and its variations.
 */
class product {
    /** @var int the product id */
    protected $id;

    /** @var int the Moodle course id */
    protected $courseid;

    /** @var bool whether the product is enabled for sale */
    protected $enabled;

    /** @var string the course full name */
    protected $fullname;

    /** @var array variation records keyed by variation id */
    protected $variations;

    /**
     * Load a product by id.
     *
     * @param int $id the product id
     */
    public function __construct(int $id) {
        global $DB;

        $sql = 'SELECT p.id, p.course_id, p.is_enabled, c.fullname
                  FROM {local_moodec_product} p
                  JOIN {course} c ON c.id = p.course_id
                 WHERE p.id = :id';
        $record = $DB->get_record_sql($sql, ['id' => $id], MUST_EXIST);

        $this->id = (int) $record->id;
        $this->courseid = (int) $record->course_id;
        $this->enabled = (bool) $record->is_enabled;
        $this->fullname = (string) $record->fullname;
        $this->variations = $DB->get_records('local_moodec_variation', ['product_id' => $this->id]);
    }

    /**
     * Return the product id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Return the associated course id.
     *
     * @return int
     */
    public function get_course_id(): int {
        return $this->courseid;
    }

    /**
     * Whether the product is enabled for sale.
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return $this->enabled;
    }

    /**
     * Return the course full name.
     *
     * @return string
     */
    public function get_fullname(): string {
        return $this->fullname;
    }

    /**
     * Return the variation records keyed by id.
     *
     * @return array
     */
    public function get_variations(): array {
        return $this->variations;
    }

    /**
     * Return a single variation record, or null if it does not belong to this product.
     *
     * @param int $variationid the variation id
     * @return \stdClass|null
     */
    public function get_variation(int $variationid): ?\stdClass {
        return $this->variations[$variationid] ?? null;
    }

    /**
     * Return the lowest variation price for the product.
     *
     * @return float
     */
    public function get_price(): float {
        $prices = [];
        foreach ($this->variations as $variation) {
            $prices[] = (float) $variation->price;
        }
        return $prices ? min($prices) : 0.0;
    }

    /**
     * Return all enabled products.
     *
     * @return product[]
     */
    public static function get_enabled(): array {
        global $DB;

        $ids = $DB->get_fieldset_select('local_moodec_product', 'id', 'is_enabled = :enabled', ['enabled' => 1]);
        $products = [];
        foreach ($ids as $id) {
            $products[(int) $id] = new self((int) $id);
        }
        return $products;
    }
}
