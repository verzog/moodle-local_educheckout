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
 * Category model for the Moodec storefront.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

defined('MOODLE_INTERNAL') || die();

/**
 * Represents a storefront category.
 */
class category {
    /** @var \stdClass the raw DB record */
    protected $record;

    /**
     * Wrap a category record.
     *
     * @param \stdClass $record
     */
    protected function __construct(\stdClass $record) {
        $this->record = $record;
    }

    /**
     * Load a category by id.
     *
     * @param int $id
     * @return category
     */
    public static function get(int $id): category {
        global $DB;
        $record = $DB->get_record('local_moodec_category', ['id' => $id], '*', MUST_EXIST);
        return new self($record);
    }

    /**
     * Return all categories ordered by sortorder then name.
     *
     * @return category[]
     */
    public static function get_all(): array {
        global $DB;
        $records = $DB->get_records('local_moodec_category', null, 'sortorder ASC, name ASC');
        $categories = [];
        foreach ($records as $record) {
            $categories[(int) $record->id] = new self($record);
        }
        return $categories;
    }

    /**
     * Create a new category.
     *
     * @param string $name
     * @param string $description
     * @param int $sortorder
     * @return category
     */
    public static function create(string $name, string $description = '', int $sortorder = 0): category {
        global $DB;
        $now = time();
        $record = (object) [
            'name' => $name,
            'description' => $description,
            'sortorder' => $sortorder,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('local_moodec_category', $record);
        return new self($record);
    }

    /**
     * Update this category.
     *
     * @param string $name
     * @param string $description
     * @param int $sortorder
     * @return void
     */
    public function update(string $name, string $description = '', int $sortorder = 0): void {
        global $DB;
        $this->record->name = $name;
        $this->record->description = $description;
        $this->record->sortorder = $sortorder;
        $this->record->timemodified = time();
        $DB->update_record('local_moodec_category', $this->record);
    }

    /**
     * Delete this category, un-assigning any products that reference it.
     *
     * @return void
     */
    public function delete(): void {
        global $DB;
        $DB->set_field('local_moodec_product', 'category_id', null, ['category_id' => $this->get_id()]);
        $DB->delete_records('local_moodec_category', ['id' => $this->get_id()]);
    }

    /**
     * Return the category id.
     *
     * @return int
     */
    public function get_id(): int {
        return (int) $this->record->id;
    }

    /**
     * Return the category name.
     *
     * @return string
     */
    public function get_name(): string {
        return (string) $this->record->name;
    }

    /**
     * Return the category description.
     *
     * @return string
     */
    public function get_description(): string {
        return (string) ($this->record->description ?? '');
    }

    /**
     * Return the sort order.
     *
     * @return int
     */
    public function get_sortorder(): int {
        return (int) $this->record->sortorder;
    }
}
