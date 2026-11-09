# CILN · Formal, calm student portal (Procesos like the SGA, preferences)

Objective: make the student portal formal, educational and easy: calmer colors and less rounding, the Procesos pages (Matrícula, Mis matrículas, Mis pagos, Clasificación, Trámites) organized like the SGA with tables, a sidebar and user menu consistent with the admin/docente panels, and a Preferencias page with dark mode.
Branch: `feat/portal-estudiante-formal` from feat/perfil-del-estudiante da15026. Delivery: ask-on-risk. Runner: Pest (`vendor/bin/pest` with `DB_PORT=5433 DB_USERNAME=ciln DB_PASSWORD=secret`), `composer check`.

## Specs

- S1 (user, verbatim): "Te muestro cómo hace el SGA para el tema de matrícula, registro de recibos. Mejora la sección de matrícula, mis matrículas (quizás mejor una tabla), debe de ser formal todo."
- S2 (user, verbatim): "Mis pagos también, claseificación, trámites."
- S3 (user, verbatim): "Como te digo, debe de ser todo formal, educativa. He cambiado de desición y no me gusta mucho el diseño, se ve confuso los pasos, las secciones de mi cuenta y reportes está bien, lo que sigue confuso es procesos, no organiza bien la información así como el SGA. Además, tiene colores muy llamativos el panel completo, mucho ruido visual con los textos, etc. Debe de ser fácil para el usuario, un panel amigable."
- S4 (user, verbatim): "Si te das cuenta, en el sga sus componentes no tiene sus bordes demasiado redondeados, cuenta con algunas configuraciones en preferencias." → smaller radii and calmer palette in the portal only (the public site and the access pages keep their design).
- S5 (user decision, AskUserQuestion): "Modo oscuro" → a Preferencias page with the theme (claro / oscuro / del sistema) saved on the student's account; no color or layout variants.
- S6 (user, verbatim): "Veo también que no tiene sidebar colapsable, el footer de el sidebar no es consistente o no sigue lo mismo que los paneles de admin o docente." → the collapse control and the account actions follow the admin/docente panels (top of the layout, user menu), not the sidebar footer.
- S7 (user, verbatim): "Crea una nueva rama y allí haremos todos estos cambios de diseño."
- S8 (assumption): Mi cuenta and Reportes keep their organization ("las secciones de mi cuenta y reportes está bien"); business rules, routes and PDFs do not change; every page title gets the SGA breadcrumb + title + one-line description pattern.
- Checks: `composer check` rc=0; desktop and 390 px captures in light and dark without overflow or JS errors.

## Tasks

- [x] T1 · S3, S4, S6 · inline · calmer tokens and radii scoped to the portal, page header with breadcrumb, sidebar collapse at the top and user menu like the panels.
- [ ] T2 · S5 · Preferencias page with dark mode saved per account and applied without a flash.
- [ ] T3 · S1, S3 · Matrícula like the SGA: status heading, requirements checklist, side information; group choice as a formal table.
- [ ] T4 · S1 · Mis matrículas as a formal table.
- [ ] T5 · S2 · Mis pagos like the SGA Registro de pagos: period selector, summary, table, register receipt.
- [ ] T6 · S2 · Clasificación and Trámites formal (tables, plain forms).
- [ ] T7 · checks · browser verification light/dark, desktop/mobile.

## Log

- L1 (2026-11-09, verbatim): "[Image #13] [Image #14] [Image #15] Te muestro cómo hace el SGA para el tema de matrícula, registro de recibos. Mejora la sección de matrícula, mis matrículas (quizás mejor una tabla), debe de ser formal todo. Mis pagos también, claseificación, trámites. Como te digo, debe de ser todo formal, educativa. He cambiado de desición y no me gusta mucho el diseño, se ve confuso los pasos, las secciones de mi cuenta y reportes está bien, lo que sigue confuso es procesos, no organiza bien la información así como el SGA. Además, tiene colores muy llamativos el panel completo, mucho ruido visual con los textos, etc. Debe de ser fácil para el usuario, un panel amigable. Si te das cuenta, en el sga sus componentes no tiene sus bordes demasiado redondeados, cuenta con algunas configuraciones en preferencias.[Image #16] Veo también que no tiene sidebar colapsable, el footer de el sidebar no es consistente o no sigue lo mismo que los paneles de admin o docente. Crea una nueva rama y allí haremos todos estos cambios de diseño."
- L2: preferences scope asked; the user chose "Modo oscuro" (no accent colors, no layout variants).
- L3: S6 clarified by the map: the admin/docente panels keep the user menu and "Contraer el menú" (⌘/Ctrl+B) pinned at the sidebar foot (`AparienciaDelPanel`, `alternar-menu.blade.php`); the portal footer sat below the fold once the menu grew, so the collapse button was not visible. T1: `resources/css/ciln/portal.css` `.tema-portal` (on the portal `<body>` via the base layout `tema` prop) remaps radii (8/6 px) and colors to cool neutrals with the logo blue as the only accent, plus `@utility tabla-portal`; the public site, acceso and panels are untouched. Sidebar is full height: nav scrolls, the foot pins an account menu (Mi perfil, Portal docente, Panel administrativo, Cerrar sesión) and the collapse button with the shortcut. Gold accents in portal components replaced by `acento`; document accesses lose the pastel circles and pill subtitles. `x-portal.encabezado` is now the SGA header: trail Alumno › NAME › page, title, one-line description, no card. Tests added with the change (MenuDelPortalTest foot and trail); 357 portal tests passed.
