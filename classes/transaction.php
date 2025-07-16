<?php
/**
 * Moodec Transaction
 *
 * @package     local_moodec
 * @author     Vernon Spain - Formerly Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

use context_system;
use core_user;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class transaction {
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';

    protected int $_id;
    protected int $_userid;
    protected string $_status;
    protected float $_cost;
    protected string $_gateway;
    protected string $_txnId;
    protected int $_timecreated;
    protected array $_items = [];

    public function __construct(int $id = null) {
        if ($id !== null) {
            $this->load($id);
        }
    }

    protected function load(int $id): void {
        global $DB;

        $record = $DB->get_record('local_moodec_transaction', ['id' => $id], '*', MUST_EXIST);

        $this->_id = (int) $record->id;
        $this->_userid = (int) $record->user_id;
        $this->_status = $record->status;
        $this->_cost = (float) $record->cost;
        $this->_gateway = $record->gateway;
        $this->_txnId = $record->txn_id;
        $this->_timecreated = (int) $record->timecreated;

        $this->_items = $this->load_items();
    }

    protected function load_items(): array {
        global $DB;

        $records = $DB->get_records('local_moodec_transaction_item', ['transaction_id' => $this->_id]);
        $items = [];

        foreach ($records as $record) {
            $items[] = new transaction_item($record);
        }

        return $items;
    }

    public function get_id(): int {
        return $this->_id;
    }

    public function get_user_id(): int {
        return $this->_userid;
    }

    public function get_status(): string {
        return $this->_status;
    }

    public function get_cost(): float {
        return $this->_cost;
    }

    public function get_gateway(): string {
        return $this->_gateway;
    }

    public function get_txn_id(): string {
        return $this->_txnId;
    }

    public function get_timecreated(): int {
        return $this->_timecreated;
    }

    public function get_items(): array {
        return $this->_items;
    }

    public function set_gateway(string $gateway): void {
        $this->_gateway = $gateway;
        $this->update_field('gateway', $gateway);
    }

    public function set_txn_id(string $txnId): void {
        $this->_txnId = $txnId;
        $this->update_field('txn_id', $txnId);
    }

    public function complete(): void {
        $this->_status = self::STATUS_COMPLETE;
        $this->update_field('status', self::STATUS_COMPLETE);
    }

    public function pending(): void {
        $this->_status = self::STATUS_PENDING;
        $this->update_field('status', self::STATUS_PENDING);
    }

    public function fail(): void {
        $this->_status = self::STATUS_FAILED;
        $this->update_field('status', self::STATUS_FAILED);
    }

    protected function update_field(string $field, mixed $value): void {
        global $DB;

        $DB->set_field('local_moodec_transaction', $field, $value, ['id' => $this->_id]);
    }
}
