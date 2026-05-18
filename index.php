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
 * Moodec catalogue page.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login(null, true);

$categoryid = optional_param('category', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/index.php', ['category' => $categoryid, 'page' => $page]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('catalogue', 'local_moodec'));
$PAGE->set_heading(get_string('catalogue', 'local_moodec'));

$perpage = (int) (get_config('local_moodec', 'pagination') ?: 12);
$filtercat = ($categoryid > 0) ? $categoryid : null;

$total = \local_moodec\product::count_enabled($filtercat);
$products = \local_moodec\product::get_enabled($filtercat, $page, $perpage);

// Build category nav.
$allcategories = \local_moodec\category::get_all();
$catitems = [];
foreach ($allcategories as $cat) {
    $catitems[] = [
        'id' => $cat->get_id(),
        'name' => format_string($cat->get_name()),
        'active' => ($categoryid === $cat->get_id()),
        'url' => (new moodle_url('/local/moodec/index.php', ['category' => $cat->get_id()]))->out(false),
    ];
}

// Load category names for display (one lookup per unique category in the current page).
$catnames = [];
foreach ($allcategories as $cat) {
    $catnames[$cat->get_id()] = $cat->get_name();
}

$currency = get_config('local_moodec', 'currency') ?: 'AUD';

$items = [];
foreach ($products as $product) {
    $imageurl = $product->get_image_url($context);
    $tagsarray = $product->get_tags_array();
    $tagdata = array_map(fn($t) => ['label' => $t], $tagsarray);

    $catid = $product->get_category_id();
    $items[] = [
        'id' => $product->get_id(),
        'fullname' => format_string($product->get_fullname()),
        'price' => get_string('price_from', 'local_moodec', format_float($product->get_price(), 2)),
        'imageurl' => $imageurl ? $imageurl->out(false) : '',
        'hastags' => !empty($tagdata),
        'tags' => $tagdata,
        'categoryname' => ($catid && isset($catnames[$catid])) ? format_string($catnames[$catid]) : '',
        'hascategoryname' => ($catid && isset($catnames[$catid])),
        'producturl' => (new moodle_url('/local/moodec/product.php', ['id' => $product->get_id()]))->out(false),
    ];
}

$totalpages = ($perpage > 0 && $total > 0) ? (int) ceil($total / $perpage) : 1;
$haspagination = $totalpages > 1;

$data = [
    'hascategories' => !empty($catitems),
    'categories' => $catitems,
    'allcaturl' => (new moodle_url('/local/moodec/index.php'))->out(false),
    'allcatactive' => ($categoryid === 0),
    'hasproducts' => !empty($items),
    'products' => $items,
    'carturl' => (new moodle_url('/local/moodec/cart.php'))->out(false),
    'haspagination' => $haspagination,
    'page' => $page + 1,
    'totalpages' => $totalpages,
    'hasprev' => $page > 0,
    'prevurl' => (new moodle_url('/local/moodec/index.php', [
        'category' => $categoryid,
        'page' => max(0, $page - 1),
    ]))->out(false),
    'hasnext' => ($page + 1) < $totalpages,
    'nexturl' => (new moodle_url('/local/moodec/index.php', [
        'category' => $categoryid,
        'page' => $page + 1,
    ]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_moodec/catalogue', $data);
echo $OUTPUT->footer();
