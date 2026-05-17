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
 * External web service: add an item to the cart.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Adds a product (optionally a variation) to the current user's cart.
 */
class cart_add extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'productid' => new external_value(PARAM_INT, 'The product id'),
            'variationid' => new external_value(PARAM_INT, 'The variation id, or 0', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Add the product to the cart.
     *
     * @param int $productid the product id
     * @param int $variationid the variation id, or 0
     * @return array
     */
    public static function execute(int $productid, int $variationid = 0): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'productid' => $productid,
            'variationid' => $variationid,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);

        $product = new \local_moodec\product($params['productid']);
        $cart = \local_moodec\cart::get_open((int) $USER->id);
        $added = $cart->add_item(
            $product->get_id(),
            $params['variationid'],
            $product->get_course_id(),
            $product->get_price($params['variationid'])
        );

        return [
            'added' => $added,
            'total' => format_float($cart->get_total(), 2),
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'added' => new external_value(PARAM_BOOL, 'Whether the item was added'),
            'total' => new external_value(PARAM_RAW, 'The formatted cart total'),
        ]);
    }
}
