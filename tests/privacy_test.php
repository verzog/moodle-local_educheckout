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
 * Tests for the Moodec privacy provider.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec;

use local_moodec\privacy\provider;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;

/**
 * Unit tests for \local_moodec\privacy\provider.
 *
 * @covers \local_moodec\privacy\provider
 */
final class privacy_test extends \advanced_testcase {
    /**
     * Metadata is described and a user's data can be located and deleted.
     *
     * @return void
     */
    public function test_metadata_and_deletion(): void {
        global $DB;
        $this->resetAfterTest();

        $collection = new \core_privacy\local\metadata\collection('local_moodec');
        $collection = provider::get_metadata($collection);
        $this->assertNotEmpty($collection->get_collection());

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $cart = cart::get_open((int) $user->id);
        $cart->add_item(1, 0, (int) $course->id, 10.0);
        order::create_from_cart($cart, (int) $user->id, null);

        $contextlist = provider::get_contexts_for_userid((int) $user->id);
        $this->assertCount(1, $contextlist->get_contextids());

        $approved = new approved_contextlist($user, 'local_moodec', $contextlist->get_contextids());
        provider::delete_data_for_user($approved);

        $this->assertFalse($DB->record_exists('local_moodec_cart', ['userid' => $user->id]));
        $this->assertFalse($DB->record_exists('local_moodec_order', ['userid' => $user->id]));
    }
}
