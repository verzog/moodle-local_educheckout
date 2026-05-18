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
 * Scheduled task to purge stale Moodec carts.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodec\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Deletes open guest carts older than 7 days and open user carts older than 30 days.
 */
class cleanup_carts extends \core\task\scheduled_task {

    /**
     * Return the task name shown in admin.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanup_carts', 'local_moodec');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $now = time();
        $guestcutoff = $now - (7 * DAYSECS);
        $usercutoff = $now - (30 * DAYSECS);

        // Guest carts older than 7 days.
        $guestcarts = $DB->get_records_select(
            'local_moodec_cart',
            "status = 'open' AND userid = 0 AND timecreated < :cutoff",
            ['cutoff' => $guestcutoff],
            '',
            'id'
        );
        foreach ($guestcarts as $cart) {
            $DB->delete_records('local_moodec_cart_item', ['cartid' => $cart->id]);
            $DB->delete_records('local_moodec_cart', ['id' => $cart->id]);
        }

        // User carts open for more than 30 days (abandoned).
        $usercarts = $DB->get_records_select(
            'local_moodec_cart',
            "status = 'open' AND userid > 0 AND timecreated < :cutoff",
            ['cutoff' => $usercutoff],
            '',
            'id'
        );
        foreach ($usercarts as $cart) {
            $DB->delete_records('local_moodec_cart_item', ['cartid' => $cart->id]);
            $DB->delete_records('local_moodec_cart', ['id' => $cart->id]);
        }

        mtrace('Moodec: removed ' . count($guestcarts) . ' guest and ' . count($usercarts) . ' abandoned user carts.');
    }
}
