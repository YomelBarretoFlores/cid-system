# CILN student portal (Blade + Livewire)

Objective: let students create their own account with their document and email, upload payment receipts, enroll in an open group with a validated payment and see their enrollments, each seeing only their own data.
Branch: `feat/portal-estudiante` (stacked on `feat/academico-filament`) · Delivery: ask-on-risk, stacked branches, one commit per unit · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Autorregistro con DNI y correo" → "Bien, entonces sigue como lo estabas haciendo, por flujos."
- S2 (Anexo M1.3): "Interfaz para que el estudiante seleccione su grupo y se matricule de forma autónoma." / "Bloqueos automáticos: el sistema impedirá la matrícula si el estudiante (a) no aprobó el ciclo anterior o no tiene el nivel requerido; (b) no cuenta con la validación de pago registrada en el Módulo de Gestión de Pagos; o (c) intenta matricularse en un grupo sin vacantes." / "Vacantes Disponibles (en tiempo real)."
- S3 (Anexo M2.1): "Registro de pagos por derechos académicos (matrícula, pensión, examen de clasificación, certificados)." / "carga del recibo en formato digital (JPG/PNG o PDF) como evidencia." / "Evidencia y Trazabilidad: Recibo escaneado, Estado (Pendiente, Validado, Rechazado)".
- S4 (Anexo M6.1): "Inicio de sesión diferenciado para estudiantes y docentes, como punto único de acceso a sus módulos." / "Recuperación de credenciales mediante envío de token al correo institucional/personal registrado."
- S5 (Anexo M1.1, data): "Tipo y Número de Documento (DNI/CE/Pasaporte), Apellidos, Nombres, Fecha de Nacimiento, Correo Personal, Celular."
- S6 (DSTI 1.2, 1.4, 3.1): "gestión insegura de sesiones, validación insuficiente de entradas, exposición de información sensible" must not repeat; "Responsive, intuitiva, accesible, dinámica, adaptable a móviles". Each student sees only their own payments, receipts and enrollments.

Assumptions (to confirm with the CILN):
- D1: The account login is the email and a password (at least 8 characters with letters and numbers). The portal requires a verified email.
- D2: A document already registered by the CILN (for example by the cashier) is linked to the new account only when its stored email equals the one entered; otherwise the student is told to go to the CILN office. A document that already has an account is refused. Both refusals share one message so the form does not reveal which case applies.
- D3: A self-registered person is "Público General". Only CILN staff change the link type (UNASAM student or staff), because it decides the fee.
- D4: Students enter the paid amount themselves; the form proposes the current fee for their link type. The cashier still validates every payment.
- D5: Groups offered to a student are those open for enrollment (open group, period in "Matrícula abierta", today inside the window) of the cycle they are enabled for in each language, or cycle I when they have none.
- D6: Throttling: 5 login attempts per minute per email and IP, and 20 per IP; 20 registrations per minute per IP (campus labs share an address); 5 reset requests per minute per IP and one reset email per minute per account; one verification email per minute.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S4, S6, D1, D6 | inline | done | 20df716 |
| T2 | S1, S5, S6, D2, D3 | inline | done | ec9aef6, see L6 |
| T3 | S3, S6, D4 | delegated (parallel W-A) | done | 69da076 |
| T4 | S2, S6, D5 | delegated (parallel W-B) | done | c4f0f71 |
| T5 | all | inline | done | eee960e, f492b00, see L9 |

- T1: Portal auth foundation: layout and Blade components (Tailwind 4, responsive, accessible), student login and logout, password reset by email token, email verification, rate limits, guests of portal routes redirected to the portal login. Risk: item 2 → own commit.
- T2: Self-registration (Personas): `RegistrarCuentaDeEstudiante` Action, one-page Livewire form, `FichaDelUsuario` contract, middleware that requires a linked person, portal home. Risk: item 2.
- T3: My payments (Pagos): list own payments with state and rejection reason, register a payment with its receipt through `RegistrarPago`, download only own receipts.
  - `/estudiante/pagos` (`estudiante.pagos`): own payments newest first with concept, operation number, payment method, payment date, amount (`S/ 0.00`), state badge (`EstadoPago::etiqueta()`/`color()`), rejection reason when rejected, and a link to the receipt.
  - Register form: active concept, payment method, operation number, payment date (not after today), amount proposed from the current fee for the person's link type when a concept is chosen (D4), receipt JPG/PNG/PDF up to 5 MB; `RegistrarPago` with `registradoPor` = the student; business errors shown on the form.
  - `/estudiante/pagos/{pago:ulid}/recibo` (`estudiante.pagos.recibo`): streams the receipt only to its owner; any other payment answers 404.
- T4: Enrollment (Matricula + Academico contracts): offer of open groups for the enabled cycles with live vacancies, enroll with an unused validated payment through `MatricularEstudiante`, my enrollments with schedule and classroom.
  - Academico contracts: `OfertaDeMatricula` (open groups of the enabled cycle per language, cycle I otherwise, D5) and `DetalleDeGrupos` (by ids), both returning one DTO with code, language, cycle number, level, modality, accelerated flag, period, schedule lines, classroom or virtual link, and available vacancies. The open-for-enrollment rule is shared with `ReservarVacante`, never copied, and its locking stays unchanged.
  - Pagos contract `PagosValidadosDePersona` (validated payments of one person for one service type); Matricula hides payments already used.
  - `/estudiante/matricula` (`estudiante.matricula`): offer grouped by language with vacancies refreshed by polling; choose an unused validated enrollment payment (or a message linking to `estudiante.pagos`); enroll through `MatricularEstudiante`; business errors shown; success goes to my enrollments with a flash message.
  - `/estudiante/matriculas` (`estudiante.matriculas`): own enrollments with group detail, state, condition and enrollment date.
- T5: Docs, demo student, seam check (full suite), independent verifier (item 2), native reviews per unit.

Forecast: about 2,500 authored lines in five commits.
Route evidence: T3 and T4 are independent units with disjoint edit surfaces (Pagos portal files vs Matricula portal files, Academico contracts and one new Pagos contract), each several hundred lines; parallel writers W-A and W-B with test databases `ciln_testing_wa` and `ciln_testing_wb`; the parent owns git, the shared layout navigation, the portal home cards and the final suite.

## Log

- L1 (2026-10-24): "Autorregistro con DNI y correo" / "Bien, entonces sigue como lo estabas haciendo, por flujos."
- L2: Map: `User` (app/Shared/Auth/Models/User.php) lacks `MustVerifyEmail`; link is `per_personas.user_id`; no rate limiters, no Fortify, no Blade layout; `RegistrarPago` takes an `UploadedFile`; `MatricularEstudiante(personaId, grupoId, pagoId)`; `ReservarVacante` holds the open-for-enrollment rule. Auth is hand-written with Laravel's broker and `RateLimiter` (no new dependency).
- L3: T1 inline: RED 20 of 21 (routes and components missing; the admin redirect already held) → GREEN 21/21; `composer check` 485/485, phpstan 0; `npm run build` compiles the theme. Spanish validation messages live in a shared trait instead of switching the app locale (Filament tests assert English strings). Not done: ending other sessions after a password reset (remember token is rotated).
- L4: Native review of 20df716 (risk, reliability) granted, approved and acknowledged (review-9e4511c022c7fd4d). Advisories adopted for a follow-up fix: lowercase stored emails, end other sessions after a password change (`auth.session`), a per-IP login limit, a test for the reset throttle. Not adopted: reset link under `guest` (Laravel default), guest redirect scope (no staff routes outside Filament).
- L5: T2 inline: RED 0 of 20 → GREEN 20/20; mutation (drop the email-match guard) killed by 2 tests; `composer check` 505/505, phpstan 0. Registration asks for consent under Ley 29733; throttle error shown at form level.
- L6: Native review of ec9aef6 (risk, reliability) granted, approved and acknowledged (review-d2e3c8d62e2ec663). Hardening commit after both reviews: emails stored lowercase; `auth.session` ends other sessions after a password change; 20 login attempts per IP; one refusal message for documents on file or with an account (no enumeration); registration limit 20 per IP for campus labs; two-process races for the same new document and the same email (unique-violation translation proved); `enParalelo` moved to `tests/Concurrency/Helpers.php`. Not adopted: linking only after verification (the owner recovers through a reset sent to the email on file, which the squatter cannot read; recorded for the CILN), stored persona emails are already normalized by the `Persona` mutator. RED 3 of 4 → GREEN; `composer check` 512/512.
- L7: W-A (T3): RED 17 of 17 → GREEN; ownership mutation killed; owner-only receipt controller with `nosniff` and `no-store`; receipt type left to `RegistrarPago`; Pagos + Architecture 175/175. W-B (T4): RED 25 → GREEN; `Grupo::abiertosParaMatricula` scope now shared by `ReservarVacante` (same lock and messages) and the offer; offer in 6 queries; form accepts only a group of the student's offer and an unused validated payment; full groups stay listed as "Sin vacantes"; virtual link shown only after enrolling (to confirm with the CILN); 261/261. Parent: nav links, home cards, demo student `estudiante@ciln.test` with an unused validated payment, demo enrollment window starts today, docs (`seguridad.md`, `modulos.md`, decisiones-pendientes rows 24-28).
- L8: Independent verifier (items 2 and 4) over 675c17b..eee960e: S1-S5, D2-D6 met; differential 224/224 baseline tests unchanged; 1 blocker B1: Livewire update requests re-run only persistent middleware, so an unverified account replaying a portal snapshot read its payments and enrolled (D1, S6). Correction: `EnsureEmailIsVerified` and `RequiereFichaDeEstudiante` added to Livewire's persistent middleware; signed-in staff sent from the student login to the admin panel (advisory 1). Probes committed as regression tests in tests/Feature/Portal (45 cases). RED 2 of 3 replay probes → GREEN; `composer check` 600/600. Advisory kept for the CILN: "Ya existe una cuenta con ese correo." reveals registered emails (row 25).
- L9: Native reviews on per-commit worktrees, all granted, approved and acknowledged: 69da076 (review-d46e2a4f4ad24714), c4f0f71 (review-d54a225e37fd147c), c4f0f71..f492b00 (review-d4bb0b634a7cf963). Adopted: the offer polls only while visible. Checked and not needed: concept existence (RegistrarPago refuses an unknown or inactive concept), date-only window columns, one enablement per person and language (unique index). Left as follow-ups: the newest-first ordering test renders the list twice, replay probes lack a positive control, demo window ends on 2027-01-06. Closed. Next: per the TdR, M1 documents (ficha PDF, constancia) or the public portal (M6).
