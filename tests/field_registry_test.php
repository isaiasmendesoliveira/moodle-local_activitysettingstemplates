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
 * Tests for the activity settings field registry.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitysettingstemplates;

use advanced_testcase;
use local_activitysettingstemplates\local\field_registry;

/**
 * Tests for the field registry.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class field_registry_test extends advanced_testcase {

    public function test_core_activity_modules_are_supported(): void {
        $this->assertTrue(field_registry::is_supported('quiz'));
        $this->assertTrue(field_registry::is_supported('assign'));
        $this->assertTrue(field_registry::is_supported('forum'));
        $this->assertTrue(field_registry::is_supported('page'));
        $this->assertFalse(field_registry::is_supported('definitely_not_a_module'));
    }

    public function test_editor_control_is_scoped_to_curated_quiz_field(): void {
        $control = field_registry::get_editor_control('quiz', 'grademethod');
        $this->assertSame('select', $control['type']);
        $this->assertArrayHasKey(1, $control['options']);
        $this->assertArrayHasKey(4, $control['options']);
    }

    public function test_editor_control_supports_common_fields(): void {
        $control = field_registry::get_editor_control('forum', 'groupmode');
        $this->assertSame('select', $control['type']);
        $this->assertCount(3, $control['options']);
    }

    public function test_all_supported_modules_receive_common_definitions(): void {
        $definitions = field_registry::get_flat_definitions('forum');
        $this->assertArrayHasKey('groupmode', $definitions);
        $this->assertArrayHasKey('completion', $definitions);
        $this->assertArrayHasKey('completionview', $definitions);
    }

    public function test_extract_config_uses_registry_whitelist(): void {
        $cm = (object)[
            'groupmode' => 1,
            'completion' => 2,
            'completionview' => 1,
        ];
        $instance = (object)[
            'attempts' => 1,
            'grademethod' => 1,
            'preferredbehaviour' => 'deferredfeedback',
        ];

        $config = field_registry::extract_config('quiz', $cm, $instance, [
            'attempts',
            'completion',
            'notallowed',
        ]);

        $this->assertSame(1, $config['attempts']);
        $this->assertSame(2, $config['completion']);
        $this->assertArrayNotHasKey('notallowed', $config);
    }
    public function test_quiz_registry_does_not_expose_sumgrades(): void {
        $definitions = field_registry::get_flat_definitions('quiz');
        foreach ($definitions as $definition) {
            $this->assertNotSame('sumgrades', $definition['formfield']);
        }
    }

    public function test_legacy_quiz_review_bitmasks_are_normalised(): void {
        $config = field_registry::normalise_stored_config('quiz', [
            'reviewcorrectness' => 0x10000 | 0x01000,
            'sumgrades' => 10,
        ]);

        $this->assertSame(1, $config['correctnessduring']);
        $this->assertSame(1, $config['correctnessimmediately']);
        $this->assertSame(0, $config['correctnessopen']);
        $this->assertSame(0, $config['correctnessclosed']);
        $this->assertArrayNotHasKey('reviewcorrectness', $config);
        $this->assertArrayNotHasKey('sumgrades', $config);
    }

    public function test_forum_registry_uses_curated_teacher_facing_fields(): void {
        $definitions = field_registry::get_flat_definitions('forum');
        $formfields = array_column($definitions, 'formfield');
        $this->assertContains('type', $formfields);
        $this->assertContains('displaywordcount', $formfields);
        $this->assertNotContains('assessed', $formfields);
        $this->assertNotContains('scale', $formfields);
    }

    public function test_lesson_registry_excludes_legacy_and_relational_fields(): void {
        $definitions = field_registry::get_flat_definitions('lesson');
        $formfields = array_column($definitions, 'formfield');
        foreach (['width', 'height', 'bgcolor', 'mediawidth', 'mediaheight', 'mediaclose', 'activitylink', 'password'] as $field) {
            $this->assertNotContains($field, $formfields);
        }
    }

    public function test_page_display_options_use_serialized_source(): void {
        $definitions = field_registry::get_flat_definitions('page');
        if (!isset($definitions['printintro'])) {
            $this->markTestSkipped('Page displayoptions are not available in this Moodle schema.');
        }
        $this->assertSame('serialized', $definitions['printintro']['source']);
        $this->assertSame('displayoptions', $definitions['printintro']['sourcefield']);
        $this->assertSame('printintro', $definitions['printintro']['serializedkey']);
    }

    public function test_core_normalisation_removes_old_automatic_fields(): void {
        $config = field_registry::normalise_stored_config('forum', [
            'displaywordcount' => 1,
            'assessed' => 1,
            'scale' => 5,
            'unknowninternalfield' => 99,
        ]);
        $this->assertArrayHasKey('displaywordcount', $config);
        $this->assertArrayNotHasKey('assessed', $config);
        $this->assertArrayNotHasKey('scale', $config);
        $this->assertArrayNotHasKey('unknowninternalfield', $config);
    }


}
