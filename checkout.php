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
 * EduCheckout checkout page.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/educheckout/checkout.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('checkout_title', 'local_educheckout'));
$PAGE->set_heading(get_string('checkout_title', 'local_educheckout'));

$cart = \local_educheckout\cart::get_open((int) $USER->id);
$guestcart = \local_educheckout\cart::find_guest(sesskey());
if ($guestcart) {
    $cart->merge_from($guestcart);
}

if ($cart->is_empty()) {
    redirect(new moodle_url('/local/educheckout/cart.php'));
}

if (!(int) get_config('local_educheckout', 'paymentaccount')) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('nogateways', 'local_educheckout'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->footer();
    exit;
}

$country = !empty($USER->country) ? $USER->country : null;
$order = \local_educheckout\order::create_from_cart($cart, (int) $USER->id, $country);
$cart->mark_ordered();

$successurl = new moodle_url('/local/educheckout/receipt.php', ['id' => $order->get_id()]);

$data = [
    'amount' => format_float($order->get_amount(), 2),
    'currency' => $order->get_currency(),
    'cost' => \core_payment\helper::get_cost_as_string($order->get_amount(), $order->get_currency()),
    'component' => 'local_educheckout',
    'paymentarea' => 'cart',
    'itemid' => $order->get_id(),
    'description' => get_string('checkout_title', 'local_educheckout'),
    'successurl' => $successurl->out(false),
];

$PAGE->requires->js_call_amd('core_payment/gateways_modal', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_educheckout/checkout', $data);
echo $OUTPUT->footer();
