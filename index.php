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
 * EduCheckout catalogue page.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login(null, true);

$categoryid = optional_param('category', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/educheckout/index.php', ['category' => $categoryid, 'page' => $page]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('catalogue', 'local_educheckout'));
$PAGE->set_heading(get_string('catalogue', 'local_educheckout'));

$data = \local_educheckout\catalogue::export_for_template($categoryid, $page);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_educheckout/catalogue', $data);
echo $OUTPUT->footer();
