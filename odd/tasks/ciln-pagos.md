# CILN payments module

Objective: Pagos (M2) schema with fees by validity, payments with receipt evidence and validation states, enrollment/placement payment links, and the immutable PostgreSQL audit trail required by Anexo II.2.
Branch: `feat/pagos` (stacked on `feat/core-data-model`) · Delivery: ask-on-risk · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Bien, sigamos con pagos." (continuation of L1 in `ciln-core-data-model.md`: "crea todo lo necesario y correcto").
- S2 (Anexo M2.1): "Registro de pagos por derechos académicos (matrícula, pensión, examen de clasificación, certificados)." / "Validación del pago por el administrador o cajero del CILN, con carga del recibo en formato digital (JPG/PNG o PDF) como evidencia." / "Registro de auditoría que indica qué usuario validó cada pago y en qué fecha." / Fields: "Número de Recibo / Código de Operación", "Tipo y Número de Documento", "Monto Total, Fecha de Pago, Medio de Pago (caja presencial, Págalo.pe, etc.)", "Concepto TUPA/TUSNE", "Recibo escaneado, Estado (Pendiente, Validado, Rechazado), Usuario que validó, Fecha de Validación".
- S3 (Anexo II.2): "Pistas de auditoría (Log Triggers) inmutables para operaciones críticas (cambio de notas, validación de pagos, modificación de actas), registrando fecha, hora, IP, ID de usuario y detalle de la operación."
- S4 (agreed rules): fees in DB with validity; payment uniqueness = payment method + operation number; financial records are voided, never deleted.

Assumptions (to confirm with the CILN):
- A1: Fees vary by concept and link with the university (estudiante UNASAM, docente/administrativo, público general), not by language or level.
- A2: A voided state (`anulado`) is added to Pendiente/Validado/Rechazado so validated payments can be reversed without deleting them.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S3 | inline | done | bd46a97 |
| T2 | S1, S2, S4 | inline | done | fbefcf8 |
| T3 | S2 | inline | done | ec30da5 |
| T4 | S1 | inline | done | this slice |

- T1: `app/Shared/Audit`: `aud_registros`, `aud_registrar()` trigger function, immutability triggers, request context (user, IP) middleware. Risk: item 2 (security/audit) → own commit.
- T2: Pagos: `pag_conceptos`, `pag_tarifas` (no overlapping validity), `pag_pagos` (states, evidence, uniqueness, voiding), audit trigger on `pag_pagos`.
- T3: `pago_id` links on `mat_matriculas` and `mat_solicitudes_clasificacion` (one payment unlocks one record).
- T4: Docs (data model, security), demo seeder.

Route evidence: shared files (bootstrap, phpstan, docs) and sequential dependency (audit → payments → links); all inline.

## Log

- L1 (2026-10-24): "Bien, sigamos con pagos."
- L2: Evidence: `composer check` → Pint ok, Rector 0, Larastan 0, Pest 121/121 (340 assertions) on PostgreSQL. Runtime on dev DB `ciln`: migrate → rollback 4 steps → migrate clean; Pagos demo seeder → 10 validated payments, 0 enrollments without payment, 10 `pag_pagos INSERT` audit rows; `DELETE` on `aud_registros` and `pag_pagos` from psql as the database owner rejected by triggers.
- L3: RED/GREEN: removing the web audit middleware made the context test fail (user null); removing the immutability trigger let UPDATE/DELETE on the audit table through (2 of 3 cases failed). Defects found and fixed: `migrate:fresh` keeps PostgreSQL functions, so `CREATE FUNCTION` failed on the second run (now `CREATE OR REPLACE`); a custom `pag_pagos_estado_check` collided with the CHECK PostgreSQL auto-names for the Laravel `enum()` column (renamed `pag_pagos_datos_estado_check`).
- L4: Docs aligned with code: audit context uses `set_config` per request (not `SET LOCAL`). Assumptions A1–A2 in `docs/decisiones-pendientes.md` rows 12–13.
- L5: Not done (out of scope): payment validation/void Actions and receipt upload flow, enrollment Actions, Docencia schema.
