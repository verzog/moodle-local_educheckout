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
 * Tests for the EduCheckout tax helper.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout;

/**
 * Unit tests for \local_educheckout\tax.
 *
 * @covers \local_educheckout\tax
 */
final class tax_test extends \advanced_testcase {
    /**
     * Rate resolution honours the enable flag and country overrides.
     *
     * @return void
     */
    public function test_resolve_rate(): void {
        $this->resetAfterTest();

        set_config('tax_enable', 0, 'local_educheckout');
        set_config('tax_rate', '10.0', 'local_educheckout');
        $this->assertEquals(0.0, tax::resolve_rate('AU'));

        set_config('tax_enable', 1, 'local_educheckout');
        $this->assertEquals(10.0, tax::resolve_rate('AU'));

        set_config('taxcountryoverrides', '{"NZ": 15}', 'local_educheckout');
        $this->assertEquals(15.0, tax::resolve_rate('NZ'));
        $this->assertEquals(10.0, tax::resolve_rate('AU'));
    }

    /**
     * Exclusive and inclusive splits compute the expected components.
     *
     * @return void
     */
    public function test_split(): void {
        $this->resetAfterTest();

        set_config('tax_mode', 'exclusive', 'local_educheckout');
        $split = tax::split(100.0, 10.0);
        $this->assertEquals(100.0, $split['net']);
        $this->assertEquals(10.0, $split['tax']);
        $this->assertEquals(110.0, $split['gross']);

        set_config('tax_mode', 'inclusive', 'local_educheckout');
        $split = tax::split(110.0, 10.0);
        $this->assertEquals(100.0, $split['net']);
        $this->assertEquals(10.0, $split['tax']);
        $this->assertEquals(110.0, $split['gross']);

        $split = tax::split(50.0, 0.0);
        $this->assertEquals(50.0, $split['net']);
        $this->assertEquals(0.0, $split['tax']);
        $this->assertEquals(50.0, $split['gross']);
    }
}
