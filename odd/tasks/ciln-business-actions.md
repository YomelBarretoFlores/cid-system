# CILN business actions: payments and enrollment

Objective: business rules on top of the data model: payment registration with receipt, validation, rejection and voiding; self-service enrollment with prerequisite, payment and vacancy checks.
Branch: `feat/business-actions` (stacked on `feat/pagos`) · Delivery: ask-on-risk, stacked branches (same chain as previous features) · Test runner: Pest (`php artisan test`).

## Specs

- S1: "el siguiente paso es la **lógica de negocio**: las Actions para validar y anular pagos, y la matrícula con reserva de vacante bajo bloqueo y validación de prerrequisito y pago. ¿Seguimos con eso?" → "Sí, pero primero compactaré la conversación." → "Bien, ahora sí sigamos."
- S2 (Anexo M2.1): "Validación del pago por el administrador o cajero del CILN, con carga del recibo en formato digital (JPG/PNG o PDF) como evidencia." / "Desbloqueo automático de los módulos condicionados (matrícula, constancias, solicitud de exámenes) una vez validado el pago." / "Evidencia y Trazabilidad: Recibo escaneado, Estado (Pendiente, Validado, Rechazado), Usuario que validó, Fecha de Validación."
- S3 (Anexo M1): "Bloqueos automáticos: el sistema impedirá la matrícula si el estudiante (a) no aprobó el ciclo anterior o no tiene el nivel requerido; (b) no cuenta con la validación de pago registrada en el Módulo de Gestión de Pagos; o (c) intenta matricularse en un grupo sin vacantes." / "Validación Académica: Condición (Regular, Repitente)." / "Vacantes Disponibles (en tiempo real)."
- S4 (recorded rules, `docs/modulos.md` M2 "Reglas para las Actions"): "verificar estado, que pertenezca a la persona, que el concepto corresponda al servicio (matrícula o examen) y que la tarifa sea del mismo concepto; impedir editar monto o número de operación de un pago validado; un pago desbloquea un solo servicio en total".
- S5 (`.claude/rules/actions.md`): final invokable Actions, `lockForUpdate` inside a transaction, idempotent retries, transitions through the enum, events after commit carrying IDs, cross-module calls only through `Contracts/` and `Data/`; concurrency proven with two real connections or processes.

Assumptions (to confirm with the CILN, recorded in `docs/decisiones-pendientes.md`):
- A1: Transitions: Pendiente → Validado or Rechazado; Validado → Anulado. Rejected and voided payments are final.
- A2: A rejected payment does not block its operation number: the student registers the payment again with a corrected receipt (uniqueness applies to non-rejected payments).
- A3: Enrollment requires a group in state Abierto, a period in state Matrícula abierta and today (America/Lima) inside the enrollment window.
- A4: Condition Repitente = the person already has a non-withdrawn enrollment in another group of the same cycle; otherwise Regular.
- A5: Voiding a payment already used by an enrollment does not withdraw the enrollment automatically; the `PagoAnulado` event leaves the reaction open.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1, S2, S4, S5 | inline | done | 3ef09d1, c0db423 |
| T2 | S2, S4, S5 | inline | done | 8ee3a10 |
| T3 | S1, S3, S4, S5 | inline | done | 8a192ac |
| T4 | S1 | inline | done | this slice |

- T1: `EstadoPago` transitions, shared business-rule exception, `ValidarPago`, `RechazarPago`, `AnularPago` with row lock and events; DB trigger rejecting forbidden transitions and edits of non-pending payments; uniqueness ignores rejected payments. Risk: item 1 (migration on payments) + item 4 (locks) → own commit.
- T2: `RegistrarPago`: receipt (JPG/PNG/PDF by content) stored on the private disk, applicable fee by concept, link and date (Personas contract for the link), file removed if the insert fails.
- T3: Contracts `Pagos\VerificacionDePago` and `Academico\ReservaDeVacante` + `ConsultaGruposDelCiclo`; `MatricularEstudiante` (payment lock → vacancy lock → prerequisite, duplicate and condition checks → enrollment → event). Concurrency test with two processes for the last vacancy. Risk: item 4 → own commit.
- T4: Docs (`modulos.md`, `arquitectura.md`, `decisiones-pendientes.md`).

Route evidence: tasks share the payment model, the enum and docs, and T3 depends on T1; no writer trigger (no parallel independent units), all inline.
Forecast: about 1,300 authored lines; one stacked branch, commits per task so each fits the review budget.

## Log

- L1 (2026-10-24): "Bien, ahora sí sigamos." (answer to S1's question after compaction).
- L2: T1 RED (32 of 56 Pagos tests failing: missing Actions, transitions, trigger) → GREEN; `composer check` 156/156. Dev DB `ciln`: migrate → rollback 1 → migrate; psql UPDATE of a validated payment's amount rejected by `pag_controlar_cambios()`.
- L3: Native review of 3ef09d1 (lenses risk, resilience, reliability): consent granted, approved and acknowledged (lineage review-05ac314888e84aeb). Advisories fixed in c0db423 (voiding fields frozen until the transition, rollback restores the full unique first, validated→rejected pinned at DB level, retry return value asserted; mutation check: removing the new trigger condition fails the test). Deferred: R1-001 no role check in the Actions (Policies when wired to UI, roles pending decision #2); R3 concurrency of validate vs reject not proven with two processes (the DB trigger refuses validado→rechazado, pinned by test).
- L4: T2 RED (receipt judged by name: fake upload reported the name's MIME; aborted test transaction after a duplicate) → GREEN with finfo on content and a savepoint; mutation (no file cleanup) fails the test; `composer check` 166/166. Assess 3ef09d1..8ee3a10: medium, under_budget (pending slice).
- L5: T3 RED (0 of 21: missing contracts, Actions, factory state) → GREEN 21/21; `composer check` 187/187 (566 assertions). Mutation: removing `lockForUpdate` from `ReservarVacante` makes the two-process test fail twice in a row (second process hits `aca_grupos_matriculados_check` instead of the business message); restored, 3 consecutive passes (~2.9 s each).
- L6: T4 docs: `modulos.md` (Actions and rules), `arquitectura.md` (contracts as invokable interfaces bound in `$bindings`, `ReglaDeNegocioIncumplida`), `modelo-de-datos.md` and `seguridad.md` (lifecycle trigger, partial unique), `testing.md` (Concurrency suite), `decisiones-pendientes.md` rows 13–17 (A1–A5). Independent verifier for items 1 and 4 launched over 9a3702a..8a192ac.
- L7: Independent verifier (items 1, 4) over 9a3702a..8a192ac: S1, S2, S4 met; S3/S5 met except blocker B1: the same person enrolling at the same time in two groups of the same cycle and period got two active enrollments (5 of 5 runs; the duplicate check read rows no lock covered). A1–A5 implemented as stated. Correction: RED two-process test `tests/Concurrency/MatriculaConcurrenteTest.php` (3 of 3 failing: both "matriculada") → per-person `pg_advisory_xact_lock` taken first in `MatricularEstudiante` → GREEN 3 of 3; `composer check` 188/188. Advisories kept as follow-ups: receipt file left if the caller's outer transaction rolls back after `RegistrarPago`; fee-concept consistency only enforced at registration (no composite FK) and amount not compared with the fee; trigger covers UPDATE only (a raw INSERT can create a validated payment); "belongs to another person" is checked before state; validate-vs-reject and one-payment-two-groups races verified by the verifier's probes but not committed as tests (DB trigger and `UNIQUE (pago_id)` back them).
