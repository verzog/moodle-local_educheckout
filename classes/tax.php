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
 * Tax calculation helper for the EduCheckout storefront.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout;

/**
 * Resolves the applicable tax rate and splits net/tax/gross amounts.
 */
class tax {
    /**
     * Resolve the applicable tax percentage for a customer country.
     *
     * @param string|null $country ISO country code, or null
     * @return float the tax rate as a percentage (0 when tax is disabled)
     */
    public static function resolve_rate(?string $country): float {
        if (!get_config('local_educheckout', 'tax_enable')) {
            return 0.0;
        }

        $rate = (float) get_config('local_educheckout', 'tax_rate');

        $overridesjson = (string) get_config('local_educheckout', 'taxcountryoverrides');
        if ($country !== null && $overridesjson !== '') {
            $overrides = json_decode($overridesjson, true);
            if (is_array($overrides) && array_key_exists($country, $overrides)) {
                $rate = (float) $overrides[$country];
            }
        }

        return $rate;
    }

    /**
     * Split a price into net, tax and gross given a rate and the configured mode.
     *
     * @param float $price the per-line price as entered for the product
     * @param float $rate the tax percentage
     * @return array {net: float, tax: float, gross: float}
     */
    public static function split(float $price, float $rate): array {
        $mode = (string) get_config('local_educheckout', 'tax_mode');

        if ($rate <= 0.0) {
            return ['net' => $price, 'tax' => 0.0, 'gross' => $price];
        }

        if ($mode === 'inclusive') {
            $net = $price / (1 + ($rate / 100));
            $tax = $price - $net;
            return [
                'net' => round($net, 2),
                'tax' => round($tax, 2),
                'gross' => round($price, 2),
            ];
        }

        $tax = $price * ($rate / 100);
        return [
            'net' => round($price, 2),
            'tax' => round($tax, 2),
            'gross' => round($price + $tax, 2),
        ];
    }
}
