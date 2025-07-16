<?php
/**
 * Moodec Product class
 *
 * @package    local_moodec
 * @category   classes
 * @copyright  2025 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

use context_course;
use core_course_list_element;
use moodle_exception;
use xmldb_table;
use xmldb_field;

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

class product {
    protected int $_id;
    protected int $_courseid;
    protected bool $_enabled;
    protected string $_type;
    protected string $_fullname;
    protected string $_shortname;
    protected int $_categoryid;
    protected string $_summary;
    protected int $_summaryFormat;
    protected string $_description;
    protected array $_tags;

    public function __construct(?int $id = null) {
        if (!is_null($id)) {
            $this->load($id);
        }
    }

    public function load(int $id): void {
        global $DB;

        $sql = "SELECT 
                    lmp.id, 
                    c.id as course_id,
                    fullname,
                    shortname,
                    is_enabled,
                    category,
                    summary,
                    c.summaryformat as summary_format,
                    type,
                    description,
                    tags,
                    timecreated
                FROM {local_moodec_product} lmp
                JOIN {course} c ON lmp.course_id = c.id
                WHERE lmp.id = :id";

        $product = $DB->get_record_sql($sql, ['id' => $id]);

        if ($product) {
            $this->_id = (int) $product->id;
            $this->_courseid = (int) $product->course_id;
            $this->_enabled = (bool) $product->is_enabled;
            $this->_fullname = $product->fullname;
            $this->_shortname = $product->shortname;
            $this->_type = $product->type;
            $this->_categoryid = (int) $product->category;
            $this->_summary = $product->summary;
            $this->_summaryFormat = (int) $product->summary_format;
            $this->_description = $product->description;
            $this->_tags = explode(',', $product->tags);
        } else {
            throw new moodle_exception('invalidproductid', 'local_moodec', '', $id);
        }
    }

    public function get_summary(): string {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->libdir . '/weblib.php');

        if (empty($this->_summary)) {
            return '';
        }

        $context = context_course::instance($this->_courseid);
        $summary = file_rewrite_pluginfile_urls($this->_summary, 'pluginfile.php', $context->id, 'course', 'summary', null);

        return format_text($summary, $this->_summaryFormat, [
            'para' => false,
            'newlines' => true,
            'overflowdiv' => false
        ], $this->_courseid);
    }

    public function get_image_url(): string|false {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        $course = get_course($this->_courseid);

        if (is_object($course)) {
            require_once($CFG->libdir . '/coursecatlib.php');
            $course = new \core_course_list_element($course);
        }

        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                return file_encode_url("$CFG->wwwroot/pluginfile.php",
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                    $file->get_filearea() . $file->get_filepath() . $file->get_filename(), false);
            }
        }

        return false;
    }

    public function get_related(int $limit = 3): array {
        return \local_moodec_get_random_products($limit, $this->_categoryid, $this->_id);
    }

    public function is_enabled(): bool {
        return $this->_enabled;
    }

    public function get_id(): int {
        return $this->_id;
    }

    public function get_course_id(): int {
        return $this->_courseid;
    }

    public function get_type(): string {
        return $this->_type;
    }

    public function get_fullname(): string {
        return $this->_fullname;
    }

    public function get_shortname(): string {
        return $this->_shortname;
    }

    public function get_category_id(): int {
        return $this->_categoryid;
    }

    public function get_description(): string {
        return $this->_description;
    }

    public function has_description(): bool {
        return !empty($this->_description);
    }
}
