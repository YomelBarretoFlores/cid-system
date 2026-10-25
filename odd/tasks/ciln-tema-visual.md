# CILN visual theme from the design handoff

Objective: apply the final high-fidelity design in `design_handoff_ciln/` (README, `fuentes/*.dc.html`, assets) to the student and teacher portals and to the Filament panel, with the styles centralized in shared tokens and components; business rules do not change.
Branch: `feat/tema-visual` (from `main` c634ae1) · Delivery: one commit per unit on the feature branch, push and merge decided by the user · Test runner: Pest (`php artisan test`); styling-only changes need no new tests, existing tests stay green.

## Specs

- S1: "Sí, arranca." (after "primero el diseño, como un solo bloque, y después seguimos con los flujos").
- S2: "Pero ojo, para esto de estilos y diseño sigue las buenas prácticas. Quizás centralizarlo, estilos repetitivos buscar una buena práctica, etc."
- S3 (user, design review): "mira lo cómo quiero que se vea visualmente, se ve elgante, moderno, único y no genérico."
- S4 (handoff README, Fidelity): "Alta fidelidad (hifi). Colores, tipografía, espaciado, radios e interacciones son finales. Recrear con precisión; en Filament, aplicar vía tema personalizado (`->viteTheme()`), `->colors()`, `->font('Figtree')` y vistas/estilos de componentes."
- S5 (handoff README, About): "No son código de producción para copiar. La tarea es recrear estos diseños en el entorno existente del repositorio ... Toda regla de negocio ya vive en las Actions; el diseño solo cambia la presentación."
- S6 (agreed scope): base theme now — Filament theme, CILN colors shared with the portals, typography, logo and favicon, global search and user menu in the sidebar, empty states, portal screens restyled; the full dashboard (M5 metrics) waits for M5, the public page for M6.

Assumptions:
- D1: One source of truth for design tokens (`resources/css/ciln/tokens.css`, Tailwind 4 `@theme`) imported by the portal stylesheet and the Filament theme; repetition is solved with Blade components (`x-ciln.*` brand pieces, `x-portal.*` controls), not with copied class strings or `@apply` chains.
- D2: Figtree is self-hosted through the Vite fonts manifest (no Google Fonts request at runtime); Filament uses it through `LocalFontProvider`.
- D3: The design files stay out of the repository (local `.git/info/exclude`); only the emblem assets are copied into `public/images/ciln/`.
- D4: Filament keeps its own tables, forms and actions; the theme restyles them (gold accent, ink primary buttons, warm grays, design radii); no Filament view is published.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, S4, D1-D3 | inline | done | 7a60490 |
| T2 | S2-S5 | inline | done | 530b316 |
| T3 | S3-S6 | inline | done | 87ec484 |
| T4 | S2, S4, S6, D4 | inline | done | 22783be |
| T5 | all | inline | done | see L7 |

- T1: Foundations: shared tokens, self-hosted Figtree, emblem assets and favicon, `x-ciln.emblema` and `x-ciln.hoja-bandera`. Risk: none.
- T2: Portal shell (sidebar layout, split auth layout) and the restyled `x-portal.*` controls. Risk: none (navigation conditions unchanged, covered by existing tests).
- T3: Student, teacher and auth screens restyled on the new components. Risk: none.
- T4: Filament theme (tokens, colors, font, logo, sidebar search and user menu, custom report pages). Risk: none.
- T5: Docs, full suite, review.

Route evidence: each unit builds on the previous one's tokens and components (no independent parallel units); all inline.

Forecast: about 1,600 authored lines in five commits.

## Log

- L1 (2026-10-24): "Sí, arranca. Pero ojo, para esto de estilos y diseño sigue las buenas prácticas. Quizás centralizarlo, estilos repetitivos buscar una buena práctica, etc."
- L2: Map: portal stylesheet `resources/css/app.css` (Tailwind 4, `@theme` with blue `ciln-*` and `oro-400`), fonts through `laravel-vite-plugin/fonts` (`bunny('Instrument Sans')`, `@fonts`), layout `components/layouts/portal.blade.php` (top bar), controls already centralized in `components/portal/*` (alerta, archivo, boton, campo, enlace-menu, insignia, seleccion, tarjeta). Filament 5.10.3 without a theme (`->colors(['primary' => Color::Blue])`); `LocalFontProvider` exists. Design tokens and screens in `design_handoff_ciln/README.md`.
- L3: T1 inline (styling, no RED applicable): `resources/css/ciln/tokens.css` (Tailwind 4 `@theme`: `piedra` and `oro` palettes, semantic colors `fondo`, `lienzo`, `tarjeta`, `tinta`, `secundario`, `acento`, `enlace`, status pairs `ambar/verde/rojo/gris/azul-fondo/texto`, radii `marco/tarjeta/interna/control/pildora`, shadows, `text-titulo`, `text-kpi`), imported by `app.css`; Figtree 400-700 self-hosted through `bunny('Figtree')` in the Vite fonts manifest; emblem assets as WebP (720 px, 49 KB instead of 274 KB PNG), favicon and touch icon; `x-ciln.emblema`, `x-ciln.hoja-bandera` (SVG flags clipped by the leaf radius; unknown language shows a gold leaf). Visual check by headless screenshot.
- L4: T2 inline: `layouts/base` (head, fonts, favicon, skip link, rounded frame) shared by `layouts/portal` (264 px sticky sidebar, Heroicons, active item in gold, user block with initials and role, panel link only with `admin-panel.access`, logout; on mobile the menu folds behind a button with `aria-expanded`) and `layouts/acceso` (split sign-in: form left, emblem with flag leaves right, hidden on mobile); sign-in, password, verification and registration screens moved to it (`#[Layout('components.layouts.acceso')]`). Repetition centralized: `@utility control`, `casilla` and `enlace` in `app.css` (input states through `aria-invalid`, focus ring token), `x-portal.control` (label, help and error once for `campo`, `seleccion`, `archivo`), `x-portal.boton` variants (`primario`, `secundario`, `enlace`; `href` renders a link), `insignia` and `alerta` on the status tokens, new `encabezado`, `vacio`, `acceso`. The right panel says "Idiomas extranjeros y lenguas originarias en la UNASAM." instead of listing languages (the handoff asks to confirm German). Portal and module tests 936/938: the two failures are `RegistroDePagoTest` building the payment date with `now()` in UTC, which is tomorrow in Lima after 19:00 (pre-existing, unrelated to this change; reported to the user).
- L5: T3 inline: student (inicio, pagos, matrícula, mis matrículas, clasificación) and teacher (mis grupos, asistencia, pre-acta) screens rebuilt on the portal components, following the handoff layouts (list rows with gold tiles, sticky side form, radio cards with `has-checked`, segmented attendance control, flag leaves per language, empty states with `x-portal.vacio`). New shared pieces: `x-portal.fila`, `dato`, `pestanas`, `encabezado` (`volver`, `inicio`, `acciones`), `seleccion vivo`, `@utility tarjeta-interna` and `columnas` (the grid without explicit columns sized to its content on phones). Old blue tokens removed. No new data or behavior: the dashboard-like widgets of the design that need new queries (next step, week, class progress, attendance summary, "mark all present") are left out (S5). Five list tests asserted information order: four now follow the order of the design (period before schedule, request date after the state, enrollment state as a data item, pre-acta scheme line); `MisPagosTest` only passed because the list was printed twice (phone and desktop) and the expected order contradicted `orderByDesc('fecha_pago')`; its data now gives the rejected payment the later date. Visual check at 1440 and 500 px (headless Chrome does not go below 500). Portal and module tests 936/938 (the two `RegistroDePagoTest` clock failures).
- L6: T4 inline: `resources/css/filament/admin/theme.css` (imports Filament's theme and the shared tokens; canvas frame, white rounded sidebar, gold active item, group labels, ink pill primary buttons, status badges on the token pairs, inputs with the focus ring, green toggles, rounded sections, tables, modals, dropdowns; `@utility tabla-reporte` for the two custom report views); panel: `viteTheme`, palettes from `App\Shared\Ui\PaletaCiln` (copy of the tokens, `tests/Unit/PaletaCilnTest.php` fails on drift; mutation of `oro-400` fails it), Figtree through `LocalFontProvider` plus the Vite fonts tags in `HEAD_END`, brand view with the emblem, favicon, no topbar, global search and user menu in the sidebar, 268 px sidebar, no dark mode. Filament keeps its own tables, forms and actions (D4). The panel shows English labels in tests and local runs with `APP_LOCALE=en`; production uses `APP_LOCALE=es` (`docs/despliegue.md`). The sidebar period card of the design needs period data and waits for M5. `composer check`: Pint, Rector, Larastan pass; Pest 1051/1053 (the same two clock failures).
- L7: T5: ADR 0008 (shared design system), `docs/arquitectura.md` section "Sistema de diseño", `.claude/rules/ui.md` (tokens and components, no hard-coded colors; palette test), decisiones-pendientes #54 (German in the visual identity). The row hover color moved to a token (`fila-suave`). Preview test removed.
