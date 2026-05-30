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
 * Event observers for the EduCheckout storefront plugin.
 *
 * @package    local_educheckout
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educheckout;

/**
 * Handles login events to absorb any guest cart into the user's cart.
 */
class observer {
    /**
     * Merge any guest cart owned by the current session into the user's cart.
     *
     * @param \core\event\base $event the login event
     * @return void
     */
    public static function user_loggedin(\core\event\base $event): void {
        global $SESSION;

        $userid = (int) $event->userid;
        if ($userid <= 0) {
            return;
        }

        // The checkout flow stashes the guest cart id before sending the user
        // off to signup/login; prefer that over the (possibly regenerated)
        // session key to make the merge robust.
        $guestcart = null;
        if (!empty($SESSION->local_educheckout_guestcartid)) {
            $guestcart = cart::find_guest_by_id((int) $SESSION->local_educheckout_guestcartid);
            unset($SESSION->local_educheckout_guestcartid);
        }
        if (!$guestcart) {
            $guestcart = cart::find_guest(sesskey());
        }
        if (!$guestcart) {
            return;
        }

        $usercart = cart::get_open($userid);
        $usercart->merge_from($guestcart);
    }
}
