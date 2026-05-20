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
 * Library callbacks for the EduCheckout storefront plugin.
 *
 * @package    local_educheckout
 * @copyright  2015 Thomas Threadgold, LearningWorks Ltd
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add the storefront entry to the global navigation for logged-in users.
 *
 * @param global_navigation $navigation
 * @return void
 */
function local_educheckout_extend_navigation(global_navigation $navigation) {
    if (!isloggedin() || isguestuser()) {
        return;
    }
    $node = $navigation->add(
        get_string('catalogue', 'local_educheckout'),
        new moodle_url('/local/educheckout/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_educheckout'
    );
    $node->showinflatnavigation = true;
}

/**
 * Serve product image files from the product_image file area.
 *
 * @param \stdClass $course not used
 * @param \stdClass $cm not used
 * @param \context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if not found
 */
function local_educheckout_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea !== 'product_image') {
        return false;
    }

    require_login(null, true);

    $itemid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? ('/' . implode('/', $args) . '/') : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_educheckout', 'product_image', $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
