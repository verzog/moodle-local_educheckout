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
 * Upgrade steps for the Moodec storefront plugin.
 *
 * @package    local_moodec
 * @copyright  2026 LearningWorks Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the Moodec storefront plugin.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool
 */
function xmldb_local_moodec_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026051700) {
        $newtables = [
            'local_moodec_cart',
            'local_moodec_cart_item',
            'local_moodec_order',
            'local_moodec_order_item',
        ];
        foreach ($newtables as $tablename) {
            $table = new xmldb_table($tablename);
            if (!$dbman->table_exists($table)) {
                $dbman->install_one_table_from_xmldb_file(
                    $CFG->dirroot . '/local/moodec/db/install.xml',
                    $tablename
                );
            }
        }
        upgrade_plugin_savepoint(true, 2026051700, 'local', 'moodec');
    }

    return true;
}
