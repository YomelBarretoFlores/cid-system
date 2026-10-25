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
- A3: the panel only accepts sessions opened through its own sign-in (the only one with the second factor). A staff account without a student or teacher profile that signs in on the portal is sent to the panel sign-in; "remember me" is not offered in the panel, since a remembered session would skip the second factor.

## Tasks

| ID | Specs | Route | Status | Commit |
|---|---|---|---|---|
| T1 | S1, S5 | inline | pending | — |
| T2 | S2, S4, A1 | inline (Risk: items 1, 2) | pending | — |
| T3 | S3, S4, A2, A3 | inline (Risk: item 2) | pending | — |
| T4 | all | docs inline + independent verifier at the end | pending | — |

- T1: profile page in the panel (user menu): name and e-mail read-only; new password with confirmation asks the current password; other sessions close after a password change.
- T2: authenticator app sign-in (migration with the secret and recovery-code columns, encrypted), required for every panel account, managed from the profile page.
- T3: panel session guard: only sessions opened by the panel sign-in, inactivity expiry, no "remember me"; the portal sign-in sends staff-only accounts to the panel sign-in.
- T4: `docs/seguridad.md`, `docs/decisiones-pendientes.md`, `docs/despliegue.md`; verifier over the whole feature.

## Log

- L1 (user, 2026-10-25): "Bien, sí avazarás con eso y el modo oscuro lo dejamos afuera por ahora. Pero antes compactaré la conversación." then "Bien, ahora puedes proseguir."
- L2 (exploration): Filament 5.10.3 provides `EditProfile` (current password field shown when the password changes) and `AppAuthentication` with recovery codes. Required MFA (`EnsureMultiFactorAuthenticationIsEnabled`) only checks that the account has a provider enabled, not that the session passed the challenge; the portal sign-in (`IniciarSesion`) opens the same `web` session and `DestinoTrasIngreso` sends staff-only accounts to the panel dashboard, so it would skip the second factor. Hence A3.
