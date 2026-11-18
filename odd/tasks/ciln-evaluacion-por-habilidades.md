# CILN evaluation by skills and exams, without syllabus

Objective: remove the syllabus feature entirely and grade with the CILN system: simple average of the four language skills, simple average of the partial and final exams, final grade = simple average of both, rounded half up to an integer only at the end.
Branch: `feat/evaluacion-por-habilidades` (from main 56e3102) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`, `composer check`).

## Specs

- S1 (user): "No hay sílabo, entonces tenemos que eliminar todo lo referente al sílabo." Scope: every syllabus model, action, contract, data, page, view, PDF, route, button, seeder, test, README and doc mention (Docencia, Matricula, Shared, docs). The `doc_silabos`, `doc_silabo_semanas`, `doc_silabo_evaluaciones` tables are dropped by a NEW migration (v1.0.0 is published, past migrations are not edited); its `down()` recreates them. The TdR (docs/requisitos) never asks for a syllabus.
- S2 (user): "Se evalúan las 4 habilidades de un idioma, de esas 4 habilidades se saca un promedio, luego se da examen parcial y final, de esos exámenes se saca otro promedio. Luego los promedios de las habilidades y exámenes se promedian y eso sería su nota final." Skills: comprensión auditiva, expresión oral, comprensión lectora, expresión escrita.
- S3 (user): "con decimales y solo redondea la nota final." "no hay pesos para la calificación, es promedio simple". "Sí, por ejemplo de 13.5 se pasa a 14, 10.5 a 11 y así. 13.6 a 14, etc." → intermediate averages keep decimals (shown with two decimals, never used rounded); the final grade is the exact value rounded half up to an integer (stored as e.g. `14.00`); no weights anywhere.
- S4 (existing rules kept): a blank component counts as zero; no grade at all is NSP; aprobado when final >= the scheme's `nota_minima_aprobatoria` (configurable per scheme in "Esquemas de evaluación", demo 11, maximum 20).
- S5 (assumption): schemes stay configurable; each component belongs to a group, `habilidades` or `examenes`; a scheme needs at least one component in each group. The weight column, its check and the "weights sum 100" trigger are removed by a new migration that backfills the group (`parcial`/`final` codes → examenes, others → habilidades). The demo scheme becomes the six CILN components. Grade sheets, pre-acta, acta, boleta and record show the two intermediate averages where they show components.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1 | delegated (context backstop, ~90 files) | done | (this commit) |
| T2 | S2–S5 | delegated (context backstop) | done | (commit after b5d4da6) |

## Log

- L1 (2026-11-17, user): "Bien, ahora hay cambios por hacer. No hay sílabo, entonces tenemos que eliminar todo lo referente al sílabo. Sobre el tema de evaluación se hace de esta manera: Se evalúan las 4 habilidades de un idioma(las conoces?), de esas 4 habilidades se saca un promedio, luego se da examen parcial y final, de esos exámenes se saaca otro promedio. Luego los promedios de las habilidades y exámenes se promedian y eso sería su nota final."
- L2 (user): "con decimales y solo redondea la nota final. Y como puedes ver no hay pesos para la calificación, es promedio simple. Y además, cuál es la nota mínima y máxima para aprobar? Hay una sección de configuración o está ya asignada?" Answer: configurable per scheme in the admin panel.
- L3 (user): "Sí, por ejemplo de 13.5 se pasa a 14, 10.5 a 11 y así. 13.6 a 14, etc."
- L4: T1 before T2 (they share seeders and docs).
- L5: T1 done: 41 files deleted, drop migration 2026_11_17_090000 (up/down verified on dev DB), composer check green except one replay test fixed and re-run (55/55 focused).
- L6: T2 done. RED 12 errors → GREEN 12/12 (PromedioFinalTest). Independent verifier: composer check exit 0, 2224 tests; S2–S5 PASS. Finding: rectifying an acta closed with weights recomputes all its rows with the new rule (the reader maps participación to skills). Not a blocker now: the system is not yet deployed, so only demo actas exist; open question for the user if real weighted actas ever appear. Browser check of the screens pending.
- L7 (2026-11-18): browser review after merging main (v1.0.1): scheme form, pre-acta, admin and teacher acta, student progress verified with fresh demo data; final state shown as plain text (badge was truncated). composer check 2228/2228.
