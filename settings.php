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
 * Admin settings for the EduCheckout storefront plugin.
 *
 * @package    local_educheckout
 * @copyright  2015 LearningWorks Ltd
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_educheckout', get_string('pluginname', 'local_educheckout'));
    $ADMIN->add('localplugins', $settings);

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_educheckout_manage',
        get_string('manage_title', 'local_educheckout'),
        new moodle_url('/local/educheckout/manage.php'),
        'local/educheckout:manageproducts'
    ));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_educheckout_categories',
        get_string('categories_title', 'local_educheckout'),
        new moodle_url('/local/educheckout/category_manage.php'),
        'local/educheckout:manageproducts'
    ));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_educheckout_orders',
        get_string('orders_title', 'local_educheckout'),
        new moodle_url('/local/educheckout/manage_orders.php'),
        'local/educheckout:viewallorders'
    ));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_educheckout_reports',
        get_string('reports_title', 'local_educheckout'),
        new moodle_url('/local/educheckout/reports.php'),
        'local/educheckout:viewallorders'
    ));

    $accounts = \core_payment\helper::get_payment_accounts_menu(context_system::instance());
    $setting = new admin_setting_configselect(
        'local_educheckout/paymentaccount',
        get_string('paymentaccount', 'local_educheckout'),
        get_string('paymentaccount_desc', 'local_educheckout'),
        0,
        $accounts ?: [0 => get_string('none')]
    );
    $settings->add($setting);

    $currencies = get_string_manager()->get_list_of_currencies();
    $setting = new admin_setting_configselect(
        'local_educheckout/currency',
        get_string('currency', 'local_educheckout'),
        get_string('currency_desc', 'local_educheckout'),
        'AUD',
        $currencies
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_educheckout/pagination',
        get_string('pagination', 'local_educheckout'),
        get_string('pagination_desc', 'local_educheckout'),
        12,
        PARAM_INT
    );
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox(
        'local_educheckout/tax_enable',
        get_string('tax_enable', 'local_educheckout'),
        get_string('tax_enable_desc', 'local_educheckout'),
        1
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_educheckout/tax_label',
        get_string('tax_label', 'local_educheckout'),
        get_string('tax_label_desc', 'local_educheckout'),
        'GST',
        PARAM_TEXT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_educheckout/tax_rate',
        get_string('tax_rate', 'local_educheckout'),
        get_string('tax_rate_desc', 'local_educheckout'),
        '10',
        PARAM_FLOAT
    );
    $settings->add($setting);

    $setting = new admin_setting_configselect(
        'local_educheckout/tax_mode',
        get_string('tax_mode', 'local_educheckout'),
        get_string('tax_mode_desc', 'local_educheckout'),
        'exclusive',
        [
            'exclusive' => get_string('tax_mode_exclusive', 'local_educheckout'),
            'inclusive' => get_string('tax_mode_inclusive', 'local_educheckout'),
        ]
    );
    $settings->add($setting);

    $setting = new admin_setting_configtextarea(
        'local_educheckout/taxcountryoverrides',
        get_string('taxcountryoverrides', 'local_educheckout'),
        get_string('taxcountryoverrides_desc', 'local_educheckout'),
        '',
        PARAM_RAW
    );
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox(
        'local_educheckout/gateway_fee_enable',
        get_string('gateway_fee_enable', 'local_educheckout'),
        get_string('gateway_fee_enable_desc', 'local_educheckout'),
        0
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_educheckout/gateway_fee_label',
        get_string('gateway_fee_label', 'local_educheckout'),
        get_string('gateway_fee_label_desc', 'local_educheckout'),
        get_string('gateway_fee_default_label', 'local_educheckout'),
        PARAM_TEXT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_educheckout/gateway_fee_percent',
        get_string('gateway_fee_percent', 'local_educheckout'),
        get_string('gateway_fee_percent_desc', 'local_educheckout'),
        '1.75',
        PARAM_FLOAT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'local_educheckout/gateway_fee_fixed',
        get_string('gateway_fee_fixed', 'local_educheckout'),
        get_string('gateway_fee_fixed_desc', 'local_educheckout'),
        '0.30',
        PARAM_FLOAT
    );
    $settings->add($setting);
}
