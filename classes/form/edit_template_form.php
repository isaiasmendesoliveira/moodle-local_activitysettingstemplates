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
 * Form definition for editing personal activity settings templates.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitysettingstemplates\form;

use local_activitysettingstemplates\local\field_registry;

/**
 * Form used to edit a personal activity preset.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_template_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition(): void {
        $mform = $this->_form;
        $preset = $this->_customdata['preset'];
        $courseid = (int)$this->_customdata['courseid'];
        $moduletype = (string)$preset->moduletype;
        $config = json_decode($preset->configjson, true);
        $config = is_array($config) ? $config : [];
        $config = field_registry::normalise_stored_config($moduletype, $config);

        $mform->addElement('hidden', 'id', (int)$preset->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('header', 'presetdetails', get_string('presetdetails', 'local_activitysettingstemplates'));
        $mform->addElement(
            'static',
            'activitytype',
            get_string('activitytype', 'local_activitysettingstemplates'),
            field_registry::get_module_name($moduletype)
        );

        $mform->addElement('text', 'presetname', get_string('presetname', 'local_activitysettingstemplates'), ['size' => 50]);
        $mform->setType('presetname', PARAM_TEXT);
        $mform->addRule('presetname', get_string('required'), 'required', null, 'client');
        $mform->addRule('presetname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement(
            'textarea',
            'description',
            get_string('description', 'local_activitysettingstemplates'),
            ['rows' => 3, 'cols' => 60]
        );
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('static', 'edithelp', '', get_string('editpresethelp', 'local_activitysettingstemplates'));

        foreach (field_registry::get_definitions($moduletype) as $sectionkey => $section) {
            $mform->addElement(
                'header',
                'section_' . $sectionkey,
                field_registry::get_section_label($section)
            );

            foreach ($section['fields'] as $key => $definition) {
                $formfield = $definition['formfield'];
                $included = array_key_exists($formfield, $config);
                $control = field_registry::get_editor_control($moduletype, $formfield);
                $currentvalue = $included ? $config[$formfield] : $control['default'];

                $include = $mform->createElement(
                    'advcheckbox',
                    'include_' . $key,
                    '',
                    get_string('includeinpreset', 'local_activitysettingstemplates')
                );

                if ($control['type'] === 'select') {
                    $options = $control['options'];
                    if (
                        !array_key_exists($currentvalue, $options)
                        && !array_key_exists((string)$currentvalue, $options)
                    ) {
                        $options[$currentvalue] = get_string(
                            'currentstoredvalue',
                            'local_activitysettingstemplates',
                            (string)$currentvalue
                        );
                    }
                    $value = $mform->createElement('select', 'value_' . $key, '', $options);
                    $groupitems = [$include, $value];
                } else if ($control['type'] === 'duration') {
                    [$durationnumber, $durationunit] = field_registry::split_duration((int)$currentvalue);
                    $number = $mform->createElement('text', 'value_' . $key . '_number', '', [
                        'size' => 6,
                        'inputmode' => 'numeric',
                    ]);
                    $unit = $mform->createElement('select', 'value_' . $key . '_unit', '', field_registry::duration_units());
                    $groupitems = [$include, $number, $unit];
                } else if ($control['type'] === 'number') {
                    $attributes = ['size' => 8, 'inputmode' => 'numeric'];
                    $value = $mform->createElement('text', 'value_' . $key, '', $attributes);
                    $groupitems = [$include, $value];
                } else {
                    $value = $mform->createElement('text', 'value_' . $key, '', ['size' => 30]);
                    $groupitems = [$include, $value];
                }

                $mform->addGroup(
                    $groupitems,
                    'setting_' . $key,
                    field_registry::get_definition_label($definition),
                    ' ',
                    false
                );

                $mform->setDefault('include_' . $key, $included ? 1 : 0);
                if ($control['type'] === 'duration') {
                    $mform->setDefault('value_' . $key . '_number', $durationnumber);
                    $mform->setDefault('value_' . $key . '_unit', $durationunit);
                    $mform->setType('value_' . $key . '_number', PARAM_INT);
                    $mform->setType('value_' . $key . '_unit', PARAM_INT);
                    $mform->disabledIf('value_' . $key . '_number', 'include_' . $key, 'notchecked');
                    $mform->disabledIf('value_' . $key . '_unit', 'include_' . $key, 'notchecked');
                } else {
                    $mform->setDefault('value_' . $key, $currentvalue);
                    $mform->setType('value_' . $key, $control['param']);
                    $mform->disabledIf('value_' . $key, 'include_' . $key, 'notchecked');
                }
            }
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
