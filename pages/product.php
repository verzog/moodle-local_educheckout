<?php
/**
 * Moodec Product Page
 *
 * @package     local_moodec
 * @author      Thomas Threadgold, OpenAI Updates
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/moodec/lib.php');

$productid = required_param('id', PARAM_INT);

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/pages/product.php', ['id' => $productid]));

if (isset($PAGE->theme->layouts['moodec_product'])) {
    $PAGE->set_pagelayout('moodec_product');
} elseif (isset($PAGE->theme->layouts['moodec'])) {
    $PAGE->set_pagelayout('moodec');
} else {
    $PAGE->set_pagelayout('standard');
}

$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/local/moodec/js/product.js'));

$product = local_moodec_get_product($productid);

if (!$product) {
    throw new moodle_exception('courseunavailable', 'error');
}

$course = $DB->get_record('course', ['id' => $product->get_course_id()], '*', MUST_EXIST);

$PAGE->set_title(get_string('product_title', 'local_moodec', ['coursename' => $product->get_fullname()]));
$PAGE->set_heading(get_string('product_title', 'local_moodec', ['coursename' => $product->get_fullname()]));

$renderer = $PAGE->get_renderer('local_moodec');

echo $OUTPUT->header();
echo $renderer->single_product($product);

if (!empty(get_config('local_moodec', 'page_product_show_related_products'))) {
    echo $renderer->related_products($product);
}

echo $OUTPUT->footer();
