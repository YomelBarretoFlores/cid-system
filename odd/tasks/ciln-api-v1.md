# CILN REST API v1 (minimal, read-only)

Objective: expose a minimal, read-only REST API at `/api/v1` for external clients (Sanctum tokens, Scramble docs) that reuses the same Actions as the screens: public offer, fees and document verification; the student's own data; the teacher's own groups.
Branch: `feat/api-v1` (from `main` b0cc120) · Delivery: one commit per unit on the feature branch, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1 (user, 2026-10-25): "Bien, avanza con ese alcance mínimo" — the agreed scope: "Público, sin llave: la oferta del periodo, las tarifas vigentes y la verificación de documentos por código." / "Estudiante, con su llave: sus datos, matrículas, notas, pagos, trámites y documentos." / "Docente, con su llave: sus grupos y horarios." / "Lo administrativo (validar pagos, emitir documentos) quedaría fuera de esta primera versión".
- S2 (Anexo, general): "Exposición de servicios mediante API REST y diseño de servicios desacoplados para futuras implementaciones."
- S3 (DSTI Informe 023-2026, 3.1): "PHP 8.x, framework moderno (Laravel, Symfony o equivalente), arquitectura MVC, API REST, bases de datos optimizadas, servicios desacoplados".
- S4 (architecture): "Todas las entradas (web, API, Filament, consola) llaman a la **misma Action**." / rule: "Use JSON Resources, never raw models." / "Rate limit login, password reset and public certificate verification." / "The REST API is documented by Scramble, not by hand."
- S5 (Anexo, security): OWASP Top 10 and "Tratamiento de datos personales conforme a la Ley N° 29733".
- S6 (user rule, 2026-10-24): resilience and observability by default.

Assumptions (to record in `docs/decisiones-pendientes.md` and confirm with the CILN and the DSTI):

- A1: a client gets a personal token with the account's e-mail and password (`POST /api/v1/sesiones`, with a device name), under the same limits as the web sign-in (5 attempts per e-mail and address, 20 per address, per minute); the token expires after 30 days and can be revoked (`DELETE /api/v1/sesiones/actual`). `GET /api/v1/sesiones/actual` tells which profiles the account has (estudiante, docente).
- A2: version 1 only reads; the only writes are creating and revoking the client's own token.
- A3: student endpoints need a verified e-mail and a person record, and return only that person's data; teacher endpoints need a teacher profile and return only that teacher's groups. Public endpoints need no token and are limited per address (60 per minute; verification 30 per minute, like the web page).
- A4: the API documentation (Scramble, `/docs/api`) is visible in local development and to staff with panel access.
- A5: JSON keys in Spanish snake_case; amounts as decimal strings in soles ("80.00"); dates as `YYYY-MM-DD` and instants in ISO 8601 with the Lima offset; business errors answer 422 with `message`, unauthenticated 401, forbidden 403, unknown 404.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1, S4, S5, S6, A1, A4, A5 | inline | done | ff1535f |
| T2 | S1, S2, S4, A3, A5 | inline | done | (T2 commit) |
| T3 | S1, S4, S5, A3, A5 | inline | pending | |
| T4 | S1, S4, S5, A3, A5 | inline | pending | |
| T5 | S1, S4, A3, A5 | inline | pending | |
| T6 | all | inline | pending | |

- T1: foundation and sessions: `AutenticarUsuario` extracted from the web sign-in (the screen reuses it), token issue/inspect/revoke, `api` rate limiter, token expiry, Scramble configuration and gate. Risk: item 2 (authentication, credentials).
- T2: public endpoints: offer of the period, fees in force, document verification (Action extracted from the web controller, which reuses it). Risk: none (public read of data already public).
- T3: student endpoints: profile, enrollments, payments (Actions extracted from the portal screens, which reuse them). Risk: item 2 (per-person data boundary).
- T4: student endpoints: grades by language, trámites, issued documents with PDF download. Risk: item 2 (per-person data boundary).
- T5: teacher endpoint: own groups with schedule (Action extracted from the teacher screen). Risk: item 2 (per-teacher boundary).
- T6: docs (decisiones, arquitectura, seguridad, modulos), full suite, independent verifier, native review.

Route evidence: every task reuses the T1 foundation (route groups, limiter, resource conventions) and several extract Actions from screens in the same modules, so there is no independent parallel unit worth a writer; all inline. Forecast about 2,000 authored changed lines; one commit per unit.

## Log

- L1 (2026-10-25, user): "Bien, avanza con ese alcance mínimo"
- L2: Mapping: `ModuleServiceProvider` already loads each module's `routes/api.php` under `api` + prefix `api/v1`; no module has one. Sanctum installed (`HasApiTokens`, tokens table, `expiration => null`); no token endpoint; sign-in logic and limits live inside the Livewire `IniciarSesion`. Offer (`OfertaPublica`) and fees (`TarifasVigentes`) are contracts; verification, enrollments, payments, trámites, documents and teacher groups query models inside controllers or components; no grades or documents list screen exists (`HistorialDeNotas` is internal to Certificacion). No JsonResources, no `config/scramble.php`, no named limiters. The `estudiante`/`docente` middleware only return 403.
- L3: T1: `AutenticarUsuario` (Shared/Auth) holds the credential check and the shared limits (5 per e-mail and address, 20 per address, per minute; same cache keys as before); the web sign-in reuses it and keeps its behavior (password cleared only on wrong credentials). `EmitirTokenDeApi` issues a 30-day Sanctum token named after the device. `routes/api.php` (registered with prefix `api/v1`): `POST /sesiones`, `GET|DELETE /sesiones/actual`. `api` limiter 60/min per token user or address (`throttleApi`). Scramble at `/docs/api` (title "API del CILN", path `api/v1`) behind the `viewApiDocs` gate (panel access); bearer auth documented automatically. Daily `sanctum:prune-expired` at 00:20 Lima. RED observed (10 failing), GREEN 10/10; sign-in, portal, architecture and API auth tests 64/64. One sign-in limiter test failed once under machine load and passes alone and in its file, with and without the change (per-minute window rolled over).
- L4: T1 reviewed alone (base b0cc120, escalated item 2) with risk, resilience and reliability lenses: approved and acknowledged, no blockers. Warnings applied, each pinned by a test that failed before: `AutenticarUsuario` again fires `Attempting`/`Failed` and upgrades outdated password hashes like `Auth::attempt` (web and API); the token is created only after the profiles are read, so a failure there leaves no orphan token; revoking without a token answers 409 instead of a silent 204; the per-address limit is proved through the API and a success clears only the account key. Not changed: tokens are issued to unverified accounts (student endpoints will require a verified e-mail, A3); the web already normalized the e-mail before the shared limits, so the cache keys are the same. API, portal and architecture 111/111.
- L5: T2: `GET /api/v1/oferta` (Academico, `OfertaPublica` contract; `{"data": null}` without a period; no room, link or teacher), `GET /api/v1/tarifas` (Pagos, `TarifasVigentes` for today in Lima; amount as decimal string), `GET /api/v1/documentos/{codigo}` (Certificacion; new `VerificarDocumentoPorCodigo` Action reused by the web page; `throttle:30,1`, `noindex`, 404 JSON "No existe un documento con ese código."; dates as `YYYY-MM-DD` through two new optional fields of `VerificacionDeDocumento`). RED observed (6 failing), GREEN 6/6; API, Certificación, public page and architecture 152/152.
