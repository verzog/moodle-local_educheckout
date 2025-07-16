<?php
/**
 * Moodec Gateway
 *
 * @package     local
 * @subpackage  local_moodec
 * @author     Vernon Spain - Formerly Thomas Threadgold
 * @copyright  2015 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec; // Add namespace

use core\message\message;
use moodle_exception;

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

abstract class gateway {

    /** @var MoodecTransaction */
    protected $_transaction;

    /** @var string */
    protected $_gatewayName;

    /** @var string */
    protected $_gatewayURL;

    /** @var moodle_enrol_plugin */
    protected $_enrolPlugin;

    public function __construct($transaction) {
        global $CFG;
        require_once($CFG->libdir . '/enrollib.php');

        $this->_transaction = ($transaction instanceof \MoodecTransaction)
            ? $transaction
            : new \MoodecTransaction($transaction);

        $this->_enrolPlugin = enrol_get_plugin('moodec') ?? enrol_get_plugin('manual');

        $this->_gatewayName = '';
        $this->_gatewayURL = '';
    }

    protected function verify_transaction(): bool {
        global $DB;

        $userid = $this->_transaction->get_user_id();
        if (!$DB->record_exists('user', ['id' => $userid])) {
            $this->send_error_to_admin("User {$userid} doesn't exist");
            $this->_transaction->fail();
            return false;
        }

        foreach ($this->_transaction->get_items() as $item) {
            $product = local_moodec_get_product($item->get_product_id());
            $courseid = $product->get_course_id();
            if (!$DB->record_exists('course', ['id' => $courseid])) {
                $this->send_error_to_admin("Course {$courseid} doesn't exist");
                $this->_transaction->fail();
                return false;
            }
        }

        return true;
    }

    protected function complete_enrolment(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/enrollib.php');
        require_once($CFG->dirroot . '/group/lib.php');

        foreach ($this->_transaction->get_items() as $item) {
            $product = local_moodec_get_product($item->get_product_id());
            $timestart = time() - 60;
            $timeend = 0;

            $courseid = $product->get_course_id();
            $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'moodec'])
                ?: $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);

            if (!$instance) {
                $this->send_error_to_admin("No enrol instance found for course {$courseid}");
                continue;
            }

            $enrolmentDuration = $product->get_type() === PRODUCT_TYPE_SIMPLE
                ? $product->get_duration(false)
                : $product->get_variation($item->get_variation_id())->get_duration(false);

            if ($enrolmentDuration !== 0) {
                $timeend = $timestart + ($enrolmentDuration * 86400);
            }

            $this->_enrolPlugin->enrol_user(
                $instance,
                $this->_transaction->get_user_id(),
                $instance->roleid,
                $timestart,
                $timeend,
                ENROL_USER_ACTIVE
            );

            $groupid = $product->get_type() === PRODUCT_TYPE_SIMPLE
                ? $product->get_group()
                : $product->get_variation($item->get_variation_id())->get_group();

            if (!empty($groupid)) {
                groups_add_member($groupid, $this->_transaction->get_user_id());
            }
        }

        $this->_transaction->complete();
    }

    protected function send_error_to_admin(string $subject, array $data = []): void {
        global $CFG;
        require_once($CFG->libdir . '/eventslib.php');

        $admin = get_admin();
        $site = get_site();

        $message = sprintf("%s: Transaction #%d failed. %s \n\n",
            $site->fullname,
            $this->_transaction->get_id(),
            $subject
        );

        foreach ($data as $key => $value) {
            $message .= "$key => $value\n";
        }

        $this->_transaction->set_error($message);

        $eventdata = new \stdClass();
        $eventdata->component = 'local_moodec';
        $eventdata->name = 'payment_gateway';
        $eventdata->userfrom = $admin;
        $eventdata->userto = $admin;
        $eventdata->subject = $this->_gatewayName . " ERROR: " . $subject;
        $eventdata->fullmessage = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = '';
        $eventdata->smallmessage = '';

        message_send($eventdata);
    }

    public function get_url(): string {
        return $this->_gatewayURL;
    }

    abstract public function handle($data = null);

    abstract public function render(): string;
}
