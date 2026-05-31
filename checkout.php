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

// Anonymous visitors hitting checkout are redirected to signup/login below
// (their guest cart is preserved and merged after authentication), so we use
// require_course_login on the site course to let them reach that handoff.
require_course_login(SITEID, true);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/educheckout/checkout.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('checkout_title', 'local_educheckout'));
$PAGE->set_heading(get_string('checkout_title', 'local_educheckout'));

$isguest = !isloggedin() || isguestuser();

if ($isguest) {
    // A guest cannot pay; an account is required to be enrolled after payment.
    // Stash the guest cart so the post-login observer can merge it, then send
    // them to signup (or login if signup is disabled). Both flows return here.
    $guestcart = \local_educheckout\cart::find_guest(sesskey());
    if (!$guestcart || $guestcart->is_empty()) {
        redirect(new moodle_url('/local/educheckout/cart.php'));
    }
    $SESSION->local_educheckout_guestcartid = $guestcart->get_id();

    $returnurl = new moodle_url('/local/educheckout/checkout.php');
    $authurl = (!empty($CFG->registerauth) && get_config('core', 'registerauth') !== '')
        ? new moodle_url('/login/signup.php')
        : new moodle_url('/login/index.php');
    $authurl->param('wantsurl', $returnurl->out_as_local_url(false));
    redirect($authurl);
}

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

$items = [];
foreach ($order->get_items() as $item) {
    $course = $DB->get_record('course', ['id' => (int) $item->courseid], 'id, fullname', IGNORE_MISSING);
    $coursename = $course
        ? format_string($course->fullname)
        : get_string('order_course_missing', 'local_educheckout');

    $summarytext = '';
    $imageurl = '';
    if ((int) $item->productid > 0) {
        try {
            $product = new \local_educheckout\product((int) $item->productid);
            $summaryhtml = $product->get_overview_html($context);
            if ($summaryhtml !== '') {
                $summarytext = shorten_text(trim(html_to_text($summaryhtml, 0, false)), 200, true);
            }
            $url = $product->get_image_url($context);
            if ($url) {
                $imageurl = $url->out(false);
            }
        } catch (\Throwable $e) {
            $summarytext = '';
        }
    }

    $items[] = [
        'coursename' => $coursename,
        'summary' => $summarytext,
        'hassummary' => $summarytext !== '',
        'imageurl' => $imageurl,
        'hasimage' => $imageurl !== '',
        'unitprice' => format_float((float) $item->unitprice + (float) $item->nettax, 2),
    ];
}

$taxamount = $order->get_tax_amount();
$hastax = $taxamount > 0.0;
$taxlabel = '';
if ($hastax) {
    $taxlabel = (string) get_config('local_educheckout', 'tax_label');
    if ($taxlabel === '') {
        $taxlabel = get_string('tax', 'local_educheckout');
    }
}

$gatewayfee = $order->get_gateway_fee();
$hasgatewayfee = $gatewayfee > 0.0;

$data = [
    'amount' => format_float($order->get_amount(), 2),
    'currency' => $order->get_currency(),
    'cost' => \core_payment\helper::get_cost_as_string($order->get_amount(), $order->get_currency()),
    'component' => 'local_educheckout',
    'paymentarea' => 'cart',
    'itemid' => $order->get_id(),
    'description' => get_string('checkout_title', 'local_educheckout'),
    'successurl' => $successurl->out(false),
    'hasitems' => !empty($items),
    'items' => $items,
    'hastax' => $hastax,
    'taxlabel' => $taxlabel,
    'netamount' => $hastax ? format_float($order->get_net_amount(), 2) : '',
    'taxamount' => $hastax ? format_float($taxamount, 2) : '',
    'hasgatewayfee' => $hasgatewayfee,
    'gatewayfeelabel' => $hasgatewayfee ? \local_educheckout\gateway_fee::get_label() : '',
    'gatewayfeeamount' => $hasgatewayfee ? format_float($gatewayfee, 2) : '',
];

$PAGE->requires->js_call_amd('core_payment/gateways_modal', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_educheckout/checkout', $data);
echo $OUTPUT->footer();
