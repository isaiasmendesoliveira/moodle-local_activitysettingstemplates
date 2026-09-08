# Testing guide

This document describes the recommended release validation for **Activity Settings Templates 1.0.0**.

## Target environments

Test at minimum:

- Moodle 4.5 LTS;
- Moodle 5.1;
- Moodle 5.2;
- Boost theme or a Boost-derived theme;
- at least one supported PHP version for each Moodle target;
- at least one PostgreSQL and one MySQL/MariaDB environment when available.

## Installation

1. Install from the release ZIP.
2. Confirm Moodle identifies the component as `local_activitysettingstemplates`.
3. Confirm release `1.0.0` and minimum Moodle `4.5`.
4. Complete database installation without warnings.
5. Purge caches.

## Permissions

Verify:

- editing teachers can use the plugin when they can manage activities;
- managers can use the plugin;
- users without activity-management capability cannot manage templates;
- a user cannot edit/delete another user's personal template by changing URL parameters.

## Basic workflow

For at least Quiz, Assignment, Forum, SCORM, Page, and one additional activity type:

1. Configure and save activity A.
2. Reopen activity A.
3. Create a template from saved settings.
4. Select a subset of settings.
5. Save the template.
6. Open activity B of the same type.
7. Confirm the template is listed.
8. Confirm templates from other activity types are not listed.
9. Review the compatibility preview.
10. Apply the template.
11. Confirm immediate status appears before the preview.
12. Review the native form.
13. Save the activity.
14. Reopen it and confirm applicable values persisted through Moodle's normal save flow.

## Compatibility preview

Create situations for each status:

- applicable field/value;
- condition-dependent field disabled by another option;
- unavailable field;
- select field where stored option is not available.

Verify status uses text and icon in addition to colour and attention-required settings appear first.

## Dependent controls

Use an activity where one setting enables another.

Confirm the first pass changes the controlling setting, Moodle reacts, and the plugin retries the dependent value once.

## Safety

Verify a template does not copy:

- activity name/description/content;
- files or packages;
- questions;
- dates/deadlines;
- learner submissions;
- passwords/tokens/credentials;
- grouping IDs or other course-local relational identifiers;
- calculated values such as quiz totals.

## Quiz-specific checks

Verify curated quiz handling for:

- attempts and grading;
- question behaviour;
- navigation/layout;
- attempt delays and grace period duration controls;
- review options by period;
- appearance/security where available;
- completion settings;
- no raw `review*` bitmask fields;
- no `sumgrades` setting.

## Template management

Verify:

- activity type column;
- localized values;
- edit name;
- edit description;
- include/remove settings;
- change supported stored values;
- delete confirmation;
- editing a template does not retroactively alter previously configured activities.

## Responsive interface

Test at approximately:

- 320 px width;
- 768 px width;
- 1024 px width;
- desktop width.

Verify:

- controls do not overflow horizontally;
- management and apply rows stack correctly on smaller screens;
- labels remain visible;
- long translated button text is readable;
- compatibility preview uses the available width.

## Accessibility

Verify:

- keyboard-only navigation;
- visible focus;
- template selector has an associated label;
- buttons are native and operable by keyboard;
- status does not rely on colour alone;
- live feedback is announced by a screen reader (`aria-live="polite"` / `role="status"`);
- 200% browser zoom does not hide or clip essential text;
- English, Brazilian Portuguese, and Spanish interfaces remain usable.

## Privacy

Run Moodle Privacy API checks where available and verify:

- template metadata is declared;
- user data can be exported;
- personal templates can be deleted through privacy requests;
- no external data transmission occurs.

## Automated checks

Recommended Moodle Plugin CI checks include:

- PHP lint;
- Moodle coding style / PHPCS;
- PHPDoc checks;
- PHPUnit tests;
- JavaScript lint/build;
- XMLDB validation.

The repository includes `tests/field_registry_test.php` for registry behaviour.

## Release acceptance

A release candidate should not be published to Moodle Marketplace until installation, core workflows, permissions, compatibility preview, responsive behaviour, translations, privacy handling, and Moodle coding-style checks have been validated in a real Moodle test site.
