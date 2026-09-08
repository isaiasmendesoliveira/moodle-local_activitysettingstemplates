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
 * Library callbacks for Activity Settings Templates.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use local_activitysettingstemplates\local\field_registry;

/**
 * Add Activity Settings Templates to course navigation for users who can manage it.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function local_activitysettingstemplates_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
): void {
    if (!has_capability('local/activitysettingstemplates:manage', $context)
            || !has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    $url = new moodle_url('/local/activitysettingstemplates/index.php', ['courseid' => $course->id]);
    $navigation->add(
        get_string('pluginname', 'local_activitysettingstemplates'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_activitysettingstemplates',
        new pix_icon('i/settings', '')
    );
}

/**
 * Inject preset controls into native activity settings forms.
 *
 * @param moodleform_mod $formwrapper
 * @param MoodleQuickForm $mform
 */
function local_activitysettingstemplates_coursemodule_standard_elements(
    moodleform_mod $formwrapper,
    MoodleQuickForm $mform
): void {
    global $COURSE, $DB, $PAGE, $USER;

    $current = $formwrapper->get_current();
    $moduletype = $current->modulename ?? optional_param('add', '', PARAM_ALPHANUMEXT);

    if (!field_registry::is_supported($moduletype) || empty($COURSE->id)) {
        return;
    }

    $context = context_course::instance($COURSE->id);
    if (!has_capability('local/activitysettingstemplates:manage', $context)
            || !has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    $presets = $DB->get_records(
        'local_ast_templates',
        ['userid' => $USER->id, 'moduletype' => $moduletype],
        'name ASC, id ASC'
    );

    $mform->addElement('header', 'local_activitysettingstemplates_header',
        get_string('formsectiontitle', 'local_activitysettingstemplates'));

    $manageurl = new moodle_url('/local/activitysettingstemplates/index.php', ['courseid' => $COURSE->id]);
    $cmid = !empty($current->coursemodule) ? (int)$current->coursemodule : 0;

    // Render the whole teacher-facing template UI as one responsive Bootstrap grid.
    // A centered Bootstrap container provides the visual composition requested for this section.
    $panelhtml = html_writer::start_div('container local-ast-form-panel');

    // Introductory guidance: full available width.
    $panelhtml .= html_writer::start_div('row mb-2');
    $panelhtml .= html_writer::start_div('col-12');
    $panelhtml .= html_writer::tag('p', get_string('formsectionintro', 'local_activitysettingstemplates'), [
        'class' => 'mb-0',
        'id' => 'local-activitysettingstemplates-intro',
    ]);
    $panelhtml .= html_writer::end_div();
    $panelhtml .= html_writer::end_div();

    // Template-management actions: equal halves on large screens and full width on small screens.
    $panelhtml .= html_writer::start_div('row g-3 mb-2 justify-content-center local-ast-management-row');
    if ($cmid > 0) {
        $createurl = new moodle_url('/local/activitysettingstemplates/create.php', ['cmid' => $cmid]);
        $panelhtml .= html_writer::start_div('col-12 col-lg-6 d-flex justify-content-center');
        $panelhtml .= html_writer::tag('button',
            get_string('createfromactivity', 'local_activitysettingstemplates'), [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary w-100 local-ast-nav-button',
                'data-url' => $createurl->out(false),
            ]
        );
        $panelhtml .= html_writer::end_div();
    }
    $panelhtml .= html_writer::start_div('col-12 col-lg-6 d-flex justify-content-center');
    $panelhtml .= html_writer::tag('button',
        get_string('managepresets', 'local_activitysettingstemplates'), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary w-100 local-ast-nav-button',
            'data-url' => $manageurl->out(false),
        ]
    );
    $panelhtml .= html_writer::end_div();
    $panelhtml .= html_writer::end_div();

    if (empty($presets)) {
        $panelhtml .= html_writer::start_div('row');
        $panelhtml .= html_writer::start_div('col-12');
        $panelhtml .= html_writer::div(
            get_string('nopresetsform', 'local_activitysettingstemplates'),
            'alert alert-light border mb-0',
            ['role' => 'status']
        );
        $panelhtml .= html_writer::end_div();
        $panelhtml .= html_writer::end_div();
        $panelhtml .= html_writer::end_div();
        $mform->addElement('static', 'local_activitysettingstemplates_panel', '', $panelhtml);
        $PAGE->requires->js_call_amd(
            'local_activitysettingstemplates/applytemplate',
            'init',
            [[], [], [], []]
        );
        return;
    }

    $options = [0 => get_string('selectpreset', 'local_activitysettingstemplates')];
    $presetdata = [];
    foreach ($presets as $preset) {
        $options[$preset->id] = format_string($preset->name);
        $decoded = json_decode($preset->configjson, true);
        $decoded = is_array($decoded) ? $decoded : [];
        $decoded = field_registry::normalise_stored_config($moduletype, $decoded);
        $presetdata[(string)$preset->id] = [
            'config' => $decoded,
            'description' => trim((string)$preset->description),
        ];
    }

    // Selection and application: equal halves on large screens; stack to full width on small screens.
    $selecthtml = html_writer::select(
        $options,
        'local_activitysettingstemplates_selector',
        0,
        false,
        [
            'id' => 'id_local_activitysettingstemplates_selector',
            'class' => 'form-select custom-select local-ast-template-select',
            'aria-describedby' => 'local-activitysettingstemplates-intro',
            'aria-controls' => 'local-activitysettingstemplates-preview',
        ]
    );

    $panelhtml .= html_writer::start_div('row g-3 align-items-center justify-content-center local-ast-selection-row');
    $panelhtml .= html_writer::start_div('col-12 col-lg-6 d-flex justify-content-center');
    $panelhtml .= html_writer::start_div('input-group w-100 local-ast-template-inputgroup');
    $panelhtml .= html_writer::tag('label', get_string('preset', 'local_activitysettingstemplates'), [
        'class' => 'input-group-text',
        'for' => 'id_local_activitysettingstemplates_selector',
    ]);
    $panelhtml .= $selecthtml;
    $panelhtml .= html_writer::end_div();
    $panelhtml .= html_writer::end_div();

    $panelhtml .= html_writer::start_div('col-12 col-lg-6 d-flex justify-content-center');
    $panelhtml .= html_writer::tag('button',
        get_string('applypreset', 'local_activitysettingstemplates'), [
        'type' => 'button',
        'id' => 'id_local_activitysettingstemplates_apply',
        'class' => 'btn btn-secondary w-100',
        'disabled' => 'disabled',
        'aria-controls' => 'local-activitysettingstemplates-preview local-activitysettingstemplates-status',
    ]);
    $panelhtml .= html_writer::end_div();

    // Immediate application feedback: shown in the teacher's current visual context,
    // immediately before the compatibility/settings preview.
    $panelhtml .= html_writer::start_div('col-12');
    $panelhtml .= html_writer::tag('div', '', [
        'id' => 'local-activitysettingstemplates-status',
        'class' => 'alert alert-success mb-0 d-none local-ast-apply-status',
        'role' => 'status',
        'aria-live' => 'polite',
        'aria-atomic' => 'true',
    ]);
    $panelhtml .= html_writer::end_div();

    // Compatibility/settings preview: always the full container width.
    $panelhtml .= html_writer::start_div('col-12');
    $panelhtml .= html_writer::start_div('local-ast-preview border rounded p-3 mt-1 d-none', [
        'id' => 'local-activitysettingstemplates-preview',
    ]);
    $panelhtml .= html_writer::tag('div', '', [
        'id' => 'local-activitysettingstemplates-preview-description',
        'class' => 'mb-3 text-muted d-none',
    ]);
    $panelhtml .= html_writer::tag('div', '', [
        'id' => 'local-activitysettingstemplates-preview-summary',
        'class' => 'mb-3 d-none',
        'role' => 'status',
        'aria-live' => 'polite',
        'aria-atomic' => 'true',
    ]);
    $panelhtml .= html_writer::tag('div', '', [
        'id' => 'local-activitysettingstemplates-preview-list',
    ]);
    $panelhtml .= html_writer::end_div();
    $panelhtml .= html_writer::end_div();
    $panelhtml .= html_writer::end_div();

    $mform->addElement('static', 'local_activitysettingstemplates_panel', '', $panelhtml);

    $fieldlabels = [];
    foreach (field_registry::get_flat_definitions($moduletype) as $definition) {
        $fieldlabels[$definition['formfield']] = field_registry::get_definition_context_label($definition);
    }

    $strings = [
        'choose' => get_string('choosepresetfirst', 'local_activitysettingstemplates'),
        'applied' => get_string('presetappliedstatus', 'local_activitysettingstemplates'),
        'appliedwithskips' => get_string('presetappliedwithskips', 'local_activitysettingstemplates'),
        'yes' => get_string('yes'),
        'no' => get_string('no'),
        'none' => get_string('none'),
        'seconds' => get_string('seconds'),
        'minutes' => get_string('minutes'),
        'hours' => get_string('hours'),
        'days' => get_string('days'),
        'previewtotal' => get_string('previewtotal', 'local_activitysettingstemplates'),
        'previewapplicable' => get_string('previewapplicable', 'local_activitysettingstemplates'),
        'previewconditional' => get_string('previewconditional', 'local_activitysettingstemplates'),
        'previewunavailable' => get_string('previewunavailable', 'local_activitysettingstemplates'),
        'previewattention' => get_string('previewattention', 'local_activitysettingstemplates'),
        'previewready' => get_string('previewready', 'local_activitysettingstemplates'),
        'statusapplicable' => get_string('statusapplicable', 'local_activitysettingstemplates'),
        'statusconditional' => get_string('statusconditional', 'local_activitysettingstemplates'),
        'statusunavailable' => get_string('statusunavailable', 'local_activitysettingstemplates'),
        'reasonapplicable' => get_string('reasonapplicable', 'local_activitysettingstemplates'),
        'reasonconditional' => get_string('reasonconditional', 'local_activitysettingstemplates'),
        'reasonmissingfield' => get_string('reasonmissingfield', 'local_activitysettingstemplates'),
        'reasoninvalidvalue' => get_string('reasoninvalidvalue', 'local_activitysettingstemplates'),
    ];

    $fieldtypes = [];
    foreach (field_registry::get_flat_definitions($moduletype) as $definition) {
        $formfield = (string)($definition['formfield'] ?? '');
        if ($formfield === '') {
            continue;
        }
        $fieldtypes[$formfield] = field_registry::get_editor_control($moduletype, $formfield)['type'] ?? 'text';
    }

    $PAGE->requires->js_call_amd(
        'local_activitysettingstemplates/applytemplate',
        'init',
        [$presetdata, $fieldlabels, $strings, $fieldtypes]
    );
}
