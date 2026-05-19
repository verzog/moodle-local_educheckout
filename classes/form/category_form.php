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
 * Category editing form for the EduCheckout storefront.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating or editing a storefront category.
 */
class category_form extends \moodleform {
    /**
     * Define the form fields.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement(
            'text',
            'name',
            get_string('category_name', 'local_educheckout'),
            ['size' => 60]
        );
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement(
            'textarea',
            'description',
            get_string('category_description', 'local_educheckout'),
            ['rows' => 4, 'cols' => 60]
        );
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement(
            'text',
            'sortorder',
            get_string('category_sortorder', 'local_educheckout'),
            ['size' => 5]
        );
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

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
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = get_string('error_categorynamerequired', 'local_educheckout');
        }
        return $errors;
    }
}
