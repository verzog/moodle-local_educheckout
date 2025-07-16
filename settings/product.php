<?php
/**
 * Moodec Product Settings
 *
 * @package     local_moodec
 * @author      Thomas Threadgold, OpenAI Updates
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/moodec/lib.php');
require_once($CFG->dirroot . '/local/moodec/forms/edit_product.php');

$courseid = required_param('id', PARAM_INT);

require_login();
$context = context_course::instance($courseid);
require_capability('moodle/course:update', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/settings/product.php', ['id' => $courseid]));
$PAGE->set_pagelayout('admin');
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/local/moodec/js/settings_product.js'));

$course = get_course($courseid);

$mform = new moodec_edit_product_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));

} else if ($data = $mform->get_data()) {
    $recordExists = $DB->get_record('local_moodec_product', ['course_id' => $data->id]);

    $recordProduct = (object) [
        'course_id' => $data->id,
        'is_enabled' => $data->product_enabled,
        'type' => $data->product_type,
        'tags' => $data->product_tags,
        'description' => $data->product_description['text'],
        'variation_count' => ($data->product_type === PRODUCT_TYPE_SIMPLE ? 1 : $data->format),
    ];

    $recordVariations = [];
    for ($i = 1; $i <= $recordProduct->variation_count; $i++) {
        $recordVariations[] = (object) [
            'is_enabled' => ($i === 1 ? 1 : $data->{'product_variation_enabled_' . $i}),
            'name' => $data->{'product_variation_name_' . $i},
            'price' => $data->{'product_variation_price_' . $i},
            'duration' => $data->{'product_variation_duration_' . $i},
            'group_id' => $data->{'product_variation_group_' . $i},
        ];
    }

    if ($recordExists) {
        $recordProduct->id = $recordExists->id;
        $DB->update_record('local_moodec_product', $recordProduct);

        $existingVariations = $DB->get_records('local_moodec_variation', ['product_id' => $recordProduct->id]);

        foreach ($existingVariations as $v) {
            $DB->update_record('local_moodec_variation', (object) ['id' => $v->id, 'is_enabled' => 0]);
        }

        foreach ($recordVariations as $variation) {
            $variation->product_id = $recordProduct->id;

            if (!empty($existingVariations)) {
                $existing = array_shift($existingVariations);
                $variation->id = $existing->id;
                $DB->update_record('local_moodec_variation', $variation);
            } else {
                $DB->insert_record('local_moodec_variation', $variation);
            }
        }
    } else {
        $productid = $DB->insert_record('local_moodec_product', $recordProduct, true);
        foreach ($recordVariations as $variation) {
            $variation->product_id = $productid;
            $DB->insert_record('local_moodec_variation', $variation);
        }
    }

    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else {
    $existingProductData = $DB->get_record('local_moodec_product', ['course_id' => $courseid]);
    $toForm = new stdClass();

    if ($existingProductData) {
        $existingVariationData = $DB->get_records('local_moodec_variation', ['product_id' => $existingProductData->id]);

        $toForm->id = $existingProductData->course_id;
        $toForm->product_enabled = $existingProductData->is_enabled;
        $toForm->product_type = $existingProductData->type;
        $toForm->product_tags = $existingProductData->tags;
        $toForm->format = $existingProductData->variation_count;
        $toForm->product_description = [
            'text' => $existingProductData->description,
            'format' => FORMAT_HTML
        ];

        $i = 1;
        foreach ($existingVariationData as $variation) {
            $toForm->{'product_variation_enabled_' . $i} = $variation->is_enabled;
            $toForm->{'product_variation_name_' . $i} = $variation->name;
            $toForm->{'product_variation_price_' . $i} = $variation->price;
            $toForm->{'product_variation_duration_' . $i} = $variation->duration;
            $toForm->{'product_variation_group_' . $i} = $variation->group_id;
            $i++;
        }
    } else {
        $toForm->id = $courseid;
    }

    $mform->set_data($toForm);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('edit_product_form_title', 'local_moodec', ['name' => $course->fullname]));

$mform->display();

echo $OUTPUT->footer();
