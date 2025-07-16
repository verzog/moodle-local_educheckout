<?php
/**
 * Moodec Single Transaction Page
 *
 * @package     local_moodec
 * @author      Thomas Threadgold, OpenAI Updates
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/moodec/lib.php');

$transactionid = optional_param('id', 0, PARAM_INT);
if (!$transactionid) {
    print_error('invalidtransactionid', 'local_moodec');
}

$context = context_system::instance();
require_login();

$transaction = new MoodecTransaction($transactionid);
if (!$transaction) {
    print_error('invalidtransaction', 'local_moodec');
}

if ($USER->id !== $transaction->get_user_id()) {
    require_capability('local/moodec:viewalltransactions', $context);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/moodec/pages/transaction/view.php', ['id' => $transactionid]));
$PAGE->navbar->add(get_string('transactions_title', 'local_moodec'),
    new moodle_url('/local/moodec/pages/transaction/index.php'));
$PAGE->navbar->add(get_string('transaction_view_title', 'local_moodec', ['id' => $transactionid]));

if (isset($PAGE->theme->layouts['moodec_transaction_view'])) {
    $PAGE->set_pagelayout('moodec_transaction_view');
} elseif (isset($PAGE->theme->layouts['moodec'])) {
    $PAGE->set_pagelayout('moodec');
} else {
    $PAGE->set_pagelayout('standard');
}

$renderer = $PAGE->get_renderer('local_moodec');

$title = get_string('transaction_view_title', 'local_moodec', ['id' => $transactionid]);
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo html_writer::tag('h1', $title, ['class' => 'page__title']);
echo $renderer->single_transaction($transaction);

if ($transaction->get_error() && has_capability('local/moodec:viewalltransactions', $context)) {
    echo html_writer::start_div('transaction-error span12 desktop-first-column');
    echo html_writer::tag('h4', get_string('transaction_section_error', 'local_moodec'));
    echo html_writer::tag('pre', s($transaction->get_error()));
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
