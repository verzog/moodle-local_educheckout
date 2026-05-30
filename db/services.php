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
 * External web service definitions for the EduCheckout storefront plugin.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_educheckout_cart_add' => [
        'classname' => 'local_educheckout\\external\\cart_add',
        'methodname' => 'execute',
        'description' => 'Add a product to the current cart.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => false,
    ],
    'local_educheckout_cart_remove' => [
        'classname' => 'local_educheckout\\external\\cart_remove',
        'methodname' => 'execute',
        'description' => 'Remove an item from the current cart.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => false,
    ],
    'local_educheckout_cart_get' => [
        'classname' => 'local_educheckout\\external\\cart_get',
        'methodname' => 'execute',
        'description' => 'Get the current cart contents.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => false,
    ],
];
