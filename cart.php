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
 * Moodec shopping cart page.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$action = optional_param('action', '', PARAM_ALPHA);

require_login(null, true);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/cart.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cart_title', 'local_moodec'));
$PAGE->set_heading(get_string('cart_title', 'local_moodec'));
$PAGE->requires->js_call_amd('local_moodec/cart', 'init');

$isguest = !isloggedin() || isguestuser();
$cart = \local_moodec\cart::get_open($isguest ? 0 : (int) $USER->id, $isguest ? sesskey() : null);

if (!$isguest) {
    $guestcart = \local_moodec\cart::find_guest(sesskey());
    if ($guestcart) {
        $cart->merge_from($guestcart);
    }
}

if ($action === 'add' && confirm_sesskey()) {
    $productid = required_param('product', PARAM_INT);
    $variationid = optional_param('variation', 0, PARAM_INT);
    $product = new \local_moodec\product($productid);
    if ($variationid > 0 && !$product->get_variation($variationid)) {
        $variationid = 0;
    }
    $cart->add_item(
        $product->get_id(),
        $variationid,
        $product->get_course_id(),
        $product->get_price($variationid)
    );
    redirect(new moodle_url('/local/moodec/cart.php'));
}

if ($action === 'remove' && confirm_sesskey()) {
    $itemid = required_param('item', PARAM_INT);
    $cart->remove_item($itemid);
    redirect(new moodle_url('/local/moodec/cart.php'));
}

$items = [];
foreach ($cart->get_items() as $item) {
    $coursename = '';
    try {
        $course = get_course($item->courseid);
        $coursename = format_string($course->fullname);
    } catch (\dml_missing_record_exception $e) {
        $coursename = '';
    }
    $items[] = [
        'id' => $item->id,
        'name' => $coursename,
        'unitprice' => format_float((float) $item->unitprice, 2),
        'sesskey' => sesskey(),
        'removeurl' => (new moodle_url('/local/moodec/cart.php'))->out(false),
    ];
}

$data = [
    'isempty' => $cart->is_empty(),
    'items' => $items,
    'total' => format_float($cart->get_total(), 2),
    'checkouturl' => (new moodle_url('/local/moodec/checkout.php'))->out(false),
    'shopurl' => (new moodle_url('/local/moodec/index.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_moodec/cart', $data);
echo $OUTPUT->footer();
