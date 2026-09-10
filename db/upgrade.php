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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade steps for Activity Settings Templates.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Upgrade Activity Settings Templates.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_local_activitysettingstemplates_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026090813) {
        $oldtable = new xmldb_table('local_ast_templates');
        $newtable = new xmldb_table('local_activitysettingstemplates');

        if ($dbman->table_exists($oldtable) && !$dbman->table_exists($newtable)) {
            $dbman->rename_table($oldtable, 'local_activitysettingstemplates');
        }

        upgrade_plugin_savepoint(true, 2026090813, 'local', 'activitysettingstemplates');
    }

    if ($oldversion < 2026091000) {
        // Code-quality and packaging maintenance release; no database schema changes are required.
        upgrade_plugin_savepoint(true, 2026091000, 'local', 'activitysettingstemplates');
    }

    return true;
}
