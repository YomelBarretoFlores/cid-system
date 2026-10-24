# CILN payment configuration screens: concepts and fees

Objective: let the administrator manage TUPA/TUSNE concepts and their fees by link and validity from the panel, keeping fee history intact and audited.
Branch: `feat/pagos-configuracion` (stacked on `feat/pagos-filament`) · Delivery: ask-on-risk, stacked branches · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Siguiente paso propuesto: las pantallas de conceptos y tarifas para el administrador." → "Sigamos con las pantallas de conceptos y tarifas"
- S2 (Anexo M2.1): "Registro de pagos por derechos académicos (matrícula, pensión, examen de clasificación, certificados)." / "Concepto TUPA/TUSNE". M6: "costos TUPA/TUSNE" shown on the public portal.
- S3 (`.claude/rules/database.md`): "Time-varying prices (TUPA/TUSNE fees, teacher hourly cost) are rows with validity dates; transactions store the amount they applied." / "Financial and academic records are voided with reason and user, never deleted."
- S4 (agreed in `ciln-pagos.md` S4 and `docs/modulos.md`): "Tarifas: Por concepto TUPA/TUSNE y tipo de vínculo, con vigencia sin superposición".

Assumptions (to confirm with the CILN):
- C1: A new permission `pagos.configurar` manages concepts and fees; the demo administrator has it, the demo cashier does not.
- C2: Concepts are never deleted: they are deactivated (inactive concepts are not offered for new payments).
- C3: Fees are not edited. A new fee for the same concept and link starting on a date closes the open one the day before; a fee can be closed on a date; an unused fee can be deleted; a fee used by a payment keeps its amount, link and start, and cannot be closed before the last payment that applied it.
- C4: Concept and fee changes are written to the audit trail.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S3, C1, C3, C4 | inline | pending | |
| T2 | S3, S4, C3 | inline | pending | |
| T3 | S1, S2, C2, C3 | inline | pending | |
| T4 | S1 | inline | pending | |

- T1: Permission `pagos.configurar` (data migration), `ConceptoPolicy`, `TarifaPolicy`, demo grants; migration with audit triggers on `pag_conceptos` and `pag_tarifas` and a trigger protecting fees already applied by payments. Risk: items 1 and 2 → own commit.
- T2: Actions `RegistrarTarifa` (closes the open predecessor, overlap as a business error) and `CerrarTarifa`.
- T3: `ConceptoResource` (list, create, edit, activate/deactivate) with a fees relation manager (register, close, delete if unused).
- T4: Docs (`modulos.md`, `seguridad.md`, `decisiones-pendientes.md`).

Route evidence: one module, shared policy/resource files, sequential dependency; all inline.
Forecast: about 800 authored lines.

## Log

- L1 (2026-10-24): "Sigamos con las pantallas de conceptos y tarifas"
