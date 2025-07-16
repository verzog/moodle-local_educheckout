<?php
/**
 * Moodec Library file
 *
 * @package     local_moodec
 * @author      Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Product type constants
define('PRODUCT_TYPE_SIMPLE', 'PRODUCT_TYPE_SIMPLE');
define('PRODUCT_TYPE_VARIABLE', 'PRODUCT_TYPE_VARIABLE');

// Gateway constants
define('MOODEC_GATEWAY_PAYPAL', 'MOODEC_GATEWAY_PAYPAL');
define('MOODEC_GATEWAY_DPS', 'MOODEC_GATEWAY_DPS');

// Class autoloading (deprecated requires removed — assumes PSR-4 or composer-based autoload)
// require_once($CFG->dirroot . '/local/moodec/autoload.php');

/**
 * Add Moodec links to global navigation.
 *
 * @param global_navigation $nav
 */
function local_moodec_extends_navigation(global_navigation $nav): void {
    global $CFG, $PAGE, $DB;

    $storenode = $PAGE->navigation->add(
        get_string('catalogue_title', 'local_moodec'),
        new moodle_url('/local/moodec/pages/catalogue.php'),
        navigation_node::TYPE_CONTAINER
    );

    if (get_config('local_moodec', 'page_product_enable')) {
        $sql = "SELECT DISTINCT cc.id, cc.visible, cc.name
                  FROM {course_categories} cc
                  JOIN {course} c ON c.category = cc.id
                  JOIN {local_moodec_product} lmp ON lmp.course_id = c.id
                 WHERE lmp.is_enabled = 1";

        $categories = $DB->get_records_sql($sql);

        foreach ($categories as $category) {
            if (!$category->visible) {
                continue;
            }

            $catnode = $storenode->add(
                $category->name,
                new moodle_url('/local/moodec/pages/catalogue.php', ['category' => $category->id]),
                navigation_node::TYPE_CONTAINER
            );

            $products = local_moodec_get_products(-1, $category->id, 'fullname');
            foreach ($products as $product) {
                $catnode->add(
                    $product->get_fullname(),
                    new moodle_url('/local/moodec/pages/product.php', ['id' => $product->get_id()])
                );
            }
        }
    }

    $PAGE->navigation->add(
        get_string('cart_title', 'local_moodec'),
        new moodle_url('/local/moodec/pages/cart.php')
    );

    $PAGE->navigation->add(
        get_string('transactions_title', 'local_moodec'),
        new moodle_url('/local/moodec/pages/transaction/index.php')
    );
}

/**
 * Add Moodec links to course settings navigation.
 *
 * @param settings_navigation $nav
 * @param stdClass $context
 */
function local_moodec_extends_settings_navigation(settings_navigation $nav, stdClass $context): void {
    global $CFG;

    if ($context->contextlevel >= CONTEXT_COURSE && ($branch = $nav->get('courseadmin'))
        && has_capability('moodle/course:update', $context)) {

        $url = new moodle_url('/local/moodec/settings/product.php', ['id' => $context->instanceid]);
        $branch->add(
            get_string('moodec_product_settings', 'local_moodec'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'moodec' . $context->instanceid,
            new pix_icon('i/settings', '')
        );
    }
}

/**
 * Get supported currencies.
 *
 * @return array
 */
function local_moodec_get_currencies(): array {
    $codes = ['AUD','CAD','CHF','DKK','EUR','GBP','HKD','JPY','MYR','NZD','SGD','THB','USD'];
    $currencies = [];

    foreach ($codes as $c) {
        $currencies[$c] = new lang_string($c, 'core_currencies');
    }

    return $currencies;
}

/**
 * Get currency symbol from currency code.
 *
 * @param string $currency
 * @return string
 */
function local_moodec_get_currency_symbol(string $currency): string {
    $symbols = [
        'AUD' => '$', 'CAD' => '$', 'CHF' => 'CHF', 'DKK' => 'kr', 'EUR' => '€',
        'GBP' => '£', 'HKD' => '$', 'JPY' => '¥', 'MYR' => 'RM', 'NZD' => '$',
        'SGD' => '$', 'THB' => '฿', 'USD' => '$',
    ];

    return $symbols[$currency] ?? '$';
}

/**
 * Get a single product object by ID.
 *
 * @param int $id
 * @return MoodecProduct
 * @throws moodle_exception
 */
function local_moodec_get_product(int $id) {
    global $DB;

    $record = $DB->get_record('local_moodec_product', ['id' => $id], 'type', IGNORE_MISSING);

    if (!$record) {
        throw new moodle_exception('productnotfound', 'local_moodec', '', $id);
    }

    return match ($record->type) {
        PRODUCT_TYPE_SIMPLE   => new MoodecProductSimple($id),
        PRODUCT_TYPE_VARIABLE => new MoodecProductVariable($id),
        default => throw new moodle_exception('unsupportedproducttype', 'local_moodec', '', $record->type),
    };
}

/**
 * Get a list of product objects.
 *
 * @param int $page
 * @param int|null $category
 * @param string $sortfield
 * @param string $sortorder
 * @return array
 */
function local_moodec_get_products(int $page = 1, ?int $category = null, string $sortfield = 'sortorder', string $sortorder = 'ASC'): array {
    global $DB;

    $products = [];
    $validfields = ['sortorder', 'price', 'fullname', 'duration', 'timecreated'];

    if (!in_array($sortfield, $validfields)) {
        $sortfield = 'sortorder';
    }

    if (!in_array(strtoupper($sortorder), ['ASC', 'DESC'])) {
        $sortorder = 'ASC';
    }

    $productsperpage = (int)get_config('local_moodec', 'pagination');
    $params = [];
    $categorysql = '';

    if ($category !== null && $category !== 'default') {
        $categorysql = 'AND c.category = :category';
        $params['category'] = $category;
    }

    $sql = "SELECT DISTINCT lmp.id AS productid
            FROM {local_moodec_product} lmp
            JOIN {local_moodec_variation} lmv ON lmp.id = lmv.product_id
            JOIN {course} c ON lmp.course_id = c.id
            WHERE lmp.is_enabled = 1
            $categorysql
            ORDER BY $sortfield $sortorder";

    if ($page === -1) {
        $records = $DB->get_records_sql($sql, $params);
    } else {
        $offset = max(0, $page - 1) * $productsperpage;
        $records = $DB->get_records_sql($sql, $params, $offset, $productsperpage);
    }

    foreach ($records as $record) {
        $products[] = local_moodec_get_product($record->productid);
    }

    return $products;
}

/**
 * Get a random list of products.
 *
 * @param int $limit
 * @param int|null $category
 * @param int $exclude
 * @return array
 */
function local_moodec_get_random_products(int $limit = 1, ?int $category = null, int $exclude = 0): array {
    global $DB;

    $products = [];
    $params = ['exclude' => $exclude];
    $categorysql = '';

    if ($category !== null && $category !== 'default') {
        $categorysql = 'AND c.category = :category';
        $params['category'] = $category;
    }

    $sql = "SELECT DISTINCT lmp.id AS productid
            FROM {local_moodec_product} lmp
            JOIN {local_moodec_variation} lmv ON lmp.id = lmv.product_id
            JOIN {course} c ON lmp.course_id = c.id
            WHERE lmp.is_enabled = 1
              AND lmp.id != :exclude
              $categorysql
            ORDER BY RANDOM()";

    $records = $DB->get_records_sql($sql, $params, 0, $limit);

    foreach ($records as $record) {
        $products[] = local_moodec_get_product($record->productid);
    }

    return $products;
}

/**
 * Generate a category dropdown.
 *
 * @param int|null $id
 * @return string
 */
function local_moodec_get_category_list(?int $id): string {
    global $DB;

    $list = html_writer::tag('option', get_string('all'), ['value' => 'default', 'selected' => is_null($id) ? 'selected' : null]);

    $categories = $DB->get_records('course_categories');
    foreach ($categories as $category) {
        if (!$category->visible) {
            continue;
        }

        $attrs = ['value' => $category->id];
        if ((int)$category->id === $id) {
            $attrs['selected'] = 'selected';
        }

        $list .= html_writer::tag('option', format_string($category->name), $attrs);
    }

    return $list;
}

/**
 * Return list of groups for a course.
 *
 * @param int $id
 * @return array
 */
function local_moodec_get_groups(int $id): array {
    global $CFG;

    require_once($CFG->libdir . '/grouplib.php');

    $arr = [0 => get_string('product_variation_group_none', 'local_moodec')];
    $groups = groups_get_all_groups($id) ?? [];

    foreach ($groups as $g) {
        $arr[$g->id] = $g->name;
    }

    return $arr;
}

/**
 * Parse sort field string into field + order.
 *
 * @param string|null $sort
 * @return array
 */
function local_moodec_extract_sort_vars(?string $sort): array {
    $sortfield = 'sortorder';
    $sortorder = 'ASC';

    if (!empty($sort) && strpos($sort, '-') !== false) {
        [$sortfield, $sortorder] = explode('-', $sort);
        $sortorder = strtoupper($sortorder);
    }

    return [$sortfield, $sortorder];
}
