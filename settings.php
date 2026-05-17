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
 * Admin settings for the Moodec storefront plugin.
 *
 * @package    local_moodec
 * @copyright  2015 LearningWorks Ltd
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_moodec', get_string('pluginname', 'local_moodec'));
    $ADMIN->add('localplugins', $settings);

    $currencies = get_string_manager()->get_list_of_currencies();
    $setting = new admin_setting_configselect(
        'local_moodec/currency',
        get_string('currency', 'local_moodec'),
        get_string('currency_desc', 'local_moodec'),
        'AUD',
        $currencies
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_moodec/pagination',
        get_string('pagination', 'local_moodec'),
        get_string('pagination_desc', 'local_moodec'),
        12,
        PARAM_INT
    );
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox(
        'local_moodec/tax_enable',
        get_string('tax_enable', 'local_moodec'),
        get_string('tax_enable_desc', 'local_moodec'),
        1
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_moodec/tax_label',
        get_string('tax_label', 'local_moodec'),
        get_string('tax_label_desc', 'local_moodec'),
        'GST',
        PARAM_TEXT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_moodec/tax_rate',
        get_string('tax_rate', 'local_moodec'),
        get_string('tax_rate_desc', 'local_moodec'),
        '10.0',
        PARAM_FLOAT
    );
    $settings->add($setting);

    $setting = new admin_setting_configselect(
        'local_moodec/tax_mode',
        get_string('tax_mode', 'local_moodec'),
        get_string('tax_mode_desc', 'local_moodec'),
        'exclusive',
        [
            'exclusive' => get_string('tax_mode_exclusive', 'local_moodec'),
            'inclusive' => get_string('tax_mode_inclusive', 'local_moodec'),
        ]
    );
    $settings->add($setting);

    $setting = new admin_setting_configtextarea(
        'local_moodec/taxcountryoverrides',
        get_string('taxcountryoverrides', 'local_moodec'),
        get_string('taxcountryoverrides_desc', 'local_moodec'),
        '',
        PARAM_RAW
    );
    $settings->add($setting);
}
