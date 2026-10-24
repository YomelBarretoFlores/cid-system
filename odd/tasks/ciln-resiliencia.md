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
- D2: Business transactions run through one shared helper that retries up to 3 attempts on deadlock or serialization failure (only the outermost transaction retries; nothing outside the database runs inside them; panel pages that change a record loaded before the transaction never retry), and turns a lock timeout or a statement timeout into a business message the panel and portal already show.
- D3: Each HTTP request gets an ID (reused from a valid incoming `X-Request-Id`, otherwise a new ULID), returned in the response header, added to every log line and stored in each audit row. Logs never carry personal data (Ley 29733): user ID, action and message only.
- D4: A test fails when any foreign key lacks an index that starts with it. Lookups by many IDs are split into chunks so no query exceeds PostgreSQL's 65,535 bind parameters.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S3, D1 | inline | done | ecdf075, b6b80cd, d64d12a |
| T2 | S2, S3, D2 | inline | done | 5a9a3ea, aaa4845 |
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
- L3: T1 inline: RED 2/2 → GREEN. First version set the limits on `ConnectionEstablished` (ecdf075); review-300d6c77bd41b6f9 approved with warnings (lost on reconnect, read PDO only, three round trips, migrations capped, typo breaks every connection). b6b80cd moved the limits into the DSN `options` through a custom `db.connector.pgsql` (`ConectorPostgres`): survives reconnects, covers read and write PDOs, no extra round trip; review-8ac15f5075f94e21 approved with warnings (console exemption too broad, malformed value failed open, units, PgBouncer). d64d12a: only `migrate*`, `db:seed`, `schema:dump` lift the statement cap; malformed values fall back to the safe default and are logged once per process; all PostgreSQL units; docs/despliegue.md "Límites de sesión" with the pooler note. review-2bf97bda45b012e9 approved and acknowledged. `composer check` 887/887.
- L4: Follow-ups from the d64d12a review, to do in T5: detect the command name after global options (`artisan --env=x migrate`) instead of `argv[1]`; reset the once-per-process log flag between tests; derive the malformed-value fallback from the configured defaults; document that a migration started through a nested `Artisan::call` or a long queued job keeps the 30 s cap and can lift it with `SET LOCAL statement_timeout = 0` inside its transaction. Accepted on purpose: queue workers and the scheduler keep the 30 s cap (no long job exists yet).
- L5: T2 inline: RED (helper missing; guard listed 34 direct transactions) → GREEN. `Transaccion::ejecutar` retries 40P01/40001 up to 3 times at the outermost level with a small backoff, catches Laravel's `DeadlockException` from nested transactions, turns 55P03 and 57014 (and exhausted retries) into business messages, and logs retries and rejections; all 34 `DB::transaction` calls moved to it. Real-process tests: a deliberate deadlock is retried and both transactions complete; a lock wait past `lock_timeout` returns the message. Mutations (no retry, no 55P03 translation) fail their tests. `composer check` 896/896 (5a9a3ea). Review review-6629416f1e34cec9 approved and acknowledged; its warning was a real defect: retrying a closure over a record loaded before the transaction could save nothing and still report success. aaa4845: `reintentar: false` for the six panel call sites, guard tests (no direct `transaction`/`beginTransaction`, no panel retry), 55P03 translated on the first attempt; 899/899. Review review-16535b39f675efa5 approved and acknowledged; not adopted: create pages could keep retries (they build a new model each time) — kept off for one simple rule in the panel. Event listeners: every domain event implements `ShouldDispatchAfterCommit`, so a rolled-back attempt dispatches nothing.
