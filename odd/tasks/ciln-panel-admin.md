# CILN · Admin panel corrections

Objective: fix every finding of the admin-panel audit of 2026-11-02 (main 25ccdb0), starting with findings 1–4 and 13.
Branch: `fix/panel-admin` from main 25ccdb0. Delivery: ask-on-risk, work-unit commits; push and merge are the user's call. Runner: Pest (`/private/tmp/claude-501/ciln-v-OYLV/pest.sh`), `composer check`.

## Specs

User request (verbatim): "Sí, corrige todo, empezando por el 1 al 4 y el 13". The numbers refer to the audit findings reported to the user:

- S1 (finding 1): "No se pueden administrar usuarios, roles ni permisos desde el panel." Staff accounts, roles and permissions managed from the panel; an account can be deactivated; an admin cannot lock everyone out.
- S2 (finding 2): "Los PDF del personal no tienen la protección de sesión del panel." The staff `/panel/...` downloads require the panel session (second factor, 30-minute timeout).
- S3 (finding 3): "El log de auditoría no se puede consultar desde el panel" and the unaudited tables (personas, periodos, grupos, horarios, aulas, idiomas, ciclos).
- S4 (finding 4): "Los mensajes de validación salen en inglés." Built-in validation messages in Spanish.
- S5 (finding 5): editing a ciclo and deleting a horario go through Actions with their business rules.
- S6 (finding 6): opening a group needs a schedule and a teacher; the minimum quota is used; closing a period finalizes its groups.
- S7 (finding 7): rectifying an acta flags the documents already issued from it.
- S8 (finding 8): a closed acta's PDF keeps the signers and denomination of its closing.
- S9 (finding 9): the certificate rule reads the cycles of each level from the configured data, not a fixed 4.
- S10 (finding 10): a procedure can be closed without running its final action.
- S11 (finding 11): staff can adjust a student's enabled cycle and correct a placement result.
- S12 (finding 12): a concept's enabled service is locked once it has payments; voiding a placement payment affects its request; the enrollment shows its payment status.
- S13 (finding 13): "«Ver el pago» desde el tablero gerencial da 404." The dashboard links each payment by its ULID.
- S14 (finding 14): one undecryptable field does not break a whole page.
- S15: minor findings (tú/usted, amounts with more than two decimals, small items of the three audit reports).

## Tasks

- [x] T1 · S13 · inline · dashboard payment link by ULID (c622f00).
- [x] T2 · S2 · inline · `/panel/...` staff downloads behind the panel session (20553c1). Risk: item 2 (security).
- [x] T3 · S4 · delegated (context backstop) · Spanish validation, auth and password messages.
- [x] T4 · S1 · delegated (context backstop) · staff users, roles and permissions screens; deactivated accounts cannot sign in. Risk: items 1 and 2.
- [x] T5 · S3 · delegated (context backstop) · audit log screen and audit triggers on the remaining tables. Risk: item 1 (migration).
- [x] T6 · S5 · delegated (context backstop) · ciclo edit and horario add/edit/delete through Actions with locks once used.
- [x] T7 · S6 · delegated (context backstop) · group open/close/finalize preconditions, minimum quota confirmation, period closing finalizes/cancels groups, start-date confirmation. Risk: items 3 and 4.
- [x] T8 · S7 · delegated (context backstop) · documents of a rectified student flagged "Por revisar" (cer_documentos.revisar_desde, motivo_revision; trigger allows only these on a valid document); panel column, filter and view section. Risk: item 1.
- [x] T9 · S8 · delegated · cer_actas.firmas and denominacion frozen at every closing; PDF falls back to the live lookup only for older actas. Risk: item 1.
- [x] T10 · S9 · delegated · Academico contract CiclosDelNivel; level completion from the configured active cycles.
- [ ] T11 · S10 · inline
- [ ] T12 · S11 · inline
- [ ] T13 · S12 · inline
- [ ] T14 · S14 · inline
- [ ] T15 · S15 · inline

## Log

- L1 (2026-11-02, verbatim): "Sí, corrige todo, empezando por el 1 al 4 y el 13 Y sobre el cambio, sí, lo hice yo."
- L2: the user's own edit of `resources/views/auth/iniciar-sesion.blade.php` committed apart as 62b3a5c.
- L3: audit evidence: three read-only explorers plus a browser walkthrough of 51 admin lists and 21 records as admin and an access probe as cashier. Verified by the parent: no `lang/` directory; `/panel/*` routes lack `ProtegerSesionDelPanel`; permissions granted only in `DatabaseSeeder`; dashboard link 404 from `CalcularPagosPorValidar.php:62`.
- L4: T2 RED→GREEN: route test listed the 5 `panel.*` routes without `panel.sesion`; behavior test got 200 instead of the panel sign-in redirect. Guests of `/panel/*` now go to the panel sign-in.
- L5: T3 RED→GREEN (writer): MensajesEnEspanolTest got "The nombre field is required." before, Spanish after; Feature+Modules 1881 passed. `.env.example` could not be read (permission rule); if it sets APP_LOCALE=en the user must change it.
- L6: T4 (writer) RED→GREEN: 21 new Auth tests failed before the code, 57 Auth tests pass after; mutation check (removing the deactivation checks) made the deactivation test fail. Audit triggers needed an identity `id` on the spatie pivot tables (aud_registrar reads NEW.id). Parent updated DemoSeederTest and NavegacionDelPanelTest lists, phpstan migration path, decision #2 note. Screens checked in the browser (users list, create, edit, role create): no errors.
- L7: composer check: 2001/2002, the one failure was TrasladoConcurrenteTest exceeding its 60 s process timeout; it passed twice alone. TarifaConcurrenteTest failed once and errored once across runs, then passed twice. Both are timing-sensitive concurrency tests outside T4's code; recorded as intermittent, to watch in CI.
- L8: independent verifier (T2+T4, scratch clones of the branch and of 25ccdb0): S1 pass; S2 fail with one change-caused blocker: `panel.sesion` ran before the admin-access check and signed portal accounts out of a `/panel` link (baseline: 403, still signed in). Advisories: a deactivated account's open marked session still downloaded; the mandatory second factor was not required on downloads; `personal:crear-administrador` exited 1 after granting when the link had been sent under a minute before; self-grant by a manager (by design); audit actor null for console calls (existing).
- L9: correction batch (composer check rc=0, 2006 tests) (RED→GREEN for each): `ProtegerDescargaDelPanel` checks `canAccessPanel` before the panel session (portal account 403 and stays signed in; deactivated account 403) and chains `ExigirDobleFactorSiEsObligatorio`; the command grants first, then sends the link and only warns if it cannot (exit 0).
- L10: review review-0420a4fd0f45d672 (T4 + corrections, granted by the user) approved and acknowledged. Advisories: creating a user threw after the account was saved when the link failed (fixed: the create page sends the link and only warns; link failures, including mail transport errors, become a Spanish warning; RED against the old code: the exception reached the user); lock serialization on the permissions row (suggestion, kept).
- L11: T5 (writer) RED→GREEN: 23 tests under tests/Feature/Auditoria. Triggers on per_personas, aca_periodos, aca_grupos, aca_horarios, aca_aulas, aca_idiomas, aca_ciclos; permission auditoria.ver granted to active user managers; read-only Configuración › Auditoría with filters and a field diff hiding secrets; index on aud_registros.usuario_id; Lima date filter binds an explicit -05:00 offset. composer check rc=0, 2030 tests.
- L12: review review-9c778811a106a8d9 (T5 + link fix, granted by the user) approved and acknowledged; advisory: the audit table filter ran DISTINCT over the growing log (fixed: fixed list from NombresDeTablas, test that it covers every table with an aud_registrar trigger).
- L13: composer check rc=0, 2067 tests. T6+T7 (writer) RED→GREEN on a baseline copy: 32 of 283 Academico tests failed before, 283 pass after; Feature+Modules+Architecture 1982 passed; CargaDocenteConcurrenteTest 4 passed. New Docencia contract ClasesDictadasDelGrupo. Decisions 124–128 recorded as assumptions. Open questions: removing the teacher or last horario of an Abierto group is still allowed; reopening a Cerrado group in an En curso period is now refused.
- L14: review of 59291ec..5d43bf8 first refused (lens_context_budget_exceeded: provider range started at fa6cb19 with already-reviewed commits); reduced to --base-ref 59291ec, user granted again; review-27ab390500ea518d approved and acknowledged. Advisory: a period test depended on the real clock (pinned with travelTo).
- L15: composer check rc=0, 2080 tests. T8–T10 (writer) RED→GREEN: S7 6 tests failed on the missing columns; S8 frozen signers null before; S9 3/4/4 split listed [5,6,7,8] before and [5,6,7] after. Certificacion 160 passed; Feature+Modules+Architecture 1995; Concurrency+Unit 85. Extra column cer_rectificaciones.notas_previas to know whose result changed. Parent ran migrate, rollback --step=2 and migrate again on the preview DB: clean. Open: the default trámite type text still says "Los cuatro ciclos" (T15); a renamed language orphans closed actas' levels (existing).
