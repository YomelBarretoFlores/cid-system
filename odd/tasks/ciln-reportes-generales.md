# CILN general reports: enrolled students, attendance, grades and teaching load per period

Objective: coordination gets the general reports the CILN asked for, filtered by period and exported to a formal PDF and to Excel. This is delivery D6 of the approved plan.
Branch: `feat/reportes-generales` (stacked on `feat/documentos-formales`). Delivery: one commit per unit; the user decides push and merge. Test runner: Pest (`php artisan test`).

## Specs

- S1 (user, L1): "Existe alguna sección de reportes generales?"
  - Plan D6: in the panel's "Reportes" group, with filters for period (required), language, level and group (optional), and exports to PDF (`x-pdf.reporte`, SGA style) and Excel.
- S2 (plan): **Matriculados**, by period and group.
  - Shows condition, state and the student's link to UNASAM.
  - Includes a summary per group.
- S3 (plan): **Asistencia por grupo**.
  - Per student: percentage over held classes and over programmed classes.
  - Per group: classes confirmed and classes observed.
- S4 (plan): **Notas por periodo**.
  - Per group: acta state, approved, failed, NSP and the group's average.
- S5 (plan): **Carga docente por periodo**.
  - Per teacher: groups, assigned hours and taught hours.
- S6 (plan): each report is closed without its permission (403).
  - Assumption: there is no `matricula.reportar`, so Matriculados uses `matricula.ver`. Asistencia and Carga docente use `docencia.reportar`; Notas uses `certificacion.actas`.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1, S2, S6 (shared base + Matriculados) | inline | done | see L3 |
| T2 | S3, S4, S5, S6 (the other three reports) | inline | done | see L3 |

## Log

- L1 (2026-10-31): "Existe alguna sección de reportes generales?" (from the user's requirement list). Plan D6 approved.
- L2 (2026-11-01): mapping from one read-only explorer.
  - The reports live in Tablero, the read-only BI module that already queries other modules' tables. This lets them reuse `HojaDelTablero`, `PeriodoPorDefecto` and `GruposDelPeriodo`, which the architecture rule keeps out of other modules.
  - Programmed classes follow `ClasesDelHorario::delPeriodo`. Assigned hours come from `CargaHoraria::de` (Docencia Data, allowed).
  - A test checks that the attendance percentages equal those of the `AsistenciaDeMatricula` contract.
  - Route: inline. There is no writer reason: the units depend on the shared base.
- L3 (2026-11-01): T1 and T2 done, in one commit, because the four reports share the base.
  - Data: `FiltrosDeReporte`, and `ReporteGeneral` made of `SeccionDeReporte` and `ColumnaDeReporte` (with `TipoDeColumna`).
  - Support:
    - `AlcanceDelReporte` gives the period, the groups (not cancelled, filtered) and the printed scope;
    - `ClasesProgramadas` and `CifrasDelReporte` hold the shared counts;
    - `LibroDelReporte` builds one Excel sheet per section through `HojaDelTablero`.
  - One trait, `ReporteGeneralDelPanel`, holds the filters, the view and the PDF/Excel downloads.
  - The pages are ReporteDeMatriculados (sort 2), ReporteDeAsistencia (3), ReporteDeNotas (4) and ReporteDeCargaDocente (5).
  - Mistake caught by Larastan: I overwrote the existing `Tablero/Support/Porcentaje.php`. It was restored from git, and the new helper is `CifrasDelReporte`.
  - RED: the 11 new tests failed first. Tablero, Feature and Architecture: 359 pass. `composer check` rc=0, 1908 tests.
  - A real PDF was rendered and read back: the formal SGA layout, with both sections.
- L4 (2026-11-01): native review of dd1126f..bcb195d.
  - Escalated with item 2, because the pages decide who sees students' data. Lenses: risk and reliability. Lineage review-d830e0660b1667eb.
  - The user granted it; it was approved and acknowledged.
  - Applied after RED tests:
    - the teaching load is grouped by teacher, not by name, so two teachers who share a name stay apart;
    - the group list follows the chosen period, language and level, and a change of language or level clears the chosen group;
    - the filter test now covers period, language, level and group;
    - the Excel test asserts a numeric cell.
  - Refuted: "several actas per group", because `cer_actas.grupo_id` has a unique index.
  - Left for later: the per-group queries of the attendance report. They are fine for a period's groups, and caching can come if volume demands it.

  D6 is complete.

