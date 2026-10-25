# CILN final grade sheets (M4.1)

Objective: the assigned teacher closes the final grade sheet (acta final) of a group within the period's grade calendar, with the weighted average and the final state computed from the pre-acta; a closed sheet is locked and only changes after an Administrador authorizes a rectification, all in the audit trail. M4.2 (constancias, certificados with QR) follows in its own feature document.
Branch: `feat/certificacion-actas-finales` (from `main` b3f353a) · Delivery: one commit per unit on the feature branch, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Bien, entonces sigamos con el M4 actas finales, constancias, certificados QR" (this document covers the actas finales; constancias and certificados go to M4.2).
- S2 (Anexo M4.1, functionality): "Interfaz exclusiva para el docente asignado, habilitada solo durante las fechas del calendario académico." / "Cálculo automático de promedios según el sistema de evaluación del CILN (Examen Parcial, Examen Final, Participación)." / "Cierre y bloqueo automático de actas validadas; toda modificación posterior requiere autorización del Administrador y queda en el log de auditoría."
- S3 (Anexo M4.1, fields): "Datos del Curso: Idioma, Nivel, Ciclo, Periodo Académico." / "Datos del Docente: ID, Nombres Completos." / "Calificaciones: ID Estudiante, Notas parciales, Promedio Final, Estado Final (Aprobado/Desaprobado/NSP)."
- S4 (Anexo, security): "Pistas de auditoría (Log Triggers) inmutables para operaciones críticas (cambio de notas, validación de pagos, modificación de actas), registrando fecha, hora, IP, ID de usuario y detalle de la operación."
- S5 (ADR 0007): the acta final stores a copy of the scheme used, the grades, the average and the final state; it takes the pre-acta grades as its starting point; Docencia exposes its partial grades through a contract.
- S6 (user rule, 2026-10-24): "Y ahora en adelante tomarás en cuenta esto de resilencia, etc." (Transaccion helper, lock order with race tests, indexes, masked rejection logs, audit).
- S7 (user rule, 2026-10-24): "llena de datos, pero solo en los flujos que ya completamos o cerramos. Luego cuando sigamos con la implementación de los flujos pues iremos agregando datos y se podrá visualizar."
- S8 (ADR 0008): screens use the design system tokens and `x-ciln.*` / `x-portal.*` components.

Assumptions (recorded in `docs/decisiones-pendientes.md`, to confirm with the CILN):

- D1: the grade calendar is a date range per period (`actas_inicio`, `actas_fin`) set by the administrator; the teacher validates only inside it. A rectification authorized by the administrator is not bound to the range.
- D2: before closing there is no stored draft: the teacher's page previews the acta from the pre-acta, and "Validar y cerrar" stores the snapshot (course, teacher, scheme, students, grades, average, state) already closed. Validation by the assigned teacher closes the acta; there is no separate approval step.
- D3: the average is the sum of grade × weight divided by the sum of weights, rounded half up to two decimals; a blank component counts as 0. Aprobado when the average is at least the scheme's passing grade; NSP when the student has no grade in any component.
- D4: only active enrollments enter the acta; withdrawn students do not.
- D5: a rectification is authorized by the administrator with a reason (5–500 characters); the teacher then edits the acta's own grades and validates again, which closes it. The pre-acta no longer feeds a closed acta.
- D6: a database trigger rejects grade changes on a closed acta, independently of the application.
- D7: permission `certificacion.actas` lets panel users see actas and authorize rectifications.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, D1 | inline | done | c185d1d |
| T2 | S5 | inline | done | 5b89207 |
| T3 | S2–S6, D2–D7 | inline | done | see L6 |
| T4 | S2, S3, S8, D2, D5 | inline | pending | |
| T5 | S2, S3, S8, D5, D7 | inline | pending | |
| T6 | all | inline | pending | |

- T1: Academico: grade calendar on periods (`actas_inicio`, `actas_fin`, both or none, start not after end), editable in the Periodo resource, and a contract with the course data of a group (language, level, cycle, teaching hours, period, calendar). Risk: item 1 (migration on an existing table).
- T2: Docencia contracts for the partial grades of a group and its assigned teacher; grade text parsing moved to `app/Shared` so both modules read grades the same way. Risk: item 3 (shared parser now also used by the pre-acta).
- T3: Certificacion core: `cer_actas`, `cer_notas_finales`, `cer_rectificaciones` with audit triggers and the closed-acta lock trigger; the average and state calculation; Actions to close an acta, authorize a rectification and rectify grades, under the group / acta lock; permission migration; race tests. Risk: items 1, 2, 4.
- T4: Teacher portal page "Acta final" (preview, validate and close, rectification editing, PDF of the acta) linked from "Mis grupos". Risk: item 2 (teacher-only access).
- T5: Panel resource "Actas finales" (filters by period and state, detail, authorize rectification with reason, PDF). Risk: item 2 (new permission).
- T6: Demo data (a past period with closed actas), docs (ADR 0007, arquitectura, seguridad, decisiones pendientes), full suite, independent verifier, native review.

Route evidence: each unit builds on the previous one (same contracts and tables), so there is no parallel writer reason; all inline. Forecast about 2,000 authored changed lines; one commit per unit, and units with a high-risk item get their own commit.

## Log

- L1 (2026-10-24, user): "Sobre los dos detalles ya lo podrás pulir más adelante. Bien, entonces sigamos con el M4 actas finales, constancias, certificados QR"
- L2: Mapping found no grade-calendar field on periods (D1), no partial-grades contract in Docencia (T2), `EstudiantesDelGrupo` returns only active enrollments (D4), `endroid/qr-code` 6.1 installed and unused (for M4.2), PDFs through spatie/laravel-pdf with DOMPDF and table layouts (ADR 0005).
- L3: T1 done (c185d1d): `aca_periodos.actas_inicio/actas_fin` with `aca_periodos_actas_check`, Periodo form fields, contract `CursoDelGrupo` → `DatosDelCurso::admiteActasEl`. RED observed (column missing), GREEN 209/209 Academico. Assessed medium, under budget.
- L4: T2 done (5b89207): `App\Shared\Support\Notas\Nota` (parser moved from `RegistrarNotasParciales`, pre-acta tests unchanged and green), contracts `NotasParcialesDelGrupo` and `DocenteDelGrupo`. RED observed (class missing), GREEN 210/210 Docencia + unit.
- L5: Slice T1–T2 escalated (item 1, migration on `aca_periodos`) and reviewed with risk + reliability lenses: approved and acknowledged (lineage review-7a9e8ebd0dba5f59). Advisories: `BuscarDocenteDelGrupo` uses `first()` without order, a non-issue because `doc_cargas_academicas.grupo_id` is unique; the calendar had no test for a moment in another time zone, added (UTC moments around Lima midnight).
- L6: T3 written implementation-first; tests then pinned it (37 Certificacion + 2 race tests), and a mutation that drops the half-up rounding fails the average test. The DB triggers reject grade changes, inserts or deletes on a closed acta, data changes on a closed acta and deleting an acta.
