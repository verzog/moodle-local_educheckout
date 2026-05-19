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
 * Tests for EduCheckout order creation and delivery.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout;

/**
 * Unit tests for \local_educheckout\order.
 *
 * @covers \local_educheckout\order
 */
final class order_test extends \advanced_testcase {
    /**
     * Create an order from a cart, then deliver it (enrol the user).
     *
     * @return void
     */
    public function test_create_and_deliver(): void {
        $this->resetAfterTest();

        set_config('tax_enable', 1, 'local_educheckout');
        set_config('tax_rate', '10.0', 'local_educheckout');
        set_config('tax_mode', 'exclusive', 'local_educheckout');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $cart = cart::get_open((int) $user->id);
        $cart->add_item(1, 0, (int) $course->id, 100.0);

        $order = order::create_from_cart($cart, (int) $user->id, 'AU');
        $this->assertEquals(110.0, $order->get_amount());

        $order->deliver((int) $user->id);
        $this->assertTrue($order->is_delivered());

        $context = \context_course::instance((int) $course->id);
        $this->assertTrue(is_enrolled($context, $user));
    }
}
