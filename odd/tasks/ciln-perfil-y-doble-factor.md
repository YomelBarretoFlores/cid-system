# CILN panel profile, two-factor sign-in and inactivity expiry

Objective: give panel staff a profile page (change password asking the current one; name and e-mail read-only), require two-factor sign-in with an authenticator app, and close the panel session after a shorter inactivity, as `docs/seguridad.md` promises.
Branch: `feat/perfil-y-doble-factor` (from `fix/menu-de-usuario` fa31ec7) · Delivery: one commit per unit on the feature branch, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1 (proposal approved 2026-10-25, "Bien, sí avazarás con eso y el modo oscuro lo dejamos afuera por ahora"): "una página de perfil en el panel con: cambio de contraseña (pidiendo la actual)" and "nombre y correo solo de lectura".
- S2 (same proposal): "doble factor con app autenticadora (Google Authenticator, Microsoft Authenticator)".
- S3 (same proposal): "La expiración por inactividad la haría en el mismo paso".
- S4 (`docs/seguridad.md`): "| Administradores y cajeros | Doble factor (MFA de Filament) y expiración por inactividad más corta (middleware propio) |".
- S5 (user, 2026-10-25): "el modo oscuro lo dejamos afuera por ahora" — no theme switcher.
- S6 (user rule, 2026-10-24): resilience and observability by default.

Assumptions (to record in `docs/decisiones-pendientes.md` and confirm with the CILN and the DSTI):

- A1: every panel account (permission `admin-panel.access`) must set up the authenticator app on its first sign-in; roles are still pending (#2), so the panel's users are the staff the security document names. Eight single-use recovery codes are given for a lost phone.
- A2: the panel closes the session after 30 minutes without activity (configurable, `SESSION_PANEL_LIFETIME`); the rest of the system keeps its 120 minutes.
- A3: the panel only accepts sessions opened through its own sign-in (the only one with the second factor); the session mark names the account that signed in. A session opened on the portal, restored by "remember me" or left by another account is closed when it reaches the panel, with "Ingresa al panel con tu correo, tu contraseña y el código de tu aplicación."; "remember me" is not offered in the panel.
- A4 (resilience, S6): staff who lose both the phone and the recovery codes get their second factor cleared by the DSTI with `php artisan auth:reiniciar-doble-factor <correo>`.

## Tasks

| ID | Specs | Route | Status | Commit |
|---|---|---|---|---|
| T1 | S1, S5 | inline | done, review approved | 611c7bf, ca0bb54 |
| T2 | S2, S4, A1 | inline (Risk: items 1, 2) | done, review approved | dae73de, fb7a6b3 |
| T3 | S3, S4, A2, A3 | inline (Risk: item 2) | done, review approved | 6db354a, 2f31ddd |
| T4 | all, A4 | docs inline + independent verifier at the end | review and verifier running | f9857c2 |

- T1: profile page in the panel (user menu): name and e-mail read-only; new password with confirmation asks the current password; other sessions close after a password change.
- T2: authenticator app sign-in (migration with the secret and recovery-code columns, encrypted), required for every panel account, managed from the profile page.
- T3: panel session guard (persistent, so Livewire actions count too): only sessions opened by the panel sign-in, bound to the account, inactivity expiry, no "remember me".
- T4: reset command (A4); `docs/seguridad.md`, `docs/decisiones-pendientes.md` (#92–#94), `docs/despliegue.md`; verifier over the whole feature.

## Log

- L1 (user, 2026-10-25): "Bien, sí avazarás con eso y el modo oscuro lo dejamos afuera por ahora. Pero antes compactaré la conversación." then "Bien, ahora puedes proseguir."
- L2 (exploration): Filament 5.10.3 provides `EditProfile` (current password field shown when the password changes) and `AppAuthentication` with recovery codes. Required MFA (`EnsureMultiFactorAuthenticationIsEnabled`) only checks that the account has a provider enabled, not that the session passed the challenge; the portal sign-in (`IniciarSesion`) opens the same `web` session and `DestinoTrasIngreso` sends staff-only accounts to the panel dashboard, so it would skip the second factor. Hence A3.
- L3 (decision): A3 implemented without touching the portal: the panel guard closes any session without the panel mark, so staff who sign in on the portal land on the panel sign-in with the notice. Tests: `TestCase::actingAs()` stands for staff already signed in through the panel (gives a panel account a secret and the mark); sign-in tests use `be()`.
- L4 (evidence): browser check on a seeded preview database (`ciln_preview`, dropped afterwards): required set-up page with QR and 8 recovery codes, challenge on sign-in, profile with read-only name and e-mail, old session refused with the notice, and a Livewire save after 2 idle minutes (`SESSION_PANEL_LIFETIME=2`) redirected to the sign-in with the inactivity notice. `composer check`: 1469/1469.
- L5 (review): T3 review warnings applied in 2f31ddd (mark bound to the account, 1-minute floor, exact 30-minute boundary, recovery sign-in through the panel page); rollout note and no-polling statement in the docs.
