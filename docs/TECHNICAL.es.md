# Documentación técnica

## Componente

- Tipo de plugin: `local`
- Componente: `local_activitysettingstemplates`
- Directorio: `local/activitysettingstemplates`
- Versión pública: `1.0.0`
- Moodle mínimo: `4.5` (`2024100700`)
- Rango declarado: Moodle `4.5`–`5.2`

## Arquitectura

Activity Settings Templates amplía los formularios nativos de configuración de los módulos de curso; no los sustituye.

Los principales callbacks se encuentran en `lib.php`:

- `local_activitysettingstemplates_extend_navigation_course()` añade acceso a la gestión de plantillas personales.
- `local_activitysettingstemplates_coursemodule_standard_elements()` inserta el selector, las acciones, la vista previa de compatibilidad y la aplicación en los formularios de actividades/recursos.

Aplicar una plantilla modifica únicamente valores editables del formulario actual. Moodle conserva la validación y el guardado cuando el docente envía el formulario nativo.

## Modelo de datos

Las plantillas personales se almacenan en `local_ast_templates` con propietario, nombre, descripción, tipo de módulo, configuración JSON y marcas de tiempo.

El índice `userid, moduletype` facilita el filtrado por usuario y tipo de actividad.

## Capacidad

`local/activitysettingstemplates:manage`

- contexto de curso;
- capacidad de escritura;
- riesgo de configuración;
- habilitada por defecto para docente editor y manager;
- basada en `moodle/course:manageactivities`.

## Registro de configuraciones

`classes/local/field_registry.php` concentra las reglas para determinar qué configuraciones pueden reutilizarse.

Incluye:

- detección de módulos instalados;
- listas seleccionadas para módulos principales de Moodle;
- configuraciones comunes seguras;
- normalización de configuración almacenada;
- valores y etiquetas orientados al docente;
- controles para edición de plantillas;
- soporte de duraciones;
- soporte de determinadas opciones serializadas de recursos;
- descubrimiento conservador para módulos de terceros.

Las listas seleccionadas evitan exponer campos internos, calculados, relacionales, de contenido o credenciales.

## Ciclo de vida

- `create.php` + `create_template_form.php`: creación desde una actividad guardada.
- `index.php`: gestión de plantillas personales.
- `edit.php` + `edit_template_form.php`: edición de nombre, descripción, configuraciones incluidas y valores.
- `delete.php`: eliminación confirmada con `sesskey()`.

El tipo de actividad permanece fijo durante la edición.

## JavaScript

`amd/src/applytemplate.js` gestiona selección, vista previa, aplicación segura, eventos de dependencias, segundo intento de campos dependientes y retroalimentación accesible.

El módulo JavaScript nunca envía automáticamente el formulario de Moodle.

## Privacidad

`classes/privacy/provider.php` implementa la API de Privacidad de Moodle para metadatos, exportación y eliminación de datos personales de plantillas.

No se transmiten datos a servicios externos.

## Seguridad

La implementación utiliza autenticación, contextos de curso, comprobación de capacidades, limpieza de parámetros, Moodle Forms, `sesskey()` para acciones destructivas, comprobación de propiedad y listas seguras de configuraciones.

## Accesibilidad y diseño responsivo

- etiquetas asociadas a controles;
- botones nativos;
- funcionamiento por teclado;
- diseño 50/50 en pantallas grandes y apilado en pantallas pequeñas;
- color + icono + texto para estados;
- mensajes `role="status"` y `aria-live="polite"`;
- sin guardado automático.

Consulte [TESTING.md](TESTING.md) para el plan de validación.
