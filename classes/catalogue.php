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
 * Catalogue presenter for the EduCheckout storefront.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout;

/**
 * Builds the context for the local_educheckout/catalogue template.
 *
 * Shared by the standalone catalogue page (index.php) and the storefront block
 * so the product grid is built in exactly one place.
 */
class catalogue {
    /**
     * Build the data array consumed by the local_educheckout/catalogue template.
     *
     * @param int $categoryid Category to filter by, or 0 for all categories.
     * @param int $page Zero-based page index.
     * @param int $perpage Items per page; 0 uses the configured default.
     * @param \moodle_url|null $linkbase Base URL for the category and pagination
     *        links; defaults to the standalone catalogue page so a block can send
     *        visitors to the full store.
     * @return array Template context for local_educheckout/catalogue.
     */
    public static function export_for_template(
        int $categoryid = 0,
        int $page = 0,
        int $perpage = 0,
        ?\moodle_url $linkbase = null
    ): array {
        global $USER;

        $context = \context_system::instance();

        if ($linkbase === null) {
            $linkbase = new \moodle_url('/local/educheckout/index.php');
        }
        if ($perpage <= 0) {
            $perpage = (int) (get_config('local_educheckout', 'pagination') ?: 12);
        }
        $filtercat = ($categoryid > 0) ? $categoryid : null;

        $total = product::count_enabled($filtercat);
        $products = product::get_enabled($filtercat, $page, $perpage);

        $allcategories = category::get_all();
        $catitems = [];
        $catnames = [];
        foreach ($allcategories as $cat) {
            $catnames[$cat->get_id()] = $cat->get_name();
            $catitems[] = [
                'id' => $cat->get_id(),
                'name' => format_string($cat->get_name()),
                'active' => ($categoryid === $cat->get_id()),
                'url' => (new \moodle_url($linkbase, ['category' => $cat->get_id()]))->out(false),
            ];
        }

        $items = [];
        foreach ($products as $product) {
            $imageurl = $product->get_image_url($context);
            $tagdata = array_map(fn($t) => ['label' => $t], $product->get_tags_array());
            $catid = $product->get_category_id();
            // A product can be added straight from the card when there is no
            // choice to make (zero or one enabled variation). With several
            // variations the buyer must pick one on the product page.
            $enabledvariations = $product->get_enabled_variations();
            $candirectadd = (count($enabledvariations) <= 1);
            $directvariationid = (count($enabledvariations) === 1) ? (int) array_key_first($enabledvariations) : 0;
            $items[] = [
                'id' => $product->get_id(),
                'fullname' => format_string($product->get_fullname()),
                'price' => get_string('price_from', 'local_educheckout', format_float($product->get_price(), 2)),
                'imageurl' => $imageurl ? $imageurl->out(false) : '',
                'hastags' => !empty($tagdata),
                'tags' => $tagdata,
                'categoryname' => ($catid && isset($catnames[$catid])) ? format_string($catnames[$catid]) : '',
                'hascategoryname' => ($catid && isset($catnames[$catid])),
                'producturl' => (new \moodle_url('/local/educheckout/product.php', ['id' => $product->get_id()]))->out(false),
                'candirectadd' => $candirectadd,
                'directvariationid' => $directvariationid,
            ];
        }

        $isguest = !isloggedin() || isguestuser();
        $cartcount = cart::count_open_items($isguest ? 0 : (int) $USER->id, $isguest ? sesskey() : null);

        $totalpages = ($perpage > 0 && $total > 0) ? (int) ceil($total / $perpage) : 1;

        return [
            'hascategories' => !empty($catitems),
            'categories' => $catitems,
            'allcaturl' => (new \moodle_url($linkbase))->out(false),
            'allcatactive' => ($categoryid === 0),
            'hasproducts' => !empty($items),
            'products' => $items,
            'carturl' => (new \moodle_url('/local/educheckout/cart.php'))->out(false),
            'addurl' => (new \moodle_url('/local/educheckout/cart.php'))->out(false),
            'sesskey' => sesskey(),
            'cartcount' => $cartcount,
            'hascartitems' => ($cartcount > 0),
            'haspagination' => $totalpages > 1,
            'page' => $page + 1,
            'totalpages' => $totalpages,
            'hasprev' => $page > 0,
            'prevurl' => (new \moodle_url($linkbase, ['category' => $categoryid, 'page' => max(0, $page - 1)]))->out(false),
            'hasnext' => ($page + 1) < $totalpages,
            'nexturl' => (new \moodle_url($linkbase, ['category' => $categoryid, 'page' => $page + 1]))->out(false),
        ];
    }
}
