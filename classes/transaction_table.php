<?php
/**
 * Moodec Transaction Table
 *
 * @package     local_moodec
 * @author      Vernon Spain - Formerly Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

use flexible_table;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/tablelib.php');

class transaction_table extends flexible_table {
    protected int $userid;

    public function __construct(int $userid) {
        parent::__construct('moodec_transaction_table');

        $this->userid = $userid;

        $this->define_columns(['id', 'timecreated', 'gateway', 'status', 'cost']);
        $this->define_headers([
            get_string('id', 'local_moodec'),
            get_string('timecreated', 'local_moodec'),
            get_string('gateway', 'local_moodec'),
            get_string('status', 'local_moodec'),
            get_string('cost', 'local_moodec')
        ]);

        $this->define_baseurl(new moodle_url('/local/moodec/pages/history.php'));

        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->collapsible(true);
        $this->set_attribute('class', 'generaltable transaction-table');
    }

    public function query_db(int $pagesize, bool $useinitialsbar = true): void {
        global $DB;

        $countsql = "SELECT COUNT(1)
                     FROM {local_moodec_transaction}
                     WHERE user_id = :userid";

        $totals = $DB->count_records_sql($countsql, ['userid' => $this->userid]);

        $this->pagesize($pagesize, $totals);

        $sort = $this->get_sql_sort();
        if (empty($sort)) {
            $sort = 'timecreated DESC';
        }

        $sql = "SELECT id, timecreated, gateway, status, cost
                FROM {local_moodec_transaction}
                WHERE user_id = :userid
                ORDER BY $sort";

        $records = $DB->get_records_sql($sql, ['userid' => $this->userid], $this->get_page_start(), $this->get_page_size());

        foreach ($records as $record) {
            $this->add_data([
                $record->id,
                userdate($record->timecreated),
                s($record->gateway),
                s($record->status),
                format_float($record->cost, 2)
            ]);
        }
    }
}
