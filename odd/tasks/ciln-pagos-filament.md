# CILN payments screens (Filament)

Objective: Filament screens for the cashier and the administrator to list, inspect, register, validate, reject and void payments, reusing the existing Actions, with permissions and audit context.
Branch: `feat/pagos-filament` (stacked on `feat/business-actions`) · Delivery: ask-on-risk, stacked branches · Test runner: Pest (`php artisan test`).

## Specs

- S1: "¿Seguimos con las pantallas de Filament para que caja valide pagos?" → "Sí, sigamos con las pantallas de Filament"
- S2 (Anexo M2.1): "Validación del pago por el administrador o cajero del CILN, con carga del recibo en formato digital (JPG/PNG o PDF) como evidencia." / "Registro de auditoría que indica qué usuario validó cada pago y en qué fecha." / Fields: "Número de Recibo / Código de Operación", "Tipo y Número de Documento", "Monto Total, Fecha de Pago, Medio de Pago (caja presencial, Págalo.pe, etc.)", "Concepto TUPA/TUSNE", "Recibo escaneado, Estado (Pendiente, Validado, Rechazado), Usuario que validó, Fecha de Validación".
- S3 (`CLAUDE.md`, `docs/arquitectura.md`): "Filament 5 (admin, cashier, BI)"; "Controller / Livewire / Filament Resource → Form Request → Action (final, invokable, one business rule) → Model"; a module only uses another module's `Contracts/`, `Data/`, `Enums/`, `Events/`.
- S4 (follow-ups recorded in `odd/tasks/ciln-business-actions.md` and `ciln-pagos.md`): role checks through Policies when the Actions are wired to screens; business-rule errors shown to the user without technical details; "no test hits Filament with the audit middleware".

Assumptions (to confirm with the CILN; roles are pending decision #2):
- B1: Granular permissions `pagos.ver`, `pagos.registrar`, `pagos.validar` (validate and reject) and `pagos.anular`; roles that group them are defined later. The demo administrator gets all of them and a demo cashier gets all but `pagos.anular`.
- B2: No edit screen: a wrong pending payment is rejected and registered again (A2 of the business actions).
- B3: The cashier registers walk-in payments from the panel; students will register theirs from the portal (later feature).

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S3, S4 | inline | pending | |
| T2 | S1, S2, S3 | inline | pending | |
| T3 | S1, S2, S4 | inline | pending | |
| T4 | S1, S2, S3 | inline | pending | |
| T5 | S1 | inline | pending | |

- T1: Payment permissions (enum + data migration), `PagoPolicy`, demo seeder grants, shared helper that runs a business Action from a Filament action and turns `ReglaDeNegocioIncumplida` into a notification. Risk: item 2 (permissions) → own commit.
- T2: `PagoResource` list (columns, state/concept/method/date filters, search by operation number and by person through Personas contracts) and view page (infolist, receipt download from the private disk).
- T3: Validate, reject (reason) and void (reason) actions on the table and the view page, visible by state and permission; test that the audit row carries the Filament user.
- T4: Register page: person search, active concept, method, number, date, amount and receipt upload → `RegistrarPago`.
- T5: Docs (`modulos.md`, `seguridad.md`, `decisiones-pendientes.md`).

Route evidence: one resource, shared files (resource, policy, seeder, docs), sequential dependency; no parallel independent units, all inline.
Forecast: about 900 authored lines; commits per task.

## Log

- L1 (2026-10-24): "Sí, sigamos con las pantallas de Filament"
