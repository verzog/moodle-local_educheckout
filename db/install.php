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
 * Install-time migration from local_moodec to local_educheckout.
 *
 * On first install of local_educheckout: if the legacy local_moodec tables
 * exist (from an upgrade-in-place scenario), drop the empty freshly-created
 * local_educheckout tables and rename the legacy tables in their place so
 * existing data carries over. Also migrates plugin config, role capability
 * names and drops stale local_moodec rows from {capabilities} and
 * {task_scheduled}. A clean install (no prior moodec data) is a no-op.
 *
 * @package    local_educheckout
 * @copyright  2026 the EduCheckout contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Migrate any leftover local_moodec data to local_educheckout.
 */
function xmldb_local_educheckout_install() {
    global $DB;

    $dbman = $DB->get_manager();

    // Child tables before parents — install.xml has FKs cart_item -> cart,
    // order -> cart, order_item -> order, trans_item -> transaction,
    // variation -> product, product -> category. Postgres rejects DROP of a
    // referenced table without CASCADE, so drop children first.
    $tables = [
        'cart_item', 'order_item', 'order', 'cart',
        'trans_item', 'transaction',
        'variation', 'product', 'category',
    ];
    foreach ($tables as $name) {
        $old = new xmldb_table('local_moodec_' . $name);
        $newname = 'local_educheckout_' . $name;
        $new = new xmldb_table($newname);
        if ($dbman->table_exists($old)) {
            if ($dbman->table_exists($new)) {
                $dbman->drop_table($new);
            }
            $dbman->rename_table($old, $newname);
        }
    }

    $DB->set_field('config_plugins', 'plugin', 'local_educheckout', ['plugin' => 'local_moodec']);

    $caps = $DB->get_records_sql(
        "SELECT id, capability FROM {role_capabilities} WHERE " . $DB->sql_like('capability', '?'),
        ['local/moodec:%']
    );
    foreach ($caps as $row) {
        $new = 'local/educheckout:' . substr($row->capability, strlen('local/moodec:'));
        $DB->set_field('role_capabilities', 'capability', $new, ['id' => $row->id]);
    }

    $DB->delete_records_select(
        'capabilities',
        $DB->sql_like('name', '?'),
        ['local/moodec:%']
    );
    $DB->delete_records('task_scheduled', ['component' => 'local_moodec']);
    $DB->delete_records('message_providers', ['component' => 'local_moodec']);
}
