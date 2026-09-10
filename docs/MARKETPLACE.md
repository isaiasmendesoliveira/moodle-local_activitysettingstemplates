# Moodle Marketplace submission

Prepared metadata for **Activity Settings Templates 1.0.0**.

## Listing name

**Activity Settings Templates**

## Frankenstyle component

`local_activitysettingstemplates`

## Plugin type

Local plugin

## Version

`1.0.0`

Internal Moodle version: `2026091000`

## Short description

Activity Settings Templates lets teachers save selected Moodle activity settings as reusable personal templates, preview compatibility, and apply compatible settings directly in the native activity form without copying content or saving automatically.

## Full description

**Activity Settings Templates** is a teacher-centered Moodle plugin designed to reduce repetitive configuration work and cognitive load when teachers create and maintain activities.

Teachers often make the same configuration decisions repeatedly: quiz attempt rules, forum behaviour, assignment options, completion conditions, group mode, SCORM settings, and many other parameters. Moodle provides powerful native configuration, but reproducing those decisions across activities and courses can require repeated navigation, recall, checking, and manual work.

Activity Settings Templates lets a teacher configure an activity normally, save selected settings as a personal reusable template, and later apply that template to another activity of the same type.

The plugin deliberately keeps Moodle in control. Applying a template only fills compatible fields in the native activity settings form. The teacher reviews the result and saves the activity through Moodle's normal workflow.

### Teacher-centered UX and cognitive-load reduction

The interface is designed around recognition rather than recall and around making exceptions visible before an action is taken.

When a teacher selects a template, the plugin analyses the current form and shows a compatibility preview:

- **Applicable** settings can be applied immediately.
- **Attention** settings exist but may depend on another option in the current form state.
- **Not applicable** settings or values are unavailable and will not be forced.

The preview communicates status with colour, icon, and explanatory text together, not colour alone. Settings requiring attention are shown before compatible settings. After application, an accessible contextual message appears immediately before the preview so the teacher receives feedback without having to search elsewhere on the page.

### Main features

- Personal reusable activity settings templates.
- Create a template from an already configured activity.
- Select exactly which supported settings should be stored.
- Templates remain associated with their original activity type.
- Only compatible templates are shown in each activity form.
- Pre-application compatibility analysis.
- Applicable, form-dependent, and unavailable states.
- Safe application only to editable native Moodle form controls.
- Automatic second pass for dependent controls after Moodle form rules react.
- Edit template name, description, included settings, and supported stored values.
- Manage templates with activity type and localized stored values.
- Delete a template without changing activities where it was previously used.
- Responsive Bootstrap-based teacher interface.
- Keyboard-compatible controls and accessible live feedback.
- English, Brazilian Portuguese, and Spanish interface files.
- No external service, API key, subscription, or runtime dependency.

### Activity/resource support

The plugin integrates with installed Moodle activity/resource modules (`mod_*`). Core modules use curated teacher-facing definitions so internal database fields are not exposed merely because they exist.

Curated support includes main reusable settings for Assignment, Book, Choice, Database, Feedback, File, Folder, Forum, Glossary, H5P activity, IMS content package, Lesson, Page, Quiz, SCORM, URL, Wiki, and Workshop.

For modules whose specific settings are primarily content, credentials, or complex relational data, the plugin deliberately remains conservative and exposes only safe common settings when appropriate.

Third-party activity modules can be recognised through a conservative fallback. Unknown raw string fields, relational identifiers, and unsafe settings are not automatically exposed.

### Safety by design

Templates are configuration templates, not activity copies. The plugin intentionally avoids copying or forcing:

- descriptions and authored activity content;
- questions;
- files and uploaded packages;
- learner submissions and learner-generated data;
- dates and deadlines;
- passwords, tokens, secrets, and credentials;
- grouping IDs and other course-local relational identifiers;
- calculated/internal values;
- legacy hidden fields;
- fields or options unavailable in the destination form.

### Privacy

Templates are personal to the user who creates them. The plugin stores the owner user ID, template name, description, activity type, selected configuration values, and timestamps in the Moodle database.

The Moodle Privacy API is implemented for metadata declaration, export, and deletion. No template data is transmitted to external services.

## Common use cases

- Reusing a standard formative quiz configuration.
- Applying consistent assignment submission and attempt rules.
- Reusing forum subscription/tracking behaviour.
- Standardising completion/group settings across similar activities.
- Reusing SCORM display and attempt settings in self-paced courses.
- Reducing configuration inconsistencies across multiple course sections.
- Creating a personal library of recurring teaching configuration decisions.

## Requirements

- Moodle 4.5 or later.
- Declared support in version 1.0.0: Moodle 4.5–5.2.
- JavaScript enabled in the browser.
- User must have permission to manage course activities.

## Dependencies

None beyond Moodle core.

## External services

None.

## License

GNU GPL v3 or later.

## Maintainer

**Isaias Mendes de Oliveira**  
Email: `isaiasmendes@gmail.com`

## Source control URL

`https://github.com/isaiasmendesoliveira/moodle-local_activitysettingstemplates`

## Issue tracker URL

`https://github.com/isaiasmendesoliveira/moodle-local_activitysettingstemplates/issues`

## Documentation URL

`https://github.com/isaiasmendesoliveira/moodle-local_activitysettingstemplates#readme`

## Tags / keywords

Suggested tags:

- teacher tools
- activity settings
- templates
- course management
- workflow
- productivity
- usability
- UX
- cognitive load
- accessibility

## Languages included in this release

- English (`en`)
- Brazilian Portuguese (`pt_br`)
- Spanish (`es`)

The project intentionally keeps the three maintained language files together for this release so the public package matches the multilingual distribution used by the maintainer. Future community translations can additionally be coordinated through Moodle's normal translation workflow.

## Images

Repository assets:

- `docs/images/activity-settings-templates-logo.png` — 512 × 512 project logo.
- `docs/images/activity-settings-templates-logo.png` can also be used as the Marketplace plugin icon.
- `docs/images/activity-settings-templates-social-preview.png` — 1280 × 640 GitHub social preview.

## Recommended screenshots

After final validation on a clean Moodle test site, capture:

1. template selector and compatibility preview;
2. immediate feedback after applying a template;
3. template creation form;
4. personal template management page;
5. template editing page;
6. responsive/mobile layout.

See `docs/screenshots/README.md`.

## Pre-submission checklist

- [ ] Public GitHub repository created using the expected Moodle naming convention.
- [ ] Public GitHub Issues enabled.
- [ ] Tag/release `v1.0.0` created.
- [ ] Moodle 4.5 installation test completed.
- [ ] Moodle 5.1 installation test completed.
- [ ] Moodle 5.2 installation test completed.
- [ ] PHP/Moodle debugging enabled during functional tests.
- [ ] PHPUnit tests pass.
- [ ] Moodle coding-style / Plugin CI checks pass.
- [ ] JavaScript source/build validated.
- [ ] Privacy API validation completed.
- [ ] PostgreSQL and MySQL/MariaDB tested where possible.
- [ ] English, Brazilian Portuguese, and Spanish interfaces reviewed.
- [ ] Marketplace screenshots captured with test data only.
- [ ] ZIP installs through Moodle's web plugin installer without manual dependency installation.
