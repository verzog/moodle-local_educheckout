<?php
/**
 * Moodec Language file
 *
 * @package     local_moodec
 * @author      Thomas Threadgold, OpenAI Updates
 * @copyright   2015 LearningWorks Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Moodec';

// General
$string['moodec'] = 'Moodec';
$string['privacy:metadata'] = 'The Moodec plugin does not store personal data directly.';

// New settings additions
$string['currency_format_locale'] = 'Currency locale';
$string['currency_format_locale_desc'] = 'The locale used to format currency (e.g. en_AU, en_US, fr_FR).';
$string['currency_symbol'] = 'Currency symbol';
$string['currency_symbol_desc'] = 'Symbol used for currency display (e.g. $, €, ¥).';

// New UI messages
$string['tooltip_add_to_cart'] = 'Click to add this course to your cart';
$string['tooltip_checkout'] = 'Proceed to payment options';
$string['tooltip_enrolled'] = 'You are already enrolled in this course';
$string['tooltip_view_details'] = 'Click to view product details';

// SETTINGS
$string['moodec_pages'] = 'Page settings';
$string['moodec_settings'] = 'General settings';
$string['moodec_course_settings'] = 'Edit product settings';
$string['moodec_product_settings'] = 'Edit product settings';
$string['page_setting_heading_catalogue'] = 'Catalogue page';
$string['page_setting_heading_product'] = 'Product page';

$string['page_catalogue_show_description'] = 'Show course description';
$string['page_catalogue_show_description_desc'] = 'This will show the course description excerpt on the catalogue list page';
$string['page_catalogue_show_additional_description'] = 'Show additional description';
$string['page_catalogue_show_additional_description_desc'] = 'This will show the additional description excerpt on the catalogue list page';
$string['page_catalogue_show_duration'] = 'Show product enrolment duration';
$string['page_catalogue_show_duration_desc'] = 'This will show the product enrolment duration excerpt on the catalogue list page';
$string['page_catalogue_show_image'] = 'Show product image';
$string['page_catalogue_show_image_desc'] = 'This will show the product image on the catalogue list page';
$string['page_catalogue_show_category'] = 'Show product category';
$string['page_catalogue_show_category_desc'] = 'This will show the product category on the catalogue list page';
$string['page_catalogue_show_price'] = 'Show product price';
$string['page_catalogue_show_price_desc'] = 'This will show the product price on the catalogue list page';
$string['page_catalogue_show_button'] = 'Show add to cart button';
$string['page_catalogue_show_button_desc'] = 'This will show the add to cart button on the catalogue list page';

$string['page_product_enable'] = 'Enable this page';
$string['page_product_enable_desc'] = 'This will allow users to view individual products and add links to the navigation block';
$string['page_product_show_image'] = 'Show product image';
$string['page_product_show_image_desc'] = 'This will show the product image on the product page';
$string['page_product_show_description'] = 'Show course description';
$string['page_product_show_description_desc'] = 'This will show the course description excerpt on the product page';
$string['page_product_show_additional_description'] = 'Show product\'s additional description';
$string['page_product_show_additional_description_desc'] = 'This will show the product\'s additional description excerpt on the product page';
$string['page_product_show_category'] = 'Show course category';
$string['page_product_show_category_desc'] = 'This will show the course category on the product page';
$string['page_product_show_related_products'] = 'Show related products';
$string['page_product_show_related_products_desc'] = 'This will show the related products on the product page';

// Currency settings for localisation
$string['currency'] = 'Currency';
$string['currency_desc'] = 'The 3-character ISO currency code (e.g. USD, AUD, EUR).';
$string['currency_locale'] = 'Locale';
$string['currency_locale_desc'] = 'Sets locale-based formatting for currency (e.g. en_AU, fr_FR).';

$string['pagination'] = 'Courses per page';
$string['pagination_desc'] = 'The number of courses to be displayed per page in the catalogue';

// Payment
$string['payment_title'] = 'Payment Gateways';
$string['payment_enable'] = 'Enable';
$string['payment_enable_desc'] = 'Enable this payment gateway';
$string['payment_dps_title'] = 'DPS';
$string['payment_dps_userid'] = 'PxPay User ID';
$string['payment_dps_userid_desc'] = 'Enter your DPS PxPay merchant user id';
$string['payment_dps_key'] = 'PxPay Key';
$string['payment_dps_key_desc'] = 'Enter your DPS PxPay merchant key';
$string['payment_dps_sandbox'] = 'Use sandbox mode';
$string['payment_dps_sandbox_desc'] = 'This will enable DPS sandbox mode for the gateway. You will need to enter a development user and key to test.';
$string['payment_paypal_title'] = 'Paypal';
$string['payment_paypal_email'] = 'Email';
$string['payment_paypal_email_desc'] = 'Enter your Paypal business email address';
$string['payment_paypal_sandbox'] = 'Use sandbox mode';
$string['payment_paypal_sandbox_desc'] = 'This will enable Paypal sandbox for the gateway';
