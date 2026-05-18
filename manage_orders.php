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
 * Moodec order management page for admins.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/moodec:viewallorders', $context);

$status = optional_param('status', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/manage_orders.php', ['status' => $status, 'page' => $page]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('orders_title', 'local_moodec'));
$PAGE->set_heading(get_string('orders_title', 'local_moodec'));

$perpage = 25;

$validstatuses = ['pending', 'paid', 'delivered', 'failed', 'cancelled'];
$where = '';
$params = [];
if (in_array($status, $validstatuses, true)) {
    $where = 'WHERE o.status = :status';
    $params['status'] = $status;
}

$countsql = "SELECT COUNT(o.id) FROM {local_moodec_order} o $where";
$total = (int) $DB->count_records_sql($countsql, $params);

$namefields = \core_user\fields::for_name()->get_sql('u', true)->selects;

$sql = "SELECT o.id, o.userid, o.status, o.amount, o.currency, o.timecreated{$namefields}
          FROM {local_moodec_order} o
          JOIN {user} u ON u.id = o.userid
               $where
         ORDER BY o.timecreated DESC";

$records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

$orders = [];
foreach ($records as $rec) {
    // Collect course names for this order's items.
    $items = $DB->get_records('local_moodec_order_item', ['orderid' => $rec->id]);
    $coursenames = [];
    foreach ($items as $item) {
        try {
            $course = get_course($item->courseid);
            $coursenames[] = format_string($course->fullname);
        } catch (\dml_missing_record_exception $e) {
            $coursenames[] = get_string('order_course_missing', 'local_moodec');
        }
    }

    $orders[] = [
        'id' => (int) $rec->id,
        'username' => fullname($rec),
        'status' => $rec->status,
        'amount' => format_float((float) $rec->amount, 2),
        'currency' => $rec->currency,
        'courses' => implode(', ', $coursenames),
        'date' => userdate($rec->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        'receipturl' => (new moodle_url('/local/moodec/receipt.php', ['id' => $rec->id]))->out(false),
    ];
}

// Build status filter links.
$allordersurl = (new moodle_url('/local/moodec/manage_orders.php'))->out(false);
$statusfilters = [
    ['label' => get_string('orders_all', 'local_moodec'), 'url' => $allordersurl, 'active' => $status === ''],
];
foreach ($validstatuses as $s) {
    $statusfilters[] = [
        'label' => get_string('order_status_' . $s, 'local_moodec'),
        'url' => (new moodle_url('/local/moodec/manage_orders.php', ['status' => $s]))->out(false),
        'active' => $status === $s,
    ];
}

$totalpages = $total > 0 ? (int) ceil($total / $perpage) : 1;

$data = [
    'hasorders' => !empty($orders),
    'orders' => $orders,
    'statusfilters' => $statusfilters,
    'haspagination' => $totalpages > 1,
    'page' => $page + 1,
    'totalpages' => $totalpages,
    'hasprev' => $page > 0,
    'prevurl' => (new moodle_url(
        '/local/moodec/manage_orders.php',
        ['status' => $status, 'page' => max(0, $page - 1)]
    ))->out(false),
    'hasnext' => ($page + 1) < $totalpages,
    'nexturl' => (new moodle_url('/local/moodec/manage_orders.php', ['status' => $status, 'page' => $page + 1]))->out(false),
    'manageurl' => (new moodle_url('/local/moodec/manage.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_moodec/manage_orders', $data);
echo $OUTPUT->footer();
