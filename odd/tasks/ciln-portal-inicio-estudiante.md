# CILN student home: payment status and documents (E2)

Objective: the student home shows where the student stands with their payments (to validate, validated, rejected with reason, current enrollment fee) and links to their documents, and its banner stops asking for a payment that is already waiting for validation.
Branch: `feat/inicio-estudiante` (from `main` 45f9228) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`). Plan: `~/.claude/plans/agile-orbiting-wreath.md` (E2).

## Specs

- S1 (plan E2): "Inicio: current enrollment status banner ('matrícula validada'), a payments summary 'debes / registraste / validado', and quick links to documents." Nothing in the system models money owed, so "debes" is shown as the current enrollment fee for the student's link (`TarifasVigentes` logic), not as a debt.
- S2 (plan E2): payments summary from a new Pagos contract `ResumenDePagosDePersona` (`ListarPagosDeEstudiante` is internal): payments to validate, validated, enrollment payments available, and the latest rejection with its reason when it is the newest payment.
- S3 (plan E2, assumption): the banner tells apart "no payment", "payment waiting for validation" and "latest payment rejected", each with its own action.
- S4 (plan E2): document links: enrollment form and registration certificate of the current active enrollment, and the trámites page for certificates.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1–S4 | inline | done | see L2 |
| T2 | all | inline | pending | — |

T1 Pagos contract and DTO, home summary and banner, documents card, docs. T2 browser check (desktop and phone). Route: inline (one unit, no writer trigger).

## Log

- L1 (2026-10-31): "Bien, sube y une. Y sigues con lo que falta." E1 fast-forwarded into main (45f9228); E2 starts from it.
- L2 (2026-10-31): T1 done. RED: 5 of 6 tests failed. New Pagos contract `ResumenDePagosDePersona` (`ResumirPagosDePersona`); `ResumirAvanceDelEstudiante` reuses `OpcionesDeMatricula::pagosDisponibles` for the step and the count. `composer check` rc=0, 1585 tests.
