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
 * Variation editing form for the Moodec storefront.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating or editing a product variation.
 */
class variation_form extends \moodleform {
    /**
     * Define the form fields.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'product_id');
        $mform->setType('product_id', PARAM_INT);

        $mform->addElement(
            'text',
            'name',
            get_string('variation_name', 'local_moodec'),
            ['size' => 50]
        );
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement(
            'text',
            'price',
            get_string('variation_price', 'local_moodec'),
            ['size' => 10]
        );
        $mform->setType('price', PARAM_FLOAT);
        $mform->addRule('price', null, 'required', null, 'client');
        $mform->setDefault('price', '0.00');

        $mform->addElement(
            'text',
            'duration',
            get_string('variation_duration', 'local_moodec'),
            ['size' => 8]
        );
        $mform->setType('duration', PARAM_INT);
        $mform->setDefault('duration', 0);
        $mform->addHelpButton('duration', 'variation_duration', 'local_moodec');

        $mform->addElement(
            'advcheckbox',
            'is_enabled',
            get_string('variation_enabled', 'local_moodec')
        );
        $mform->setDefault('is_enabled', 1);

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
        if (isset($data['price']) && $data['price'] < 0) {
            $errors['price'] = get_string('error_negativeprice', 'local_moodec');
        }
        if (isset($data['duration']) && $data['duration'] < 0) {
            $errors['duration'] = get_string('error_negativeduration', 'local_moodec');
        }
        return $errors;
    }
}
