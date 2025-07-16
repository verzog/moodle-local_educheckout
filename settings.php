<?php
/**
 * Moodec Settings file
 *
 * @package     local_moodec
 * @author      Thomas Threadgold
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // Add main Moodec settings category under Local Plugins
    $ADMIN->add('localplugins', new admin_category('local_moodec', get_string('pluginname', 'local_moodec')));

    // === General Settings ===
    $settings_general = new admin_settingpage('local_moodec_settings', get_string('moodec_settings', 'local_moodec'));
    $ADMIN->add('local_moodec', $settings_general);

    // Currency options (manually defined)
    $currencies = [
        'AUD' => new lang_string('AUD', 'core_currencies'),
        'CAD' => new lang_string('CAD', 'core_currencies'),
        'CHF' => new lang_string('CHF', 'core_currencies'),
        'DKK' => new lang_string('DKK', 'core_currencies'),
        'EUR' => new lang_string('EUR', 'core_currencies'),
        'GBP' => new lang_string('GBP', 'core_currencies'),
        'HKD' => new lang_string('HKD', 'core_currencies'),
        'JPY' => new lang_string('JPY', 'core_currencies'),
        'MYR' => new lang_string('MYR', 'core_currencies'),
        'NZD' => new lang_string('NZD', 'core_currencies'),
        'SGD' => new lang_string('SGD', 'core_currencies'),
        'THB' => new lang_string('THB', 'core_currencies'),
        'USD' => new lang_string('USD', 'core_currencies'),
    ];

    $settings_general->add(new admin_setting_configselect(
        'local_moodec/currency',
        get_string('currency', 'local_moodec'),
        '',
        'USD',
        $currencies
    ));

    $settings_general->add(new admin_setting_configtext(
        'local_moodec/pagination',
        get_string('pagination', 'local_moodec'),
        get_string('pagination_desc', 'local_moodec'),
        10,
        PARAM_INT
    ));

    // === Payment Settings ===
    $ADMIN->add('local_moodec', new admin_category('moodec_payment', get_string('payment_title', 'local_moodec')));

    // DPS
    $settings_dps = new admin_settingpage('local_moodec_settings_dps', get_string('payment_dps_title', 'local_moodec'));
    $ADMIN->add('moodec_payment', $settings_dps);

    $settings_dps->add(new admin_setting_configcheckbox(
        'local_moodec/payment_dps_enable',
        get_string('payment_enable', 'local_moodec'),
        get_string('payment_enable_desc', 'local_moodec'),
        0
    ));
    $settings_dps->add(new admin_setting_configtext(
        'local_moodec/payment_dps_userid',
        get_string('payment_dps_userid', 'local_moodec'),
        get_string('payment_dps_userid_desc', 'local_moodec'),
        '',
        PARAM_TEXT
    ));
    $settings_dps->add(new admin_setting_configtext(
        'local_moodec/payment_dps_key',
        get_string('payment_dps_key', 'local_moodec'),
        get_string('payment_dps_key_desc', 'local_moodec'),
        '',
        PARAM_TEXT
    ));
    $settings_dps->add(new admin_setting_configcheckbox(
        'local_moodec/payment_dps_sandbox',
        get_string('payment_dps_sandbox', 'local_moodec'),
        get_string('payment_dps_sandbox_desc', 'local_moodec'),
        0
    ));

    // PayPal
    $settings_paypal = new admin_settingpage('local_moodec_settings_paypal', get_string('payment_paypal_title', 'local_moodec'));
    $ADMIN->add('moodec_payment', $settings_paypal);

    $settings_paypal->add(new admin_setting_configcheckbox(
        'local_moodec/payment_paypal_enable',
        get_string('payment_enable', 'local_moodec'),
        get_string('payment_enable_desc', 'local_moodec'),
        0
    ));
    $settings_paypal->add(new admin_setting_configtext(
        'local_moodec/payment_paypal_email',
        get_string('payment_paypal_email', 'local_moodec'),
        get_string('payment_paypal_email_desc', 'local_moodec'),
        '',
        PARAM_EMAIL
    ));
    $settings_paypal->add(new admin_setting_configcheckbox(
        'local_moodec/payment_paypal_sandbox',
        get_string('payment_paypal_sandbox', 'local_moodec'),
        get_string('payment_paypal_sandbox_desc', 'local_moodec'),
        0
    ));

    // === Page Display Settings ===
    $settings_pages = new admin_settingpage('local_moodec_pages', get_string('moodec_pages', 'local_moodec'));
    $ADMIN->add('local_moodec', $settings_pages);

    // Catalogue
    $settings_pages->add(new admin_setting_heading(
        'local_moodec/page_setting_heading_catalogue',
        get_string('page_setting_heading_catalogue', 'local_moodec'),
        ''
    ));

    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_catalogue_show_description', get_string('page_catalogue_show_description', 'local_moodec'), get_string('page_catalogue_show_description_desc', 'local_moodec'), 1));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_catalogue_show_additional_description', get_string('page_catalogue_show_additional_description', 'local_moodec'), get_string('page_catalogue_show_additional_description_desc', 'local_moodec'), 0));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_catalogue_show_duration', get_string('page_catalogue_show_duration', 'local_moodec'), get_string('page_catalogue_show_duration_desc', 'local_moodec'), 1));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_catalogue_show_image', get_string('page_catalogue_show_image', 'local_moodec'), get_string('page_catalogue_show_image_desc', 'local_moodec'), 1));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_catalogue_show_category', get_string('page_catalogue_show_category', 'local_moodec'), get_string('page_catalogue_show_category_desc', 'local_moodec'), 1));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_catalogue_show_price', get_string('page_catalogue_show_price', 'local_moodec'), get_string('page_catalogue_show_price_desc', 'local_moodec'), 1));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_catalogue_show_button', get_string('page_catalogue_show_button', 'local_moodec'), get_string('page_catalogue_show_button_desc', 'local_moodec'), 1));

    // Product page
    $settings_pages->add(new admin_setting_heading(
        'local_moodec/page_setting_heading_product',
        get_string('page_setting_heading_product', 'local_moodec'),
        ''
    ));

    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_product_enable', get_string('page_product_enable', 'local_moodec'), get_string('page_product_enable_desc', 'local_moodec'), 1));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_product_show_image', get_string('page_product_show_image', 'local_moodec'), get_string('page_product_show_image_desc', 'local_moodec'), 1));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_product_show_description', get_string('page_product_show_description', 'local_moodec'), get_string('page_product_show_description_desc', 'local_moodec'), 1));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_product_show_additional_description', get_string('page_product_show_additional_description', 'local_moodec'), get_string('page_product_show_additional_description_desc', 'local_moodec'), 1));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_product_show_category', get_string('page_product_show_category', 'local_moodec'), get_string('page_product_show_category_desc', 'local_moodec'), 1));
    $settings_pages->add(new admin_setting_configcheckbox('local_moodec/page_product_show_related_products', get_string('page_product_show_related_products', 'local_moodec'), get_string('page_product_show_related_products_desc', 'local_moodec'), 1));
}
