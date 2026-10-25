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

- D1: the page shows the period whose enrollment is open, or else the next one whose enrollment has not started; its offer lists the programmed and open groups of active languages (language, cycle, level, modality, schedule, vacancies, or "Sin vacantes" / "Próximamente"), never the room, the virtual link or the teacher.
- D2: costs are the active TUPA/TUSNE concepts with the fee in force today for each link to the UNASAM (vínculo).
- D3: the dates are the period's enrollment window, start and end of classes; requirements are fixed institutional text in the view until the CILN provides the official text.
- D4: one account and one guard; `/estudiante/ingresar` and `/docente/ingresar` are two entries with their own heading. After signing in (when no page was requested before), the teacher entry goes to the teacher module if the user has a teacher profile; otherwise a user with a person record goes to the student module, staff without one go to the panel. Guests of teacher pages are sent to the teacher entry. Every teacher has a person record, so teachers already reach the student portal; the change is their own entry and landing.
- D5: credential recovery already exists (`/recuperar-clave`, token by e-mail, Spanish mail); M6.1 only links it from both entries.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, D1, D2 | inline | done | 1ee675e |
| T2 | S2, S3, S4, S7, D1–D3 | inline | done | 3831048 + fix (see L5) |
| T3 | S2, S4, D4, D5 | inline | done | 6ad1b24 + fix (see L7) |
| T4 | all | inline | done | 3572838 + closing commit (see L9–L10) |

- T1: Academico contract for the public offer (period, dates, open groups with vacancies) and Pagos contract for the fees in force, both with public-safe DTOs. Risk: none (new read contracts).
- T2: public page `/` (header, hero with figures, offer by language and modality, costs by link, period dates, requirements, verification form, footer with the portal and staff entries), responsive and indexable. Risk: item 2 (first public data page beyond verification: data minimization).
- T3: teacher sign-in entry, post-login landing by profile, guests of teacher pages sent to the teacher entry, recovery links. Risk: item 2 (authentication redirects).
- T4: demo data check, docs (decisiones, arquitectura, seguridad), full suite, independent verifier, native review.

Route evidence: T2 and T3 depend on T1 or touch the same layout and auth flow, so there is no parallel writer reason; all inline. Forecast about 1,000 authored changed lines; one commit per unit.

## Log

- L1 (2026-10-25, user): "bien, puedes continuar"
- L2: Mapping: `/` renders Laravel's `welcome.blade.php`; one Livewire login `estudiante.ingresar` sends everyone to `estudiante.inicio`, which needs a student record (a teacher without one gets 403); token reset by e-mail exists and is tested; no public contract lists fees (`pag_conceptos`, `pag_tarifas`); `FichaDeGrupo` carries the virtual link, so the public offer needs its own DTO; design "Web Publica v2" in the user's design handoff.
- L3: T1 (1ee675e): `OfertaPublica` → `OfertaDelPeriodo` with `GrupoOfertado` (no room, link or teacher; programmed and open groups of active languages; vacancies never below 0) and `TarifasVigentes` → `TarifaVigente` (active concepts, fee in force on the date). RED observed (contracts missing), GREEN 5/5; Academico, Pagos and architecture 435/435. Assessed medium, under budget.
- L4: T2: `/` is the Livewire page `PortadaPublica` (Tramites) on `ContenidoDePortada`: header with section links and the student entry, hero with the open or next enrollment, figures, offer by language and modality (filtered with Alpine on the client, opening on the first language with groups), costs by link, period dates (Concluido / En curso / Próximo), four requirement steps, verification form to `/verificar`, footer with the student portal and staff entries; Laravel's `welcome.blade.php` removed. `Formato` gains `diaYMes`, `diaDeMes`, `fechaLarga`, `rango`, `romano`. RED observed (3 failing), GREEN 3/3 + Formato; browser check on the demo (desktop and phone) found the page opening on a language without groups, fixed.
- L5: T1–T2 reviewed with risk and reliability lenses: approved and acknowledged (lineage review-9e4547a1458d643a); risk lens found no issue (DTO without room, link or teacher; escaped output; read-only page). Reliability advisories: R3-002 was real (app timezone is UTC, so comparing Lima midnight with the period's dates showed the last enrollment day as "Concluido"): days are now compared as Lima calendar dates, pinned by boundary-day tests that failed before the fix (R3-003); R3-001 (fees asked with the current instant) is harmless because `vigenteEn` compares dates, and the page now passes today's date.
- L6: T3: `/docente/ingresar` reuses `IniciarSesion` with a locked `EntradaDelPortal` (from the route, or the `portal` mount parameter); `DestinoTrasIngreso` picks the landing (teacher entry + teacher profile → teacher module; person record → student module; staff → panel) and is used after login (unless a page was requested) and by `redirectUsersTo`; guests of teacher pages go to the teacher entry; each entry links the other; the access screens' brand links to the public page (`x-ciln.marca`, shared with the public header); the public footer links the teacher entry. Mapping correction: every teacher has a person record, so teachers already reached the student portal. RED observed (5 of 8 failing), GREEN 8/8; Feature, Tramites, Docencia 352/352 after updating the one Docencia test that pinned the old guest redirect (replaced on purpose by D4).
- L7: T2 fix + T3 reviewed with risk and reliability lenses: approved and acknowledged (lineage review-…, base 3831048). Advisory R3-001 applied: signed-in staff visiting an entry page went to the student module when they had a person record (baseline: always the panel); `redirectUsersTo` again sends staff to the panel first, pinned by a test that failed before the fix. R3-002 accepted: a Livewire action on a teacher page after the session expired is posted to the Livewire endpoint and falls back to the student entry; Livewire reloads the page on an expired session, and the reload goes to the teacher entry.
- L8: T4: docs (decisiones #70–#73, seguridad: two entries and the public page without personal data, arquitectura: Tramites row). Demo: the public page reads the existing demo periods, groups and fees; no new demo data. `composer check`: Pint, Rector, Larastan clean; Pest 1242/1242.
- L9: Independent verifier (baseline afee628, HEAD 3572838): no blockers; every S# and D# met (40 probes plus the suite, 1282/1282 in its scratch copy); baseline portal tests pass unchanged, and the only changed baseline assertion is the guest redirect D4 replaces. Advisories: D1 text was narrower than the page (programmed and full groups are shown), D1 reworded to match decision #70; a period left open past its enrollment end disappears from the page (accepted, it matches D1); expired Livewire session on a teacher page (accepted in L7); the header's "Ingresar" goes to the student entry, the teacher entry is in the footer (accepted, the design has one sign-in button). Probes committed as regression tests: teacher name absent from the offer, fee boundary on the Lima day, escaping inside the client filter data, locked entry. Verifier scratch /private/tmp/m61verify.CKeJ3s removed.
- L10: Whole-branch review asked by the stop hook (risk, resilience, readability, reliability): approved and acknowledged (lineage review-a28af9b357e2e45b). Advisories applied: modality buttons built from `Modalidad::cases()` (R2-003, R3-001); `siguientePlanificado` renamed `conMatriculaPorAbrir` and `matriculaAbierta` set from the query that found the period, with a test for an open-state period starting later (R2-002, R3-002); a comment explains why signed-in staff keep going to the panel (R2-001). Accepted: no cache for the public page yet (R4-001, recorded for deployment sizing, decision #5); the "I–XII" figure is the fixed 12-cycle curriculum (R2-004); the Lima timezone literal follows the existing convention (R2-005). `composer check`: Pint, Rector, Larastan clean; Pest 1246/1246. Feature closed; next: M6.2 trámite pipelines.
