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
 * Moodec order receipt page.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$orderid = required_param('id', PARAM_INT);

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/receipt.php', ['id' => $orderid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('ordersummary', 'local_moodec'));
$PAGE->set_heading(get_string('ordersummary', 'local_moodec'));

$order = \local_moodec\order::instance($orderid);

$data = [
    'delivered' => $order->is_delivered(),
    'amount' => format_float($order->get_amount(), 2),
    'currency' => $order->get_currency(),
    'shopurl' => (new moodle_url('/local/moodec/index.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_moodec/receipt', $data);
echo $OUTPUT->footer();
