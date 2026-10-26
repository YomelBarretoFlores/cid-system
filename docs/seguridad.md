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
| Personal del panel | Doble factor con aplicación autenticadora (MFA de Filament), que cada persona activa desde su perfil y que puede volverse obligatorio con `PANEL_DOBLE_FACTOR_OBLIGATORIO=true`, y cierre de la sesión del panel tras 30 minutos sin actividad (middleware `ProtegerSesionDelPanel`) |

## Autenticación

Sesiones con cookie, no JWT ([`adr/0004-sesiones-para-autenticacion.md`](adr/0004-sesiones-para-autenticacion.md)). Sanctum usa la cookie para la web propia y tokens solo para clientes externos de la API. La recuperación de contraseña usa `password_reset_tokens` (token de un solo uso por correo, como pide el M6).

API REST v1 (clientes externos):

- Token personal de Sanctum con `POST /api/v1/sesiones` (correo, contraseña y nombre del dispositivo). La verificación de credenciales es la misma Action del ingreso web (`AutenticarUsuario`): comparte sus límites, emite los eventos de intento y de fallo, y actualiza el hash de la contraseña si cambió el costo. El token vence a los 30 días, se revoca con `DELETE /api/v1/sesiones/actual` (solo el de la propia cuenta), ingresar de nuevo desde el mismo dispositivo reemplaza su token anterior y los vencidos se borran cada noche (`sanctum:prune-expired`). La descarga de PDF tiene un límite propio de 10 por minuto.
- Los servicios del estudiante y del docente exigen correo verificado (`correo.verificado`) y la ficha o el perfil correspondiente; cada uno devuelve solo los datos del dueño del token. El PDF de un documento ajeno o anulado responde 404.
- Límite de 60 pedidos por minuto por usuario o dirección (`throttle:api`); la verificación pública de documentos, 30. La documentación (`/docs/api`) solo se ve con acceso al panel fuera del entorno local.

Panel administrativo (`/admin`, personal con `admin-panel.access`):

- Ingreso en `/admin/login` con correo y contraseña, y además el código de 6 dígitos de una aplicación autenticadora (Google Authenticator, Microsoft Authenticator u otra compatible) en las cuentas que la activaron. Cada persona la activa desde su perfil con el botón "Configurar"; con `PANEL_DOBLE_FACTOR_OBLIGATORIO=true`, toda cuenta del panel debe configurarla antes de entrar (se cambia sin tocar código; si la configuración está en caché, hay que regenerarla con `php artisan config:cache`). Al activarla recibe 8 códigos de recuperación de un solo uso para cuando no tenga el teléfono. El secreto y los códigos se guardan cifrados con la clave de la aplicación (los códigos, además, con hash) y nunca se muestran de nuevo.
- El panel solo acepta sesiones abiertas por su propio ingreso, el único que pide el segundo factor a las cuentas que lo activaron: la marca de la sesión lleva la cuenta que ingresó. Una sesión abierta en el portal, recordada por una cookie ("recordarme") o de otra cuenta en el mismo navegador se cierra al llegar al panel, con el aviso "Vuelve a ingresar al panel desde su página de acceso.". Por eso el panel no ofrece "recordarme". Cada cierre forzado queda en el log con el ID de la cuenta y la causa (`sin_ingreso_del_panel` o `inactividad`). Todo cierre forzado, también el de inactividad, anula el "recordarme" de la cuenta en todos sus dispositivos, incluido el portal.
- La sesión del panel se cierra tras 30 minutos sin actividad (`SESSION_PANEL_LIFETIME`, mínimo 1), con el aviso "Tu sesión del panel se cerró por inactividad. Vuelve a ingresar.". Cuenta cada página y cada acción del panel; el panel no tiene consultas automáticas en segundo plano que mantengan viva una sesión inactiva.
- Buscador general (`Ctrl/⌘ + K`): cada sección aparece solo para quien puede verla. Las personas se buscan por inicio del documento o por apellidos y nombres, y solo las ve quien tiene `personas.ver`, que ya accede a esos datos en su pantalla; el resultado muestra el documento, el nombre y el vínculo con el CILN, como el listado de Personas (Ley N° 29733: no se exponen datos que esa persona no pueda ver).
- Página de perfil (menú de usuario → perfil): el nombre y el correo solo se leen (los registra el CILN); el cambio de contraseña pide la actual y cierra las demás sesiones de la cuenta. Desde ahí también se regeneran los códigos de recuperación.
- Si alguien pierde el teléfono y los códigos, la DSTI quita el segundo factor con `php artisan auth:reiniciar-doble-factor <correo>` y la persona lo configura de nuevo al ingresar (configurarlo pide la contraseña actual). Por si el teléfono fue robado, el comando también anula el "recordarme" de la cuenta y cierra sus sesiones abiertas, que se encuentran por cuenta solo con `SESSION_DRIVER=database`. El log registra el ID de la cuenta, no el correo.

  | Código de salida | Significado |
  |---|---|
  | `0` | Hecho: doble factor quitado (salvo con `--solo-sesiones`), "recordarme" anulado y sesiones cerradas |
  | `1` | No hay una cuenta con ese correo; no se cambió nada |
  | `2` | Lo demás está hecho, pero las sesiones siguen abiertas: si falló la conexión, volver a ejecutar con `--solo-sesiones` (anula el "recordarme" y cierra las sesiones sin quitar de nuevo un doble factor ya reconfigurado); con otro almacenamiento de sesiones, expiran solas |
- El doble factor protege el panel. La API v1 solo expone los datos propios de estudiantes y docentes, sin funciones administrativas.

Portal del estudiante (inicio de sesión diferenciado del panel administrativo, M6):

- Estudiantes y docentes ingresan en `/ingresar` con correo y contraseña (una sola cuenta). Al entrar, quien tiene perfil docente va al portal docente (con el menú del estudiante si también estudia) y el resto al portal del estudiante. Las entradas anteriores `/estudiante/ingresar` y `/docente/ingresar` redirigen allí (redirección temporal, para poder cambiarlo sin depender de la caché del navegador). El mensaje de error no revela si la cuenta existe. El correo se guarda siempre en minúsculas. Un visitante sin sesión que abre una página del portal va a `/ingresar` y, al entrar, vuelve a esa página. El personal ingresa al panel por separado, en `/admin/login`.
- Autorregistro en `/estudiante/registro` con documento y correo, con consentimiento expreso de la Ley N° 29733. Una ficha que el CILN ya registró solo se vincula si el correo coincide con el que figura en ella; si no coincide, o si el documento ya tiene cuenta, el formulario responde con un único mensaje, para no revelar cuál de los dos casos aplica.
- El portal exige el correo verificado (`verified`), una ficha de estudiante vinculada (middleware `estudiante`) y `auth.session`, que cierra las demás sesiones cuando cambia la contraseña.
- El portal del docente (`/docente`) exige además un perfil docente (middleware `docente`); cada página de grupo comprueba en cada solicitud que el grupo siga a cargo de ese docente (si no, 404), y las Actions lo vuelven a comprobar bajo el bloqueo del grupo.
- Cada estudiante ve solo sus pagos, recibos y matrículas: las consultas parten de su ficha, nunca de un ID recibido del navegador, y el recibo de otra persona responde 404.
- Los correos de verificación y de recuperación se envían en español; el enlace de recuperación vence a los 60 minutos.

## Autorización

- Permisos por acción con `spatie/laravel-permission`, creados por migraciones (también `admin-panel.access`), nunca solo por seeders: así existen en producción.
- Cada recurso del panel tiene su Policy. El panel corre con `strictAuthorization()`: si falta un método en la Policy, Filament lanza un error en vez de permitir la acción (por defecto la permitiría).
- Cada Policy usa el trait `DeniegaLoNoConcedido`, que niega toda habilidad que no concede de forma explícita (editar, borrar, restaurar, duplicar, asociar...).
- Permisos existentes: `pagos.*` (ver, registrar, validar, anular, configurar, reportar), `academico.ver`, `academico.gestionar`, `personas.ver`, `personas.gestionar`, `matricula.ver`, `matricula.gestionar`, `clasificacion.ver`, `clasificacion.evaluar`, `docencia.ver`, `docencia.gestionar`, `docencia.costos`, `docencia.reportar`, `certificacion.actas`, `certificacion.documentos`, `tramites.gestionar`, `tramites.configurar`, `tablero.ver` y `admin-panel.access`.
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
- **Bloqueo de actas finales en la base**: `cer_actas_bloqueo` rechaza cambios en los datos de un acta cerrada, su borrado y reabrirla sin una rectificación abierta; `cer_notas_finales_bloqueo` rechaza agregar, cambiar, mover a otra acta o borrar notas de un acta cerrada; `cer_rectificaciones_bloqueo` impide editar o borrar una rectificación (solo se registra cuándo se volvió a cerrar el acta).
- **Trámites en la base**: `tra_tramites_bloqueo` impide borrar un trámite, cambiar su solicitud (persona, tipo, etapas copiadas, datos) y modificar uno finalizado; `tra_movimientos` (historial) no admite cambios ni borrados y `tra_notificaciones` no admite borrados; las tres rechazan `TRUNCATE` con filas. Las acciones del personal comprueban `tramites.gestionar`, y la acción final, el permiso del módulo que la ejecuta.
- **Tablero gerencial**: solo lee y muestra cifras agregadas; no presenta nombres ni documentos de estudiantes (los pagos por validar se listan por concepto, monto y fechas). En la hoja de cálculo exportada todo texto se escribe como texto, así un nombre que empiece con «=» no se convierte en fórmula.
- **Documentos emitidos inmutables**: `cer_documentos_bloqueo` rechaza editar o borrar una constancia o un certificado; solo admite anularlo una vez, con motivo. Un índice único parcial impide dos certificados vigentes del mismo nivel e idioma para una persona. Emitir y anular exigen el permiso `certificacion.documentos` también dentro de las Actions. Las tablas `cer_actas`, `cer_notas_finales`, `cer_rectificaciones` y `cer_documentos` rechazan `TRUNCATE` cuando tienen filas (los tests de concurrencia levantan ese bloqueo solo durante su limpieza). La autorización de una rectificación exige el permiso `certificacion.actas` también dentro de la Action.

Tablas auditadas además de pagos: tarifas, conceptos, `mat_matriculas`, `mat_movimientos`, `mat_solicitudes_clasificacion`, `mat_habilitaciones`, `doc_docentes`, `doc_docente_idiomas`, `doc_disponibilidades`, `doc_cargas_academicas`, `doc_clases_dictadas`, `doc_asistencias`, `doc_notas_parciales`, `doc_costos_hora`, `cer_actas`, `cer_notas_finales`, `cer_rectificaciones` y `cer_documentos` (la cuenta bancaria queda cifrada también en la auditoría); los movimientos (traslados y retiros) rechazan UPDATE, DELETE y TRUNCATE con filas.

Tests: `tests/Feature/Audit/AuditTrailTest.php` (inmutabilidad, contexto web/API, cambios por query builder), `tests/Modules/Pagos/PagoTest.php` (validación auditada) y `tests/Modules/Matricula/PermisosYAuditoriaTest.php`.

## Ley N° 29733 (datos personales)

- Guardar solo los datos necesarios y definir su tiempo de conservación.
- Cifrar con el cast `encrypted` los datos sensibles que no se buscan (por ejemplo, la cuenta bancaria del docente). **No cifrar el DNI**, porque se busca por él.
- La verificación pública de constancias y certificados (`/verificar/{código}`) muestra solo los datos mínimos: tipo, código, fecha de emisión, nombres, documento de identidad con solo sus últimos cuatro caracteres, idioma, nivel, horas, nota final y si está vigente o anulado (nunca el motivo de anulación). Limita las consultas a 30 por minuto por IP y pide a los buscadores no indexarla (`X-Robots-Tag` y meta `robots`). El código único tiene 10 caracteres aleatorios, así que no se puede adivinar ni recorrer.
- La ficha de matrícula se puede volver a abrir sin sesión desde su QR o su dirección (`/fichas/{código}`, ULID de 26 caracteres guardado en `mat_fichas`): es la misma ficha en PDF, con el documento de identidad enmascarado (solo los últimos 4 caracteres) y sin el detalle del pago (concepto, número de operación, medio y fecha) ni el enlace de la clase virtual, limitada a 30 consultas por minuto y con `X-Robots-Tag: noindex, nofollow`. Muestra nombres y lo matriculado, como la ficha del SGA de la UNASAM (supuesto por confirmar, `decisiones-pendientes.md` #96).
- El registro de correos de trámites guarda destinatario, asunto, fecha y estado de entrega, nunca el contenido; un envío fallido guarda solo la clase del error. El estudiante descarga solo sus documentos vigentes (`/estudiante/documentos/{id}/pdf`); cualquier otro responde 404.
- La portada pública (`/`) no muestra datos personales: la oferta usa un objeto propio (`GrupoOfertado`) sin aula, enlace virtual ni docente, y los costos salen de los conceptos y tarifas vigentes. Es la única página pública indexable.
- **Nunca usar datos personales reales en tests ni en staging.**

## Pruebas de seguridad

OWASP ZAP y k6 se ejecutan contra **staging** y solo con **autorización escrita de la DSTI**. El reporte de ZAP es evidencia para la conformidad.
