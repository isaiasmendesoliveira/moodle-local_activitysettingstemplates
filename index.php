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
 * Manage personal activity settings templates.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_activitysettingstemplates\local\field_registry;

require_once(__DIR__ . '/../../config.php');

/**
 * Format a stored preset scalar for the management-page summary.
 *
 * The management page does not have the native activity form available, so
 * common Moodle values are translated here into teacher-facing labels instead
 * of exposing internal values such as "deferredfeedback" or "sequential".
 *
 * @param string $moduletype
 * @param string $field
 * @param mixed $value
 * @return string
 */
function local_activitysettingstemplates_index_format_value(string $moduletype, string $field, $value): string {
    return field_registry::format_stored_value($moduletype, $field, $value);
}

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($course->id);
require_capability('local/activitysettingstemplates:manage', $context);
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_url(new moodle_url('/local/activitysettingstemplates/index.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('managepresets', 'local_activitysettingstemplates'));
$PAGE->set_heading(format_string($course->fullname));

$presets = $DB->get_records('local_activitysettingstemplates', ['userid' => $USER->id], 'moduletype ASC, name ASC');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managepresets', 'local_activitysettingstemplates'));

echo $OUTPUT->notification(get_string('indexintro', 'local_activitysettingstemplates'),
    \core\output\notification::NOTIFY_INFO);

if (empty($presets)) {
    echo $OUTPUT->notification(get_string('nopresets', 'local_activitysettingstemplates'),
        \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('presetname', 'local_activitysettingstemplates'),
    get_string('activitytype', 'local_activitysettingstemplates'),
    get_string('settingscount', 'local_activitysettingstemplates'),
    get_string('description', 'local_activitysettingstemplates'),
    get_string('actions'),
];
$table->attributes['class'] = 'generaltable';

foreach ($presets as $preset) {
    $config = json_decode($preset->configjson, true);
    $config = is_array($config) ? $config : [];
    $config = field_registry::normalise_stored_config($preset->moduletype, $config);
    $count = count($config);
    $modulename = field_registry::get_module_name($preset->moduletype);

    $labels = [];
    foreach (field_registry::get_flat_definitions($preset->moduletype) as $definition) {
        $labels[$definition['formfield']] = field_registry::get_definition_context_label($definition);
    }

    $settingitems = [];
    foreach ($config as $field => $value) {
        $label = $labels[$field] ?? $field;
        $settingitems[] = html_writer::tag(
            'li',
            html_writer::tag('strong', s($label) . ': ') .
                local_activitysettingstemplates_index_format_value($preset->moduletype, $field, $value),
            ['class' => 'mb-1']
        );
    }

    // Keep the saved configuration visible without an additional "view" label/click.
    $settingscell = html_writer::tag('div', (string)$count, [
        'class' => 'badge bg-secondary badge-secondary text-white mb-2',
        'aria-label' => get_string('settingscount', 'local_activitysettingstemplates') . ': ' . $count,
    ]);
    if (!empty($settingitems)) {
        $settingscell .= html_writer::tag('ul', implode('', $settingitems), [
            'class' => 'mb-0 mt-1 ps-3 pl-3',
        ]);
    }

    $editurl = new moodle_url('/local/activitysettingstemplates/edit.php', [
        'id' => $preset->id,
        'courseid' => $courseid,
    ]);
    $editicon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
    $edit = html_writer::link($editurl, $editicon, [
        'class' => 'me-2 mr-2',
        'title' => get_string('editpreset', 'local_activitysettingstemplates'),
        'aria-label' => get_string('editpresetnamed', 'local_activitysettingstemplates', format_string($preset->name)),
    ]);

    $deleteurl = new moodle_url('/local/activitysettingstemplates/delete.php', [
        'id' => $preset->id,
        'courseid' => $courseid,
    ]);
    $deleteicon = $OUTPUT->pix_icon('t/delete', get_string('delete'));
    $delete = html_writer::link($deleteurl, $deleteicon, [
        'title' => get_string('deletepreset', 'local_activitysettingstemplates'),
        'aria-label' => get_string('deletepresetnamed', 'local_activitysettingstemplates', format_string($preset->name)),
    ]);

    $activitytype = html_writer::tag('span', format_string($modulename), [
        'class' => 'badge bg-light text-dark border',
    ]);

    $table->data[] = [
        format_string($preset->name),
        $activitytype,
        $settingscell,
        s($preset->description),
        $edit . $delete,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
