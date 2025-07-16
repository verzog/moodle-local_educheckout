<?php
/**
 * Moodec Catalogue Page
 *
 * @package     local_moodec
 * @author     Vernon Spain - Formerly Thomas Threadgold,
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/moodec/lib.php');

require_login();
$context = context_system::instance();

$categoryid = optional_param('category', null, PARAM_INT);
$sort = optional_param('sort', null, PARAM_TEXT);
$page = optional_param('page', 1, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/pages/catalogue.php'));

if (isset($PAGE->theme->layouts['moodec_catalogue'])) {
    $PAGE->set_pagelayout('moodec_catalogue');
} elseif (isset($PAGE->theme->layouts['moodec'])) {
    $PAGE->set_pagelayout('moodec');
} else {
    $PAGE->set_pagelayout('standard');
}

$PAGE->set_title(get_string('catalogue_title', 'local_moodec'));
$PAGE->set_heading(get_string('catalogue_title', 'local_moodec'));
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/local/moodec/js/catalogue.js'));

$renderer = $PAGE->get_renderer('local_moodec');
list($sortfield, $sortorder) = local_moodec_extract_sort_vars($sort);

echo $OUTPUT->header();
echo html_writer::tag('h1', get_string('catalogue_title', 'local_moodec'), ['class' => 'page__title']);

echo $renderer->filter_bar($categoryid, $sort);

$products = local_moodec_get_products($page, $categoryid, $sortfield, $sortorder);
echo $renderer->catalogue($products);

$allproducts = local_moodec_get_products(-1, $categoryid, $sortfield, $sortorder);
echo $renderer->pagination($allproducts, $page, $categoryid, $sort);

echo $OUTPUT->footer();
