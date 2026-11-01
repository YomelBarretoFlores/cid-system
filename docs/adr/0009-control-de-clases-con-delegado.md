# ADR 0009: Control de clases con dos llamados y confirmación del delegado

- Estado: aceptado por el equipo (2026-10-31), a partir de la práctica del SGA descrita por el usuario. Las ventanas y las reglas abiertas figuran como supuestos en [`decisiones-pendientes.md`](../decisiones-pendientes.md) (#98 a #103).

## Contexto

En el SGA de la UNASAM el docente llama lista dos veces, al inicio y al final de la clase, y al final el estudiante delegado del curso valida esa asistencia con sus credenciales. El objetivo es que quede constancia de que el docente dictó la clase, no solo de qué estudiantes asistieron. El Anexo pide el llamado de asistencia durante las clases y el expediente de horas dictadas; el pago y los descuentos al docente son competencia de la DGF.

Hasta ahora el sistema guardaba un solo llamado por clase, que el docente podía registrar o corregir en cualquier momento del periodo. Eso prueba la asistencia de los estudiantes, pero no que la clase se dictó en su horario.

## Decisión

| Tema | Decisión |
|---|---|
| Dos llamados | Llamado de inicio desde 10 minutos antes del inicio hasta el fin de la clase (Presente o Ausente). Llamado de cierre desde 15 minutos antes del fin hasta 30 minutos después (Presente o Ausente), solo para quienes estuvieron presentes al inicio y solo después del primer llamado. Las ventanas se configuran en `config/ciln.php` y se calculan en la hora de Lima |
| Asistencia efectiva | Ausente si el estudiante está ausente en el cierre (se retiró); si no, cuenta el estado del inicio |
| Tema de la sesión | El docente escribe el tema dictado; es obligatorio para tomar el llamado de cierre y en el registro posterior. La clase se enlaza con la semana del sílabo publicado que cubre su fecha, cuyo contenido se sugiere como tema. El delegado ve el tema al confirmar y puede observar la clase si no corresponde |
| Delegado | El docente designa al delegado del grupo entre sus estudiantes con matrícula activa (uno por grupo, se puede cambiar). Un delegado cuya matrícula deja de estar activa ya no es delegado |
| Confirmación | Al cerrar el segundo llamado la clase queda "por confirmar". El delegado responde desde su propio portal ("Sí, se dictó" o "No se dictó / observar", con comentario obligatorio), hasta 120 minutos después del fin y solo si estuvo presente en el cierre. Nadie escribe su contraseña en el equipo de otra persona ni se comparten credenciales |
| Estado calculado | "Sin confirmar" (venció el plazo sin respuesta) y "sin llamado de cierre" no se guardan: se calculan con la hora de la consulta, en PHP (`EstadoDeLaClase::de`) y en SQL para filtrar (`EstadoDeLaClase::filtrar`), con una prueba que exige que coincidan |
| Coordinación | Página "Control de clases" del panel, de solo lectura, con filtros por periodo, docente, grupo y estado. El expediente de horas muestra cuántas clases se confirmaron y cuántas no (observadas o sin confirmar) |

## Alternativas descartadas

- **El delegado escribe su contraseña en el equipo del docente**: reproduce el SGA, pero expone la credencial del estudiante a otra persona y no prueba nada que no pruebe su propia sesión.
- **Un código de un solo uso que el docente muestra en clase**: agrega un paso y no evita que el código se comparta fuera del aula.
- **Guardar "sin confirmar" con una tarea programada**: un estado guardado puede quedar desfasado si la tarea falla; calcularlo con la hora de la consulta siempre es exacto.

## Consecuencias

- Una clase pasada que no se registró en sus ventanas se registra después en un solo llamado y queda como "registro posterior"; nunca llega al delegado.
- Las clases guardadas antes de este cambio conservan su significado y se leen como "registro anterior" (registrada).
- Confirmadas y no confirmadas son informativas en el expediente de horas: no cambian las horas ni el monto. Los descuentos al docente por clases no dictadas se definirán más adelante y fuera del sistema, porque el Anexo deja la planilla a la DGF.
- Cambiar una ventana es editar `config/ciln.php`; las reglas, la página del docente, la del delegado y el filtro de coordinación leen los mismos valores.
