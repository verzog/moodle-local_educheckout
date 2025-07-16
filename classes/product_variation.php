<?php
/**
 * Moodec Product Variation
 *
 * @package     local
 * @subpackage  local_moodec
 * @author      Vernon Spain - formerly Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

use stdClass;

defined('MOODLE_INTERNAL') || die();

class product_variation {
    protected int $_id;
    protected int $_productid;
    protected string $_name;
    protected float $_cost;
    protected string $_sku;

    /**
     * Constructor accepts either an ID or a DB record.
     */
    public function __construct(int|stdClass $data) {
        if (is_int($data)) {
            $this->load($data);
        } else {
            $this->populate_from_record($data);
        }
    }

    /**
     * Populate object from DB record.
     */
    protected function populate_from_record(stdClass $record): void {
        $this->_id = (int) $record->id;
        $this->_productid = (int) $record->product_id;
        $this->_name = (string) $record->name;
        $this->_cost = (float) $record->cost;
        $this->_sku = (string) $record->sku;
    }

    /**
     * Load variation from database.
     */
    protected function load(int $id): void {
        global $DB;

        $record = $DB->get_record('local_moodec_product_variation', ['id' => $id], '*', MUST_EXIST);
        $this->populate_from_record($record);
    }

    public function get_id(): int {
        return $this->_id;
    }

    public function get_product_id(): int {
        return $this->_productid;
    }

    public function get_name(): string {
        return $this->_name;
    }

    public function get_cost(): float {
        return $this->_cost;
    }

    public function get_sku(): string {
        return $this->_sku;
    }

    public function has_sku(): bool {
        return !empty($this->_sku);
    }
}
