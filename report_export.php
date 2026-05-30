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
 * CSV exporter for EduCheckout reports (price list and sales detail).
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

require_login();
require_capability('local/educheckout:viewallorders', context_system::instance());

$type = required_param('type', PARAM_ALPHA);

$currency = (string) (get_config('local_educheckout', 'currency') ?: 'AUD');
$today = make_timestamp((int) date('Y'), (int) date('n'), (int) date('j'), 23, 59, 59);

if ($type === 'pricelist') {
    $filename = 'educheckout-pricelist-' . date('Ymd');
    $csv = new csv_export_writer();
    $csv->set_filename($filename);
    $csv->add_data([
        get_string('reports_csv_productid', 'local_educheckout'),
        get_string('reports_csv_product', 'local_educheckout'),
        get_string('reports_csv_category', 'local_educheckout'),
        get_string('reports_csv_variation', 'local_educheckout'),
        get_string('reports_csv_price', 'local_educheckout'),
        get_string('reports_csv_currency', 'local_educheckout'),
    ]);
    foreach (\local_educheckout\report::get_price_list() as $row) {
        $csv->add_data([
            $row['productid'],
            $row['fullname'],
            $row['categoryname'],
            $row['variationname'],
            number_format($row['price'], 2, '.', ''),
            $currency,
        ]);
    }
    $csv->download_file();
    exit;
}

if ($type === 'sales') {
    $fromraw = optional_param('fromdate', '', PARAM_TEXT);
    $toraw = optional_param('todate', '', PARAM_TEXT);
    $from = $fromraw !== '' ? strtotime($fromraw . ' 00:00:00') : strtotime('-90 days', $today);
    $to = $toraw !== '' ? strtotime($toraw . ' 23:59:59') : $today;
    if (!$from) {
        $from = strtotime('-90 days', $today);
    }
    if (!$to) {
        $to = $today;
    }
    if ($to < $from) {
        [$from, $to] = [$to, $from];
    }

    $filename = 'educheckout-sales-' . date('Ymd', $from) . '-' . date('Ymd', $to);
    $csv = new csv_export_writer();
    $csv->set_filename($filename);
    $csv->add_data([
        get_string('reports_csv_orderid', 'local_educheckout'),
        get_string('reports_csv_date', 'local_educheckout'),
        get_string('reports_csv_status', 'local_educheckout'),
        get_string('reports_csv_student', 'local_educheckout'),
        get_string('reports_csv_product', 'local_educheckout'),
        get_string('reports_csv_variationid', 'local_educheckout'),
        get_string('reports_csv_unitprice', 'local_educheckout'),
        get_string('reports_csv_tax', 'local_educheckout'),
        get_string('reports_csv_currency', 'local_educheckout'),
    ]);
    foreach (\local_educheckout\report::get_sales_detail($from, $to) as $row) {
        $csv->add_data([
            $row['orderid'],
            $row['date'],
            $row['status'],
            $row['student'],
            $row['productname'],
            $row['variationid'],
            number_format($row['unitprice'], 2, '.', ''),
            number_format($row['tax'], 2, '.', ''),
            $row['currency'],
        ]);
    }
    $csv->download_file();
    exit;
}

throw new moodle_exception('invalidparameter');
