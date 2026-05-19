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
 * Behat data generator for the EduCheckout storefront plugin.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generates EduCheckout Behat test data.
 */
class behat_local_educheckout_generator extends behat_generator_base {
    /**
     * Declare entities that can be created via "the following ... exist:" steps.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'product' => [
                'datagenerator' => 'product',
                'required' => ['course'],
            ],
        ];
    }
}
