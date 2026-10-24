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
| T3 | S3, S6, D4 | pending | pending | |
| T4 | S2, S6, D5 | pending | pending | |
| T5 | all | inline | pending | |

- T1: Portal auth foundation: layout and Blade components (Tailwind 4, responsive, accessible), student login and logout, password reset by email token, email verification, rate limits, guests of portal routes redirected to the portal login. Risk: item 2 → own commit.
- T2: Self-registration (Personas): `RegistrarCuentaDeEstudiante` Action, two-step Livewire form, `PersonaDelUsuario` contract, middleware that requires a linked person, portal home. Risk: item 2.
- T3: My payments (Pagos): list own payments with state and rejection reason, register a payment with its receipt through `RegistrarPago`, download only own receipts.
- T4: Enrollment (Matricula + Academico contracts): offer of open groups for the enabled cycles with live vacancies, enroll with an unused validated payment through `MatricularEstudiante`, my enrollments with schedule and classroom.
- T5: Docs, demo student, seam check (full suite), independent verifier (item 2), native reviews per unit.

Forecast: about 2,500 authored lines in five commits.

## Log

- L1 (2026-10-24): "Autorregistro con DNI y correo" / "Bien, entonces sigue como lo estabas haciendo, por flujos."
- L2: Map: `User` (app/Shared/Auth/Models/User.php) lacks `MustVerifyEmail`; link is `per_personas.user_id`; no rate limiters, no Fortify, no Blade layout; `RegistrarPago` takes an `UploadedFile`; `MatricularEstudiante(personaId, grupoId, pagoId)`; `ReservarVacante` holds the open-for-enrollment rule. Auth is hand-written with Laravel's broker and `RateLimiter` (no new dependency).
- L3: T1 inline: RED 20 of 21 (routes and components missing; the admin redirect already held) → GREEN 21/21; `composer check` 485/485, phpstan 0; `npm run build` compiles the theme. Spanish validation messages live in a shared trait instead of switching the app locale (Filament tests assert English strings). Not done: ending other sessions after a password reset (remember token is rotated).
- L4: Native review of 20df716 (risk, reliability) granted, approved and acknowledged (review-9e4511c022c7fd4d). Advisories adopted for a follow-up fix: lowercase stored emails, end other sessions after a password change (`auth.session`), a per-IP login limit, a test for the reset throttle. Not adopted: reset link under `guest` (Laravel default), guest redirect scope (no staff routes outside Filament).
- L5: T2 inline: RED 0 of 20 → GREEN 20/20; mutation (drop the email-match guard) killed by 2 tests; `composer check` 505/505, phpstan 0. Registration asks for consent under Ley 29733; throttle error shown at form level.
- L6: Native review of ec9aef6 (risk, reliability) granted, approved and acknowledged (review-d2e3c8d62e2ec663). Hardening commit after both reviews: emails stored lowercase; `auth.session` ends other sessions after a password change; 20 login attempts per IP; one refusal message for documents on file or with an account (no enumeration); registration limit 20 per IP for campus labs; two-process races for the same new document and the same email (unique-violation translation proved); `enParalelo` moved to `tests/Concurrency/Helpers.php`. Not adopted: linking only after verification (the owner recovers through a reset sent to the email on file, which the squatter cannot read; recorded for the CILN), stored persona emails are already normalized by the `Persona` mutator. RED 3 of 4 → GREEN; `composer check` 512/512.
