# Technical documentation

## Component

- Plugin type: `local`
- Component: `local_activitysettingstemplates`
- Installation directory: `local/activitysettingstemplates`
- Public release: `1.0.0`
- Minimum Moodle version: `4.5` (`2024100700`)
- Declared supported range: Moodle `4.5` through `5.2`

## Architecture

Activity Settings Templates extends Moodle's native course-module configuration forms rather than replacing them.

The main integration points are implemented in `lib.php`:

- `local_activitysettingstemplates_extend_navigation_course()` adds access to personal template management for users with the required capabilities.
- `local_activitysettingstemplates_coursemodule_standard_elements()` adds the template selector, management actions, compatibility preview, and apply action to activity/resource settings forms.

Applying a template only changes editable values in the current browser form. Moodle remains responsible for validating and saving the activity when the teacher submits the native form.

## Data model

Personal templates are stored in `local_activitysettingstemplates`.

Fields:

| Field | Purpose |
| --- | --- |
| `id` | Template primary key |
| `userid` | Owner user ID |
| `name` | Template name |
| `description` | Optional description |
| `moduletype` | Moodle activity/resource module name |
| `configjson` | JSON representation of selected reusable settings |
| `timecreated` | Creation timestamp |
| `timemodified` | Last modification timestamp |

An index on `userid, moduletype` supports filtering personal templates by the current activity type.

## Capabilities

`local/activitysettingstemplates:manage`

- Context: course
- Type: write
- Risk: configuration
- Default archetypes: editing teacher and manager
- Cloned from: `moodle/course:manageactivities`

The entry pages also require the native `moodle/course:manageactivities` capability.

## Field registry

`classes/local/field_registry.php` centralises the rules for reusable settings.

Its responsibilities include:

- detecting installed activity/resource modules;
- providing curated definitions for core Moodle modules;
- adding safe common course-module settings;
- normalising stored configuration from earlier development versions;
- formatting stored values using teacher-facing labels;
- providing editor controls for template management;
- handling duration controls;
- handling selected serialized display options used by Moodle resources;
- conservatively discovering eligible fields for third-party activity modules.

Core modules deliberately use curated allow-lists. This prevents raw database fields such as calculated values, credentials, relational identifiers, content, and legacy fields from leaking into the teacher interface.

## Template lifecycle

### Creation

`create.php` and `classes/form/create_template_form.php`:

1. Resolve the source course module and activity instance.
2. Verify course capabilities.
3. Obtain eligible setting definitions from the field registry.
4. Let the teacher select the reusable settings.
5. Extract only selected safe values.
6. Store the template as personal JSON configuration.

### Management

`index.php` lists the current user's templates and formats stored values through the field registry.

### Editing

`edit.php` and `classes/form/edit_template_form.php` allow the owner to change:

- template name;
- description;
- included settings;
- stored values supported by that activity type.

The activity type itself remains fixed.

### Deletion

`delete.php` requires confirmation and `sesskey()` before deleting the personal template.

Deleting a template does not alter activities where it was previously applied.

## JavaScript application layer

Source module: `amd/src/applytemplate.js`

The AMD module:

- handles management navigation buttons;
- watches template selection;
- renders the compatibility preview;
- classifies settings as applicable, condition-dependent, or unavailable;
- applies values only to editable native form controls;
- triggers standard `input` and `change` events so Moodle form dependencies can react;
- retries pending dependent values once;
- shows an accessible post-application status near the preview.

The JavaScript never submits the Moodle activity form on behalf of the teacher.

## Compatibility analysis

Before application, each stored setting is evaluated against the current form.

Possible states:

- **Applicable**: a compatible editable control/value is available.
- **Attention**: the field exists but is currently disabled or otherwise dependent on the form state.
- **Not applicable**: the field or stored option is unavailable in the current form.

Status is conveyed with text and icon in addition to colour.

## Privacy API

`classes/privacy/provider.php` implements:

- metadata declaration;
- context discovery;
- user-data export;
- deletion for a user;
- deletion for all users in a context;
- user-list discovery and deletion.

The plugin stores personal template data in Moodle only and does not transmit it to external services.

## Security model

The plugin uses:

- Moodle authentication and course contexts;
- capability checks;
- parameter cleaning (`required_param`, `optional_param`);
- Moodle forms and validation;
- session-key confirmation for destructive actions;
- ownership checks on template records;
- safe allow-lists for reusable settings.

Templates do not intentionally contain passwords, tokens, uploaded files, learner submissions, or other learner-generated data.

## Accessibility and responsive design

The teacher-facing integration uses Moodle/Bootstrap classes and preserves the native activity form.

Key accessibility decisions include:

- explicit label association for the template selector;
- native buttons for actions;
- keyboard-operable controls;
- responsive 50/50 desktop layout with full-width stacking on smaller screens;
- colour plus icon plus text for compatibility states;
- `role="status"` and `aria-live="polite"` for dynamic feedback;
- no automatic saving after template application.

## Tests

`tests/field_registry_test.php` covers core registry behaviour including supported modules, curated controls, common settings, filtering, duration support, legacy normalization, and safe third-party discovery.

See [TESTING.md](TESTING.md) for manual release testing.
