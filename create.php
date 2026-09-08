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
 * Create a personal activity settings template from an existing activity.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_activitysettingstemplates\form\create_template_form;
use local_activitysettingstemplates\local\field_registry;

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);

require_login($course, false, $cm);
$context = context_course::instance($course->id);
require_capability('local/activitysettingstemplates:manage', $context);
require_capability('moodle/course:manageactivities', $context);

$module = $DB->get_record('modules', ['id' => $cm->module], 'id,name', MUST_EXIST);
$moduletype = $module->name;
if (!field_registry::is_supported($moduletype)) {
    throw new moodle_exception('unsupportedmodule', 'local_activitysettingstemplates');
}

$instance = $DB->get_record($moduletype, ['id' => $cm->instance], '*', MUST_EXIST);
$activityname = $instance->name ?? $cm->name;

$PAGE->set_url(new moodle_url('/local/activitysettingstemplates/create.php', ['cmid' => $cmid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('createpreset', 'local_activitysettingstemplates'));
$PAGE->set_heading(format_string($course->fullname));

$form = new create_template_form(null, [
    'cmid' => $cmid,
    'courseid' => $course->id,
    'moduletype' => $moduletype,
    'activityname' => $activityname,
]);
$form->set_data((object)[
    'presetname' => get_string('defaultpresetname', 'local_activitysettingstemplates', format_string($activityname)),
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/modedit.php', ['update' => $cmid, 'return' => 1]));
}

if ($data = $form->get_data()) {
    $definitions = field_registry::get_flat_definitions($moduletype);
    $selected = [];
    foreach (array_keys($definitions) as $key) {
        $property = 'field_' . $key;
        if (!empty($data->{$property})) {
            $selected[] = $key;
        }
    }

    if (empty($selected)) {
        redirect(
            new moodle_url('/local/activitysettingstemplates/create.php', ['cmid' => $cmid]),
            get_string('selectatleastonefield', 'local_activitysettingstemplates'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $config = field_registry::extract_config($moduletype, $cm, $instance, $selected);
    if (empty($config)) {
        redirect(
            new moodle_url('/local/activitysettingstemplates/create.php', ['cmid' => $cmid]),
            get_string('nocapturablefields', 'local_activitysettingstemplates'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $record = (object)[
        'userid' => $USER->id,
        'name' => trim($data->presetname),
        'description' => trim((string)$data->description),
        'moduletype' => $moduletype,
        'configjson' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'timecreated' => time(),
        'timemodified' => time(),
    ];
    $DB->insert_record('local_ast_templates', $record);

    redirect(
        new moodle_url('/local/activitysettingstemplates/index.php', ['courseid' => $course->id]),
        get_string('presetcreated', 'local_activitysettingstemplates'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('createpreset', 'local_activitysettingstemplates'));
echo $OUTPUT->notification(get_string('createsafetyinfo', 'local_activitysettingstemplates'),
    \core\output\notification::NOTIFY_INFO);
$form->display();
echo $OUTPUT->footer();
