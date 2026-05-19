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

    if ($oldversion < 2026051800) {
        // Create the category table.
        $table = new xmldb_table('local_moodec_category');
        if (!$dbman->table_exists($table)) {
            $dbman->install_one_table_from_xmldb_file(
                $CFG->dirroot . '/local/moodec/db/install.xml',
                'local_moodec_category'
            );
        }

        // Add category_id to product table.
        $table = new xmldb_table('local_moodec_product');
        $field = new xmldb_field('category_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'course_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add sort_order to product table.
        $field = new xmldb_field('sort_order', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'variation_count');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add description_format to product table.
        $field = new xmldb_field('description_format', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'description');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index on category_id.
        $index = new xmldb_index('category_id', XMLDB_INDEX_NOTUNIQUE, ['category_id']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2026051800, 'local', 'moodec');
    }

    if ($oldversion < 2026051902) {
        $table = new xmldb_table('local_moodec_variation');

        $field = new xmldb_field('session_starttime', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'group_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('session_endtime', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'session_starttime');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('session_location', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'session_endtime');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('session_capacity', XMLDB_TYPE_INTEGER, '6', null, null, null, '0', 'session_location');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026051902, 'local', 'moodec');
    }

    return true;
}
