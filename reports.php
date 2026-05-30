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
 * EduCheckout sales reports page for admins.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/educheckout:viewallorders', $context);

$today = make_timestamp((int) date('Y'), (int) date('n'), (int) date('j'), 23, 59, 59);
$defaultfrom = strtotime('-90 days', $today);

$fromraw = optional_param('fromdate', '', PARAM_TEXT);
$toraw = optional_param('todate', '', PARAM_TEXT);
$from = $fromraw !== '' ? strtotime($fromraw . ' 00:00:00') : $defaultfrom;
$to = $toraw !== '' ? strtotime($toraw . ' 23:59:59') : $today;
if (!$from) {
    $from = $defaultfrom;
}
if (!$to) {
    $to = $today;
}
if ($to < $from) {
    [$from, $to] = [$to, $from];
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/educheckout/reports.php', [
    'fromdate' => date('Y-m-d', $from),
    'todate' => date('Y-m-d', $to),
]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('reports_title', 'local_educheckout'));
$PAGE->set_heading(get_string('reports_title', 'local_educheckout'));

$currency = (string) (get_config('local_educheckout', 'currency') ?: 'AUD');

$kpis = \local_educheckout\report::get_kpis($from, $to);
$top = \local_educheckout\report::get_top_products($from, $to, 10);
$trend = \local_educheckout\report::get_monthly_trend($from, $to, 5);
$pricelist = \local_educheckout\report::get_price_list();

// Best-performing products bar chart.
$topchart = '';
if (!empty($top)) {
    $bar = new \core\chart_bar();
    $bar->set_title(get_string('reports_top_products', 'local_educheckout'));
    $units = new \core\chart_series(get_string('reports_units_sold', 'local_educheckout'), array_column($top, 'units'));
    $revenue = new \core\chart_series(
        get_string('reports_revenue', 'local_educheckout'),
        array_map(static fn($r) => round($r['revenue'], 2), $top)
    );
    $revenue->set_type(\core\chart_series::TYPE_LINE);
    $bar->add_series($units);
    $bar->add_series($revenue);
    $bar->set_labels(array_column($top, 'fullname'));
    $topchart = $OUTPUT->render_chart($bar, false);
}

// Trend line chart (one series per top product).
$trendchart = '';
if (!empty($trend['series'])) {
    $line = new \core\chart_line();
    $line->set_title(get_string('reports_trend', 'local_educheckout'));
    foreach ($trend['series'] as $s) {
        $line->add_series(new \core\chart_series($s['label'], $s['data']));
    }
    $line->set_labels($trend['labels']);
    $trendchart = $OUTPUT->render_chart($line, false);
}

// Format KPI tiles and table rows for the template.
$toprows = array_map(static function(array $row) use ($currency) {
    return [
        'fullname' => format_string($row['fullname']),
        'units' => $row['units'],
        'revenue' => format_float($row['revenue'], 2),
        'currency' => $currency,
    ];
}, $top);

$pricerows = array_map(static function(array $row) use ($currency) {
    return [
        'fullname' => format_string($row['fullname']),
        'categoryname' => format_string($row['categoryname']),
        'variationname' => format_string($row['variationname']),
        'hasvariation' => $row['variationname'] !== '',
        'price' => format_float($row['price'], 2),
        'currency' => $currency,
    ];
}, $pricelist);

$data = [
    'fromhuman' => userdate($from, get_string('strftimedate', 'langconfig')),
    'tohuman' => userdate($to, get_string('strftimedate', 'langconfig')),
    'fromiso' => date('Y-m-d', $from),
    'toiso' => date('Y-m-d', $to),
    'formaction' => (new moodle_url('/local/educheckout/reports.php'))->out(false),
    'kpi_orders' => $kpis['orders'],
    'kpi_units' => $kpis['units'],
    'kpi_revenue' => format_float($kpis['revenue'], 2),
    'kpi_aov' => format_float($kpis['aov'], 2),
    'currency' => $currency,
    'hastop' => !empty($toprows),
    'toprows' => $toprows,
    'topchart' => $topchart,
    'hastrend' => $trendchart !== '',
    'trendchart' => $trendchart,
    'haspricelist' => !empty($pricerows),
    'pricerows' => $pricerows,
    'exportpriceurl' => (new moodle_url(
        '/local/educheckout/report_export.php',
        ['type' => 'pricelist']
    ))->out(false),
    'exportsalesurl' => (new moodle_url(
        '/local/educheckout/report_export.php',
        ['type' => 'sales', 'fromdate' => date('Y-m-d', $from), 'todate' => date('Y-m-d', $to)]
    ))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_educheckout/reports', $data);
echo $OUTPUT->footer();
