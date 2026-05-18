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
 * Moodec product management page.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/moodec:manageproducts', context_system::instance());

$action = optional_param('action', '', PARAM_ALPHA);
$productid = optional_param('productid', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/manage.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manage_title', 'local_moodec'));
$PAGE->set_heading(get_string('manage_title', 'local_moodec'));

// Handle toggle enable/disable.
if ($action === 'enable' || $action === 'disable') {
    require_sesskey();
    if ($productid > 0) {
        $product = new \local_moodec\product($productid);
        $product->set_enabled($action === 'enable');
    }
    redirect(new moodle_url('/local/moodec/manage.php'));
}

// Handle delete.
if ($action === 'delete') {
    require_sesskey();
    if ($productid > 0) {
        $product = new \local_moodec\product($productid);
        $product->delete();
    }
    redirect(new moodle_url('/local/moodec/manage.php'));
}

// Build the product list from all courses that have a product record,
// plus a list of courses that could be added.
$products = [];
$productrecords = $DB->get_records_sql(
    'SELECT p.id, p.course_id, p.is_enabled, p.sort_order, p.category_id,
            c.fullname, p.tags
       FROM {local_moodec_product} p
       JOIN {course} c ON c.id = p.course_id
      ORDER BY p.sort_order ASC, c.fullname ASC'
);
foreach ($productrecords as $record) {
    $products[] = [
        'id' => (int) $record->id,
        'fullname' => format_string($record->fullname),
        'is_enabled' => (bool) $record->is_enabled,
        'enabled_class' => $record->is_enabled ? 'badge-success' : 'badge-secondary',
        'tags' => (string) ($record->tags ?? ''),
        'editurl' => (new moodle_url('/local/moodec/product_edit.php', ['id' => $record->id]))->out(false),
        'toggleurl' => (new moodle_url('/local/moodec/manage.php', [
            'action' => $record->is_enabled ? 'disable' : 'enable',
            'productid' => $record->id,
            'sesskey' => sesskey(),
        ]))->out(false),
        'togglelabel' => $record->is_enabled
            ? get_string('product_disable', 'local_moodec')
            : get_string('product_enable', 'local_moodec'),
        'deleteurl' => (new moodle_url('/local/moodec/manage.php', [
            'action' => 'delete',
            'productid' => $record->id,
            'sesskey' => sesskey(),
        ]))->out(false),
    ];
}

$data = [
    'hasproducts' => !empty($products),
    'products' => array_values($products),
    'addurl' => (new moodle_url('/local/moodec/product_edit.php'))->out(false),
    'categoryurl' => (new moodle_url('/local/moodec/category_manage.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_moodec/manage', $data);
echo $OUTPUT->footer();
