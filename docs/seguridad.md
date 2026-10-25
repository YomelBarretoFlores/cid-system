# Seguridad

Responde al Anexo II.2 (OWASP Top 10, Ley N° 29733, auditoría inmutable) y a la sección 1.2 del Informe de la DSTI.

## Controles

| Riesgo | Control |
|---|---|
| Inyección SQL | Eloquent / query builder con bindings; nunca concatenar entrada del usuario en SQL crudo |
| XSS | Escapado de Blade (`{{ }}`); `{!! !!}` prohibido con datos del usuario; cabeceras CSP |
| CSRF | Middleware `PreventRequestForgery` de Laravel 13 (token + verificación de origen) |
| Sesiones | Cookies `HttpOnly`, `secure`, `SameSite=lax`; regeneración al iniciar sesión; `SESSION_DRIVER=database` |
| Fuerza bruta | Rate limiting en login (5 intentos por minuto por correo e IP y 20 por IP), registro (20 por minuto por IP), recuperación de contraseña (5 por minuto por IP y un correo por minuto por cuenta), verificación de correo y verificación pública de QR |
| Contraseñas | `Password::defaults()`: al menos 8 caracteres con letras y números. No se usa `uncompromised()` porque consulta un servicio externo (pendiente de aprobación de la DSTI) |
| Autorización | Una Policy por acción sensible; acceso al panel solo con el permiso `admin-panel.access` |
| IDOR | IDs públicos con ULID; Policies en cada recurso |
| Archivos subidos | Validar tipo real por contenido; guardar fuera de `public/`; servir por controlador con Policy |
| Exposición de información | `APP_DEBUG=false` en producción, páginas de error propias, sin trazas |
| Dependencias | `composer audit` y `npm audit` en CI |
| Administradores y cajeros | Doble factor (MFA de Filament) y expiración por inactividad más corta (middleware propio) |

## Autenticación

Sesiones con cookie, no JWT ([`adr/0004-sesiones-para-autenticacion.md`](adr/0004-sesiones-para-autenticacion.md)). Sanctum usa la cookie para la web propia y tokens solo para clientes externos de la API. La recuperación de contraseña usa `password_reset_tokens` (token de un solo uso por correo, como pide el M6).

Portal del estudiante (inicio de sesión diferenciado del panel administrativo, M6):

- Ingreso en `/estudiante/ingresar` con correo y contraseña; el mensaje de error no revela si la cuenta existe. El correo se guarda siempre en minúsculas.
- Autorregistro en `/estudiante/registro` con documento y correo, con consentimiento expreso de la Ley N° 29733. Una ficha que el CILN ya registró solo se vincula si el correo coincide con el que figura en ella; si no coincide, o si el documento ya tiene cuenta, el formulario responde con un único mensaje, para no revelar cuál de los dos casos aplica.
- El portal exige el correo verificado (`verified`), una ficha de estudiante vinculada (middleware `estudiante`) y `auth.session`, que cierra las demás sesiones cuando cambia la contraseña.
- El portal del docente (`/docente`) exige además un perfil docente (middleware `docente`); cada página de grupo comprueba en cada solicitud que el grupo siga a cargo de ese docente (si no, 404), y las Actions lo vuelven a comprobar bajo el bloqueo del grupo.
- Cada estudiante ve solo sus pagos, recibos y matrículas: las consultas parten de su ficha, nunca de un ID recibido del navegador, y el recibo de otra persona responde 404.
- Los correos de verificación y de recuperación se envían en español; el enlace de recuperación vence a los 60 minutos.

## Autorización

- Permisos por acción con `spatie/laravel-permission`, creados por migraciones (también `admin-panel.access`), nunca solo por seeders: así existen en producción.
- Cada recurso del panel tiene su Policy. El panel corre con `strictAuthorization()`: si falta un método en la Policy, Filament lanza un error en vez de permitir la acción (por defecto la permitiría).
- Cada Policy usa el trait `DeniegaLoNoConcedido`, que niega toda habilidad que no concede de forma explícita (editar, borrar, restaurar, duplicar, asociar...).
- Permisos existentes: `pagos.*` (ver, registrar, validar, anular, configurar, reportar), `academico.ver`, `academico.gestionar`, `personas.ver`, `personas.gestionar`, `matricula.ver`, `matricula.gestionar`, `clasificacion.ver`, `clasificacion.evaluar`, `docencia.ver`, `docencia.gestionar`, `docencia.costos`, `docencia.reportar`, `certificacion.actas` y `admin-panel.access`.
- Los roles que agrupan permisos están pendientes ([`decisiones-pendientes.md`](decisiones-pendientes.md) #2). Usuarios de demostración: `admin@ciln.test` (todos los permisos de pagos) y `caja@ciln.test` (todo menos anular); en el portal, `estudiante@ciln.test`, con un pago de matrícula validado sin usar, y `docente@ciln.test`, docente del primer grupo.

## Auditoría inmutable (Log Triggers)

El Anexo exige pistas **inmutables** para operaciones críticas (cambio de notas, validación de pagos, modificación de actas) con fecha, hora, IP, ID de usuario y detalle.

| Pieza | Diseño |
|---|---|
| Fuente oficial | Triggers de PostgreSQL en las tablas críticas, que escriben en una tabla de auditoría |
| Logs | Los rechazos de negocio se registran con DNI, RUC, cuentas, teléfonos y correos enmascarados. Los mensajes de `ReglaDeNegocioIncumplida` no incluyen nombres de personas (no se pueden enmascarar con seguridad) |
| Trazabilidad | Cada fila guarda `solicitud_id`, el ULID de la solicitud HTTP que la escribió (vacío fuera de una solicitud), igual al de la cabecera `X-Request-Id` y al de los logs |
| Inmutabilidad | Triggers que rechazan `UPDATE`, `DELETE` y `TRUNCATE` sobre la tabla de auditoría. `aud_registrar()` es `SECURITY DEFINER` con `search_path` fijo (`pg_catalog, public, pg_temp`) y escribe en `public.aud_registros`, así que una tabla temporal no puede suplantarla y la aplicación no necesita permiso de escritura sobre la auditoría |
| Usuario e IP | Laravel los fija en cada petición con `set_config` (sesión de PostgreSQL) y el trigger los lee |
| Cobertura | Captura también cambios hechos con query builder o SQL directo (los paquetes solo ven eventos de Eloquent) |
| Complemento | `spatie/laravel-activitylog` para mostrar contexto de negocio en pantalla |

Implementación (`app/Shared/Audit`):

| Pieza | Dónde |
|---|---|
| Tabla y triggers | migración `2026_10_24_125000_create_aud_registros_table` (`aud_registrar()`, `aud_impedir_cambios()`) |
| Contexto de la petición | middleware `EstablecerContextoAuditoria` en los grupos `web` y `api` y en el panel de Filament; usa `ContextoAuditoria`. Las acciones del panel (peticiones de Livewire) pasan por el grupo `web`, así que también llevan el usuario |
| Tablas auditadas | `pag_pagos`, `pag_conceptos`, `pag_tarifas` (después: notas y actas) |

Reglas de uso:
- **Jobs y comandos** que modifiquen tablas críticas deben llamar a `ContextoAuditoria::establecer($usuarioId, null)` al empezar y `limpiar()` al terminar: un worker reutiliza la conexión entre jobs.
- **Producción**: un rol dueño del esquema ejecuta las migraciones y la aplicación se conecta con otro rol que **no es dueño** de ninguna tabla. Ese rol de aplicación no recibe `INSERT`, `UPDATE`, `DELETE` ni `TRUNCATE` sobre `aud_registros` (solo `SELECT` si debe consultarla). El dueño de una tabla puede desactivar sus triggers con `ALTER TABLE ... DISABLE TRIGGER`, por eso nunca debe ser el rol con el que corre la aplicación.
- El contexto (`app.usuario_id`, `app.ip`) lo fija la aplicación; quien tenga acceso SQL directo podría falsearlo. La protección real es que nadie fuera de la aplicación se conecte con el rol de aplicación.
- Las pruebas con `DatabaseTruncation` deben excluir `aud_registros` y `pag_pagos`, que rechazan `TRUNCATE`.
- **Ciclo de vida del pago en la base**: el trigger `pag_pagos_ciclo_de_vida` rechaza transiciones no permitidas y cambios en los datos de un pago validado, rechazado o anulado, aunque se hagan por SQL directo. Las Actions aplican las mismas reglas con mensajes para el usuario.
- **Bloqueo de actas finales en la base**: `cer_actas_bloqueo` rechaza cambios en los datos de un acta cerrada (solo puede reabrirse para una rectificación) y su borrado; `cer_notas_finales_bloqueo` rechaza agregar, cambiar o borrar notas de un acta cerrada. La autorización de una rectificación exige el permiso `certificacion.actas` también dentro de la Action.

Tablas auditadas además de pagos: tarifas, conceptos, `mat_matriculas`, `mat_movimientos`, `mat_solicitudes_clasificacion`, `mat_habilitaciones`, `doc_docentes`, `doc_docente_idiomas`, `doc_disponibilidades`, `doc_cargas_academicas`, `doc_clases_dictadas`, `doc_asistencias`, `doc_notas_parciales`, `doc_costos_hora`, `cer_actas`, `cer_notas_finales` y `cer_rectificaciones` (la cuenta bancaria queda cifrada también en la auditoría); los movimientos (traslados y retiros) rechazan UPDATE, DELETE y TRUNCATE con filas.

Tests: `tests/Feature/Audit/AuditTrailTest.php` (inmutabilidad, contexto web/API, cambios por query builder), `tests/Modules/Pagos/PagoTest.php` (validación auditada) y `tests/Modules/Matricula/PermisosYAuditoriaTest.php`.

## Ley N° 29733 (datos personales)

- Guardar solo los datos necesarios y definir su tiempo de conservación.
- Cifrar con el cast `encrypted` los datos sensibles que no se buscan (por ejemplo, la cuenta bancaria del docente). **No cifrar el DNI**, porque se busca por él.
- La verificación pública de certificados muestra solo los datos mínimos.
- **Nunca usar datos personales reales en tests ni en staging.**

## Pruebas de seguridad

OWASP ZAP y k6 se ejecutan contra **staging** y solo con **autorización escrita de la DSTI**. El reporte de ZAP es evidencia para la conformidad.
