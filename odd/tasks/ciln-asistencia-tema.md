# CILN attendance: present or absent only, and the topic taught in each class

Objective: attendance has two states (present, absent) and every class records the topic taught, linked to the syllabus week when the group has a published syllabus. Delivery D1 of the approved plan.
Branch: `feat/asistencia-tema` · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1 (user, L1): "Y solo es presente y ausente. Elimina lo de tardanza, eso no se toma en cuenta."
  - Stored late arrivals become present. They already counted as attended, so no percentage changes.
  - The stored value `falta` stays and is shown as "Ausente".
  - The previous value stays in the audit table.
- S2 (user, L1): "en cada sesión o clase, en asistencia el docente debe de colocar el tema que ha dictado para esa clase, o sea, la sesión de la clase."
  - The topic is optional in the first call.
  - It is required in the closing call and in a registro posterior.
  - A later call keeps the saved topic unless the teacher changes it.
- S3 (plan): the class is linked to the week of the published syllabus that covers its date (`silabo_semana_id`). That week's content is suggested as the topic, and the teacher can edit it.
- S4 (plan): the topic is shown:
  - to the delegado when confirming the class;
  - in the student's attendance report (page and PDF);
  - in the coordination's Control de clases.

  Classes saved before this change, without a topic, are still read.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1 | inline | done | see L3 |
| T2 | S2, S3 | inline | done | see L3 |
| T3 | S4 | inline | done | see L4 |

## Log

- L1 (2026-10-31): "Sobre la asistencia, ya te comenté que se toma dos asistencias, al inicio y al final de la clase. Y que lo valida el estudiante delegado del curso. Además, en cada sesión o clase, en asistencia el docente debe de colocar el tema que ha dictado para esa clase, o sea, la sesión de la clase. Y solo es presente y ausente. Elimina lo de tardanza, eso no se toma en cuenta."
- L2 (2026-11-01): plan D1–D6 approved by the user. Route: inline. No writer trigger: one sequential unit, and the suite shares `ciln_testing`.
  - Decision: the topic rule lives in the Actions, not in a CHECK. A NOT VALID CHECK would still reject updates (e.g. the delegado confirming) of classes saved before the change without a topic.
- L3 (2026-11-01): T1 and T2 done in one commit, because they share the migration `2026_11_01_090000_keep_present_or_absent_and_add_class_topic`.
  - `EstadoAsistencia` keeps Presente and Falta (label "Ausente"). The migration turns stored tardanzas into presente and replaces the estado CHECK. Its test runs the migration down and up again and reads the audit row.
  - `TemaDeLaClase` normalises the topic (spaces collapsed, at most 1000 characters) and requires it in the closing call and in a registro posterior. It finds the week of the published syllabus for the date and suggests "unidad: saber".
  - `AsistenciaDelGrupo` has the "Tema dictado en la sesión" field, prefilled with the saved topic or the suggestion.
  - The demo seeders write the syllabus topic (`SilaboDeDemo::temaDeLaClase`).
  - Docs: #47 resolved, #105 adjusted, #113 new, ADR 0009, `modulos.md`.
  - RED: 13 of 16 new tests failed first. GREEN: Docencia, Matricula, Feature and Concurrency, 997 tests pass. Pint, Larastan and Rector are clean.
- L4 (2026-11-01): T3 done.
  - The topic travels in `AsistenciaDeClase` and `ClaseParaConfirmar`.
  - The delegado's page shows "Tema dictado", or "Sin tema registrado".
  - The attendance PDF has a Tema column ("—" for classes without one).
  - Control de clases has a "Tema dictado" column.
  - The reports page keeps its summary; the class detail stays in the PDF.
  - RED: 4 new assertions failed first.
  - `composer check` rc=0, 1814 tests.
- L5 (2026-11-01): checked with the demo data.
  - Seeded `main` into `ciln_preview` from a main worktree with its own vendor. 92 late arrivals were stored.
  - Migrated with this branch: all 92 became presente and 92 audit rows (tardanza → presente) were written. The attended count per enrollment (70 enrollments) is identical before and after.
  - Browser, teacher page at 1280 and 390: Presente and Ausente only, the "Tema dictado en la sesión" field, no page overflow, no JS errors.
  - Defect found: a closed class with no topic showed the syllabus suggestion as if it had been written. Fixed so the suggestion appears only while a call is open, and a closed class shows "Sin tema registrado". RED test first.
  - The attendance PDF shows the Tema column ("—" for demo classes recorded before this change).

