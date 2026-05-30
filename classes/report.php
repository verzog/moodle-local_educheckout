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
 * Reporting queries for the EduCheckout storefront.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout;

defined('MOODLE_INTERNAL') || die();

/**
 * Aggregates order data into the figures used by the admin reports page and CSV exports.
 *
 * Only paid and delivered orders count toward revenue and units; pending/failed/cancelled
 * orders are ignored so a partially completed checkout does not skew the totals.
 */
class report {

    /** @var array Order statuses that represent a completed sale. */
    private const COUNTED_STATUSES = ['paid', 'delivered'];

    /**
     * Return KPI totals for the given date window.
     *
     * @param int $from Start timestamp (inclusive).
     * @param int $to End timestamp (inclusive).
     * @return array With keys 'orders', 'units', 'revenue', 'aov'.
     */
    public static function get_kpis(int $from, int $to): array {
        global $DB;

        [$instatus, $statusparams] = $DB->get_in_or_equal(self::COUNTED_STATUSES, SQL_PARAMS_NAMED, 's');
        $params = $statusparams + ['from' => $from, 'to' => $to];

        $sql = "SELECT COUNT(DISTINCT o.id) AS orders, COUNT(oi.id) AS units
                  FROM {local_educheckout_order} o
                  JOIN {local_educheckout_order_item} oi ON oi.orderid = o.id
                 WHERE o.status $instatus
                   AND o.timecreated BETWEEN :from AND :to";

        $row = $DB->get_record_sql($sql, $params);
        $orders = (int) ($row->orders ?? 0);
        $units = (int) ($row->units ?? 0);
        // Revenue is summed from {order}.amount in a separate query so an order with multiple
        // items isn't double-counted by the join above.
        $rev = (float) $DB->get_field_sql(
            "SELECT COALESCE(SUM(o.amount), 0)
               FROM {local_educheckout_order} o
              WHERE o.status $instatus
                AND o.timecreated BETWEEN :from AND :to",
            $params
        );
        $aov = $orders > 0 ? $rev / $orders : 0.0;

        return [
            'orders' => $orders,
            'units' => $units,
            'revenue' => $rev,
            'aov' => $aov,
        ];
    }

    /**
     * Return the top performing products in the date window, sorted by units sold (desc).
     *
     * @param int $from Start timestamp (inclusive).
     * @param int $to End timestamp (inclusive).
     * @param int $limit Max rows returned.
     * @return array List of rows with keys 'productid', 'fullname', 'units', 'revenue'.
     */
    public static function get_top_products(int $from, int $to, int $limit = 10): array {
        global $DB;

        [$instatus, $statusparams] = $DB->get_in_or_equal(self::COUNTED_STATUSES, SQL_PARAMS_NAMED, 's');
        $params = $statusparams + ['from' => $from, 'to' => $to];

        $sql = "SELECT oi.productid,
                       COUNT(oi.id) AS units,
                       COALESCE(SUM(oi.unitprice + oi.nettax), 0) AS revenue
                  FROM {local_educheckout_order_item} oi
                  JOIN {local_educheckout_order} o ON o.id = oi.orderid
                 WHERE o.status $instatus
                   AND o.timecreated BETWEEN :from AND :to
                   AND oi.productid > 0
              GROUP BY oi.productid
              ORDER BY units DESC, revenue DESC";

        $rows = $DB->get_records_sql($sql, $params, 0, $limit);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'productid' => (int) $row->productid,
                'fullname' => self::product_name((int) $row->productid),
                'units' => (int) $row->units,
                'revenue' => (float) $row->revenue,
            ];
        }
        return $out;
    }

    /**
     * Return monthly sales (units) per product for the given date window.
     *
     * Series are restricted to the top N products by units sold over the window so the
     * resulting line chart stays legible. Months with no sales for a series are zero-filled.
     *
     * @param int $from Start timestamp (inclusive).
     * @param int $to End timestamp (inclusive).
     * @param int $topn Number of product series to include.
     * @return array With keys 'labels' (string[]), 'series' (array of ['label'=>string,'data'=>int[]]).
     */
    public static function get_monthly_trend(int $from, int $to, int $topn = 5): array {
        global $DB;

        $top = self::get_top_products($from, $to, $topn);
        if (empty($top)) {
            return ['labels' => [], 'series' => []];
        }
        $productids = array_column($top, 'productid');

        [$instatus, $statusparams] = $DB->get_in_or_equal(self::COUNTED_STATUSES, SQL_PARAMS_NAMED, 's');
        [$inproducts, $productparams] = $DB->get_in_or_equal($productids, SQL_PARAMS_NAMED, 'p');
        $params = $statusparams + $productparams + ['from' => $from, 'to' => $to];

        // Bucket in PHP to stay portable across MySQL/PostgreSQL date functions.
        $sql = "SELECT oi.id, oi.productid, o.timecreated
                  FROM {local_educheckout_order_item} oi
                  JOIN {local_educheckout_order} o ON o.id = oi.orderid
                 WHERE o.status $instatus
                   AND o.timecreated BETWEEN :from AND :to
                   AND oi.productid $inproducts";

        $records = $DB->get_records_sql($sql, $params);

        $months = self::month_range($from, $to);
        $buckets = [];
        foreach ($productids as $pid) {
            $buckets[$pid] = array_fill_keys($months, 0);
        }
        foreach ($records as $rec) {
            $key = userdate((int) $rec->timecreated, '%Y-%m');
            if (isset($buckets[(int) $rec->productid][$key])) {
                $buckets[(int) $rec->productid][$key]++;
            }
        }

        $series = [];
        foreach ($top as $row) {
            $series[] = [
                'label' => $row['fullname'],
                'data' => array_values($buckets[$row['productid']]),
            ];
        }
        $labels = array_map(static fn($m) => userdate(strtotime($m . '-01'), '%b %Y'), $months);

        return ['labels' => $labels, 'series' => $series];
    }

    /**
     * Return the price list of enabled products with their enabled variations.
     *
     * @return array List of rows with keys 'productid', 'fullname', 'categoryname',
     *               'variationid', 'variationname', 'price'.
     */
    public static function get_price_list(): array {
        $rows = [];
        $products = product::get_enabled(null, 0, 0);
        foreach ($products as $product) {
            $catid = $product->get_category_id();
            $catname = '';
            if ($catid) {
                try {
                    $catname = category::get($catid)->get_name();
                } catch (\dml_missing_record_exception $e) {
                    $catname = '';
                }
            }
            $enabled = $product->get_enabled_variations();
            if (empty($enabled)) {
                $rows[] = [
                    'productid' => $product->get_id(),
                    'fullname' => $product->get_fullname(),
                    'categoryname' => $catname,
                    'variationid' => 0,
                    'variationname' => '',
                    'price' => $product->get_price(),
                ];
                continue;
            }
            foreach ($enabled as $variation) {
                $rows[] = [
                    'productid' => $product->get_id(),
                    'fullname' => $product->get_fullname(),
                    'categoryname' => $catname,
                    'variationid' => (int) $variation->id,
                    'variationname' => (string) $variation->name,
                    'price' => $product->get_price((int) $variation->id),
                ];
            }
        }
        return $rows;
    }

    /**
     * Return the per-order-item sales detail for the given window (used by CSV export).
     *
     * @param int $from Start timestamp (inclusive).
     * @param int $to End timestamp (inclusive).
     * @return array List of row arrays in display order.
     */
    public static function get_sales_detail(int $from, int $to): array {
        global $DB;

        [$instatus, $statusparams] = $DB->get_in_or_equal(self::COUNTED_STATUSES, SQL_PARAMS_NAMED, 's');
        $params = $statusparams + ['from' => $from, 'to' => $to];

        $namefields = \core_user\fields::for_name()->get_sql('u', true)->selects;
        $sql = "SELECT oi.id, oi.orderid, oi.productid, oi.variationid, oi.unitprice, oi.nettax,
                       o.status, o.currency, o.timecreated, o.userid{$namefields}
                  FROM {local_educheckout_order_item} oi
                  JOIN {local_educheckout_order} o ON o.id = oi.orderid
                  JOIN {user} u ON u.id = o.userid
                 WHERE o.status $instatus
                   AND o.timecreated BETWEEN :from AND :to
              ORDER BY o.timecreated ASC, oi.id ASC";

        $rows = [];
        foreach ($DB->get_records_sql($sql, $params) as $rec) {
            $rows[] = [
                'orderid' => (int) $rec->orderid,
                'date' => userdate((int) $rec->timecreated, '%Y-%m-%d %H:%M'),
                'status' => $rec->status,
                'student' => fullname($rec),
                'productname' => self::product_name((int) $rec->productid),
                'variationid' => (int) $rec->variationid,
                'unitprice' => (float) $rec->unitprice,
                'tax' => (float) $rec->nettax,
                'currency' => (string) $rec->currency,
            ];
        }
        return $rows;
    }

    /**
     * Look up the display name for a product id, falling back gracefully when the record is gone.
     *
     * @param int $productid Product id (0 for missing).
     * @return string The product's full name, or a placeholder string.
     */
    private static function product_name(int $productid): string {
        if ($productid <= 0) {
            return get_string('report_unknown_product', 'local_educheckout');
        }
        try {
            return (new product($productid))->get_fullname();
        } catch (\dml_missing_record_exception $e) {
            return get_string('report_unknown_product', 'local_educheckout');
        }
    }

    /**
     * Build the list of YYYY-MM keys spanning the given date range, inclusive.
     *
     * @param int $from Start timestamp.
     * @param int $to End timestamp.
     * @return array List of 'YYYY-MM' keys in ascending order.
     */
    private static function month_range(int $from, int $to): array {
        $months = [];
        $cursor = strtotime(date('Y-m-01', $from));
        $end = strtotime(date('Y-m-01', $to));
        while ($cursor <= $end) {
            $months[] = date('Y-m', $cursor);
            $cursor = strtotime('+1 month', $cursor);
        }
        return $months;
    }
}
