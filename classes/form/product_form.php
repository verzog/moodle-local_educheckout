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
 * Product editing form for the EduCheckout storefront.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating or editing a product.
 */
class product_form extends \moodleform {
    /**
     * Define the form fields.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $categoryoptions = $this->_customdata['categoryoptions'] ?? [];
        $isnew = empty($this->_customdata['productid']);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Course selector (only shown when creating a new product).
        if ($isnew) {
            $mform->addElement(
                'course',
                'course_id',
                get_string('product_course', 'local_educheckout')
            );
            $mform->addRule('course_id', null, 'required', null, 'client');
        } else {
            $mform->addElement('hidden', 'course_id');
            $mform->setType('course_id', PARAM_INT);
        }

        $mform->addElement(
            'advcheckbox',
            'is_enabled',
            get_string('product_enabled', 'local_educheckout')
        );

        $mform->addElement(
            'select',
            'category_id',
            get_string('product_category', 'local_educheckout'),
            [0 => get_string('uncategorised', 'local_educheckout')] + $categoryoptions
        );

        $mform->addElement(
            'text',
            'tags',
            get_string('product_tags', 'local_educheckout'),
            ['size' => 60]
        );
        $mform->setType('tags', PARAM_TEXT);
        $mform->addHelpButton('tags', 'product_tags', 'local_educheckout');

        $mform->addElement(
            'editor',
            'description_editor',
            get_string('product_description', 'local_educheckout'),
            null,
            ['maxfiles' => 0, 'noclean' => false]
        );
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement(
            'filemanager',
            'image',
            get_string('product_image', 'local_educheckout'),
            null,
            [
                'subdirs' => 0,
                'maxfiles' => 1,
                'accepted_types' => ['image'],
            ]
        );

        $mform->addElement(
            'text',
            'sort_order',
            get_string('product_sortorder', 'local_educheckout'),
            ['size' => 5]
        );
        $mform->setType('sort_order', PARAM_INT);
        $mform->setDefault('sort_order', 0);

        $this->add_action_buttons();
    }

    /**
     * Validate submitted data.
     *
     * @param array $data
     * @param array $files
     * @return array errors keyed by field name
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
