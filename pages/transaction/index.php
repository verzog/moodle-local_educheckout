<?php
/**
 * Moodec Transactions Page
 *
 * @package     local_moodec
 * @author      Thomas Threadgold, OpenAI Updates
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/moodec/lib.php');
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/local/moodec/classes/transaction_table.php');

$params = [];

// Get filter parameters
$userID = optional_param('user', 0, PARAM_INT);
$dateFrom = optional_param('date-from', null, PARAM_RAW);
$dateTo = optional_param('date-to', null, PARAM_RAW);
$gatewayPaypal = optional_param('paypal', 0, PARAM_BOOL);
$gatewayDPS = optional_param('dps', 0, PARAM_BOOL);
$statusComplete = optional_param('status-complete', 0, PARAM_BOOL);
$statusFailed = optional_param('status-failed', 0, PARAM_BOOL);
$statusPending = optional_param('status-pending', 0, PARAM_BOOL);
$statusNoSubmit = optional_param('status-nosubmit', 0, PARAM_BOOL);
$download = optional_param('download', '', PARAM_ALPHA);

if ($userID > 0) {
    $params['user'] = $userID;
}
if (!empty($dateFrom) && strtotime($dateFrom)) {
    $params['date-from'] = $dateFrom;
}
if (!empty($dateTo) && strtotime($dateTo)) {
    $params['date-to'] = $dateTo;
}
if ($gatewayPaypal) {
    $params['paypal'] = 1;
}
if ($gatewayDPS) {
    $params['dps'] = 1;
}
if ($statusComplete) {
    $params['status-complete'] = 1;
}
if ($statusFailed) {
    $params['status-failed'] = 1;
}
if ($statusPending) {
    $params['status-pending'] = 1;
}
if ($statusNoSubmit) {
    $params['status-nosubmit'] = 1;
}

$context = context_system::instance();
require_login();

if (!has_capability('local/moodec:viewalltransactions', $context)) {
    $userID = $USER->id;
    $params['user'] = $userID;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/pages/transaction/index.php', $params));

// Set pagelayout fallback
if (isset($PAGE->theme->layouts['moodec_transactions'])) {
    $PAGE->set_pagelayout('moodec_transactions');
} elseif (isset($PAGE->theme->layouts['moodec'])) {
    $PAGE->set_pagelayout('moodec');
} else {
    $PAGE->set_pagelayout('standard');
}

$renderer = $PAGE->get_renderer('local_moodec');

$table = new moodec_transaction_table('moodec-transactions-list');
$table->is_downloading($download, 'transaction-report', 'Moodec Transaction Report');

if (!$table->is_downloading()) {
    $PAGE->set_title(get_string('transactions_title', 'local_moodec'));
    $PAGE->set_heading(get_string('transactions_title', 'local_moodec'));
    echo $OUTPUT->header();
    echo html_writer::tag('h1', get_string('transactions_title', 'local_moodec'), ['class' => 'page__title']);
    echo $renderer->transaction_filter($params, new moodle_url('/local/moodec/pages/transaction/index.php'));
}

$table->define_baseurl(new moodle_url('/local/moodec/pages/transaction/index.php', $params));
$table->set_attribute('class', 'admintable generaltable transaction_table');
$table->collapsible(false);
$table->is_downloadable(true);
$table->show_download_buttons_at([TABLE_P_BOTTOM]);

$where = [];
if (isset($params['user'])) {
    $where[] = 'user_id = :user';
}
if (isset($params['date-from'])) {
    $where[] = 'purchase_date > :datefrom';
}
if (isset($params['date-to'])) {
    $where[] = 'purchase_date < :dateto';
}

$gateways = [];
if (isset($params['paypal'])) {
    $gateways[] = "gateway = 'paypal'";
}
if (isset($params['dps'])) {
    $gateways[] = "gateway = 'dps'";
}
if ($gateways) {
    $where[] = '(' . implode(' OR ', $gateways) . ')';
}

$statuses = [];
if (isset($params['status-complete'])) {
    $statuses[] = 'status = ' . MoodecTransaction::STATUS_COMPLETE;
}
if (isset($params['status-failed'])) {
    $statuses[] = 'status = ' . MoodecTransaction::STATUS_FAILED;
}
if (isset($params['status-pending'])) {
    $statuses[] = 'status = ' . MoodecTransaction::STATUS_PENDING;
}
if (isset($params['status-nosubmit'])) {
    $statuses[] = 'status = ' . MoodecTransaction::STATUS_NOT_SUBMITTED;
}
if ($statuses) {
    $where[] = '(' . implode(' OR ', $statuses) . ')';
}

$sqlwhere = implode(' AND ', $where);
$table->set_sql('*', '{local_moodec_transaction}', $sqlwhere, [
    'user' => $userID,
    'datefrom' => isset($params['date-from']) ? strtotime($params['date-from']) : null,
    'dateto' => isset($params['date-to']) ? strtotime($params['date-to']) : null,
]);

$table->out(50, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
