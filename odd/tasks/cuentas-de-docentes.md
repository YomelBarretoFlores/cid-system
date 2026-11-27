# Teacher accounts managed by staff, and deactivation of portal accounts

Objective: teachers sign in with their DNI and a password that CILN gives them; staff create those accounts, reset passwords at the office, and deactivate or reactivate portal accounts. Branch `feat/cuentas-de-docentes`. Delivery: one commit per task. Runner: Pest on PostgreSQL 18 (`php artisan test --compact`, `composer check`).

## Specs

- S1. Teachers sign in with a username and password that CILN gives them: "anteriormente en el sistema viejo ellos accedían con un usuario y contraseña que el CILN les proporcionaba. Algo así debe de seguir, esto porque la mayoría de los docentes son personas de la 3ra edad y que no manejan mucho la tecnología." The username is the DNI: "Sí, que su usuario sea su DNI." The sign-in field accepts the username or the email, so students and staff keep signing in as today. An email is not required for these accounts.
- S2. Staff manage teacher accounts: "Creo que sería lo mejor que el administrador tenga una sección o algo para que pueda administrar las cuentas de los docentes." A «Cuentas de docentes» section creates the account (username = DNI, initial password typed by staff), shows the username, and does not force a password change on first sign-in (agreed proposal).
- S3. Password reset at the office: "Y si es que se olvidan el usuario o contraseña el docente vaya a la oficina correspondiente a que le puedan cambiar de contraseña." Staff set a new password; the teacher's open sessions are closed.
- S4. Deactivate and reactivate accounts: "creo que sería bueno además, que haya opción de dar de baja cuentas" … "obvio si hay la opción de dar de baja también debe de haber la opción de revertir o reincorporar." Agreed cases: a teacher who leaves CILN, a compromised or misused account, a duplicate account. Inactive students are not deactivated for inactivity. Deactivation asks for a reason, keeps all data, closes sessions, and is audited; staff deactivation already exists.

## Tasks

- [ ] T1 · S1 · `users.usuario` (DNI) and sign-in with username or email (portal and API); email optional for those accounts · inline
- [ ] T2 · S2–S3 · «Cuentas de docentes» section: create account with DNI and staff-typed password, office password reset · inline
- [ ] T3 · S4 · Deactivate and reactivate portal accounts (teachers and students) with a reason · inline
- [ ] T4 · all · Manuals (staff, teacher) and data dictionary · inline

## Log

- L1 (2026-11-27) Original request: "Bien, ahora vamos a corregir algunas cosas. Primero los inicios de sesión para los docentes, anteriormente en el sistema viejo ellos accedían con un usuario y contraseña que el CILN les proporcionaba. Algo así debe de seguir, esto porque la mayoría de los docentes son personas de la 3ra edad y que no manejan mucho la tecnología. Creo que sería lo mejor que el administrador tenga una sección o algo para que pueda administrar las cuentas de los docentes. Y si es que se olvidan el usuario o contraseña el docente vaya a la oficina correspondiente a que le puedan cambiar de contraseña. Y ahora que lo pienso creo que sería bueno además, que haya opción de dar de baja cuentas, esto sería útil?, para qué casos? Quizás si un estudiante ya no estudia por largos años darle de baja y ya no acceda a su cuenta?, o no es lo mejor?, obvio si hay la opción de dar de baja también debe de haber la opción de revertir o reincorporar."
- L2 (2026-11-27) "Sí, que su usuario sea su DNI."
- L3 Current state: sign-in is by email only (`AutenticarUsuario`, portal `IniciarSesion`, API `EmitirTokenDeApi`); teachers use the portal sign-in and the `docente` panel. Teacher accounts are created by `CrearCuentaDelPortal` (record email plus emailed link). `DesactivarUsuario`/`ReactivarUsuario` exist only for staff accounts (`GestoresDeUsuarios`); `users.desactivado_en` already blocks sign-in and panel access.
