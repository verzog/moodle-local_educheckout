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
 * External web service: get the current cart contents.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Returns the items and total in the current user's cart.
 */
class cart_get extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Return the cart contents.
     *
     * @return array
     */
    public static function execute(): array {
        global $USER;

        $context = \context_system::instance();
        self::validate_context($context);

        $cart = \local_educheckout\cart::get_open((int) $USER->id);
        $items = [];
        foreach ($cart->get_items() as $item) {
            $items[] = [
                'id' => (int) $item->id,
                'courseid' => (int) $item->courseid,
                'unitprice' => format_float((float) $item->unitprice, 2),
            ];
        }

        return [
            'items' => $items,
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
            'items' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'The cart item id'),
                'courseid' => new external_value(PARAM_INT, 'The course id'),
                'unitprice' => new external_value(PARAM_RAW, 'The formatted unit price'),
            ])),
            'total' => new external_value(PARAM_RAW, 'The formatted cart total'),
        ]);
    }
}
