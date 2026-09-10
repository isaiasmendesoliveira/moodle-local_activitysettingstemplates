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
 * Form definition for creating personal activity settings templates.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitysettingstemplates\form;

use local_activitysettingstemplates\local\field_registry;

/**
 * Form used to create a personal preset from an existing activity.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_template_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition(): void {
        $mform = $this->_form;
        $cmid = (int)$this->_customdata['cmid'];
        $courseid = (int)$this->_customdata['courseid'];
        $moduletype = (string)$this->_customdata['moduletype'];
        $activityname = (string)$this->_customdata['activityname'];

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('header', 'presetdetails', get_string('presetdetails', 'local_activitysettingstemplates'));
        $mform->addElement(
            'static',
            'sourceactivity',
            get_string('sourceactivity', 'local_activitysettingstemplates'),
            format_string($activityname)
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

        $mform->addElement('static', 'fieldhelp', '', get_string('choosefieldshelp', 'local_activitysettingstemplates'));

        foreach (field_registry::get_definitions($moduletype) as $sectionkey => $section) {
            $mform->addElement(
                'header',
                'section_' . $sectionkey,
                field_registry::get_section_label($section)
            );

            foreach ($section['fields'] as $key => $definition) {
                $name = 'field_' . $key;
                $mform->addElement('advcheckbox', $name, '', field_registry::get_definition_label($definition));
                $mform->setDefault($name, 1);
            }
        }

        $this->add_action_buttons(true, get_string('savepreset', 'local_activitysettingstemplates'));
    }
}
