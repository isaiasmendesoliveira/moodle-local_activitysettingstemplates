# Changelog




- Database table naming aligned with the Moodle component prefix for Marketplace validation.
## 1.2.10-beta

- Moved the post-application feedback message to immediately before the settings compatibility preview.
- Added a highlighted accessible alert (`role=status`, `aria-live=polite`) so teachers receive immediate contextual feedback after applying a template.

## 1.2.9-beta

- Replaced management navigation links styled as buttons with real Bootstrap `<button>` elements.
- Removed custom line-height, transform, padding and flex overrides that interfered with native vertical text alignment.
- Kept navigation accessible through the plugin AMD module using `data-url`.
- Apply button now also uses native Bootstrap button metrics without an inner transformed span.

## 1.2.8-beta

- Corrige o alinhamento óptico vertical dos textos dos botões de criação, gerenciamento e aplicação de modelos.
- Os rótulos dos botões passam a usar um elemento interno próprio, centralizado por Flexbox.
- Adiciona pequena compensação óptica baseada em `em`, preservando zoom, quebra de linha e responsividade.
- Mantém altura mínima e padding uniformes sem impor altura fixa aos controles.

## 1.2.7-beta - 2026-09-08

- Equalises the vertical alignment of **Create template from saved settings** and **Manage my templates**, including when one label wraps to multiple lines.
- Replaces the verbose post-application list of skipped settings with a concise confirmation message.
- Keeps incompatibility details in the pre-application compatibility preview, avoiding duplicated information and reducing cognitive load.

## 1.2.6-beta - 2026-09-08

- Aligns the activity-form template area exactly with the requested Bootstrap `container` composition.
- Removes the plugin CSS `max-width: 100%` override that neutralised Bootstrap's responsive `.container` max-width.
- Keeps management actions at 50/50 on large screens, selection/application at 50/50, and the settings preview at 100% of the inner container.
- Preserves responsive stacking to 100% on smaller screens and existing accessibility semantics.

## 1.2.5-beta - 2026-09-08

### Two-column activity-form layout

- Uses a centered Bootstrap `container` for the teacher-facing template section.
- Places Create template and Manage my templates in equal 50/50 columns on large screens.
- Places the Model input group and Apply template action in equal 50/50 columns on large screens.
- Removes intentionally empty desktop columns from the previous layout.
- Keeps the compatibility/settings preview at 100% width below the controls.
- Stacks all controls to 100% width on smaller screens while preserving accessible labels, live regions and 44px minimum touch targets.

## 1.2.4-beta - 2026-09-08

### Responsive activity-form composition

- Rebuilds the teacher-facing template area as one scoped responsive Bootstrap grid inside Moodle's native activity form.
- Uses the full form-container width for introductory guidance and the compatibility/settings preview.
- Places Create template and Manage my templates in the first two thirds on large screens, leaving the final third intentionally clear.
- Places the Model input group in the first third and Apply template in the second third on large screens, leaving the final third clear.
- Stacks actionable controls to full width below the large breakpoint; reserved desktop columns are hidden on smaller screens.
- Keeps all action controls at a consistent minimum 44px touch target with equal padding while allowing text to reflow under zoom.
- Associates the visible Model label directly with the select control and adds accessible live regions for compatibility summaries and application status.
- Uses `container-fluid` rather than a nested fixed-width container so the plugin does not unnecessarily shrink Moodle's form content.
- Keeps the compatibility/settings area at 100% width and preserves keyboard focus visibility.

## 1.2.3-beta - 2026-09-08

### Activity form layout refinement

- Makes the introductory guidance and template compatibility preview use the full available form container width.
- Places Create template and Manage my templates actions in an equal 50/50 layout on medium and large screens.
- Places the Template label, template selector and Apply template button in equal thirds on medium and large screens.
- Standardises button height and padding across all template actions.
- Keeps the interface responsive by stacking action/control columns to 100% width on small screens.
- Ensures the configuration compatibility area uses 100% of its container width.

## 1.2.2-beta - 2026-09-08

- Adds a pre-application compatibility analysis directly below the template selector.
- Uses colour, icon and text together to distinguish applicable, condition-dependent and unavailable settings.
- Shows attention-required settings before compatible settings to reduce scanning effort.
- Adds summary counters for total, applicable, conditional and unavailable settings.
- Retries dependent settings once after Moodle form dependency rules react.
- Extends safe form filling to textareas and common text-like input types.

## 1.2.1-beta

- Fixed a Moodle 5.x TypeError caused by treating `PARAM_*` validation constants as integers.
- Updated select/number helper methods to accept Moodle validation parameter identifiers correctly.
- Fix applies globally to common settings and module-specific settings, including textual select values such as quiz behaviours, forum types, wiki formats and workshop strategies.

## 1.2.0-beta - 2026-09-08

### Curated settings across core Moodle activities

- Replaces raw automatic database-field discovery with curated teacher-facing allow-lists for core Moodle modules.
- Adds curated settings for Assignment, Forum, Choice, Database, Feedback, Folder, Book, Glossary, H5P activity, IMS content package, Lesson, Page, File, URL, Wiki, Workshop and SCORM, while retaining the curated Quiz implementation.
- Keeps complex/content-oriented core modules such as LTI, Text and media area, Survey and BigBlueButton deliberately conservative: common safe course-module settings remain available without exposing credentials, content or relational structures.
- Adds explicit handling of serialized display options used by Page, File and URL resources.
- Uses teacher-facing labels and localized option values in template management instead of raw database codes.
- Extends duration handling beyond Quiz so duration controls from other modules can be applied and previewed correctly.
- Existing templates for core modules are automatically normalised against the new safe allow-list, removing technical fields introduced by earlier generic discovery.
- Third-party module discovery remains available but is now conservative: only clearly reusable numeric/boolean fields with native Moodle labels are eligible.
- Common group settings are shown only when the module supports groups; view-completion is shown only when the module supports view tracking.

## 1.1.1-beta - 2026-09-08

### Curated Quiz settings

- Replaces automatic raw-field discovery for Quiz with a teacher-facing curated registry.
- Organises Quiz settings into Attempts and grading, Layout, Question behaviour, Submission handling, Review options, Appearance, Extra restrictions and Completion.
- Adds clear labels for `attemptonlast`, `canredoquestions`, browser security, network restriction, offline attempts, delays, grace period, pre-created attempts, appearance and Quiz completion rules.
- Removes `sumgrades` from templates because it is a calculated internal value.
- Raw `review*` bitmask fields are no longer exposed. Review settings are represented by the same four periods used by the native Quiz form.
- Existing templates containing legacy `review*` values are normalised at read time so they remain usable.
- Duration settings such as attempt delays and grace period are applied to Moodle duration controls and can be edited using number + time unit.
- Minimum-attempt completion automatically toggles its enable checkbox when a template is applied.

## 1.1.0-beta - 2026-09-07

### Renamed plugin

- Renamed the plugin from **Activity Presets** to **Activity Settings Templates**.
- New Moodle component: `local_activitysettingstemplates`.
- Portuguese public name: **Modelos de Configuração de Atividades**.
- Interface terminology now uses **template/modelo** instead of preset.

### All activity types

- The template section is now available in every installed Moodle `mod_*` activity/resource type.
- Templates are automatically filtered by activity type in the native settings form.
- Adds automatic discovery of simple, reusable scalar settings from each module's primary database table.
- Keeps curated Quiz and Assignment definitions from the previous development version.
- Adds common group/completion settings to all supported activity types.
- Supports third-party activity modules automatically when installed.

### Safety

- Automatic discovery excludes content, files, long text/blob fields, dates/times, relational IDs, identity fields and generic grade fields.
- Settings unavailable or locked in the destination form are skipped rather than forced.
- The application status continues to name every setting that could not be applied.
- Applying a template only fills the native Moodle form; the teacher must review and save normally.

### Management

- Keeps the activity-type column in the management table.
- Keeps template editing for name, description, included settings and stored values.
- Existing templates remain bound to their activity type.

### Migration

- Adds a best-effort install migration from the former `local_activitypresets` development table when it is still present.

- Build fix: internal Moodle plugin version raised to `2026090809` to allow upgrade from `2026090808`.
