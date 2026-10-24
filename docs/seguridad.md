# Seguridad

Responde al Anexo II.2 (OWASP Top 10, Ley N° 29733, auditoría inmutable) y a la sección 1.2 del Informe de la DSTI.

## Controles

| Riesgo | Control |
|---|---|
| Inyección SQL | Eloquent / query builder con bindings; nunca concatenar entrada del usuario en SQL crudo |
| XSS | Escapado de Blade (`{{ }}`); `{!! !!}` prohibido con datos del usuario; cabeceras CSP |
| CSRF | Middleware `PreventRequestForgery` de Laravel 13 (token + verificación de origen) |
| Sesiones | Cookies `HttpOnly`, `secure`, `SameSite=lax`; regeneración al iniciar sesión; `SESSION_DRIVER=database` |
| Fuerza bruta | Rate limiting en login, recuperación de contraseña y verificación pública de QR |
| Contraseñas | `Password::defaults()` con longitud mínima y `uncompromised()` |
| Autorización | Una Policy por acción sensible; acceso al panel solo con el permiso `admin-panel.access` |
| IDOR | IDs públicos con ULID; Policies en cada recurso |
| Archivos subidos | Validar tipo real por contenido; guardar fuera de `public/`; servir por controlador con Policy |
| Exposición de información | `APP_DEBUG=false` en producción, páginas de error propias, sin trazas |
| Dependencias | `composer audit` y `npm audit` en CI |
| Administradores y cajeros | Doble factor (MFA de Filament) y expiración por inactividad más corta (middleware propio) |

## Autenticación

Sesiones con cookie, no JWT ([`adr/0004-sesiones-para-autenticacion.md`](adr/0004-sesiones-para-autenticacion.md)). Sanctum usa la cookie para la web propia y tokens solo para clientes externos de la API. La recuperación de contraseña usa `password_reset_tokens` (token de un solo uso por correo, como pide el M6).

## Auditoría inmutable (Log Triggers)

El Anexo exige pistas **inmutables** para operaciones críticas (cambio de notas, validación de pagos, modificación de actas) con fecha, hora, IP, ID de usuario y detalle.

| Pieza | Diseño |
|---|---|
| Fuente oficial | Triggers de PostgreSQL en las tablas críticas, que escriben en una tabla de auditoría |
| Inmutabilidad | Trigger que rechaza `UPDATE` y `DELETE` sobre la tabla de auditoría; el usuario de base de datos de la aplicación solo tiene `INSERT` y `SELECT` |
| Usuario e IP | Laravel los fija en cada petición con `set_config` (sesión de PostgreSQL) y el trigger los lee |
| Cobertura | Captura también cambios hechos con query builder o SQL directo (los paquetes solo ven eventos de Eloquent) |
| Complemento | `spatie/laravel-activitylog` para mostrar contexto de negocio en pantalla |

Implementación (`app/Shared/Audit`):

| Pieza | Dónde |
|---|---|
| Tabla y triggers | migración `2026_10_24_125000_create_aud_registros_table` (`aud_registrar()`, `aud_impedir_cambios()`) |
| Contexto de la petición | middleware `EstablecerContextoAuditoria` en los grupos `web` y `api` y en el panel de Filament; usa `ContextoAuditoria` |
| Tablas auditadas | `pag_pagos` (después: notas y actas) |

Reglas de uso:
- **Jobs y comandos** que modifiquen tablas críticas deben llamar a `ContextoAuditoria::establecer($usuarioId, null)` al empezar y `limpiar()` al terminar: un worker reutiliza la conexión entre jobs.
- **Producción**: migrar con un rol dueño del esquema y conectar la aplicación con otro rol que solo tenga `INSERT` y `SELECT` sobre `aud_registros`. Los triggers protegen incluso al dueño; solo un superusuario podría desactivarlos.
- Las pruebas con `DatabaseTruncation` deben excluir `aud_registros`.

Tests: `tests/Feature/Audit/AuditTrailTest.php` (inmutabilidad, contexto web/API, cambios por query builder) y `tests/Modules/Pagos/PagoTest.php` (validación auditada).

## Ley N° 29733 (datos personales)

- Guardar solo los datos necesarios y definir su tiempo de conservación.
- Cifrar con el cast `encrypted` los datos sensibles que no se buscan (por ejemplo, la cuenta bancaria del docente). **No cifrar el DNI**, porque se busca por él.
- La verificación pública de certificados muestra solo los datos mínimos.
- **Nunca usar datos personales reales en tests ni en staging.**

## Pruebas de seguridad

OWASP ZAP y k6 se ejecutan contra **staging** y solo con **autorización escrita de la DSTI**. El reporte de ZAP es evidencia para la conformidad.
