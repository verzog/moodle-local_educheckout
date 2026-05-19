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
 * Data generator for the EduCheckout storefront plugin.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generates EduCheckout test data.
 */
class local_educheckout_generator extends component_generator_base {
    /**
     * Create a product record.
     *
     * Accepted keys: course (shortname), is_enabled, sort_order, tags, category_id.
     *
     * @param array|stdClass $record
     * @return stdClass the inserted product row
     */
    public function create_product($record): stdClass {
        global $DB;

        $record = (array) $record;

        if (empty($record['course'])) {
            throw new coding_exception('create_product requires a course shortname');
        }

        $course = $DB->get_record('course', ['shortname' => $record['course']], 'id', MUST_EXIST);

        $row = (object) [
            'course_id'          => (int) $course->id,
            'is_enabled'         => isset($record['is_enabled']) ? (int) $record['is_enabled'] : 1,
            'variation_count'    => 0,
            'sort_order'         => isset($record['sort_order']) ? (int) $record['sort_order'] : 0,
            'category_id'        => isset($record['category_id']) ? (int) $record['category_id'] : null,
            'tags'               => $record['tags'] ?? '',
            'description'        => $record['description'] ?? '',
            'description_format' => FORMAT_HTML,
        ];
        $row->id = $DB->insert_record('local_educheckout_product', $row);
        return $row;
    }
}
