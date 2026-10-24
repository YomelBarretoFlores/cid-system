# CILN resilience and observability (cross-cutting)

Objective: bound waits on the database, retry transactions that lose a deadlock, turn lock and statement timeouts into clear messages, trace every request through logs and the audit trail, and guarantee indexes and large lookups hold under volume.
Branch: `feat/resiliencia` (from `main` 8cb097d) · Delivery: one commit per unit on the feature branch, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Hazlo ahora, antes de seguir con la asistencia y las pre-actas"
- S2: "Sobre resilentia, atomicidad, transacciones, race tracing, índices en BD, etc También lo tomas en cuenta o lo tomarás en cuenta cuando sea conveniente?"
- S3 (the four gaps the user approved): "Tiempos límite en la base: no hay `lock_timeout` ni `statement_timeout`" / "Reintentos: no se reintenta ante un deadlock o un fallo de serialización" / "Logs: no hay registro de eventos de negocio (rechazos, esperas de bloqueo, errores)" / "Rendimiento real: no he medido consultas con `EXPLAIN` sobre volúmenes realistas [...] un rango con más de 65 535 pagos supera el límite de parámetros de PostgreSQL."
- S4 (DSTI 3.1): high availability, scalability, audit and traceability; project rules (`docs/seguridad.md`, `.claude/rules/database.md`: index every foreign key and every report filter column).

Assumptions (technical defaults, configurable by env vars, documented in `docs/despliegue.md`):
- D1: PostgreSQL session limits on every connection: `lock_timeout` 5 s, `statement_timeout` 30 s, `idle_in_transaction_session_timeout` 60 s.
- D2: Business transactions run through one shared helper that retries up to 3 attempts on deadlock or serialization failure (only the outermost transaction retries; nothing outside the database runs inside them), and turns a lock timeout or a statement timeout into a business message the panel and portal already show.
- D3: Each HTTP request gets an ID (reused from a valid incoming `X-Request-Id`, otherwise a new ULID), returned in the response header, added to every log line and stored in each audit row. Logs never carry personal data (Ley 29733): user ID, action and message only.
- D4: A test fails when any foreign key lacks an index that starts with it. Lookups by many IDs are split into chunks so no query exceeds PostgreSQL's 65,535 bind parameters.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S3, D1 | inline | pending | |
| T2 | S2, S3, D2 | inline | pending | |
| T3 | S2, S3, S4, D3 | inline | pending | |
| T4 | S2, S3, S4, D4 | inline | pending | |
| T5 | all | inline | pending | |

- T1: Session timeouts applied when a PostgreSQL connection opens. Risk: item 5 (environment).
- T2: `Transaccion` helper (retry, timeout messages, logs); every `DB::transaction` in `app/` moves to it; guard test; real lock-timeout concurrency test. Risk: item 4.
- T3: Request ID middleware, log context, response header, `aud_registros.solicitud_id` and `aud_registrar()` update; business rejections logged. Risk: item 1 (audit table and function).
- T4: Foreign-key index guard and fixes; chunked lookups in the batch contracts; `EXPLAIN` of the collection report recorded. Risk: item 6.
- T5: Docs, full suite, independent verifier, native review.

Forecast: about 900 authored lines in five commits.

## Log

- L1 (2026-10-24): "Hazlo ahora, antes de seguir con la asistencia y las pre-actas" (after "Sobre resilentia, atomicidad, transacciones, race tracing, índices en BD, etc [...]").
- L2: Map: 34 `DB::transaction` calls (Actions, three Filament pages, one concern, one seeder); Laravel's `attempts` argument already retries `deadlock detected` and SQLSTATE 40001 but not PostgreSQL's lock timeout (55P03) or statement timeout (57014). `RegistrarPago` stores the receipt before its transaction and deletes it on failure, so a retry repeats no file work. The PostgreSQL connector has no option for session settings, so they are set on `ConnectionEstablished`. Audit context is set per request with `set_config` in `EstablecerContextoAuditoria`; no request ID and no business logging exist.
