# CILN user manuals (estudiante, docente, personal del CILN)

Objective: three user manuals in Spanish with step-by-step screenshots, same institutional Word/PDF style as the final technical report, closing Anexo IV.1.b and TdR 4.2/6.1 "manuales".
Branch: main (docs only; sources docs/manuales/manual-*.md) · Delivery: Word + PDF in ~/Downloads · Checks: structural readback.

## Specs

- S1 (user): "luego sigues con los manuales de usuario, creo que ahora puedas ya acceder al navegador y hacer capturas."
- S2 (agreed): three documents — Manual del Estudiante, Manual del Docente, Manual del Personal del CILN (administración, caja, coordinación, certificación, trámites, tablero, catálogos, seguridad) — same cover/historial/índice style as the technical report, author ELECTRODATA INGENIERIA & SISTEMAS S.A.C., no personal names.
- S3: content reflects the current system (no syllabus; evaluation by four skills and two exams).
- S4 (user): "La demo yo te diré cuando inicie." — stop data-creating captures when the user announces the live demo.

## Tasks

| ID | Specs | Route | Status |
|----|-------|-------|--------|
| T1 | S1–S3 | delegated (parallel unit: manual del estudiante) | done |
| T2 | S1–S3 | delegated (parallel unit: manual del docente) | done |
| T3 | S1–S3 | delegated (parallel unit: manual del personal) | done |
| T4 | S2 | delegated after T1–T3 (Word/PDF builder) | done |

## Log

- L1 (2026-11-18, user): "Empieza con el 1, luego sigues con los manuales de usuario, creo que ahora puedas ya acceder al navegador y hacer capturas."
- L2: flow inventory per role obtained from code (routes, Livewire, Filament, seeders); demo accounts admin/caja/docente/estudiante@ciln.test.
- L3: T1–T4 done: estudiante (38 pp, 29 fig), docente (28 pp, 15 fig), personal (49 pp, 36 fig) in ~/Downloads as Word+PDF; author ELECTRODATA; no data changed except one unverified sign-up (DNI 70000099) and synthetic pre-acta grades. Gaps: acta validation modal not capturable outside acta dates; classification form needs a validated payment; empty Actas pendientes.
