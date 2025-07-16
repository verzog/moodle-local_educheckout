<?php
/**
 * Moodec Simple Product
 *
 * @package     local
 * @subpackage  local_moodec
 * @author      Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

use moodle_exception;

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
require_once(__DIR__ . '/product.php');

class product_simple extends product {
    protected float $_price;

    public function __construct(?int $id = null) {
        parent::__construct($id);

        if (!is_null($id)) {
            $this->load_simple_data($id);
        }
    }

    /**
     * Load simple product specific data.
     */
    protected function load_simple_data(int $id): void {
        global $DB;

        $record = $DB->get_record('local_moodec_product_simple', ['product_id' => $id], '*', IGNORE_MISSING);

        if ($record) {
            $this->_price = (float) $record->price;
        } else {
            throw new moodle_exception('missingproductpricedata', 'local_moodec', '', $id);
        }
    }

    public function get_price(): float {
        return $this->_price;
    }

    public function has_price(): bool {
        return $this->_price > 0;
    }
}
