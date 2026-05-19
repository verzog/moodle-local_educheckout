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
 * Tests for the EduCheckout cart.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout;

/**
 * Unit tests for \local_educheckout\cart.
 *
 * @covers \local_educheckout\cart
 */
final class cart_test extends \advanced_testcase {
    /**
     * Add, dedupe, total, remove and guest-merge behave correctly.
     *
     * @return void
     */
    public function test_cart_lifecycle(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $usercart = cart::get_open((int) $user->id);
        $this->assertTrue($usercart->is_empty());

        $this->assertTrue($usercart->add_item(1, 0, (int) $course->id, 10.0));
        $this->assertFalse($usercart->add_item(1, 0, (int) $course->id, 10.0));
        $this->assertFalse($usercart->is_empty());
        $this->assertEquals(10.0, $usercart->get_total());

        $items = $usercart->get_items();
        $item = reset($items);
        $usercart->remove_item((int) $item->id);
        $this->assertTrue($usercart->is_empty());

        $guest = cart::get_open(0, 'sesskey123');
        $guest->add_item(2, 0, (int) $course->id, 25.0);
        $found = cart::find_guest('sesskey123');
        $this->assertNotNull($found);

        $usercart->merge_from($found);
        $this->assertEquals(25.0, $usercart->get_total());
        $this->assertNull(cart::find_guest('sesskey123'));
    }
}
