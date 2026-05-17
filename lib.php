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
 * Library callbacks for the Moodec storefront plugin.
 *
 * @package    local_moodec
 * @copyright  2015 LearningWorks Ltd
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add the storefront entry to the global navigation.
 *
 * @param global_navigation $navigation
 * @return void
 */
function local_moodec_extend_navigation(global_navigation $navigation) {
    $node = $navigation->add(
        get_string('catalogue', 'local_moodec'),
        new moodle_url('/local/moodec/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_moodec'
    );
    $node->showinflatnavigation = true;
}
