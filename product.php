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
 * EduCheckout single product page.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $DB, $OUTPUT, $PAGE, $USER;

$productid = required_param('id', PARAM_INT);

require_login(null, true);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/educheckout/product.php', ['id' => $productid]));
$PAGE->set_pagelayout('standard');

$product = new \local_educheckout\product($productid);
$PAGE->set_title($product->get_fullname());
$PAGE->set_heading($product->get_fullname());

$issessionproduct = $product->is_session_type();

// For session products, count seats sold per variation from paid/delivered orders.
$seatssold = [];
if ($issessionproduct) {
    $enabledids = array_keys($product->get_enabled_variations());
    if (!empty($enabledids)) {
        [$insql, $inparams] = $DB->get_in_or_equal($enabledids, SQL_PARAMS_NAMED);
        $sql = "SELECT oi.variationid, COUNT(oi.id) AS cnt
                  FROM {local_educheckout_order_item} oi
                  JOIN {local_educheckout_order} o ON o.id = oi.orderid
                 WHERE oi.variationid $insql
                   AND o.status IN ('paid', 'delivered')
              GROUP BY oi.variationid";
        foreach ($DB->get_records_sql($sql, $inparams) as $row) {
            $seatssold[(int) $row->variationid] = (int) $row->cnt;
        }
    }
}

$variations = [];
foreach ($product->get_enabled_variations() as $variation) {
    $hassessiondata = $issessionproduct && !empty($variation->session_starttime);
    $capacity = (int) ($variation->session_capacity ?? 0);
    $sold = $seatssold[(int) $variation->id] ?? 0;
    $seatsremaining = ($capacity > 0) ? max(0, $capacity - $sold) : null;

    $seatstext = '';
    if ($hassessiondata) {
        if ($capacity === 0) {
            $seatstext = get_string('session_seats_unlimited', 'local_educheckout');
        } else if ($seatsremaining === 0) {
            $seatstext = get_string('session_full', 'local_educheckout');
        } else {
            $seatstext = get_string('session_seats_remaining', 'local_educheckout', $seatsremaining);
        }
    }

    $variations[] = [
        'id' => (int) $variation->id,
        'name' => format_string($variation->name),
        'price' => format_float((float) $variation->price, 2),
        'hassessiondata' => $hassessiondata,
        'session_starttime_formatted' => $hassessiondata
            ? userdate((int) $variation->session_starttime)
            : '',
        'session_endtime_formatted' => ($hassessiondata && !empty($variation->session_endtime))
            ? userdate((int) $variation->session_endtime)
            : '',
        'session_location' => $hassessiondata ? format_string($variation->session_location ?? '') : '',
        'seats_text' => $seatstext,
        'is_full' => $hassessiondata && $seatsremaining === 0,
    ];
}

$imageurl = $product->get_image_url($context);

$tagsarray = $product->get_tags_array();
$tagdata = array_map(fn($t) => ['label' => $t], $tagsarray);

$categoryname = '';
$catid = $product->get_category_id();
if ($catid) {
    try {
        $cat = \local_educheckout\category::get($catid);
        $categoryname = format_string($cat->get_name());
    } catch (\dml_missing_record_exception $e) {
        $categoryname = '';
    }
}

$description = '';
if ($product->get_description() !== '') {
    $description = format_text(
        $product->get_description(),
        $product->get_description_format(),
        ['context' => $context]
    );
}

// Check if the student is already enrolled in this course.
$alreadyenrolled = false;
if (isloggedin() && !isguestuser()) {
    $coursecontext = context_course::instance($product->get_course_id(), IGNORE_MISSING);
    if ($coursecontext && is_enrolled($coursecontext, $USER)) {
        $alreadyenrolled = true;
    }
}

$data = [
    'id' => $product->get_id(),
    'fullname' => format_string($product->get_fullname()),
    'price' => format_float($product->get_price(), 2),
    'imageurl' => $imageurl ? $imageurl->out(false) : '',
    'hasdescription' => $description !== '',
    'description' => $description,
    'hastags' => !empty($tagdata),
    'tags' => $tagdata,
    'categoryname' => $categoryname,
    'hascategoryname' => $categoryname !== '',
    'hasvariations' => !empty($variations),
    'variations' => $variations,
    'alreadyenrolled' => $alreadyenrolled,
    'courseurl' => (new moodle_url('/course/view.php', ['id' => $product->get_course_id()]))->out(false),
    'addurl' => (new moodle_url('/local/educheckout/cart.php'))->out(false),
    'catalogueurl' => (new moodle_url('/local/educheckout/index.php'))->out(false),
    'sesskey' => sesskey(),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_educheckout/product', $data);
echo $OUTPUT->footer();
