<?php
/**
 * Moodec Checkout
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
$PAGE->set_url(new moodle_url('/local/moodec/pages/checkout.php'));

if (isset($PAGE->theme->layouts['moodec_checkout'])) {
    $PAGE->set_pagelayout('moodec_checkout');
} elseif (isset($PAGE->theme->layouts['moodec'])) {
    $PAGE->set_pagelayout('moodec');
} else {
    $PAGE->set_pagelayout('standard');
}

$PAGE->set_title(get_string('checkout_title', 'local_moodec'));
$PAGE->set_heading(get_string('checkout_title', 'local_moodec'));
$PAGE->navbar->add(get_string('cart_title', 'local_moodec'), new moodle_url('/local/moodec/pages/cart.php'));
$PAGE->navbar->add(get_string('checkout_title', 'local_moodec'));

$renderer = $PAGE->get_renderer('local_moodec');

$cart = new MoodecCart();

if ($cart->is_empty()) {
    redirect(new moodle_url('/local/moodec/pages/cart.php'));
}

$removedproducts = $cart->refresh();

if ($cart->get_transaction_id()) {
    $transaction = new MoodecTransaction($cart->get_transaction_id());
    $transaction->reset();
} else {
    $transaction = new MoodecTransaction();
}

$cart->set_transaction_id($transaction->get_id());

foreach ($cart->get() as $pid => $vid) {
    $product = local_moodec_get_product($pid);

    if ($product->get_type() === PRODUCT_TYPE_VARIABLE) {
        $transaction->add($pid, $product->get_variation($vid)->get_price(), $vid);
    } else {
        $transaction->add($pid, $product->get_price());
    }
}

echo $OUTPUT->header();
echo html_writer::tag('h1', get_string('checkout_title', 'local_moodec'), ['class' => 'page__title']);
echo $renderer->cart_review($cart, $removedproducts);
echo $OUTPUT->footer();
