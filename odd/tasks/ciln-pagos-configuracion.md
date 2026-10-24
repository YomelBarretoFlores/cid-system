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
| T1 | S3, C1, C3, C4 | inline | done | b078411, 1caf03d |
| T2 | S3, S4, C3 | inline | done | e6fc3bd |
| T3 | S1, S2, C2, C3 | inline | done | e395071 |
| T4 | S1 | inline | done | c8cbfa0 |

- T1: Permission `pagos.configurar` (data migration), `ConceptoPolicy`, `TarifaPolicy`, demo grants; migration with audit triggers on `pag_conceptos` and `pag_tarifas` and a trigger protecting fees already applied by payments. Risk: items 1 and 2 → own commit.
- T2: Actions `RegistrarTarifa` (closes the open predecessor, overlap as a business error) and `CerrarTarifa`.
- T3: `ConceptoResource` (list, create, edit, activate/deactivate) with a fees relation manager (register, close, delete if unused).
- T4: Docs (`modulos.md`, `seguridad.md`, `decisiones-pendientes.md`).

Route evidence: one module, shared policy/resource files, sequential dependency; all inline.
Forecast: about 800 authored lines.

## Log

- L1 (2026-10-24): "Sigamos con las pantallas de conceptos y tarifas"
- L2: T1 RED (permission enum case missing) → GREEN; migration roundtrip on dev DB ok. Native review of b078411 (risk, reliability): granted, approved and acknowledged (review-1ee76fe6f34dec42). Advisories: R3-002 race between a payment and a fee change; R3-001 audit test depended on id sequences; R3-003 fee UPDATE not audited in tests; R1-001 delete of an applied fee (blocked by the existing RESTRICT foreign key, now tested).
- L3: T2 RED (Actions missing, 9 of 9) → GREEN.
- L4: Race analysis with two-process tests: closing through `CerrarTarifa` already waited for an uncommitted payment (its `FOR UPDATE` conflicts with the payment's `FOR KEY SHARE`). The reverse order was a real defect: a payment read the fee while a closing was uncommitted and was applied to a fee closed before its date (RED 2 of 2). Fix in 1caf03d: `RegistrarPago` re-reads the fee `FOR SHARE` inside the insert transaction; this also makes direct SQL updates of the fee wait (mutation: without it, both the reverse order and direct SQL tests fail; without `lockForUpdate` in `CerrarTarifa` the first order fails).
- L5: T3 RED (8 of 9) → GREEN 9/9; mutation: deleting applied fees allowed fails the test. `composer check` 283/283.
- L6: T4 docs: `modulos.md`, `seguridad.md` (denial trait, audited tables), `modelo-de-datos.md` (fee protection), `decisiones-pendientes.md` #19.
- L7: Final review of b078411..c8cbfa0 (risk, resilience, reliability, readability): granted, approved and acknowledged (review-f4ebfba19911212b). Fixed: R4-001/R3-002 lock order — `RegistrarPago` now locks the concept `FOR SHARE` before the fee, the order `RegistrarTarifa` uses, removing the deadlock risk and the false "no hay tarifa vigente" while a fee is replaced (two-process test RED 2 of 2 → GREEN 3 of 3, four races in `TarifaConcurrenteTest`); R3-001 a past fee filling the gap before the open one is accepted, overlaps still refused by the exclusion constraint; R3-004 a code created concurrently becomes a form error (mutation: without the catch, a raw unique violation); R2-003 one code normalizer (`Concepto::normalizarCodigo` + mutator); R2-001 comment on the fail-fast pre-check; R2-004 T4 hash. Deferred: R2-002 overlap translation lives in `CerrarTarifa`; R3-003 concurrency tests rely on wall-clock barriers and leave committed rows until the next `migrate:fresh`. `composer check` 287/287.
- L8: Review of b47ac7a (resilience, reliability): granted, approved and acknowledged (review-14af369ec0211c13). Applied R4-001: only the code's unique index becomes the form error. Deferred R3-001 (wall-clock barriers in concurrency tests, as before).
- L9: Closed. Next: the student portal (Livewire) for receipt upload and enrollment, or the public portal with TUPA/TUSNE costs (M6).
