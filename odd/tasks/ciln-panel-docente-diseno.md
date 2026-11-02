# CILN teacher panel with the new design

Objective: the teacher panel (Filament) follows the design proposal (`docs/diseno/propuesta-visual-2026-11/CILN - Portal Docente.html`) with Filament components, and attendance stays present/absent with the topic. This is delivery V5 of the plan `golden-rolling-hippo`.
Branch: `feat/panel-docente-diseno` (stacked on `feat/portal-estudiante-diseno`). Delivery: one commit per unit; the user decides push and merge. Test runner: Pest.

## Specs

- S1 (user): "Recuerda que el panel de docente se usa filament, así que usa componentes de filament, pero obvio estilizado." and "en el diseño para asistencias está tardanza, pero tú sabes que te dije que ya no iría".
- S2 (plan): "Mis grupos" shows one card per group with the next or today's class, the steps Sílabo / Asistencia / Notas / Acta final with their state, and a button for the next step.
- S3 (plan): attendance shows both calls as steps, present/absent pills, an "all present" shortcut for the opening call and the topic highlighted. There is no late option.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2 | inline | done | see L2 |
| T2 | S3 | inline | pending | |

## Log

- L1 (2026-11-01): "Avanza con todo lo faltante". Plan V1–V5 approved.
- L2 (2026-11-01): T1 done.
  - New Certificacion contract `EstadosDeActas` (batch, `ConsultarEstadosDeActas`).
  - `ResumirMisGrupos` builds `ResumenDeMiGrupo` per group from:
    - `EstadosDeSilabos`;
    - one query for classes held per group, and `ClasesDelHorario::delPeriodo` for programmed classes and the next class;
    - `NotasParcialesDelGrupo` against the active students times the scheme components;
    - `EstadosDeActas` and the acta window from `CursoDelGrupo`.
  - The card shows the next class ("Hoy · …", "Próxima clase: …", "Empieza: …"), the four steps (done, current, pending) linking to each page, and the next-step button.
  - Next step, in order: Completar sílabo, Tomar asistencia (class today), Ver acta final (closed), Cerrar acta (window open and grades complete), Completar notas, then Tomar asistencia.
  - RED: 2 new tests failed first. Docencia panel and Architecture: 70 pass. Browser on the demo: no JS errors, no overflow at 390 px.
  - Deviation from the plan: no separate teacher theme. The cards use Filament components and the shared theme's tokens, which is enough.
