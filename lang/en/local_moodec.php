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
 * Strings for the Moodec storefront plugin.
 *
 * @package    local_moodec
 * @copyright  2015 LearningWorks Ltd
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['catalogue'] = 'Course store';
$string['currency'] = 'Currency';
$string['currency_desc'] = 'The currency in which courses are sold. Must match the currency of the configured Moodle payment account.';
$string['messageprovider:order_receipt'] = 'Moodec order receipts';
$string['moodec:viewallorders'] = 'View all Moodec orders';
$string['pagination'] = 'Courses per page';
$string['pagination_desc'] = 'Number of courses shown per page in the catalogue before pagination.';
$string['pluginname'] = 'Moodec storefront';
$string['privacy:metadata'] = 'The Moodec storefront stores shopping cart and order records associated with a user.';
$string['tax_enable'] = 'Enable tax';
$string['tax_enable_desc'] = 'Apply tax to orders. When disabled, orders are recorded with no tax.';
$string['tax_label'] = 'Tax label';
$string['tax_label_desc'] = 'Label shown for tax on the cart, checkout and receipt (for example GST or VAT).';
$string['tax_mode'] = 'Tax mode';
$string['tax_mode_desc'] = 'Whether displayed prices already include tax (inclusive) or tax is added at checkout (exclusive).';
$string['tax_mode_exclusive'] = 'Exclusive (tax added at checkout)';
$string['tax_mode_inclusive'] = 'Inclusive (prices include tax)';
$string['tax_rate'] = 'Default tax rate (%)';
$string['tax_rate_desc'] = 'Default tax percentage applied when no country-specific override matches.';
$string['taxcountryoverrides'] = 'Tax rate overrides by country';
$string['taxcountryoverrides_desc'] = 'Optional JSON map of ISO country code to tax percentage, for example {"NZ": 15, "GB": 20}. Falls back to the default rate.';
