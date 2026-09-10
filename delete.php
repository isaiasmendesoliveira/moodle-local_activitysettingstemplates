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
 * Delete a personal activity settings template.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/activitysettingstemplates:manage', $context);
require_capability('moodle/course:manageactivities', $context);

$preset = $DB->get_record('local_activitysettingstemplates', ['id' => $id, 'userid' => $USER->id], '*', MUST_EXIST);
$returnurl = new moodle_url('/local/activitysettingstemplates/index.php', ['courseid' => $courseid]);

$PAGE->set_url(new moodle_url('/local/activitysettingstemplates/delete.php', ['id' => $id, 'courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('deletepreset', 'local_activitysettingstemplates'));
$PAGE->set_heading(format_string($course->fullname));

if ($confirm) {
    require_sesskey();
    $DB->delete_records('local_activitysettingstemplates', ['id' => $preset->id, 'userid' => $USER->id]);
    redirect(
        $returnurl,
        get_string('presetdeleted', 'local_activitysettingstemplates'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deletepreset', 'local_activitysettingstemplates'));
$confirmurl = new moodle_url('/local/activitysettingstemplates/delete.php', [
    'id' => $id,
    'courseid' => $courseid,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);
echo $OUTPUT->confirm(
    get_string('deletepresetconfirm', 'local_activitysettingstemplates', format_string($preset->name)),
    $confirmurl,
    $returnurl
);
echo $OUTPUT->footer();
