# Activity Settings Templates

<p align="center">
  <img src="docs/images/activity-settings-templates-logo.png" alt="Activity Settings Templates logo" width="280">
</p>

**Activity Settings Templates** (`local_activitysettingstemplates`) is a teacher-centered local plugin for Moodle LMS that allows teachers to save selected activity settings as reusable personal templates and apply them later to activities of the same type.

The plugin reduces repetitive configuration work, configuration errors, and cognitive load while keeping Moodle's native activity forms, validation, permissions, and save workflow in control.

> Public release: **1.0.0**  
> Moodle: **4.5–5.2**  
> License: **GNU GPL v3 or later**

Documentation: **English** | [Português (Brasil)](docs/README.pt-BR.md) | [Español](docs/README.es.md)  
Technical documentation: [Architecture and implementation](docs/TECHNICAL.md)

## Why this plugin?

Teachers often configure similar activities repeatedly: quiz attempt rules, forum behavior, completion conditions, group mode, SCORM options, assignment settings, and many other parameters.

Moodle provides powerful activity configuration, but repeating the same decisions across many activities or courses can require significant navigation, recall, checking, and manual work.

Activity Settings Templates let teachers configure settings once and reuse them later. A template stores **configuration choices**, not activity content.

## Main features

- Create personal settings templates from already configured activities.
- Choose exactly which supported settings belong to each template.
- Apply templates from the native Moodle activity settings form.
- Show only templates compatible with the current activity type.
- Preview the stored settings before applying them.
- Analyze compatibility with the current form before any setting is changed.
- Clearly distinguish applicable, form-dependent, and unavailable settings using color, icon, and explanatory text.
- Apply compatible settings while safely skipping unavailable or locked controls.
- Retry dependent controls once after Moodle form dependencies react.
- Edit template name, description, included settings, and stored values.
- Delete personal templates without affecting activities where they were previously used.
- Manage templates from a teacher-facing overview with activity type and stored values.
- Responsive Bootstrap-based interface integrated into Moodle forms.
- Accessible labels, keyboard-compatible controls, and polite live-region feedback.
- English, Brazilian Portuguese, and Spanish interface.
- No external services or runtime dependencies.

## Teacher workflow

1. Configure an activity normally and save it.
2. Reopen the activity settings.
3. In **Activity Settings Templates**, select **Create template from saved settings**.
4. Give the template a name and optional description.
5. Select only the settings that should be reusable.
6. Save the template.
7. Open another activity of the same type.
8. Select the desired template.
9. Review the compatibility preview.
10. Select **Apply template**.
11. Review the native Moodle form and save the activity normally.

Applying a template **does not save the activity automatically**. The teacher always reviews and confirms the final Moodle form.

## Activity support

The template section is available in the configuration form of installed Moodle activity/resource modules (`mod_*`).

Core Moodle modules use curated, teacher-facing setting definitions so that internal, calculated, relational, or content-oriented fields are not exposed as reusable settings.

Curated support includes the main reusable settings of:

- Assignment
- Book
- Choice
- Database
- Feedback
- File
- Folder
- Forum
- Glossary
- H5P activity
- IMS content package
- Lesson
- Page
- Quiz
- SCORM
- URL
- Wiki
- Workshop

For modules whose specific configuration mainly includes content, credentials, or complex relations, the plugin remains deliberately conservative and exposes only safe, common settings when appropriate.

Third-party activity modules can also be detected through a conservative fallback. Unknown raw database fields are not presented unless they can be represented safely and meaningfully.

## Safety principles

Activity Settings Templates intentionally avoid copying or forcing:

- activity content and descriptions;
- questions and authored activity data;
- files and uploaded packages;
- learner submissions or learner-generated data;
- dates and deadlines;
- passwords, tokens, secrets, and credentials;
- course-local relational identifiers such as grouping IDs;
- calculated and internal values;
- legacy hidden fields;
- values that cannot be safely represented in the destination form.

If a stored setting is unavailable, locked, or incompatible with the current form, the plugin does not force it. The compatibility preview identifies this before the teacher applies the template.

## Compatibility preview

When a template is selected, the plugin analyses the current activity form and shows:

- **Applicable** — the setting can be applied now.
- **Attention** — the setting exists but is currently unavailable and may depend on another form option.
- **Not applicable** — the field or stored value is not available in the current form.

Attention-required settings are shown before compatible settings to reduce scanning effort. After applying the template, a contextual status message appears immediately before the preview.

## Template ownership and privacy

Templates are personal to the teacher account that created them. The plugin stores the template owner, template name, description, activity type, selected configuration values, and timestamps in Moodle's database.

The plugin implements the Moodle Privacy API to export and delete this personal data. It does not send data to external services.

## Installation

### From ZIP

1. Download the release ZIP.
2. In Moodle, go to **Site administration → Plugins → Install plugins**.
3. Upload the ZIP and complete validation.
4. Visit **Site administration → Notifications** to finish installation.
5. Purge Moodle caches if necessary.

### From Git

Clone the repository into `local/activitysettingstemplates`:

```bash
git clone https://github.com/isaiasmendesoliveira/moodle-local_activitysettingstemplates.git local/activitysettingstemplates
```

Then visit **Site administration → Notifications**.

## Requirements

- Moodle 4.5 or later.
- Supported release range declared by this version: Moodle 4.5–5.2.
- JavaScript enabled in the browser for template preview and application.
- Permission to manage activities in the course.

## Repository structure

The repository follows the same public-project layout used by the maintainer's other Moodle plugins, with GitHub templates, documentation, images, tests, language packs, and Moodle runtime files kept at the repository root.

## Documentation

- [Marketplace submission text](docs/MARKETPLACE.md)
- [Technical documentation](docs/TECHNICAL.md)
- [Testing guide](docs/TESTING.md)
- [Portuguese documentation](docs/README.pt-BR.md)
- [Spanish documentation](docs/README.es.md)

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

Please report security issues privately. See [SECURITY.md](SECURITY.md).

## License

This plugin is licensed under the [GNU General Public License v3 or later](LICENSE).
