<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     local_advancedperfs
 * @subpackage  local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright   (C) 2016 onwards Valery Fremaux
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_advancedperfs_upgrade($oldversion = 0) {
    global $DB;

    $result = true;
    // Removed old upgrade stuff, as it now uses install.xml by default to install.

    $dbman = $DB->get_manager();

    if ($oldversion < 2016111901) {
        // Define table.
        // Define table to be created.
        $table = new xmldb_table('local_advancedperfs_slowp');

        // Adding fields to table.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timespent', XMLDB_TYPE_NUMBER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('dbcalls', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timeindb', XMLDB_TYPE_NUMBER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('memused', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

        // Adding keys to table.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table.
        $table->add_index('ix_user', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Add field to track the last writer in a page.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2016111901, 'local', 'advancedperfs');
    }

    if ($oldversion < 2017110100) {
        // Define table.
        $table = new xmldb_table('local_advancedperfs_slowp');

        $field = new xmldb_field('onlineusers');
        $field->set_attributes(XMLDB_TYPE_INTEGER, 6, null, null, null, null, null, 0, 'url');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('activeusers');
        $field->set_attributes(XMLDB_TYPE_INTEGER, 6, null, null, null, null, null, 0, 'onlineusers');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2017110100, 'local', 'advancedperfs');
    }

    return $result;
}
