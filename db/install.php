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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Installation steps for Activity Settings Templates.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Migrate personal templates from the former Activity Presets development plugin when its table exists.
 *
 * @return bool
 */
function xmldb_local_activitysettingstemplates_install(): bool {
    global $DB;

    $dbman = $DB->get_manager();
    $oldtable = new xmldb_table('local_activitypresets');
    if (!$dbman->table_exists($oldtable)) {
        return true;
    }

    $oldrecords = $DB->get_records('local_activitypresets', null, 'id ASC');
    foreach ($oldrecords as $oldrecord) {
        $duplicate = $DB->record_exists('local_ast_templates', [
            'userid' => $oldrecord->userid,
            'name' => $oldrecord->name,
            'moduletype' => $oldrecord->moduletype,
        ]);
        if ($duplicate) {
            continue;
        }

        $record = (object)[
            'userid' => $oldrecord->userid,
            'name' => $oldrecord->name,
            'description' => $oldrecord->description,
            'moduletype' => $oldrecord->moduletype,
            'configjson' => $oldrecord->configjson,
            'timecreated' => $oldrecord->timecreated,
            'timemodified' => $oldrecord->timemodified,
        ];
        $DB->insert_record('local_ast_templates', $record);
    }

    return true;
}
