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
 * Moodec single product page.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$productid = required_param('id', PARAM_INT);

require_login(null, true);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/product.php', ['id' => $productid]));
$PAGE->set_pagelayout('standard');

$product = new \local_moodec\product($productid);
$PAGE->set_title($product->get_fullname());
$PAGE->set_heading($product->get_fullname());
$PAGE->requires->js_call_amd('local_moodec/cart', 'init');

$variations = [];
foreach ($product->get_enabled_variations() as $variation) {
    $variations[] = [
        'id' => (int) $variation->id,
        'name' => format_string($variation->name),
        'price' => format_float((float) $variation->price, 2),
    ];
}

$data = [
    'id' => $product->get_id(),
    'fullname' => $product->get_fullname(),
    'price' => format_float($product->get_price(), 2),
    'hasvariations' => !empty($variations),
    'variations' => $variations,
    'addurl' => (new moodle_url('/local/moodec/cart.php'))->out(false),
    'sesskey' => sesskey(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_moodec/product', $data);
echo $OUTPUT->footer();
