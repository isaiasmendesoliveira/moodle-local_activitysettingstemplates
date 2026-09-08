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
 * Registry and formatting helpers for reusable activity settings.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitysettingstemplates\local;

/**
 * Registry and automatic discovery of reusable activity settings.
 *
 * @package   local_activitysettingstemplates
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_registry {

    private const REVIEW_PERIODS = [
        'during' => 0x10000,
        'immediately' => 0x01000,
        'open' => 0x00100,
        'closed' => 0x00010,
    ];

    private const REVIEW_FIELDS = [
        'attempt' => 'reviewattempt',
        'correctness' => 'reviewcorrectness',
        'maxmarks' => 'reviewmaxmarks',
        'marks' => 'reviewmarks',
        'specificfeedback' => 'reviewspecificfeedback',
        'generalfeedback' => 'reviewgeneralfeedback',
        'rightanswer' => 'reviewrightanswer',
        'overallfeedback' => 'reviewoverallfeedback',
    ];

    private const EXCLUDED_INSTANCE_FIELDS = [
        'id', 'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified',
        'revision', 'idnumber', 'grade', 'gradepass', 'gradecat', 'gradecategory', 'groupingid', 'templateid',
        'scaleid', 'completionexpected', 'availability', 'availabilityconditionsjson',
        'content', 'contentformat', 'summary', 'summaryformat', 'externalurl', 'reference',
        'source', 'packagefile', 'packagehash', 'password', 'secret', 'token', 'apikey',
        'sumgrades',
    ];

    private const EXCLUDED_DATE_PATTERNS = [
        '/^time/i', '/date$/i', '/deadline/i', '/duedate/i', '/cutoff/i',
        '/availablefrom/i', '/availableuntil/i', '/open$/i', '/close$/i',
        '/start$/i', '/end$/i', '/starttime/i', '/endtime/i',
    ];


    /**
     * Core activity/resource modules for which this plugin provides an explicit,
     * teacher-facing allow-list. Automatic database discovery is never used for
     * these modules because their tables also contain calculated, legacy and
     * internal fields that are not meaningful settings in the native form.
     */
    private const CURATED_CORE_MODULES = [
        'assign', 'bigbluebuttonbn', 'book', 'chat', 'choice', 'data', 'feedback',
        'folder', 'forum', 'glossary', 'h5pactivity', 'imscp', 'label', 'lesson',
        'lti', 'page', 'quiz', 'resource', 'scorm', 'survey', 'url', 'wiki', 'workshop',
    ];

    public static function get_supported_modules(): array {
        return array_keys(\core_component::get_plugin_list('mod'));
    }

    public static function is_supported(string $moduletype): bool {
        return $moduletype !== '' && array_key_exists($moduletype, \core_component::get_plugin_list('mod'));
    }

    public static function get_module_name(string $moduletype): string {
        $component = 'mod_' . $moduletype;
        $stringman = get_string_manager();
        if ($stringman->string_exists('modulename', $component)) {
            return get_string('modulename', $component);
        }
        return ucfirst(str_replace('_', ' ', $moduletype));
    }

    public static function get_definition_label(array $definition): string {
        if (!empty($definition['labeltext'])) {
            return (string)$definition['labeltext'];
        }
        if (!empty($definition['label'])) {
            return get_string($definition['label'], 'local_activitysettingstemplates');
        }
        return self::humanise_field_name((string)($definition['formfield'] ?? ''));
    }

    /**
     * Get a context-rich label for previews and management summaries.
     *
     * @param array $definition
     * @return string
     */
    public static function get_definition_context_label(array $definition): string {
        $label = self::get_definition_label($definition);
        if (empty($definition['reviewoption'])) {
            return $label;
        }
        $formfield = (string)($definition['formfield'] ?? '');
        foreach (array_keys(self::REVIEW_PERIODS) as $period) {
            if (str_ends_with($formfield, $period)) {
                return $label . ' — ' . get_string('reviewperiod' . $period, 'local_activitysettingstemplates');
            }
        }
        return $label;
    }

    public static function get_section_label(array $section): string {
        if (!empty($section['labeltext'])) {
            return (string)$section['labeltext'];
        }
        if (!empty($section['label'])) {
            return get_string($section['label'], 'local_activitysettingstemplates');
        }
        return get_string('sectiondetected', 'local_activitysettingstemplates');
    }

    /**
     * Get definitions grouped by UI section.
     */
    public static function get_definitions(string $moduletype): array {
        static $cache = [];
        if (isset($cache[$moduletype])) {
            return $cache[$moduletype];
        }
        if (!self::is_supported($moduletype)) {
            return [];
        }

        if ($moduletype === 'quiz') {
            $sections = self::filter_missing_instance_fields('quiz', self::get_quiz_definitions());
        } else if (in_array($moduletype, self::CURATED_CORE_MODULES, true)) {
            $sections = self::filter_missing_instance_fields($moduletype,
                self::get_core_curated_definitions($moduletype));
        } else {
            // Third-party modules remain supported, but the fallback is deliberately
            // conservative: only scalar fields with a native label and a clearly
            // reusable boolean/numeric semantic are exposed.
            $detected = self::get_detected_instance_definitions($moduletype, []);
            $sections = empty($detected) ? [] : [
                'detected' => ['label' => 'sectioncompatible', 'fields' => $detected],
            ];
        }

        $commonfields = self::get_common_definitions($moduletype);
        if (!empty($commonfields)) {
            $sections['common'] = [
                'label' => 'sectioncommon',
                'fields' => $commonfields,
            ];
        }

        $cache[$moduletype] = $sections;
        return $sections;
    }

    /**
     * Remove curated instance settings that do not exist in the installed module schema.
     * This keeps the plugin compatible across supported Moodle releases.
     *
     * @param string $moduletype
     * @param array $sections
     * @return array
     */
    private static function filter_missing_instance_fields(string $moduletype, array $sections): array {
        global $DB;
        try {
            $columns = $DB->get_columns($moduletype);
        } catch (\Throwable $e) {
            return $sections;
        }
        foreach ($sections as $sectionkey => $section) {
            foreach ($section['fields'] as $fieldkey => $definition) {
                $sourcekind = (string)($definition['source'] ?? '');
                if (!in_array($sourcekind, ['instance', 'serialized'], true)) {
                    continue;
                }
                $sourcefield = (string)($definition['sourcefield'] ?? '');
                if ($sourcefield !== '' && !array_key_exists($sourcefield, $columns)) {
                    unset($sections[$sectionkey]['fields'][$fieldkey]);
                }
            }
            if (empty($sections[$sectionkey]['fields'])) {
                unset($sections[$sectionkey]);
            }
        }
        return $sections;
    }

    /**
     * Curated Quiz settings. Raw database-only fields are deliberately not auto-discovered.
     */
    private static function get_quiz_definitions(): array {
        $sections = [
            'quizattempts' => [
                'label' => 'sectionquizattempts',
                'fields' => [
                    'attempts' => self::instance_definition('attempts', 'fieldattempts'),
                    'grademethod' => self::instance_definition('grademethod', 'fieldgrademethod'),
                    'attemptonlast' => self::instance_definition('attemptonlast', 'fieldattemptonlast'),
                    'delay1' => self::instance_definition('delay1', 'fielddelay1'),
                    'delay2' => self::instance_definition('delay2', 'fielddelay2'),
                ],
            ],
            'quizlayout' => [
                'label' => 'sectionquizlayout',
                'fields' => [
                    'questionsperpage' => self::instance_definition('questionsperpage', 'fieldquestionsperpage'),
                    'navmethod' => self::instance_definition('navmethod', 'fieldnavmethod'),
                ],
            ],
            'quizquestionbehaviour' => [
                'label' => 'sectionquizquestionbehaviour',
                'fields' => [
                    'shuffleanswers' => self::instance_definition('shuffleanswers', 'fieldshuffleanswers'),
                    'preferredbehaviour' => self::instance_definition('preferredbehaviour', 'fieldpreferredbehaviour'),
                    'canredoquestions' => self::instance_definition('canredoquestions', 'fieldcanredoquestions'),
                ],
            ],
            'quizsubmission' => [
                'label' => 'sectionquizsubmission',
                'fields' => [
                    'overduehandling' => self::instance_definition('overduehandling', 'fieldoverduehandling'),
                    'graceperiod' => self::instance_definition('graceperiod', 'fieldgraceperiod'),
                    'precreateattempts' => self::instance_definition('precreateattempts', 'fieldprecreateattempts'),
                ],
            ],
        ];

        $reviewlabels = [
            'attempt' => 'fieldreviewattempt',
            'correctness' => 'fieldreviewcorrectness',
            'maxmarks' => 'fieldreviewmaxmarks',
            'marks' => 'fieldreviewmarks',
            'specificfeedback' => 'fieldreviewspecificfeedback',
            'generalfeedback' => 'fieldreviewgeneralfeedback',
            'rightanswer' => 'fieldreviewrightanswer',
            'overallfeedback' => 'fieldreviewoverallfeedback',
        ];

        $periodsections = [
            'during' => 'sectionreviewduring',
            'immediately' => 'sectionreviewimmediately',
            'open' => 'sectionreviewopen',
            'closed' => 'sectionreviewclosed',
        ];
        foreach ($periodsections as $period => $sectionlabel) {
            $fields = [];
            foreach (self::REVIEW_FIELDS as $short => $sourcefield) {
                // Moodle fixes these two values in the native form; do not offer settings that cannot be changed.
                if (($period === 'during' && $short === 'attempt') || ($period === 'during' && $short === 'overallfeedback')) {
                    continue;
                }
                $key = 'review_' . $short . '_' . $period;
                $fields[$key] = [
                    'source' => 'instance',
                    'sourcefield' => $sourcefield,
                    'formfield' => $short . $period,
                    'label' => $reviewlabels[$short],
                    'bitmask' => self::REVIEW_PERIODS[$period],
                    'reviewoption' => true,
                ];
            }
            $sections['review_' . $period] = ['label' => $sectionlabel, 'fields' => $fields];
        }

        $sections['quizappearance'] = [
            'label' => 'sectionquizappearance',
            'fields' => [
                'showuserpicture' => self::instance_definition('showuserpicture', 'fieldshowuserpicture'),
                'decimalpoints' => self::instance_definition('decimalpoints', 'fielddecimalpoints'),
                'questiondecimalpoints' => self::instance_definition('questiondecimalpoints', 'fieldquestiondecimalpoints'),
                'showblocks' => self::instance_definition('showblocks', 'fieldshowblocks'),
            ],
        ];
        $sections['quizrestrictions'] = [
            'label' => 'sectionquizrestrictions',
            'fields' => [
                'subnet' => self::instance_definition('subnet', 'fieldsubnet'),
                'browsersecurity' => self::instance_definition('browsersecurity', 'fieldbrowsersecurity'),
                'allowofflineattempts' => self::instance_definition('allowofflineattempts', 'fieldallowofflineattempts'),
            ],
        ];
        $sections['quizcompletion'] = [
            'label' => 'sectionquizcompletion',
            'fields' => [
                'completionattemptsexhausted' => self::instance_definition('completionattemptsexhausted', 'fieldcompletionattemptsexhausted'),
                'completionminattempts' => self::instance_definition('completionminattempts', 'fieldcompletionminattempts'),
            ],
        ];
        return $sections;
    }


    /**
     * Curated definitions for core Moodle modules other than Quiz.
     *
     * @param string $moduletype
     * @return array
     */
    private static function get_core_curated_definitions(string $moduletype): array {
        switch ($moduletype) {
            case 'assign':
                return [
                    'assignsubmission' => ['label' => 'sectionassignsubmission', 'fields' => [
                        'submissiondrafts' => self::bool_instance('submissiondrafts', 'fieldsubmissiondrafts'),
                        'requiresubmissionstatement' => self::bool_instance('requiresubmissionstatement', 'fieldrequiresubmissionstatement'),
                        'alwaysshowdescription' => self::bool_instance('alwaysshowdescription', 'fieldalwaysshowdescription'),
                        'timelimit' => self::duration_instance('timelimit', 'fieldtimelimit'),
                    ]],
                    'assigngroups' => ['label' => 'sectionassigngroups', 'fields' => [
                        'teamsubmission' => self::bool_instance('teamsubmission', 'fieldteamsubmission'),
                        'preventsubmissionnotingroup' => self::bool_instance('preventsubmissionnotingroup', 'fieldpreventsubmissionnotingroup'),
                        'requireallteammemberssubmit' => self::bool_instance('requireallteammemberssubmit', 'fieldrequireallteammemberssubmit'),
                    ]],
                    'assignattempts' => ['label' => 'sectionassignattempts', 'fields' => [
                        'attemptreopenmethod' => self::select_instance('attemptreopenmethod', 'fieldattemptreopenmethod', [
                            'none' => get_string('valueattemptreopennone', 'local_activitysettingstemplates'),
                            'manual' => get_string('valueattemptreopenmanual', 'local_activitysettingstemplates'),
                            'untilpass' => get_string('valueattemptreopenuntilpass', 'local_activitysettingstemplates'),
                        ], 'none', PARAM_ALPHA),
                        'maxattempts' => self::select_instance('maxattempts', 'fieldmaxattempts', self::number_options(1, 30, [
                            -1 => get_string('valueunlimited', 'local_activitysettingstemplates'),
                        ]), -1),
                    ]],
                    'assignmarking' => ['label' => 'sectionassignmarking', 'fields' => [
                        'blindmarking' => self::bool_instance('blindmarking', 'fieldblindmarking'),
                        'hidegrader' => self::bool_instance('hidegrader', 'fieldhidegrader'),
                        'markingworkflow' => self::bool_instance('markingworkflow', 'fieldmarkingworkflow'),
                        'markingallocation' => self::bool_instance('markingallocation', 'fieldmarkingallocation'),
                    ]],
                    'assignnotifications' => ['label' => 'sectionassignnotifications', 'fields' => [
                        'sendnotifications' => self::bool_instance('sendnotifications', 'fieldsendnotifications'),
                        'sendlatenotifications' => self::bool_instance('sendlatenotifications', 'fieldsendlatenotifications'),
                        'sendstudentnotifications' => self::bool_instance('sendstudentnotifications', 'fieldsendstudentnotifications'),
                    ]],
                ];

            case 'forum':
                return [
                    'forumgeneral' => ['label' => 'sectionforumgeneral', 'fields' => [
                        'type' => self::select_instance('type', 'fieldforumtype', self::forum_type_options(), 'general', PARAM_ALPHANUMEXT),
                        'showimmediately' => self::bool_instance('showimmediately', 'fieldshowimmediately'),
                    ]],
                    'forumattachments' => ['label' => 'sectionforumattachments', 'fields' => [
                        'maxattachments' => self::select_instance('maxattachments', 'fieldmaxattachments', [
                            0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9', 10 => '10', 20 => '20', 50 => '50', 100 => '100',
                        ], 1),
                        'displaywordcount' => self::bool_instance('displaywordcount', 'fielddisplaywordcount'),
                    ]],
                    'forumsubscription' => ['label' => 'sectionforumsubscription', 'fields' => [
                        'forcesubscribe' => self::select_instance('forcesubscribe', 'fieldforcesubscribe', self::forum_subscription_options(), 0),
                        'trackingtype' => self::select_instance('trackingtype', 'fieldtrackingtype', self::forum_tracking_options(), 1),
                    ]],
                    'forumrss' => ['label' => 'sectionforumrss', 'fields' => [
                        'rsstype' => self::select_instance('rsstype', 'fieldrsstype', [
                            0 => get_string('none'),
                            1 => get_string('valuerssdiscussions', 'local_activitysettingstemplates'),
                            2 => get_string('valuerssposts', 'local_activitysettingstemplates'),
                        ], 0),
                        'rssarticles' => self::select_instance('rssarticles', 'fieldrssarticles', self::number_options(0, 50), 0),
                    ]],
                    'forumlocking' => ['label' => 'sectionforumlocking', 'fields' => [
                        'lockdiscussionafter' => self::duration_instance('lockdiscussionafter', 'fieldlockdiscussionafter'),
                        'blockperiod' => self::duration_instance('blockperiod', 'fieldblockperiod'),
                        'blockafter' => self::number_instance('blockafter', 'fieldblockafter'),
                        'warnafter' => self::number_instance('warnafter', 'fieldwarnafter'),
                    ]],
                    'forumcompletion' => ['label' => 'sectionforumcompletion', 'fields' => [
                        'completiondiscussions' => self::number_instance('completiondiscussions', 'fieldcompletiondiscussions'),
                        'completionreplies' => self::number_instance('completionreplies', 'fieldcompletionreplies'),
                        'completionposts' => self::number_instance('completionposts', 'fieldcompletionposts'),
                    ]],
                ];

            case 'choice':
                return [
                    'choiceoptions' => ['label' => 'sectionchoiceoptions', 'fields' => [
                        'display' => self::select_instance('display', 'fieldchoicedisplay', [
                            0 => get_string('valuehorizontal', 'local_activitysettingstemplates'),
                            1 => get_string('valuevertical', 'local_activitysettingstemplates'),
                        ], 0),
                        'allowupdate' => self::bool_instance('allowupdate', 'fieldallowupdate'),
                        'allowmultiple' => self::bool_instance('allowmultiple', 'fieldallowmultiple'),
                        'limitanswers' => self::bool_instance('limitanswers', 'fieldlimitanswers'),
                        'showavailable' => self::bool_instance('showavailable', 'fieldshowavailable'),
                        'showpreview' => self::bool_instance('showpreview', 'fieldshowpreview'),
                    ]],
                    'choiceresults' => ['label' => 'sectionchoiceresults', 'fields' => [
                        'showresults' => self::select_instance('showresults', 'fieldshowresults', [
                            0 => get_string('valueshowresultsnever', 'local_activitysettingstemplates'),
                            1 => get_string('valueshowresultsafteranswer', 'local_activitysettingstemplates'),
                            2 => get_string('valueshowresultsafterclose', 'local_activitysettingstemplates'),
                            3 => get_string('valueshowresultsalways', 'local_activitysettingstemplates'),
                        ], 0),
                        'publish' => self::select_instance('publish', 'fieldpublishresults', [
                            0 => get_string('valueanonymousresults', 'local_activitysettingstemplates'),
                            1 => get_string('valuefullresults', 'local_activitysettingstemplates'),
                        ], 0),
                        'showunanswered' => self::bool_instance('showunanswered', 'fieldshowunanswered'),
                        'includeinactive' => self::bool_instance('includeinactive', 'fieldincludeinactive'),
                    ]],
                    'choicecompletion' => ['label' => 'sectionchoicecompletion', 'fields' => [
                        'completionsubmit' => self::bool_instance('completionsubmit', 'fieldcompletionsubmit'),
                    ]],
                ];

            case 'data':
                return [
                    'dataentries' => ['label' => 'sectiondataentries', 'fields' => [
                        'approval' => self::bool_instance('approval', 'fieldapproval'),
                        'manageapproved' => self::bool_instance('manageapproved', 'fieldmanageapproved'),
                        'comments' => self::bool_instance('comments', 'fieldcomments'),
                        'requiredentries' => self::number_instance('requiredentries', 'fieldrequiredentries'),
                        'requiredentriestoview' => self::number_instance('requiredentriestoview', 'fieldrequiredentriestoview'),
                        'maxentries' => self::number_instance('maxentries', 'fieldmaxentries'),
                    ]],
                    'datarss' => ['label' => 'sectiondatarss', 'fields' => [
                        'rssarticles' => self::select_instance('rssarticles', 'fieldrssarticles', self::number_options(0, 50), 0),
                    ]],
                    'datacompletion' => ['label' => 'sectiondatacompletion', 'fields' => [
                        'completionentries' => self::number_instance('completionentries', 'fieldcompletionentries'),
                    ]],
                ];

            case 'feedback':
                return [
                    'feedbacksettings' => ['label' => 'sectionfeedbacksettings', 'fields' => [
                        'anonymous' => self::select_instance('anonymous', 'fieldanonymous', [
                            1 => get_string('valueanonymous', 'local_activitysettingstemplates'),
                            2 => get_string('valuenonanonym', 'local_activitysettingstemplates'),
                        ], 1),
                        'multiple_submit' => self::bool_instance('multiple_submit', 'fieldmultiplesubmit'),
                        'email_notification' => self::bool_instance('email_notification', 'fieldemailnotification'),
                        'autonumbering' => self::bool_instance('autonumbering', 'fieldautonumbering'),
                        'publish_stats' => self::bool_instance('publish_stats', 'fieldpublishstats'),
                    ]],
                ];

            case 'folder':
                return [
                    'folderdisplay' => ['label' => 'sectionfolderdisplay', 'fields' => [
                        'display' => self::select_instance('display', 'fielddisplay', [
                            0 => get_string('valuedisplayseparate', 'local_activitysettingstemplates'),
                            1 => get_string('valuedisplayinline', 'local_activitysettingstemplates'),
                        ], 0),
                        'showexpanded' => self::bool_instance('showexpanded', 'fieldshowexpanded'),
                        'showdownloadfolder' => self::bool_instance('showdownloadfolder', 'fieldshowdownloadfolder'),
                        'forcedownload' => self::bool_instance('forcedownload', 'fieldforcedownload'),
                    ]],
                ];

            case 'book':
                return [
                    'bookappearance' => ['label' => 'sectionbookappearance', 'fields' => [
                        'numbering' => self::select_instance('numbering', 'fieldbooknumbering', [
                            0 => get_string('valuebooknumberingnone', 'local_activitysettingstemplates'),
                            1 => get_string('valuebooknumberingnumbers', 'local_activitysettingstemplates'),
                            2 => get_string('valuebooknumberingbullets', 'local_activitysettingstemplates'),
                            3 => get_string('valuebooknumberingindented', 'local_activitysettingstemplates'),
                        ], 1),
                        'customtitles' => self::bool_instance('customtitles', 'fieldcustomtitles'),
                    ]],
                ];

            case 'glossary':
                return [
                    'glossaryentries' => ['label' => 'sectionglossaryentries', 'fields' => [
                        'defaultapproval' => self::bool_instance('defaultapproval', 'fielddefaultapproval'),
                        'editalways' => self::bool_instance('editalways', 'fieldeditalways'),
                        'allowduplicatedentries' => self::bool_instance('allowduplicatedentries', 'fieldallowduplicates'),
                        'allowcomments' => self::bool_instance('allowcomments', 'fieldallowcomments'),
                        'usedynalink' => self::bool_instance('usedynalink', 'fieldusedynalink'),
                        'entbypage' => self::number_instance('entbypage', 'fieldentriesperpage', 10),
                    ]],
                    'glossarybrowse' => ['label' => 'sectionglossarybrowse', 'fields' => [
                        'showalphabet' => self::bool_instance('showalphabet', 'fieldshowalphabet'),
                        'showall' => self::bool_instance('showall', 'fieldshowall'),
                        'showspecial' => self::bool_instance('showspecial', 'fieldshowspecial'),
                        'allowprintview' => self::bool_instance('allowprintview', 'fieldallowprintview'),
                    ]],
                    'glossaryrss' => ['label' => 'sectionglossaryrss', 'fields' => [
                        'rsstype' => self::select_instance('rsstype', 'fieldrsstype', [
                            0 => get_string('none'),
                            1 => get_string('valuerssconcepts', 'local_activitysettingstemplates'),
                            2 => get_string('valuerssauthor', 'local_activitysettingstemplates'),
                        ], 0),
                        'rssarticles' => self::select_instance('rssarticles', 'fieldrssarticles', self::number_options(0, 50), 0),
                    ]],
                    'glossarycompletion' => ['label' => 'sectionglossarycompletion', 'fields' => [
                        'completionentries' => self::number_instance('completionentries', 'fieldcompletionentries'),
                    ]],
                ];

            case 'h5pactivity':
                return [
                    'h5pattempts' => ['label' => 'sectionh5pattempts', 'fields' => [
                        'enabletracking' => self::bool_instance('enabletracking', 'fieldenabletracking'),
                        'grademethod' => self::select_instance('grademethod', 'fieldh5pgrademethod', self::h5p_grademethod_options(), 1),
                        'reviewmode' => self::select_instance('reviewmode', 'fieldh5previewmode', self::h5p_reviewmode_options(), 1),
                    ]],
                ];

            case 'imscp':
                return [
                    'imscpdisplay' => ['label' => 'sectionimscpdisplay', 'fields' => [
                        'keepold' => self::select_instance('keepold', 'fieldkeepold', [
                            -1 => get_string('valuekeepall', 'local_activitysettingstemplates'),
                            0 => get_string('no'), 1 => '1', 2 => '2', 5 => '5', 10 => '10', 20 => '20',
                        ], -1),
                    ]],
                ];

            case 'lesson':
                return [
                    'lessonappearance' => ['label' => 'sectionlessonappearance', 'fields' => [
                        'progressbar' => self::bool_instance('progressbar', 'fieldprogressbar'),
                        'ongoing' => self::bool_instance('ongoing', 'fieldongoing'),
                        'displayleft' => self::bool_instance('displayleft', 'fielddisplayleft'),
                        'displayleftif' => self::select_instance('displayleftif', 'fielddisplayleftif', self::percentage_options(), 0),
                        'slideshow' => self::bool_instance('slideshow', 'fieldslideshow'),
                        'maxanswers' => self::select_instance('maxanswers', 'fieldmaxanswers', self::number_options(2, 20), 4),
                        'feedback' => self::bool_instance('feedback', 'fieldlessonfeedback'),
                    ]],
                    'lessonflow' => ['label' => 'sectionlessonflow', 'fields' => [
                        'modattempts' => self::bool_instance('modattempts', 'fieldmodattempts'),
                        'review' => self::bool_instance('review', 'fieldlessonreview'),
                        'maxattempts' => self::select_instance('maxattempts', 'fieldlessonmaxattempts', self::number_options(1, 10, [0 => get_string('valueunlimited', 'local_activitysettingstemplates')]), 0),
                        'nextpagedefault' => self::select_instance('nextpagedefault', 'fieldnextpagedefault', self::lesson_nextpage_options(), 0),
                        'maxpages' => self::select_instance('maxpages', 'fieldmaxpages', self::number_options(0, 100), 0),
                    ]],
                    'lessongrade' => ['label' => 'sectionlessongrade', 'fields' => [
                        'practice' => self::bool_instance('practice', 'fieldpractice'),
                        'custom' => self::bool_instance('custom', 'fieldcustomscoring'),
                        'retake' => self::bool_instance('retake', 'fieldretake'),
                        'usemaxgrade' => self::select_instance('usemaxgrade', 'fieldusemaxgrade', [
                            0 => get_string('valueusemean', 'local_activitysettingstemplates'),
                            1 => get_string('valueusemaximum', 'local_activitysettingstemplates'),
                        ], 0),
                        'minquestions' => self::select_instance('minquestions', 'fieldminquestions', self::number_options(0, 100), 0),
                    ]],
                    'lessonlimits' => ['label' => 'sectionlessonlimits', 'fields' => [
                        'timelimit' => self::duration_instance('timelimit', 'fieldtimelimit'),
                    ]],
                ];

            case 'page':
                return [
                    'pagedisplay' => ['label' => 'sectionpagedisplay', 'fields' => [
                        'display' => self::select_instance('display', 'fielddisplay', self::resource_display_options(), 0),
                        'printintro' => self::serialized_definition('displayoptions', 'printintro', 'printintro', 'fieldprintintro', self::bool_control()),
                        'printlastmodified' => self::serialized_definition('displayoptions', 'printlastmodified', 'printlastmodified', 'fieldprintlastmodified', self::bool_control()),
                        'popupwidth' => self::serialized_definition('displayoptions', 'popupwidth', 'popupwidth', 'fieldpopupwidth', self::number_control(620)),
                        'popupheight' => self::serialized_definition('displayoptions', 'popupheight', 'popupheight', 'fieldpopupheight', self::number_control(450)),
                    ]],
                ];

            case 'resource':
                return [
                    'resourcedisplay' => ['label' => 'sectionresourcedisplay', 'fields' => [
                        'display' => self::select_instance('display', 'fielddisplay', self::resource_display_options(), 0),
                        'showsize' => self::serialized_definition('displayoptions', 'showsize', 'showsize', 'fieldshowsize', self::bool_control()),
                        'showtype' => self::serialized_definition('displayoptions', 'showtype', 'showtype', 'fieldshowtype', self::bool_control()),
                        'showdate' => self::serialized_definition('displayoptions', 'showdate', 'showdate', 'fieldshowdate', self::bool_control()),
                        'printintro' => self::serialized_definition('displayoptions', 'printintro', 'printintro', 'fieldprintintro', self::bool_control()),
                        'popupwidth' => self::serialized_definition('displayoptions', 'popupwidth', 'popupwidth', 'fieldpopupwidth', self::number_control(620)),
                        'popupheight' => self::serialized_definition('displayoptions', 'popupheight', 'popupheight', 'fieldpopupheight', self::number_control(450)),
                        'filterfiles' => self::select_instance('filterfiles', 'fieldfilterfiles', [
                            0 => get_string('valuefilternone', 'local_activitysettingstemplates'),
                            1 => get_string('valuefilterhtml', 'local_activitysettingstemplates'),
                            2 => get_string('valuefilterall', 'local_activitysettingstemplates'),
                        ], 0),
                    ]],
                ];

            case 'url':
                return [
                    'urldisplay' => ['label' => 'sectionurldisplay', 'fields' => [
                        'display' => self::select_instance('display', 'fielddisplay', self::resource_display_options(), 0),
                        'printintro' => self::serialized_definition('displayoptions', 'printintro', 'printintro', 'fieldprintintro', self::bool_control()),
                        'popupwidth' => self::serialized_definition('displayoptions', 'popupwidth', 'popupwidth', 'fieldpopupwidth', self::number_control(620)),
                        'popupheight' => self::serialized_definition('displayoptions', 'popupheight', 'popupheight', 'fieldpopupheight', self::number_control(450)),
                    ]],
                ];

            case 'wiki':
                return [
                    'wikisettings' => ['label' => 'sectionwikisettings', 'fields' => [
                        'wikimode' => self::select_instance('wikimode', 'fieldwikimode', [
                            'collaborative' => get_string('valuewikicollaborative', 'local_activitysettingstemplates'),
                            'individual' => get_string('valuewikiindividual', 'local_activitysettingstemplates'),
                        ], 'collaborative', PARAM_ALPHA),
                        'defaultformat' => self::select_instance('defaultformat', 'fielddefaultformat', self::wiki_format_options(), 'html', PARAM_ALPHANUMEXT),
                        'forceformat' => self::bool_instance('forceformat', 'fieldforceformat'),
                    ]],
                ];

            case 'workshop':
                return [
                    'workshopgrading' => ['label' => 'sectionworkshopgrading', 'fields' => [
                        'strategy' => self::select_instance('strategy', 'fieldworkshopstrategy', self::workshop_strategy_options(), 'accumulative', PARAM_ALPHANUMEXT),
                        'gradedecimals' => self::select_instance('gradedecimals', 'fieldgradedecimals', self::number_options(0, 5), 2),
                    ]],
                    'workshopsubmission' => ['label' => 'sectionworkshopsubmission', 'fields' => [
                        'submissiontypetextavailable' => self::bool_instance('submissiontypetextavailable', 'fieldsubmissiontextavailable'),
                        'submissiontypetextrequired' => self::bool_instance('submissiontypetextrequired', 'fieldsubmissiontextrequired'),
                        'submissiontypefileavailable' => self::bool_instance('submissiontypefileavailable', 'fieldsubmissionfileavailable'),
                        'submissiontypefilerequired' => self::bool_instance('submissiontypefilerequired', 'fieldsubmissionfilerequired'),
                        'nattachments' => self::select_instance('nattachments', 'fieldnattachments', self::number_options(0, 7), 1),
                        'latesubmissions' => self::bool_instance('latesubmissions', 'fieldlatesubmissions'),
                    ]],
                    'workshopassessment' => ['label' => 'sectionworkshopassessment', 'fields' => [
                        'useselfassessment' => self::bool_instance('useselfassessment', 'fielduseselfassessment'),
                    ]],
                    'workshopfeedback' => ['label' => 'sectionworkshopfeedback', 'fields' => [
                        'overallfeedbackmode' => self::select_instance('overallfeedbackmode', 'fieldoverallfeedbackmode', [
                            0 => get_string('valuefeedbackdisabled', 'local_activitysettingstemplates'),
                            1 => get_string('valuefeedbackoptional', 'local_activitysettingstemplates'),
                            2 => get_string('valuefeedbackrequired', 'local_activitysettingstemplates'),
                        ], 1),
                        'overallfeedbackfiles' => self::select_instance('overallfeedbackfiles', 'fieldoverallfeedbackfiles', self::number_options(0, 7), 0),
                    ]],
                    'workshopexamples' => ['label' => 'sectionworkshopexamples', 'fields' => [
                        'useexamples' => self::bool_instance('useexamples', 'fielduseexamples'),
                        'examplesmode' => self::select_instance('examplesmode', 'fieldexamplesmode', self::workshop_example_options(), 0),
                    ]],
                ];

            case 'scorm':
                return [
                    'scormpackage' => ['label' => 'sectionscormpackage', 'fields' => [
                        'updatefreq' => self::select_instance('updatefreq', 'fieldupdatefreq', self::scorm_options('scorm_get_updatefreq_array', [
                            0 => get_string('valuenever', 'local_activitysettingstemplates'),
                            1 => get_string('valueeveryday', 'local_activitysettingstemplates'),
                            2 => get_string('valueeverytimeused', 'local_activitysettingstemplates'),
                        ]), 0),
                    ]],
                    'scormappearance' => ['label' => 'sectionscormappearance', 'fields' => [
                        'popup' => self::select_instance('popup', 'fieldscormpopup', self::scorm_options('scorm_get_popup_display_array', [0 => get_string('no'), 1 => get_string('yes')]), 0),
                        'skipview' => self::select_instance('skipview', 'fieldskipview', self::scorm_options('scorm_get_skip_view_array', [
                            0 => get_string('valuenever', 'local_activitysettingstemplates'),
                            1 => get_string('valuefirstaccess', 'local_activitysettingstemplates'),
                            2 => get_string('valuealways', 'local_activitysettingstemplates'),
                        ]), 0),
                        'hidebrowse' => self::bool_instance('hidebrowse', 'fieldhidebrowse'),
                        'displaycoursestructure' => self::bool_instance('displaycoursestructure', 'fielddisplaycoursestructure'),
                        'hidetoc' => self::select_instance('hidetoc', 'fieldhidetoc', self::scorm_options('scorm_get_hidetoc_array', [
                            0 => get_string('valuetocside', 'local_activitysettingstemplates'),
                            1 => get_string('valuetocdrop', 'local_activitysettingstemplates'),
                            2 => get_string('valuetocentry', 'local_activitysettingstemplates'),
                            3 => get_string('valuetocdisabled', 'local_activitysettingstemplates'),
                        ]), 0),
                        'displayattemptstatus' => self::select_instance('displayattemptstatus', 'fielddisplayattemptstatus', self::scorm_options('scorm_get_attemptstatus_array', [0 => get_string('no'), 1 => get_string('yes')]), 0),
                    ]],
                    'scormgrading' => ['label' => 'sectionscormgrading', 'fields' => [
                        'grademethod' => self::select_instance('grademethod', 'fieldscormgrademethod', self::scorm_options('scorm_get_grade_method_array', self::scorm_grademethod_options()), 0),
                        'maxgrade' => self::number_instance('maxgrade', 'fieldmaxgrade', 100),
                    ]],
                    'scormattempts' => ['label' => 'sectionscormattempts', 'fields' => [
                        'maxattempt' => self::select_instance('maxattempt', 'fieldmaxattempt', self::scorm_options('scorm_get_attempts_array', self::number_options(1, 6, [0 => get_string('valueunlimited', 'local_activitysettingstemplates')])), 1),
                        'whatgrade' => self::select_instance('whatgrade', 'fieldwhatgrade', self::scorm_options('scorm_get_what_grade_array', [
                            0 => get_string('valuehighestattempt', 'local_activitysettingstemplates'),
                            1 => get_string('valueaverageattempt', 'local_activitysettingstemplates'),
                            2 => get_string('valuefirstattempt', 'local_activitysettingstemplates'),
                            3 => get_string('valuelastattempt', 'local_activitysettingstemplates'),
                        ]), 0),
                        'forcenewattempt' => self::bool_instance('forcenewattempt', 'fieldforcenewattempt'),
                        'lastattemptlock' => self::bool_instance('lastattemptlock', 'fieldlastattemptlock'),
                    ]],
                    'scormcompatibility' => ['label' => 'sectionscormcompatibility', 'fields' => [
                        'forcecompleted' => self::bool_instance('forcecompleted', 'fieldforcecompleted'),
                        'auto' => self::bool_instance('auto', 'fieldautocontinue'),
                        'autocommit' => self::bool_instance('autocommit', 'fieldautocommit'),
                        'masteryoverride' => self::bool_instance('masteryoverride', 'fieldmasteryoverride'),
                    ]],
                ];

            // These core modules are intentionally conservative. Their useful
            // module-specific data is primarily content, relational configuration,
            // credentials or a complex sub-structure. Common Moodle settings remain
            // available through get_common_definitions().
            case 'bigbluebuttonbn':
            case 'chat':
            case 'label':
            case 'lti':
            case 'survey':
            default:
                return [];
        }
    }

    /**
     * Common course-module settings that are safe to reuse.
     */
    private static function get_common_definitions(string $moduletype): array {
        $fields = [];
        $supportsgroups = true;
        $tracksviews = true;
        try {
            if (function_exists('plugin_supports') && defined('FEATURE_GROUPS')) {
                $supportsgroups = (bool)plugin_supports('mod', $moduletype, FEATURE_GROUPS, false);
            }
            if (function_exists('plugin_supports') && defined('FEATURE_COMPLETION_TRACKS_VIEWS')) {
                $tracksviews = (bool)plugin_supports('mod', $moduletype, FEATURE_COMPLETION_TRACKS_VIEWS, false);
            }
        } catch (\Throwable $e) {
            // Keep conservative defaults; the native form will still reject absent fields.
        }
        if ($supportsgroups) {
            $fields['groupmode'] = [
                'source' => 'cm', 'sourcefield' => 'groupmode', 'formfield' => 'groupmode',
                'label' => 'fieldgroupmode', 'control' => self::select_control([
                    0 => get_string('valuegroupmode0', 'local_activitysettingstemplates'),
                    1 => get_string('valuegroupmode1', 'local_activitysettingstemplates'),
                    2 => get_string('valuegroupmode2', 'local_activitysettingstemplates'),
                ], 0),
            ];
        }
        $fields['completion'] = [
            'source' => 'cm', 'sourcefield' => 'completion', 'formfield' => 'completion',
            'label' => 'fieldcompletion', 'control' => self::select_control([
                0 => get_string('valuecompletion0', 'local_activitysettingstemplates'),
                1 => get_string('valuecompletion1', 'local_activitysettingstemplates'),
                2 => get_string('valuecompletion2', 'local_activitysettingstemplates'),
            ], 0),
        ];
        if ($tracksviews) {
            $fields['completionview'] = [
                'source' => 'cm', 'sourcefield' => 'completionview', 'formfield' => 'completionview',
                'label' => 'fieldcompletionview', 'control' => self::bool_control(),
            ];
        }
        return $fields;
    }

    private static function bool_control(int $default = 0): array {
        return ['type' => 'select', 'options' => [0 => get_string('no'), 1 => get_string('yes')],
            'param' => PARAM_INT, 'default' => $default];
    }

    private static function number_control($default = 0, $param = PARAM_INT): array {
        return ['type' => 'number', 'param' => $param, 'default' => $default];
    }

    private static function duration_control(int $default = 0): array {
        return ['type' => 'duration', 'param' => PARAM_INT, 'default' => $default];
    }

    private static function text_control(string $default = ''): array {
        return ['type' => 'text', 'param' => PARAM_TEXT, 'default' => $default];
    }

    private static function select_control(array $options, $default = 0, $param = PARAM_INT): array {
        return ['type' => 'select', 'options' => $options, 'param' => $param, 'default' => $default];
    }

    private static function bool_instance(string $field, string $label, int $default = 0): array {
        $definition = self::instance_definition($field, $label);
        $definition['control'] = self::bool_control($default);
        return $definition;
    }

    private static function number_instance(string $field, string $label, $default = 0, $param = PARAM_INT): array {
        $definition = self::instance_definition($field, $label);
        $definition['control'] = self::number_control($default, $param);
        return $definition;
    }

    private static function duration_instance(string $field, string $label, int $default = 0): array {
        $definition = self::instance_definition($field, $label);
        $definition['control'] = self::duration_control($default);
        return $definition;
    }

    private static function select_instance(string $field, string $label, array $options, $default = 0,
            $param = PARAM_INT): array {
        $definition = self::instance_definition($field, $label);
        $definition['control'] = self::select_control($options, $default, $param);
        return $definition;
    }

    private static function serialized_definition(string $storagefield, string $key, string $formfield,
            string $label, array $control): array {
        return [
            'source' => 'serialized',
            'sourcefield' => $storagefield,
            'serializedkey' => $key,
            'formfield' => $formfield,
            'label' => $label,
            'control' => $control,
        ];
    }

    private static function number_options(int $min, int $max, array $prepend = []): array {
        $options = $prepend;
        for ($i = $min; $i <= $max; $i++) {
            $options[$i] = (string)$i;
        }
        return $options;
    }

    private static function percentage_options(): array {
        $options = [];
        for ($i = 0; $i <= 100; $i++) {
            $options[$i] = $i . '%';
        }
        return $options;
    }

    private static function forum_type_options(): array {
        global $CFG;
        try {
            require_once($CFG->dirroot . '/mod/forum/lib.php');
            if (function_exists('forum_get_forum_types')) {
                $options = forum_get_forum_types();
                if (is_array($options) && !empty($options)) {
                    return $options;
                }
            }
        } catch (\Throwable $e) {
            // Use stable fallback values below.
        }
        return [
            'general' => get_string('valueforumgeneral', 'local_activitysettingstemplates'),
            'eachuser' => get_string('valueforumeachuser', 'local_activitysettingstemplates'),
            'single' => get_string('valueforumsingle', 'local_activitysettingstemplates'),
            'qanda' => get_string('valueforumqanda', 'local_activitysettingstemplates'),
            'blog' => get_string('valueforumblog', 'local_activitysettingstemplates'),
        ];
    }

    private static function forum_subscription_options(): array {
        global $CFG;
        try {
            require_once($CFG->dirroot . '/mod/forum/lib.php');
            if (function_exists('forum_get_subscriptionmode_options')) {
                $options = forum_get_subscriptionmode_options();
                if (is_array($options) && !empty($options)) {
                    return $options;
                }
            }
        } catch (\Throwable $e) {
            // Fallback below.
        }
        return [
            0 => get_string('valueforumsubscribeoptional', 'local_activitysettingstemplates'),
            1 => get_string('valueforumsubscribeforced', 'local_activitysettingstemplates'),
            2 => get_string('valueforumsubscribeinitial', 'local_activitysettingstemplates'),
            3 => get_string('valueforumsubscribedisabled', 'local_activitysettingstemplates'),
        ];
    }

    private static function forum_tracking_options(): array {
        global $CFG;
        try {
            require_once($CFG->dirroot . '/mod/forum/lib.php');
            $options = [];
            if (defined('FORUM_TRACKING_OPTIONAL')) {
                $options[FORUM_TRACKING_OPTIONAL] = get_string('trackingoptional', 'forum');
            }
            if (defined('FORUM_TRACKING_OFF')) {
                $options[FORUM_TRACKING_OFF] = get_string('trackingoff', 'forum');
            }
            if (defined('FORUM_TRACKING_FORCED')) {
                $options[FORUM_TRACKING_FORCED] = get_string('trackingon', 'forum');
            }
            if (!empty($options)) {
                return $options;
            }
        } catch (\Throwable $e) {
            // Fallback below.
        }
        return [
            0 => get_string('valueforumtrackingoff', 'local_activitysettingstemplates'),
            1 => get_string('valueforumtrackingoptional', 'local_activitysettingstemplates'),
            2 => get_string('valueforumtrackingforced', 'local_activitysettingstemplates'),
        ];
    }

    private static function resource_display_options(): array {
        return [
            0 => get_string('valuedisplayautomatic', 'local_activitysettingstemplates'),
            1 => get_string('valuedisplayembed', 'local_activitysettingstemplates'),
            2 => get_string('valuedisplayframe', 'local_activitysettingstemplates'),
            3 => get_string('valuedisplaynew', 'local_activitysettingstemplates'),
            4 => get_string('valuedisplaydownload', 'local_activitysettingstemplates'),
            5 => get_string('valuedisplayopen', 'local_activitysettingstemplates'),
            6 => get_string('valuedisplaypopup', 'local_activitysettingstemplates'),
        ];
    }

    private static function wiki_format_options(): array {
        global $CFG;
        try {
            require_once($CFG->dirroot . '/mod/wiki/locallib.php');
            if (function_exists('wiki_get_formats')) {
                $options = wiki_get_formats();
                if (is_array($options) && !empty($options)) {
                    return $options;
                }
            }
        } catch (\Throwable $e) {
            // Fallback below.
        }
        return [
            'html' => 'HTML',
            'creole' => 'Creole',
            'nwiki' => 'NWiki',
        ];
    }

    private static function lesson_nextpage_options(): array {
        global $CFG;
        $options = [0 => get_string('valuenormal', 'local_activitysettingstemplates')];
        try {
            require_once($CFG->dirroot . '/mod/lesson/locallib.php');
            if (defined('LESSON_UNSEENPAGE')) {
                $options[LESSON_UNSEENPAGE] = get_string('showanunseenpage', 'lesson');
            }
            if (defined('LESSON_UNANSWEREDPAGE')) {
                $options[LESSON_UNANSWEREDPAGE] = get_string('showanunansweredpage', 'lesson');
            }
        } catch (\Throwable $e) {
            // The normal option remains valid.
        }
        return $options;
    }

    private static function h5p_grademethod_options(): array {
        try {
            if (class_exists('\\mod_h5pactivity\\local\\manager')) {
                $options = \mod_h5pactivity\local\manager::get_grading_methods();
                if (is_array($options) && !empty($options)) {
                    return $options;
                }
            }
        } catch (\Throwable $e) {
            // Fallback below.
        }
        return [
            1 => get_string('valueh5phighest', 'local_activitysettingstemplates'),
            2 => get_string('valueh5paverage', 'local_activitysettingstemplates'),
            3 => get_string('valueh5plast', 'local_activitysettingstemplates'),
            4 => get_string('valueh5pfirst', 'local_activitysettingstemplates'),
        ];
    }

    private static function h5p_reviewmode_options(): array {
        try {
            if (class_exists('\\mod_h5pactivity\\local\\manager')) {
                $options = \mod_h5pactivity\local\manager::get_review_modes();
                if (is_array($options) && !empty($options)) {
                    return $options;
                }
            }
        } catch (\Throwable $e) {
            // Fallback below.
        }
        return [
            0 => get_string('valueh5previewnone', 'local_activitysettingstemplates'),
            1 => get_string('valueh5previewown', 'local_activitysettingstemplates'),
        ];
    }

    private static function workshop_strategy_options(): array {
        return [
            'accumulative' => get_string('valueworkshopaccumulative', 'local_activitysettingstemplates'),
            'comments' => get_string('valueworkshopcomments', 'local_activitysettingstemplates'),
            'numerrors' => get_string('valueworkshopnumerrors', 'local_activitysettingstemplates'),
            'rubric' => get_string('valueworkshoprubric', 'local_activitysettingstemplates'),
        ];
    }

    private static function workshop_example_options(): array {
        return [
            0 => get_string('valueexamplesvoluntary', 'local_activitysettingstemplates'),
            1 => get_string('valueexamplesbeforesubmission', 'local_activitysettingstemplates'),
            2 => get_string('valueexamplesbeforeselfassessment', 'local_activitysettingstemplates'),
        ];
    }

    private static function scorm_options(string $function, array $fallback): array {
        global $CFG;
        try {
            require_once($CFG->dirroot . '/mod/scorm/locallib.php');
            if (function_exists($function)) {
                $options = $function();
                if (is_array($options) && !empty($options)) {
                    return $options;
                }
            }
        } catch (\Throwable $e) {
            // Use supplied fallback.
        }
        return $fallback;
    }

    private static function scorm_grademethod_options(): array {
        return [
            0 => get_string('valuescormhighest', 'local_activitysettingstemplates'),
            1 => get_string('valuescormaverage', 'local_activitysettingstemplates'),
            2 => get_string('valuescormsum', 'local_activitysettingstemplates'),
            3 => get_string('valuescormlearningobjects', 'local_activitysettingstemplates'),
        ];
    }

    private static function instance_definition(string $field, string $label): array {
        return ['source' => 'instance', 'sourcefield' => $field, 'formfield' => $field, 'label' => $label];
    }

    private static function get_detected_instance_definitions(string $moduletype, array $existingformfields): array {
        global $DB;
        try {
            $columns = $DB->get_columns($moduletype);
        } catch (\Throwable $e) {
            return [];
        }
        $fields = [];
        foreach ($columns as $name => $column) {
            $name = (string)$name;
            if (isset($existingformfields[$name]) || !self::is_safe_detected_field($name, $column)) {
                continue;
            }
            $label = self::resolve_native_field_label_strict($moduletype, $name);
            if ($label === null) {
                continue;
            }
            $metatype = (string)($column->meta_type ?? '');
            $isboolean = self::looks_like_boolean_field($name, $column);
            $control = $isboolean ? self::bool_control() : self::number_control(0,
                in_array($metatype, ['N', 'F'], true) ? PARAM_FLOAT : PARAM_INT);
            $fields['auto_' . $name] = [
                'source' => 'instance',
                'sourcefield' => $name,
                'formfield' => $name,
                'labeltext' => $label,
                'detected' => true,
                'metatype' => $metatype,
                'maxlength' => (int)($column->max_length ?? 0),
                'control' => $control,
            ];
        }
        ksort($fields, SORT_NATURAL | SORT_FLAG_CASE);
        return $fields;
    }

    private static function is_safe_detected_field(string $name, object $column): bool {
        if (in_array($name, self::EXCLUDED_INSTANCE_FIELDS, true)) {
            return false;
        }
        foreach (self::EXCLUDED_DATE_PATTERNS as $pattern) {
            if (preg_match($pattern, $name)) {
                return false;
            }
        }
        if (preg_match('/id$/i', $name)) {
            return false;
        }
        $metatype = (string)($column->meta_type ?? '');
        if (!in_array($metatype, ['I', 'N', 'F'], true)) {
            return false;
        }
        if (self::looks_like_boolean_field($name, $column)) {
            return true;
        }
        // Numeric fallback is intentionally narrow: only obvious reusable limits/counts.
        return (bool)preg_match('/(max|min|limit|count|number|entries|attempts|attachments|pages|questions|replies|posts|articles|size|threshold)$/i', $name);
    }

    private static function looks_like_boolean_field(string $name, object $column): bool {
        $metatype = (string)($column->meta_type ?? '');
        if ($metatype !== 'I') {
            return false;
        }
        return (bool)preg_match('/^(allow|enable|show|hide|force|require|prevent|use|track|notify|email|auto|multiple|anonymous|comments|approval|custom|practice|retake|review|display|lock)/i', $name);
    }

    private static function resolve_native_field_label_strict(string $moduletype, string $field): ?string {
        $stringman = get_string_manager();
        $component = 'mod_' . $moduletype;
        $candidates = [$field, $field . 'label', $field . 'name'];
        foreach ($candidates as $candidate) {
            if ($stringman->string_exists($candidate, $component)) {
                return get_string($candidate, $component);
            }
        }
        foreach ($candidates as $candidate) {
            if ($stringman->string_exists($candidate, 'moodle')) {
                return get_string($candidate);
            }
        }
        return null;
    }

    private static function resolve_native_field_label(string $moduletype, string $field): string {
        return self::resolve_native_field_label_strict($moduletype, $field) ?? self::humanise_field_name($field);
    }

    private static function humanise_field_name(string $field): string {
        $field = preg_replace('/([a-z])([A-Z])/', '$1 $2', $field);
        $field = str_replace(['_', '-'], ' ', $field);
        return ucfirst(trim(preg_replace('/\s+/', ' ', $field)));
    }

    public static function get_flat_definitions(string $moduletype): array {
        $flat = [];
        foreach (self::get_definitions($moduletype) as $section) {
            foreach ($section['fields'] as $key => $definition) {
                $flat[$key] = $definition;
            }
        }
        return $flat;
    }

    /**
     * Normalise templates created before the curated Quiz registry.
     */
    public static function normalise_stored_config(string $moduletype, array $config): array {
        if ($moduletype === 'quiz') {
            unset($config['sumgrades']);
            foreach (self::REVIEW_FIELDS as $short => $sourcefield) {
                if (!array_key_exists($sourcefield, $config)) {
                    continue;
                }
                $stored = (int)$config[$sourcefield];
                foreach (self::REVIEW_PERIODS as $period => $mask) {
                    if (($period === 'during' && $short === 'attempt') ||
                            ($period === 'during' && $short === 'overallfeedback')) {
                        continue;
                    }
                    $formfield = $short . $period;
                    if (!array_key_exists($formfield, $config)) {
                        $config[$formfield] = ($stored & $mask) ? 1 : 0;
                    }
                }
                unset($config[$sourcefield]);
            }
        }

        // Core modules are curated allow-lists. This automatically cleans old
        // templates created by generic DB discovery without requiring recreation.
        if (in_array($moduletype, self::CURATED_CORE_MODULES, true)) {
            $allowed = [];
            foreach (self::get_flat_definitions($moduletype) as $definition) {
                $allowed[(string)$definition['formfield']] = true;
            }
            $config = array_intersect_key($config, $allowed);
        }
        return $config;
    }

    public static function get_editor_control(string $moduletype, string $formfield): array {
        foreach (self::get_flat_definitions($moduletype) as $definition) {
            if (($definition['formfield'] ?? '') === $formfield && !empty($definition['control'])) {
                return $definition['control'];
            }
        }

        $booleanfields = [
            'shuffleanswers', 'submissiondrafts', 'requiresubmissionstatement', 'teamsubmission',
            'requireallteammemberssubmit', 'blindmarking', 'hidegrader', 'markingworkflow',
            'markingallocation', 'completionview', 'attemptonlast', 'canredoquestions', 'precreateattempts',
            'showblocks', 'allowofflineattempts', 'completionattemptsexhausted',
        ];
        if (preg_match('/^(attempt|correctness|maxmarks|marks|specificfeedback|generalfeedback|rightanswer|overallfeedback)(during|immediately|open|closed)$/', $formfield)) {
            $booleanfields[] = $formfield;
        }
        if (in_array($formfield, $booleanfields, true)) {
            return ['type' => 'select', 'options' => [0 => get_string('no'), 1 => get_string('yes')], 'param' => PARAM_INT, 'default' => 0];
        }
        if (in_array($formfield, ['delay1', 'delay2', 'graceperiod'], true)) {
            return ['type' => 'duration', 'param' => PARAM_INT, 'default' => 0];
        }
        if ($formfield === 'groupmode') {
            return ['type' => 'select', 'options' => [
                0 => get_string('valuegroupmode0', 'local_activitysettingstemplates'),
                1 => get_string('valuegroupmode1', 'local_activitysettingstemplates'),
                2 => get_string('valuegroupmode2', 'local_activitysettingstemplates'),
            ], 'param' => PARAM_INT, 'default' => 0];
        }
        if ($formfield === 'completion') {
            return ['type' => 'select', 'options' => [
                0 => get_string('valuecompletion0', 'local_activitysettingstemplates'),
                1 => get_string('valuecompletion1', 'local_activitysettingstemplates'),
                2 => get_string('valuecompletion2', 'local_activitysettingstemplates'),
            ], 'param' => PARAM_INT, 'default' => 0];
        }

        if ($moduletype === 'quiz') {
            if ($formfield === 'attempts') {
                $options = [0 => get_string('valueunlimited', 'local_activitysettingstemplates')];
                for ($i = 1; $i <= 10; $i++) { $options[$i] = (string)$i; }
                return ['type' => 'select', 'options' => $options, 'param' => PARAM_INT, 'default' => 1];
            }
            if ($formfield === 'grademethod') {
                return ['type' => 'select', 'options' => [
                    1 => get_string('valuegrademethodhighest', 'local_activitysettingstemplates'),
                    2 => get_string('valuegrademethodaverage', 'local_activitysettingstemplates'),
                    3 => get_string('valuegrademethodfirst', 'local_activitysettingstemplates'),
                    4 => get_string('valuegrademethodlast', 'local_activitysettingstemplates'),
                ], 'param' => PARAM_INT, 'default' => 1];
            }
            if ($formfield === 'preferredbehaviour') {
                return ['type' => 'select', 'options' => [
                    'deferredfeedback' => get_string('valuebehaviourdeferredfeedback', 'local_activitysettingstemplates'),
                    'immediatefeedback' => get_string('valuebehaviourimmediatefeedback', 'local_activitysettingstemplates'),
                    'interactive' => get_string('valuebehaviourinteractive', 'local_activitysettingstemplates'),
                    'deferredcbm' => get_string('valuebehaviourdeferredcbm', 'local_activitysettingstemplates'),
                    'immediatecbm' => get_string('valuebehaviourimmediatecbm', 'local_activitysettingstemplates'),
                    'adaptive' => get_string('valuebehaviouradaptive', 'local_activitysettingstemplates'),
                    'adaptivenopenalty' => get_string('valuebehaviouradaptivenopenalty', 'local_activitysettingstemplates'),
                ], 'param' => PARAM_ALPHANUMEXT, 'default' => 'deferredfeedback'];
            }
            if ($formfield === 'navmethod') {
                return ['type' => 'select', 'options' => [
                    'free' => get_string('valuenavfree', 'local_activitysettingstemplates'),
                    'sequential' => get_string('valuenavsequential', 'local_activitysettingstemplates'),
                ], 'param' => PARAM_ALPHA, 'default' => 'free'];
            }
            if ($formfield === 'questionsperpage') {
                $options = [0 => get_string('valuenever', 'local_activitysettingstemplates')];
                for ($i = 1; $i <= 50; $i++) { $options[$i] = (string)$i; }
                return ['type' => 'select', 'options' => $options, 'param' => PARAM_INT, 'default' => 1];
            }
            if ($formfield === 'decimalpoints') {
                $options = [];
                for ($i = 0; $i <= 5; $i++) { $options[$i] = (string)$i; }
                return ['type' => 'select', 'options' => $options, 'param' => PARAM_INT, 'default' => 2];
            }
            if ($formfield === 'questiondecimalpoints') {
                $options = [-1 => get_string('valuesameasoverallgrade', 'local_activitysettingstemplates')];
                for ($i = 0; $i <= 5; $i++) { $options[$i] = (string)$i; }
                return ['type' => 'select', 'options' => $options, 'param' => PARAM_INT, 'default' => -1];
            }
            if ($formfield === 'overduehandling') {
                return ['type' => 'select', 'options' => [
                    'autosubmit' => get_string('valueoverdueautosubmit', 'local_activitysettingstemplates'),
                    'graceperiod' => get_string('valueoverduegraceperiod', 'local_activitysettingstemplates'),
                    'autoabandon' => get_string('valueoverdueautoabandon', 'local_activitysettingstemplates'),
                ], 'param' => PARAM_ALPHA, 'default' => 'autosubmit'];
            }
            if ($formfield === 'showuserpicture') {
                return ['type' => 'select', 'options' => [
                    0 => get_string('valueshowuserpicture0', 'local_activitysettingstemplates'),
                    1 => get_string('valueshowuserpicture1', 'local_activitysettingstemplates'),
                    2 => get_string('valueshowuserpicture2', 'local_activitysettingstemplates'),
                ], 'param' => PARAM_INT, 'default' => 0];
            }
            if ($formfield === 'browsersecurity') {
                $options = ['-' => get_string('valuebrowsersecuritynone', 'local_activitysettingstemplates')];
                try {
                    if (class_exists('\\mod_quiz\\access_manager')) {
                        $options = \mod_quiz\access_manager::get_browser_security_choices();
                    }
                } catch (\Throwable $e) {
                    // Keep the safe fallback.
                }
                return ['type' => 'select', 'options' => $options, 'param' => PARAM_RAW_TRIMMED, 'default' => '-'];
            }
            if ($formfield === 'completionminattempts') {
                return ['type' => 'number', 'param' => PARAM_INT, 'default' => 0, 'min' => 0, 'max' => 1000];
            }
        }

        if ($moduletype === 'assign') {
            if ($formfield === 'attemptreopenmethod') {
                return ['type' => 'select', 'options' => [
                    'none' => get_string('valueattemptreopennone', 'local_activitysettingstemplates'),
                    'manual' => get_string('valueattemptreopenmanual', 'local_activitysettingstemplates'),
                    'untilpass' => get_string('valueattemptreopenuntilpass', 'local_activitysettingstemplates'),
                ], 'param' => PARAM_ALPHA, 'default' => 'none'];
            }
            if ($formfield === 'maxattempts') {
                $options = [-1 => get_string('valueunlimited', 'local_activitysettingstemplates')];
                for ($i = 1; $i <= 30; $i++) { $options[$i] = (string)$i; }
                return ['type' => 'select', 'options' => $options, 'param' => PARAM_INT, 'default' => -1];
            }
        }

        foreach (self::get_flat_definitions($moduletype) as $definition) {
            if (($definition['formfield'] ?? '') !== $formfield || empty($definition['detected'])) {
                continue;
            }
            $metatype = (string)($definition['metatype'] ?? '');
            if ($metatype === 'I') { return ['type' => 'number', 'param' => PARAM_INT, 'default' => 0]; }
            if (in_array($metatype, ['N', 'F'], true)) { return ['type' => 'number', 'param' => PARAM_FLOAT, 'default' => 0]; }
            break;
        }
        return ['type' => 'text', 'param' => PARAM_TEXT, 'default' => ''];
    }

    /**
     * Format a stored scalar using the same editor metadata used by the edit form.
     */
    public static function format_stored_value(string $moduletype, string $formfield, $value): string {
        $control = self::get_editor_control($moduletype, $formfield);
        if (($control['type'] ?? '') === 'select') {
            foreach (($control['options'] ?? []) as $optionvalue => $label) {
                if ((string)$optionvalue === (string)$value) {
                    return s((string)$label);
                }
            }
        }
        if (($control['type'] ?? '') === 'duration') {
            return (int)$value > 0 ? format_time((int)$value) : get_string('none');
        }
        if (($control['type'] ?? '') === 'filesize') {
            return (int)$value > 0 ? display_size((int)$value) : get_string('none');
        }
        if (is_scalar($value) || $value === null) {
            return s((string)$value);
        }
        return s(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Get form fields rendered by Moodle as duration controls.
     *
     * @param string $moduletype Activity/resource module name.
     * @return string[] Form field names.
     */
    public static function get_duration_formfields(string $moduletype): array {
        $fields = [];
        foreach (self::get_flat_definitions($moduletype) as $definition) {
            $field = (string)($definition['formfield'] ?? '');
            if ($field !== '' && (self::get_editor_control($moduletype, $field)['type'] ?? '') === 'duration') {
                $fields[] = $field;
            }
        }
        return array_values(array_unique($fields));
    }

    public static function split_duration(int $seconds): array {
        $seconds = max(0, $seconds);
        foreach ([86400, 3600, 60, 1] as $unit) {
            if ($seconds === 0) {
                return [0, 60];
            }
            if ($seconds % $unit === 0) {
                return [(int)($seconds / $unit), $unit];
            }
        }
        return [$seconds, 1];
    }

    public static function duration_units(): array {
        return [
            1 => get_string('seconds'),
            60 => get_string('minutes'),
            3600 => get_string('hours'),
            86400 => get_string('days'),
        ];
    }

    public static function extract_config(string $moduletype, \stdClass $cm, \stdClass $instance, array $selectedkeys): array {
        $definitions = self::get_flat_definitions($moduletype);
        $config = [];
        foreach ($selectedkeys as $key) {
            if (!isset($definitions[$key])) {
                continue;
            }
            $definition = $definitions[$key];
            $sourcekind = (string)($definition['source'] ?? 'instance');
            $source = $sourcekind === 'cm' ? $cm : $instance;
            $sourcefield = (string)$definition['sourcefield'];
            if (!property_exists($source, $sourcefield)) {
                continue;
            }
            $value = $source->{$sourcefield};

            if ($sourcekind === 'serialized') {
                $values = self::decode_serialized_array($value);
                $serializedkey = (string)($definition['serializedkey'] ?? '');
                if ($serializedkey === '' || !array_key_exists($serializedkey, $values)) {
                    continue;
                }
                $value = $values[$serializedkey];
            }

            if (isset($definition['bitmask'])) {
                $value = (((int)$value & (int)$definition['bitmask']) !== 0) ? 1 : 0;
            }
            if (!is_scalar($value) && $value !== null) {
                continue;
            }
            $config[$definition['formfield']] = $value;
        }
        return self::normalise_stored_config($moduletype, $config);
    }

    private static function decode_serialized_array($value): array {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }
        try {
            if (function_exists('unserialize_array')) {
                $decoded = unserialize_array($value);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        } catch (\Throwable $e) {
            // Fall through to PHP unserialize.
        }
        try {
            $decoded = @unserialize($value, ['allowed_classes' => false]);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
