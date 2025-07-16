<?php
/**
 * Moodec Cart Page
 *
 * @package     local_moodec
 * @author      Thomas Threadgold, OpenAI Updates
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/moodec/lib.php');

require_login();
$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/pages/cart.php'));

if (isset($PAGE->theme->layouts['moodec_cart'])) {
    $PAGE->set_pagelayout('moodec_cart');
} elseif (isset($PAGE->theme->layouts['moodec'])) {
    $PAGE->set_pagelayout('moodec');
} else {
    $PAGE->set_pagelayout('standard');
}

$PAGE->set_title(get_string('cart_title', 'local_moodec'));
$PAGE->set_heading(get_string('cart_title', 'local_moodec'));
$renderer = $PAGE->get_renderer('local_moodec');

$cart = new MoodecCart();
$action = optional_param('action', '', PARAM_ALPHA);
$productid = optional_param('id', 0, PARAM_INT);
$variation = optional_param('variation', null, PARAM_ALPHANUMEXT);

switch ($action) {
    case 'addToCart':
        if ($productid) {
            $cart->add($productid);
        }
        redirect(new moodle_url('/local/moodec/pages/cart.php'));
        break;

    case 'addVariationToCart':
        if ($productid && $variation !== null) {
            $cart->add($productid, $variation);
        }
        redirect(new moodle_url('/local/moodec/pages/cart.php'));
        break;

    case 'removeFromCart':
        if ($productid) {
            $cart->remove($productid);
        }
        redirect(new moodle_url('/local/moodec/pages/cart.php'));
        break;

    case 'emptyCart':
        $cart->clear();
        redirect(new moodle_url('/local/moodec/pages/catalogue.php'));
        break;
}

echo $OUTPUT->header();
echo html_writer::tag('h1', get_string('cart_title', 'local_moodec'), ['class' => 'page__title']);
echo $renderer->moodec_cart($cart);

// Render related products from first product found that has any
foreach ($cart->get() as $id => $variation) {
    $product = local_moodec_get_product($id);
    $related = $renderer->related_products($product);
    if (!empty($related)) {
        echo $related;
        break;
    }
}

echo $OUTPUT->footer();
