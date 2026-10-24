# CILN collection reports (M2.2)

Objective: let CILN staff build a consolidated report of validated payments for a date range, grouped by TUPA/TUSNE concept and by language and level, with the detail per student, and export it as PDF and CSV for the treasury file.
Branch: `feat/reportes-recaudacion` (from `main` b1c34e4) · Delivery: ask-on-risk, one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1: "súbela y únela a main, luego sigue con los reportes"
- S2 (Anexo M2.2): "Generación de reportes consolidados de pagos validados por periodo, idioma, nivel y concepto TUPA/TUSNE, según corresponda." / "Exportación en PDF y/o hoja de cálculo (Excel/CSV) para su impresión y presentación como expediente ante las áreas correspondientes (DGF / Tesorería)." / "Campos Requeridos (Reporte): Periodo, Concepto, Monto total recaudado, Número de operaciones, Detalle por estudiante."
- S3 (project rules): money never as float; Pagos reads other modules only through their contracts; strict policies.

Assumptions (to confirm with the CILN):
- D1: "Periodo" is a range of payment dates (fecha de pago, as treasury reconciles), with an optional filter by academic period that keeps only payments tied to an enrollment of that period. Only validated payments count; voided, rejected and pending ones never do.
- D2: Language and level come from the enrollment the payment paid for (or the language of a placement request); other payments (pension, certificates) are grouped under "Sin idioma".
- D3: Permission `pagos.reportar`; the demo administrator gets it. Export formats: PDF (DOMPDF) and CSV in UTF-8 with BOM so spreadsheet programs open accents; no new dependency.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S3, D3 | inline | done | see L3 |
| T2 | S2, S3, D1, D2 | inline | pending | |
| T3 | S2, D3 | inline | pending | |
| T4 | all | inline | pending | |

- T1: `PermisoPago::Reportar` and data migration, `PagoPolicy::reportar`, demo grant. Risk: item 2.
- T2: Matricula contract `ContextoAcademicoDePagos` (payment id → period, language, level); Pagos Action `GenerarReporteDeRecaudacion` returning a report DTO (filters, totals in cents, by concept, by language and level, detail rows with student document and name).
- T3: Filament page "Reporte de recaudación" (filters, summaries) with "Descargar PDF" and "Descargar CSV".
- T4: Docs, full suite, independent verifier, native review.

Forecast: about 900 authored lines in four commits.

## Log

- L1 (2026-10-24): "súbela y únela a main, luego sigue con los reportes"
- L2: Map: payments link to an enrollment through `mat_matriculas.pago_id` (group → period, cycle → language and level) and to a placement request through `mat_solicitudes_clasificacion.pago_id`; `FuenteConcepto` gives TUPA/TUSNE; bcmath is available; no spreadsheet library installed.
- L3: T1 inline: RED (dataset references the missing case) → GREEN; `pagos.reportar` by migration, `PagoPolicy::reportar`; the demo administrator already receives every payment permission. `composer check` green.
