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
 * Moodec product edit page (create / edit product + manage variations).
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
$varaction = optional_param('varaction', '', PARAM_ALPHA);
$varid = optional_param('varid', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/product_edit.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');

$isnew = ($id === 0);
$product = null;

if (!$isnew) {
    $product = new \local_moodec\product($id);
    $PAGE->set_title(get_string('product_edit_title', 'local_moodec'));
    $PAGE->set_heading(get_string('product_edit_title', 'local_moodec'));
} else {
    $PAGE->set_title(get_string('product_add_title', 'local_moodec'));
    $PAGE->set_heading(get_string('product_add_title', 'local_moodec'));
}

// Handle variation actions before rendering the form.
if (!$isnew && $product !== null) {
    if ($varaction === 'deletevar' && $varid > 0) {
        require_sesskey();
        $product->delete_variation($varid);
        redirect(new moodle_url('/local/moodec/product_edit.php', ['id' => $id]));
    }
}

// Build category options for the product form.
$categories = \local_moodec\category::get_all();
$categoryoptions = [];
foreach ($categories as $cat) {
    $categoryoptions[$cat->get_id()] = $cat->get_name();
}

$productform = new \local_moodec\form\product_form(
    new moodle_url('/local/moodec/product_edit.php', ['id' => $id]),
    ['productid' => $id, 'categoryoptions' => $categoryoptions]
);

if ($productform->is_cancelled()) {
    redirect(new moodle_url('/local/moodec/manage.php'));
}

if ($formdata = $productform->get_data()) {
    if ($isnew) {
        $product = \local_moodec\product::create((int) $formdata->course_id);
        $id = $product->get_id();
    }

    // Save file area.
    file_save_draft_area_files(
        (int) $formdata->image,
        $context->id,
        'local_moodec',
        'product_image',
        $product->get_id(),
        ['subdirs' => 0, 'maxfiles' => 1]
    );

    $descdata = $formdata->description_editor;
    $product->save(
        ($formdata->category_id > 0) ? (int) $formdata->category_id : null,
        clean_param($formdata->tags ?? '', PARAM_TEXT),
        $descdata['text'],
        (int) $descdata['format'],
        (int) ($formdata->sort_order ?? 0)
    );
    $product->set_enabled(!empty($formdata->is_enabled));
    $product->set_type(clean_param($formdata->type ?? '', PARAM_ALPHA));

    redirect(
        new moodle_url('/local/moodec/product_edit.php', ['id' => $product->get_id()]),
        get_string('product_saved', 'local_moodec')
    );
}

// Populate form with existing data.
if (!$isnew && $product !== null && !$productform->is_submitted()) {
    $draftitemid = file_get_submitted_draft_itemid('image');
    file_prepare_draft_area(
        $draftitemid,
        $context->id,
        'local_moodec',
        'product_image',
        $product->get_id(),
        ['subdirs' => 0, 'maxfiles' => 1]
    );

    $productform->set_data([
        'id' => $product->get_id(),
        'course_id' => $product->get_course_id(),
        'is_enabled' => (int) $product->is_enabled(),
        'type' => $product->get_type(),
        'category_id' => $product->get_category_id() ?? 0,
        'tags' => $product->get_tags(),
        'description_editor' => [
            'text' => $product->get_description(),
            'format' => $product->get_description_format(),
        ],
        'image' => $draftitemid,
        'sort_order' => $product->get_sort_order(),
    ]);
}

$varform = null;
if (!$isnew && $product !== null) {
    $editvarid = optional_param('editvarid', 0, PARAM_INT);
    $varform = new \local_moodec\form\variation_form(
        new moodle_url('/local/moodec/product_edit.php', ['id' => $id, 'editvarid' => $editvarid]),
        ['is_session_product' => $product->is_session_type()]
    );

    if ($varform->is_cancelled()) {
        redirect(new moodle_url('/local/moodec/product_edit.php', ['id' => $id]));
    }

    if ($vardata = $varform->get_data()) {
        $issession = $product->is_session_type();
        $sessionstarttime = $issession ? (int) ($vardata->session_starttime ?? 0) : 0;
        $sessionendtime = $issession ? (int) ($vardata->session_endtime ?? 0) : 0;
        $sessionlocation = $issession ? clean_param($vardata->session_location ?? '', PARAM_TEXT) : '';
        $sessioncapacity = $issession ? (int) ($vardata->session_capacity ?? 0) : 0;
        if ((int) $vardata->id > 0) {
            $product->update_variation(
                (int) $vardata->id,
                clean_param($vardata->name, PARAM_TEXT),
                (float) $vardata->price,
                (int) $vardata->duration,
                0,
                !empty($vardata->is_enabled),
                $sessionstarttime,
                $sessionendtime,
                $sessionlocation,
                $sessioncapacity
            );
        } else {
            $product->add_variation(
                clean_param($vardata->name, PARAM_TEXT),
                (float) $vardata->price,
                (int) $vardata->duration,
                0,
                !empty($vardata->is_enabled),
                $sessionstarttime,
                $sessionendtime,
                $sessionlocation,
                $sessioncapacity
            );
        }
        redirect(new moodle_url('/local/moodec/product_edit.php', ['id' => $id]));
    }

    if ($editvarid > 0 && !$varform->is_submitted()) {
        $varrecord = $product->get_variation($editvarid);
        if ($varrecord) {
            $vardata = [
                'id' => $varrecord->id,
                'product_id' => $id,
                'name' => $varrecord->name,
                'price' => format_float($varrecord->price, 2, true),
                'duration' => $varrecord->duration,
                'is_enabled' => (int) $varrecord->is_enabled,
            ];
            if ($product->is_session_type()) {
                $vardata['session_starttime'] = (int) ($varrecord->session_starttime ?? 0);
                $vardata['session_endtime'] = (int) ($varrecord->session_endtime ?? 0);
                $vardata['session_location'] = $varrecord->session_location ?? '';
                $vardata['session_capacity'] = (int) ($varrecord->session_capacity ?? 0);
            }
            $varform->set_data($vardata);
        }
    } else if (!$varform->is_submitted()) {
        $varform->set_data(['id' => 0, 'product_id' => $id]);
    }
}

echo $OUTPUT->header();
$headingtitle = $isnew
    ? get_string('product_add_title', 'local_moodec')
    : get_string('product_edit_title', 'local_moodec');
echo $OUTPUT->heading($headingtitle);

$productform->display();

if (!$isnew && $product !== null) {
    $varheading = $product->is_session_type()
        ? get_string('sessions_heading', 'local_moodec')
        : get_string('variations_heading', 'local_moodec');
    echo $OUTPUT->heading($varheading, 3);

    $issessionproduct = $product->is_session_type();
    $variations = [];
    foreach ($product->get_variations() as $var) {
        $variations[] = [
            'id' => (int) $var->id,
            'name' => format_string($var->name),
            'price' => format_float($var->price, 2),
            'duration' => (int) $var->duration,
            'is_enabled' => (bool) $var->is_enabled,
            'session_starttime_formatted' => ($issessionproduct && !empty($var->session_starttime))
                ? userdate((int) $var->session_starttime)
                : '',
            'session_endtime_formatted' => ($issessionproduct && !empty($var->session_endtime))
                ? userdate((int) $var->session_endtime)
                : '',
            'session_location' => format_string($var->session_location ?? ''),
            'session_capacity' => (int) ($var->session_capacity ?? 0),
            'editurl' => (new moodle_url('/local/moodec/product_edit.php', [
                'id' => $id,
                'editvarid' => $var->id,
            ]))->out(false),
            'deleteurl' => (new moodle_url('/local/moodec/product_edit.php', [
                'id' => $id,
                'varaction' => 'deletevar',
                'varid' => $var->id,
                'sesskey' => sesskey(),
            ]))->out(false),
        ];
    }

    echo $OUTPUT->render_from_template('local_moodec/variation_list', [
        'hasvariations' => !empty($variations),
        'is_session_product' => $issessionproduct,
        'variations' => $variations,
    ]);

    echo $OUTPUT->heading(
        empty(optional_param('editvarid', 0, PARAM_INT))
            ? get_string('variation_add', 'local_moodec')
            : get_string('variation_edit', 'local_moodec'),
        4
    );
    $varform->display();
}

echo $OUTPUT->footer();
