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
 * Payment gateway surcharge helper for the EduCheckout storefront.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout;

/**
 * Computes the payment-gateway surcharge (e.g. Stripe card processing fee)
 * applied to an order based on the configured percentage + fixed amount.
 */
class gateway_fee {
    /**
     * Compute the surcharge amount for the given pre-fee subtotal.
     *
     * Returns 0 when the surcharge is disabled or both rate components are zero.
     *
     * @param float $subtotal The order amount before the surcharge (typically net + tax).
     * @return float The surcharge, rounded to two decimal places.
     */
    public static function calculate(float $subtotal): float {
        if (!get_config('local_educheckout', 'gateway_fee_enable')) {
            return 0.0;
        }
        $percent = (float) get_config('local_educheckout', 'gateway_fee_percent');
        $fixed = (float) get_config('local_educheckout', 'gateway_fee_fixed');
        if ($percent <= 0.0 && $fixed <= 0.0) {
            return 0.0;
        }
        if ($subtotal <= 0.0) {
            return 0.0;
        }
        return round(($subtotal * $percent / 100) + $fixed, 2);
    }

    /**
     * Return the configured display label for the surcharge line, falling back to a default string.
     *
     * @return string
     */
    public static function get_label(): string {
        $label = trim((string) get_config('local_educheckout', 'gateway_fee_label'));
        if ($label === '') {
            return get_string('gateway_fee_default_label', 'local_educheckout');
        }
        return $label;
    }

    /**
     * Whether the surcharge is enabled in admin settings.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return (bool) get_config('local_educheckout', 'gateway_fee_enable');
    }
}
