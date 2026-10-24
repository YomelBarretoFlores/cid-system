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
| T1 | S3, S4 | inline | done | 87dc0fa, 38aeca4, 59c7eaf |
| T2 | S1, S2, S3 | inline | done | b5bf19f, f762a60 |
| T3 | S1, S2, S4 | inline | done | ff76696 |
| T4 | S1, S2, S3 | inline | done | 836c2b3 |
| T5 | S1 | inline | done | docs commit |

- T1: Payment permissions (enum + data migration), `PagoPolicy`, demo seeder grants, shared helper that runs a business Action from a Filament action and turns `ReglaDeNegocioIncumplida` into a notification. Risk: item 2 (permissions) → own commit.
- T2: `PagoResource` list (columns, state/concept/method/date filters, search by operation number and by person through Personas contracts) and view page (infolist, receipt download from the private disk).
- T3: Validate, reject (reason) and void (reason) actions on the table and the view page, visible by state and permission; test that the audit row carries the Filament user.
- T4: Register page: person search, active concept, method, number, date, amount and receipt upload → `RegistrarPago`.
- T5: Docs (`modulos.md`, `seguridad.md`, `decisiones-pendientes.md`).

Route evidence: one resource, shared files (resource, policy, seeder, docs), sequential dependency; no parallel independent units, all inline.
Forecast: about 900 authored lines; commits per task.

## Log

- L1 (2026-10-24): "Sí, sigamos con las pantallas de Filament"
- L2: T1 RED (permission enum missing) → GREEN; policy denies each ability without its permission. Native review of 87dc0fa (risk, reliability): granted, approved and acknowledged (review-4c93e0c9e64667d6); advisories fixed in 38aeca4 (exact cashier grants, permissions compared with the enum). Deferred: R1-001 `view` is not record-scoped (fine for staff; the student portal needs its own scoping).
- L3: Defects found while building T2: `admin-panel.access` existed only in the demo seeder (no panel access in production) → data migration; Filament allows any action whose policy method is missing (`get_authorization_response` falls back to allow) → `strictAuthorization()` plus explicit denials in `PagoPolicy` (59c7eaf; RED: 3 of 3 failing without it). Committed before its own review, so the review covered 87dc0fa..b5bf19f together (risk, reliability, readability): granted, approved and acknowledged (review-a58638f20dd903d5). Advisories fixed in f762a60 (person search no longer capped at 200; missing receipt explained, slugged download name; state color on the enum; panel timezone set once; per-record column assertions). Mutations: removing the escape of `%`/`_`, the search limit fix, `->authorize()` or the state visibility each fail a test.
- L4: T3: Filament re-checks visibility when an action is called, so a stale state cannot reach the Action from the UI; `AccionDeNegocio` is tested directly (success, broken rule → notification + Halt, no user). Livewire update requests run the `web` group, which carries the audit middleware (route test).
- L5: T4 RED (CreatePago missing) → GREEN 8/8. Full suite hit PHP's 128 MB CLI limit with the Filament tests → `memory_limit` 512M in `phpunit.xml`. `composer check` 241/241. Smoke on `php artisan serve` with dev DB: `/admin/login` 200, `/admin/pagos` and `/admin/pagos/registrar` redirect guests to login.
- L6: T5 docs: `modulos.md` (screens and permissions), `seguridad.md` (authorization section, Livewire audit context), `decisiones-pendientes.md` (#2 updated, #18 no edit screen).
