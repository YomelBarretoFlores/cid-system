# CILN academic load: no classroom or teacher clashes across periods

Objective: a classroom or a teacher is never booked twice at the same time, including across periods whose dates overlap and after a period's dates change, with the database as the last guard. Delivery D3 of the approved plan.
Branch: `feat/cruces-carga` (stacked on `feat/pagos-boleta-original`) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1 (user, L1): "verifica que por ejemplo un aula esté ocupada en cierto horario y se le asigne a otro grupo el mismo aula en ese horario o se solape."
  - Already enforced within one period.
  - Now also between groups of different periods whose dates overlap, in schedule lines and when a group changes classroom or period.
  - Message: "El aula A-101 ya está ocupada por el grupo FRA-01 el Viernes de 16:00 a 18:00."
- S2 (user, L1, "Cubre los huecos"): changing a period's dates re-checks its groups.
  - Classrooms, with the message "El aula A-101 ya está ocupada por el grupo FRA-01 el Viernes de 16:00 a 18:00, en un periodo que se cruza con las nuevas fechas del grupo ING-01."
  - Teachers, with the existing teacher clash message.
  - On a clash the dates do not change. This closes assumption #43.
- S3 (plan): the database refuses a classroom clash or a teacher clash written outside the Actions: a schedule line, a group's classroom, period or state, a period's dates, or a teacher assignment. The Action messages stay what users see; the database is the last guard.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1, S2 | inline | done | see L3 |
| T2 | S3 | inline | done | see L4 |

## Log

- L1 (2026-10-31): "Sobre la carga académica, veo que actualmente se arman grupos y que también hay aulas, eso está bien, pero verifica que por ejemplo un aula esté ocupada en cierto horario y se le asigne a otro grupo el mismo aula en ese horario o se solape." Later: "3. Cubre los huecos."
- L2 (2026-11-01): plan approved.
  - Route: inline. One unit per task; no writer trigger.
  - Existing state:
    - `GuardarHorario` and `GuardarGrupo` compare only the same `periodo_id`;
    - the teacher check (`ReglasDeCargaDocente::asegurarSinCruces`) already compares overlapping periods;
    - `EditPeriodo` saves without re-checking;
    - no database guard exists.
- L3 (2026-11-01): T1 done.
  - `Horario::ocupanAula` now takes the period and matches groups of any period whose dates overlap. `GuardarHorario` and `GuardarGrupo` pass the period.
  - New `VerificarCrucesDelPeriodo` locks the period's non-cancelled groups by id, then each classroom, and calls `VerificacionDeCargaDocente`. `EditPeriodo` runs it inside its transaction when the dates changed.
  - `GruposTest` "another period" now uses a non-overlapping April period.
  - Docs: #22 widened, #43 resolved, `modulos.md`.
  - RED: 8 of 10 new tests failed first. The remaining 4 failing are the database guards of T2.
- L4 (2026-11-01): T2 done.
  - Classroom guard: `aca_asegurar_aula_libre()` on `aca_horarios`, `aca_grupos` (aula, periodo, estado) and `aca_periodos` (dates).
  - Teacher guard: `doc_asegurar_docente_sin_cruces()` on `doc_cargas_academicas`, `aca_horarios`, `aca_grupos` (periodo, estado) and `aca_periodos`.
  - Both lock the classroom or the teacher like the Actions do.
  - Found in the first run: immediate triggers fired before the Actions' own checks, so `EditPeriodo` and `GuardarHorario` showed a database error instead of the message. The guards became `DEFERRABLE INITIALLY DEFERRED` constraint triggers, checked at commit.
  - The guard tests run `SET CONSTRAINTS ALL IMMEDIATE`, because the test transaction never commits.
  - `EditPeriodo` moved its body to `actualizar()` so the panel-transaction rule recognises `reintentar: false`.
  - Docs: `modelo-de-datos.md` constraint table (also the D2 voucher and fingerprint rows).
  - `composer check` rc=0, 1859 tests, including the teacher concurrency test.

