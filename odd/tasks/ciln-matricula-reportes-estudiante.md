# CILN student reports per period (E4)

Objective: the student gets, per period, the reports the UNASAM SGA gives its students, in the CILN PDF format: grade progress, grade report (boleta), attendance, weekly schedule, and a per-language record with the cycles missing for the level certificate.
Branch: `feat/reportes-estudiante` (stacked on `feat/control-de-clases` 6a25287) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`). Plan: `~/.claude/plans/agile-orbiting-wreath.md` (E4).

## Specs

- S1 (user): "Sobre lo extra ya hay luz verde." The SGA shows students "sus horarios, malla curricular, avance de notas, asistencias, sílabos de los cursos, boleta de notas, ficha de matrícula, etc." (structure only was recorded; no personal SGA data is copied).
- S2 (plan E4): page `estudiante.reportes` in Matricula with a period selector (only periods where the student has an enrollment). Each report shows a summary on the page and a "PDF" button that opens in a new tab. Shared PDF layout `x-pdf.documento` (DOMPDF, tables only, per `.claude/rules/ui.md`).
- S3 (plan E4, avance de notas, per enrollment): component, weight, pre-acta grade and partial average, from a new Certificacion contract `AvanceDeNotas` that reuses `ActaPrevia` / `PromedioFinal` (no duplicated grade math). When the acta is closed, the final grades.
- S4 (plan E4, boleta, per period): group, language, cycle, hours, final grade and state; "acta abierta" while the acta is not closed. Source: `HistorialDeNotas` exposed through a contract.
- S5 (plan E4, asistencia, per enrollment): per class: date, time, start call, closing call, result (effective state, S3 of E3) and confirmation state; totals and percentages over classes **held** and over classes **programmed** (programmed from `ClasesDelHorario` over the whole period). New Docencia contract `AsistenciaDeMatricula`.
- S6 (plan E4, horario semanal, per period): day × hour grid and a legend with group, language, cycle, teacher, classroom and virtual link; also printable HTML. Sources: `HorariosDeGrupos`, `DetalleDeGrupos`, `DocentesDeGrupos`.
- S7 (plan E4, récord por idioma): per cycle: period, group, grade, state, times taken; average; cycles missing for the level certificate (levels of 4 cycles, `HistorialDeNotas::CICLOS_POR_NIVEL`). It carries the notice "Documento informativo, sin valor oficial; para trámites solicite la constancia de notas", so it never replaces the paid constancia (M5).
- S8 (plan E4): every report answers 404 for an enrollment or period that is not the student's (`BuscarMatriculaDelEstudiante` pattern); tests with seeded data and `Pdf::fake()`.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1–S8 | delegated (context backstop) | pending | — |
| T2 | all | inline | pending | — |

T1 reports page, contracts and the five reports with their PDFs, docs. T2 browser check (desktop and phone, open each PDF). Route: one bounded writer; the reports share the page and the PDF layout, so they are not independent units.

## Log

- L1 (2026-10-31): "Bien, sube y une. Y sigues con lo que falta." E1 merged; E2 and E3 closed on their branches (not pushed); E4 stacks on E3.
