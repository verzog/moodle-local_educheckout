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
 * Moodec category management page.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/moodec:manageproducts', $context);

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/category_manage.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('categories_title', 'local_moodec'));
$PAGE->set_heading(get_string('categories_title', 'local_moodec'));

// Handle delete — two-step: show confirmation page, then perform on confirm.
if ($action === 'delete' && $id > 0) {
    $confirmed = optional_param('confirmed', 0, PARAM_INT);
    if ($confirmed) {
        require_sesskey();
        $cat = \local_moodec\category::get($id);
        $cat->delete();
        redirect(new moodle_url('/local/moodec/category_manage.php'));
    }
    $confirmurl = new moodle_url('/local/moodec/category_manage.php', [
        'action' => 'delete',
        'id' => $id,
        'confirmed' => 1,
        'sesskey' => sesskey(),
    ]);
    $cancelurl = new moodle_url('/local/moodec/category_manage.php');
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('category_delete_confirm', 'local_moodec'), $confirmurl, $cancelurl);
    echo $OUTPUT->footer();
    exit;
}

// Category form (handles both create and edit).
$form = new \local_moodec\form\category_form(
    new moodle_url('/local/moodec/category_manage.php', ['id' => $id])
);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/moodec/category_manage.php'));
}

if ($formdata = $form->get_data()) {
    if ((int) $formdata->id > 0) {
        $cat = \local_moodec\category::get((int) $formdata->id);
        $cat->update(
            clean_param($formdata->name, PARAM_TEXT),
            clean_param($formdata->description ?? '', PARAM_TEXT),
            (int) $formdata->sortorder
        );
    } else {
        \local_moodec\category::create(
            clean_param($formdata->name, PARAM_TEXT),
            clean_param($formdata->description ?? '', PARAM_TEXT),
            (int) $formdata->sortorder
        );
    }
    redirect(new moodle_url('/local/moodec/category_manage.php'));
}

// Pre-fill form for editing an existing category.
if ($id > 0 && !$form->is_submitted()) {
    $cat = \local_moodec\category::get($id);
    $form->set_data([
        'id' => $cat->get_id(),
        'name' => $cat->get_name(),
        'description' => $cat->get_description(),
        'sortorder' => $cat->get_sortorder(),
    ]);
}

// Build category list.
$categories = \local_moodec\category::get_all();
$catlist = [];
foreach ($categories as $cat) {
    $catlist[] = [
        'id' => $cat->get_id(),
        'name' => format_string($cat->get_name()),
        'sortorder' => $cat->get_sortorder(),
        'editurl' => (new moodle_url('/local/moodec/category_manage.php', ['id' => $cat->get_id()]))->out(false),
        'deleteurl' => (new moodle_url('/local/moodec/category_manage.php', [
            'action' => 'delete',
            'id' => $cat->get_id(),
        ]))->out(false),
    ];
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('local_moodec/category_list', [
    'hascategories' => !empty($catlist),
    'categories' => $catlist,
    'manageurl' => (new moodle_url('/local/moodec/manage.php'))->out(false),
]);

echo $OUTPUT->heading(
    $id > 0 ? get_string('category_edit', 'local_moodec') : get_string('category_add', 'local_moodec'),
    3
);
$form->display();

echo $OUTPUT->footer();
