# Buenas prácticas

Reglas de desarrollo del CILN. La regla de fondo: **desacoplar en las fronteras (entre módulos y con sistemas externos) y dejar simple lo de adentro de cada módulo**. Las herramientas del CI hacen cumplir lo que se puede automatizar.

## Código limpio

| Regla | Cómo se aplica | Lo verifica |
|---|---|---|
| Sin anidación excesiva | Guard clauses y early return; máximo ~2 niveles; Collections en lugar de `for` anidados | Complejidad cognitiva ≤ 8 por método |
| Archivos y clases cortos | Actions de una sola responsabilidad; apuntar a < 200 líneas por clase | Complejidad ≤ 40 por clase |
| Comentarios mínimos | Solo para un *por qué* no obvio (regla del CILN, restricción). Nada de bloques de varias líneas. PHPDoc solo donde el tipo no se puede expresar (array shapes) | Revisión |
| Tipado estricto | `declare(strict_types=1)`, tipos de parámetros y retorno explícitos | Pint, Larastan nivel 8 |
| Nombres descriptivos | Términos de negocio en español (`Matricula`, `ValidarPago`, `EstadoPago`); términos técnicos de Laravel en inglés | Revisión |

## Separación de responsabilidades

- **Controller → Form Request → Action → Model.** El controlador recibe y responde.
- **Actions**: `final`, invocables y con una sola regla de negocio (`ValidarPago`, `EmitirCertificado`). Las reutilizan web, API, Filament y consola.
- **Filament y Livewire llaman a Actions.** No se pone lógica en closures de Resources ni en componentes.
- **Modelos delgados**: relaciones, casts y scopes. `$fillable` explícito.
- **Estados en enums** con sus transiciones permitidas (`EstadoPago::Validado->puedeTransitarA(...)`).
- **DTOs solo en las fronteras entre módulos**, no entre cada capa interna.
- **Shared solo para lo transversal** (auth, auditoría, archivos, notificaciones). Sin carpetas `Utils`. Lo que usa un módulo, vive en ese módulo.

## Resiliencia

- **Nada externo dentro de la petición del usuario**: correo, PDF masivos y futuras integraciones van en jobs con `timeout`, reintentos con backoff y `failed()`.
- **Idempotencia**: revisar el estado antes de actuar; `ShouldBeUnique` evita duplicados en cola.
- **Transacciones** en toda operación con varios pasos (matrícula, validación de pago, cierre de acta).
- **Eventos después del commit** (`ShouldDispatchAfterCommit`).
- **Bloqueos correctos**: `Model::whereKey($id)->lockForUpdate()->firstOrFail()` dentro de la transacción. Nunca `$model->lockForUpdate()->refresh()`, que no bloquea.
- **Correlativos** (certificados): fila contador con `SELECT ... FOR UPDATE` en la misma transacción + `UNIQUE` en la base. No usar `Cache::lock`.
- **Degradación elegante**: si falla un servicio externo, el registro queda pendiente y se reintenta; el resto del sistema sigue.
- **Workers supervisados** (Supervisor/systemd), monitoreo de `failed_jobs` y health check `/up`.

## Datos e integridad

- PostgreSQL con llaves foráneas, `UNIQUE` y `CHECK` (por ejemplo, vacantes ≥ 0) como red de seguridad.
- Montos en `numeric(12,2)` o enteros en céntimos. **Nunca float.**
- Fechas en columnas `timestampTz`; se muestran en `America/Lima`.
- Tarifas TUPA/TUSNE y costo por hora docente en base de datos **con vigencia**; cada transacción guarda el monto aplicado.
- **Nada financiero se borra**: se anula con motivo y usuario.
- IDs públicos (URLs, QR) con ULID para evitar IDOR.

## Lo que NO se hace

- Repository pattern sobre Eloquent.
- CQRS, event sourcing o microservicios.
- Interfaces "por si acaso": solo para integraciones externas o dos implementaciones reales.
- Buscar portabilidad de base de datos: se usan a propósito funciones de PostgreSQL (triggers, vistas, `CHECK`, `timestamptz`).

## Mantenibilidad (pensando en la DSTI)

- API versionada en `/api/v1`, documentada con Scramble.
- ADRs en [`adr/`](adr/) para cada decisión importante.
- Tres entornos: local, staging y producción. Guía en [`despliegue.md`](despliegue.md).
- Commits convencionales y por unidad de trabajo, con sus tests.
