# CILN transfers and withdrawals (M1.4, admin)

Objective: let CILN staff move a student to another group or register an official withdrawal from the admin panel, with the reason and the authorizing user recorded, and consult enrollments with their movement history.
Branch: `feat/traslados-retiros` (stacked on `feat/documentos-matricula`) · Delivery: ask-on-risk, stacked branches, one commit per unit · Test runner: Pest (`php artisan test`).

## Specs

- S1: "continúa" (after "Next: M1.4 transfers and withdrawals (admin), then the placement test (M1.1)")
- S2 (Anexo M1.4): "Herramienta administrativa para cambiar a un alumno de grupo/horario por motivos excepcionales (cierre de grupo, cruce de horarios)." / "Registro de retiros oficiales del ciclo para evitar penalidades académicas." / "Datos de Movimiento: Grupo Origen, Grupo Destino, Fecha." / "Sustento: Motivo del traslado o retiro, Usuario administrativo que autoriza."
- S3 (project rules): Filament calls Actions; strict policies; lock rows inside transactions with a fixed order; financial records untouched; official audit trail by PostgreSQL triggers.

Assumptions (to confirm with the CILN):
- D1: Permissions `matricula.ver` (consult enrollments and movements) and `matricula.gestionar` (transfer and withdraw). The demo administrator gets both; the demo cashier gets `matricula.ver`.
- D2: A transfer moves an active enrollment to another group of the same period and cycle that is "Abierto" or "Cerrado" and has a vacancy, even outside the enrollment window (it is an administrative exception); the origin seat is freed. The enrollment keeps its payment, date and condition.
- D3: A withdrawal applies to an active enrollment of a group that is not cancelled or finished; it marks the enrollment "Retirada" with its date and frees the seat. The payment is neither refunded nor reusable.
- D4: Movements are never edited or deleted; enrollments and movements are audited by triggers.
- D5: The reason is required (at most 500 characters).

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S3, D1, D4 | inline | done | babb2f3, 3e9d89a |
| T2 | S2, S3, D2, D3, D5 | inline | done | d2b21fd, see L6 |
| T3 | S2, D1 | inline | pending | |
| T4 | all | inline | pending | |

- T1: `PermisoMatricula` enum and data migration, `MatriculaPolicy` (strict, `DeniegaLoNoConcedido`), demo grants, audit triggers on `mat_matriculas` and `mat_movimientos`, trigger that rejects UPDATE/DELETE/TRUNCATE on `mat_movimientos`. Risk: items 1 and 2.
- T2: Academico contracts `TrasladoDeVacante` and `LiberacionDeVacante`; Matricula Actions `TrasladarMatricula` and `RetirarMatricula`; lock order person (advisory, same key as `MatricularEstudiante`) → enrollment → groups by ascending id; two-process race tests. Risk: item 4.
- T3: Filament `MatriculaResource` (list with search by person, filters by period, group and state; view with group detail, movement history, staff download of the ficha) and the "Trasladar" and "Retirar" actions.
- T4: Docs, full suite, independent verifier, native reviews.

Forecast: about 1,300 authored lines in four commits.

## Log

- L1 (2026-10-24): "continúa"
- L2: Map: `mat_movimientos` already exists (tipo traslado/retiro, origen, destino, motivo, autorizado_por, efectuado_en, CHECKs); `mat_matriculas` has a partial unique index on active (persona, grupo); no audit triggers on Matricula tables; groups are cancelled only without active enrollments.
- L3: T1 inline: RED 5 of 5 → GREEN (babb2f3). Correction per `.claude/rules/database.md`: movements also reject TRUNCATE, except of an empty table, because the concurrency tests truncate the academic offer with CASCADE; so T2 race tests stay at the Academico contract level and create no movements. A full run showed one intermittent failure in `TarifaConcurrenteTest` (duplicate concept code): leftover concepts persist across concurrency runs and the factory had 1,000 codes; factory widened to 100,000.
- L4: Native review of 8dc87eb..3e9d89a (risk, reliability) granted, approved and acknowledged. Adopted: ordered and bound audit assertions, a test for truncating an empty movements table, concept codes with letters (6.7 million combinations). Not needed: cascade deletes (all Matricula foreign keys are RESTRICT).
- L5: T2 inline: RED 14 of 14 → GREEN; `MovimientoDeMatricula` holds the person advisory lock (same key 1, now also used by `MatricularEstudiante`), the active-enrollment row lock and the reason rules; Academico `MoverVacante` locks both groups in ascending id order, `LiberarVacante` refuses cancelled or finished groups. Races: last vacancy to one of two transfers, opposite transfers without deadlock; mutation (unordered locks) produced `40P01 deadlock detected` and failed the test. `composer check` 656/656.
- L6: Native review of c639cd0..d2b21fd (resilience, reliability) granted, approved and acknowledged (review-6708ab41a2192788). Adopted: a finished origin group refuses transfers like it refuses withdrawals (RED 1 → GREEN). Not adopted: a stale enrollment id raises ModelNotFound (404 in the panel) rather than a business error.
