<?php
/**
 * Moodec Variable Product
 *
 * @package     local
 * @subpackage  local_moodec
 * @author      Vernon Spain formerly Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

use moodle_exception;

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/product.php');
require_once(__DIR__ . '/product_variation.php');

class product_variable extends product {
    /**
     * @var product_variation[]
     */
    protected array $_variations = [];

    public function __construct(?int $id = null) {
        parent::__construct($id);

        if (!is_null($id)) {
            $this->load_variations();
        }
    }

    /**
     * Load all variations for this product.
     */
    protected function load_variations(): void {
        global $DB;

        $records = $DB->get_records('local_moodec_product_variation', ['product_id' => $this->_id]);

        foreach ($records as $record) {
            $this->_variations[] = new product_variation($record);
        }
    }

    /**
     * Get a variation by ID.
     */
    public function get_variation(int $id): ?product_variation {
        foreach ($this->_variations as $variation) {
            if ($variation->get_id() === $id) {
                return $variation;
            }
        }
        return null;
    }

    /**
     * Get all variations.
     */
    public function get_variations(): array {
        return $this->_variations;
    }

    /**
     * Returns true if there are any variations.
     */
    public function has_variations(): bool {
        return !empty($this->_variations);
    }
}
