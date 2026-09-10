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
 * Edit a personal activity settings template.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_activitysettingstemplates\form\edit_template_form;
use local_activitysettingstemplates\local\field_registry;

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('local/activitysettingstemplates:manage', $context);
require_capability('moodle/course:manageactivities', $context);

$preset = $DB->get_record('local_activitysettingstemplates', ['id' => $id, 'userid' => $USER->id], '*', MUST_EXIST);
if (!field_registry::is_supported($preset->moduletype)) {
    throw new moodle_exception('unsupportedmodule', 'local_activitysettingstemplates');
}

$returnurl = new moodle_url('/local/activitysettingstemplates/index.php', ['courseid' => $courseid]);
$PAGE->set_url(new moodle_url('/local/activitysettingstemplates/edit.php', ['id' => $id, 'courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('editpreset', 'local_activitysettingstemplates'));
$PAGE->set_heading(format_string($course->fullname));

$form = new edit_template_form(null, [
    'preset' => $preset,
    'courseid' => $courseid,
]);
$form->set_data((object)[
    'presetname' => $preset->name,
    'description' => $preset->description,
]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $definitions = field_registry::get_flat_definitions($preset->moduletype);
    $newconfig = [];

    foreach ($definitions as $key => $definition) {
        $includeproperty = 'include_' . $key;
        $valueproperty = 'value_' . $key;
        if (empty($data->{$includeproperty})) {
            continue;
        }

        $control = field_registry::get_editor_control($preset->moduletype, $definition['formfield']);
        if ($control['type'] === 'duration') {
            $numberproperty = $valueproperty . '_number';
            $unitproperty = $valueproperty . '_unit';
            if (!property_exists($data, $numberproperty) || !property_exists($data, $unitproperty)) {
                continue;
            }
            $newconfig[$definition['formfield']] = max(0, (int)$data->{$numberproperty}) * max(1, (int)$data->{$unitproperty});
            continue;
        }

        if (!property_exists($data, $valueproperty)) {
            continue;
        }

        $newconfig[$definition['formfield']] = $data->{$valueproperty};
    }

    if (empty($newconfig)) {
        redirect(
            new moodle_url('/local/activitysettingstemplates/edit.php', ['id' => $id, 'courseid' => $courseid]),
            get_string('selectatleastonefield', 'local_activitysettingstemplates'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $preset->name = trim($data->presetname);
    $preset->description = trim((string)$data->description);
    $preset->configjson = json_encode($newconfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $preset->timemodified = time();
    $DB->update_record('local_activitysettingstemplates', $preset);

    redirect(
        $returnurl,
        get_string('presetupdated', 'local_activitysettingstemplates'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editpreset', 'local_activitysettingstemplates'));
echo $OUTPUT->notification(
    get_string('editsafetyinfo', 'local_activitysettingstemplates'),
    \core\output\notification::NOTIFY_INFO
);
$form->display();
echo $OUTPUT->footer();
