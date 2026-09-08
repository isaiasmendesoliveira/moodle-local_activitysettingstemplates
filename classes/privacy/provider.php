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
 * Privacy API implementation for Activity Settings Templates.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitysettingstemplates\privacy;

use context;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for Activity Settings Templates.
 *
 * Presets are personal resources and are associated with the user's context.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_activitysettingstemplates', [
            'userid' => 'privacy:metadata:local_activitysettingstemplates:userid',
            'name' => 'privacy:metadata:local_activitysettingstemplates:name',
            'description' => 'privacy:metadata:local_activitysettingstemplates:description',
            'moduletype' => 'privacy:metadata:local_activitysettingstemplates:moduletype',
            'configjson' => 'privacy:metadata:local_activitysettingstemplates:configjson',
            'timecreated' => 'privacy:metadata:local_activitysettingstemplates:timecreated',
            'timemodified' => 'privacy:metadata:local_activitysettingstemplates:timemodified',
        ], 'privacy:metadata:local_activitysettingstemplates');

        return $collection;
    }

    /**
     * Get contexts containing personal data for a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists('local_activitysettingstemplates', ['userid' => $userid])) {
            $contextlist->add_user_context($userid);
        }
        return $contextlist;
    }

    /**
     * Export personal preset data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $usercontext = context_user::instance($userid);
        if (!in_array($usercontext->id, $contextlist->get_contextids(), true)) {
            return;
        }

        $records = $DB->get_records('local_activitysettingstemplates', ['userid' => $userid], 'id ASC');
        if (!$records) {
            return;
        }

        $export = [];
        foreach ($records as $record) {
            $export[] = (object)[
                'name' => $record->name,
                'description' => $record->description,
                'moduletype' => $record->moduletype,
                'configuration' => $record->configjson,
                'timecreated' => transform::datetime($record->timecreated),
                'timemodified' => transform::datetime($record->timemodified),
            ];
        }

        writer::with_context($usercontext)->export_data(
            [get_string('pluginname', 'local_activitysettingstemplates')],
            (object)['presets' => $export]
        );
    }

    /**
     * Delete all data for users represented by a user context.
     *
     * @param context $context
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if ($context->contextlevel === CONTEXT_USER) {
            $DB->delete_records('local_activitysettingstemplates', ['userid' => $context->instanceid]);
        }
    }

    /**
     * Delete approved user data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $usercontext = context_user::instance($userid);
        if (in_array($usercontext->id, $contextlist->get_contextids(), true)) {
            $DB->delete_records('local_activitysettingstemplates', ['userid' => $userid]);
        }
    }

    /**
     * Add users with data in a user context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        if ($DB->record_exists('local_activitysettingstemplates', ['userid' => $context->instanceid])) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Delete data for an approved list of users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        $userids = $userlist->get_userids();
        if (in_array($context->instanceid, $userids, true)) {
            $DB->delete_records('local_activitysettingstemplates', ['userid' => $context->instanceid]);
        }
    }
}
