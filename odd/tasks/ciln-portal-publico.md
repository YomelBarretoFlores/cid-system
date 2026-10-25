# CILN public portal and access (M6.1)

Objective: replace Laravel's starter page at `/` with the CILN public portal (offer, requirements, dates, TUPA/TUSNE costs, document verification) built on the "Web Publica v2" design, and give students and teachers their own sign-in entry that lands each on their module. M6.2 (trámite pipelines) follows in its own feature document.
Branch: `feat/portal-publico` (from `main` afee628) · Delivery: one commit per unit on the feature branch, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1: "bien, puedes continuar" (after M4 was merged; the agreed next module is M6).
- S2 (Anexo M6.1, functionality): "Página pública con información institucional del CILN (oferta, requisitos, fechas, costos según TUPA/TUSNE)." / "Inicio de sesión diferenciado para estudiantes y docentes, como punto único de acceso a sus módulos." / "Recuperación de credenciales mediante envío de token al correo institucional/personal registrado."
- S3 (Anexo M6, objective): "Desplegar una interfaz pública en el dominio oficial (https://cid.unasam.edu.pe/) como punto de ingreso de estudiantes y docentes".
- S4 (Anexo, security): OWASP Top 10 and "Tratamiento de datos personales conforme a la Ley N° 29733".
- S5 (user rule, 2026-10-24): "Y ahora en adelante tomarás en cuenta esto de resilencia, etc."
- S6 (user rule, 2026-10-24): "llena de datos, pero solo en los flujos que ya completamos o cerramos."
- S7 (ADR 0008): "Las pantallas nuevas (M4, M5, M6) se construyen con los mismos componentes" and "la portada pública, M6" (design `fuentes/Web Publica v2.dc.html`).

Assumptions (recorded in `docs/decisiones-pendientes.md`, to confirm with the CILN):

- D1: the page shows the period whose enrollment is open, or else the next planned one; its offer lists the open groups with vacancies (language, cycle, level, modality, schedule, vacancies), never the room, the virtual link or the teacher.
- D2: costs are the active TUPA/TUSNE concepts with the fee in force today for each link to the UNASAM (vínculo).
- D3: the dates are the period's enrollment window, start and end of classes; requirements are fixed institutional text in the view until the CILN provides the official text.
- D4: one account and one guard; `/estudiante/ingresar` and `/docente/ingresar` are two entries with their own heading. After signing in, a panel user goes to the panel, the teacher entry goes to the teacher module, and the student entry goes to the student module, or to the teacher module when the user has a teacher profile and no student record.
- D5: credential recovery already exists (`/recuperar-clave`, token by e-mail, Spanish mail); M6.1 only links it from both entries.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, D1, D2 | inline | pending | |
| T2 | S2, S3, S4, S7, D1–D3 | inline | pending | |
| T3 | S2, S4, D4, D5 | inline | pending | |
| T4 | all | inline | pending | |

- T1: Academico contract for the public offer (period, dates, open groups with vacancies) and Pagos contract for the fees in force, both with public-safe DTOs. Risk: none (new read contracts).
- T2: public page `/` (header, hero with figures, offer by language and modality, costs by link, period dates, requirements, verification form, footer with the portal and staff entries), responsive and indexable. Risk: item 2 (first public data page beyond verification: data minimization).
- T3: teacher sign-in entry, post-login landing by profile, guests of teacher pages sent to the teacher entry, recovery links. Risk: item 2 (authentication redirects).
- T4: demo data check, docs (decisiones, arquitectura, seguridad), full suite, independent verifier, native review.

Route evidence: T2 and T3 depend on T1 or touch the same layout and auth flow, so there is no parallel writer reason; all inline. Forecast about 1,000 authored changed lines; one commit per unit.

## Log

- L1 (2026-10-25, user): "bien, puedes continuar"
- L2: Mapping: `/` renders Laravel's `welcome.blade.php`; one Livewire login `estudiante.ingresar` sends everyone to `estudiante.inicio`, which needs a student record (a teacher without one gets 403); token reset by e-mail exists and is tested; no public contract lists fees (`pag_conceptos`, `pag_tarifas`); `FichaDeGrupo` carries the virtual link, so the public offer needs its own DTO; design "Web Publica v2" in the user's design handoff.
