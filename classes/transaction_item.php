<?php
/**
 * Moodec Transaction Item
 *
 * @package     local_moodec
 * @author      Vernon Spain - formerly Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

defined('MOODLE_INTERNAL') || die();

class transaction_item {
    protected int $id;
    protected int $transactionid;
    protected int $productid;
    protected ?int $variationid;
    protected float $cost;

    public function __construct(\stdClass $record) {
        $this->id = (int) $record->id;
        $this->transactionid = (int) $record->transaction_id;
        $this->productid = (int) $record->product_id;
        $this->variationid = isset($record->variation_id) ? (int) $record->variation_id : null;
        $this->cost = (float) $record->cost;
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_transaction_id(): int {
        return $this->transactionid;
    }

    public function get_product_id(): int {
        return $this->productid;
    }

    public function get_variation_id(): ?int {
        return $this->variationid;
    }

    public function get_cost(): float {
        return $this->cost;
    }
}
