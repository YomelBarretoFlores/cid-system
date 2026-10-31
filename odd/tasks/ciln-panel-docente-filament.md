# CILN teacher panel in Filament (decision #95)

Objective: move the teacher portal (Blade + Livewire under `/docente`) to its own Filament panel at `/docente`, with sign-in through `/ingresar`, the same Actions, and the student options behind "Mi portal de estudiante".
Branch: `feat/panel-docente-filament` (from `main` 90075ff) · Delivery: single-pr (L5), one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`). Plan: `~/.claude/plans/agile-orbiting-wreath.md`.

## Specs

- S1: "Bien, para continuar puedes hacer la migración a filamente para el panel del docente que acordamos?, arma el plan completo"
- S2 (decision #95, docs/decisiones-pendientes.md:100): "pasar el portal docente a un panel Filament propio en `/docente` (mis grupos, asistencia, pre-acta, acta final y actas), con ingreso por `/ingresar` y las mismas Actions; y mostrar al docente las opciones de estudiante solo detrás de un enlace "Mi portal de estudiante", para quien también estudia"
- S3 (plan, keep behavior): same URLs (`/docente`, `/docente/grupos/{grupo}/asistencia|notas|acta`, `/docente/actas`, `/docente/grupos/{grupo}/acta/pdf`); guest → `/ingresar`; no teacher profile → 403; unverified email → verification notice; group of another teacher → 404 on mount and on every action; rejections logged; logout → `/ingresar`; the admin panel is unchanged; Actions, contracts and API v1 unchanged.
- S4 (plan, access): `canAccessPanel` by panel (`admin` → `admin-panel.access`; `docente` → verified email and teacher profile); no `ProtegerSesionDelPanel` and no required MFA on the teacher panel.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, S3, S4 | inline | done | see L4 |
| T2 | S2, S3 | inline | done | see L6 |
| T3 | S2, S3 | inline | done | see L6 |
| T4 | S2, S3 | inline | done | see L6 |
| T5 | S2, S3 | inline | done | see L6 |
| T6 | S2, S3 | inline | done | see L6 |
| T7 | all | inline | pending | |

T1 panel, access, landing, shared panel look, "Mi portal de estudiante", and the "Mis grupos" page, since the panel takes over `/docente` (Risk item 2). T2 "Actas finales" page. T3 attendance page. T4 pre-acta page. T5 final acta page (Risk item 2). T6 remove the Livewire teacher portal, fix links, migrate tests, docs (Risk item 3). T7 browser check (desktop and phone). Route: inline for all (no writer trigger: units depend on T1 and share the panel).

## Log

- L1 (2026-10-30): "Bien, para continuar puedes hacer la migración a filamente para el panel del docente que acordamos?, arma el plan completo /plan"
- L2 (2026-10-30): plan approved by the user in plan mode. Forecast about 1,500 to 2,000 authored lines; the delivery chain strategy is asked once before the second commit.
- L3 (2026-10-30): every teacher already has a person record, which counts as a student record (`ObtenerFichaDelUsuario`). The user chose "A todo docente (Recommended)": the "Mi portal de estudiante" link shows for every teacher.
- L4 (2026-10-31): T1 done. Laravel's middleware priority runs authentication first, so the profile refusal with its message moved into `AutenticarAlPanelDocente` (extends Filament `Authenticate`). An interim "Actas finales" navigation item points to the Livewire page until T2. `composer check` rc=0, 1552 tests.
- L5 (2026-10-31): independent verifier on T1 (ecb0a26): no blockers; 17 probes plus the suite (1569) passed in a scratch clone. Advisories: an unverified non-teacher now gets 403 instead of the verification notice; one extra profile query per request. The user chose "Rama única (Recommended)" for delivery: one PR for the branch.
- L6 (2026-10-31): T2–T6 written inline in one sitting, so the code landed in one commit and the docs in another (the shared layout, routes and test files made per-task commits impractical). Fixes found while testing: a non-native Select renders no options in the HTML, so the class options are asserted through the field; `defaultSort` closures must name their argument `$query`, otherwise the actas list answered 500 over HTTP while `Livewire::test` passed, so an HTTP check was added. `composer check` rc=0, 1552 tests.
