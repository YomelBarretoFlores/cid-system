# CILN visual plan: the items still missing

Objective: close every item of the visual plan `golden-rolling-hippo` that was left out of V1–V5 and `feat/reportes-y-notas-diseno`, except the sidebar blink (waits for the user's recording).
Branch: `feat/plan-faltante` (from main ca64909). Delivery: one commit per unit; the user decides push and merge. Test runner: Pest (`/private/tmp/claude-501/ciln-v-OYLV/pest.sh`).

## Specs

- S1 (user): "Sube y une y continúa con lo faltante".
- S2 (plan V5): "la acción "Todos presentes" en el llamado de inicio".
- S3 (plan V4, Inicio): "Te toca a ti: … rendir la clasificación pendiente."; "Le toca al CILN: pagos por validar ("Lleva tu boleta original al CILN") y trámites en revisión."; "Esta semana: las clases y los plazos reales (cierre de matrícula, ventana de actas). No se inventan exámenes."
- S4 (plan V5): "Tema propio `resources/css/filament/docente/theme.css`: importa el del admin y agrega lo del docente. Se suma a Vite y se aplica con `->viteTheme()` en `DocentePanelProvider`."
- S5 (plan V4): "Mis pagos, Mis matrículas, Clasificación y Trámites: el diseño de la propuesta, sin cambiar la lógica."
- S6 (review advisory R3-dash-assertion-weak): the test of an acta without validation date must prove the Validada cell shows the dash, not any dash on the page.
- S7 (user, standing): no "tardanza"; administrable language flags stay; schedule and enrollment-sheet formats stay.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, S6 | inline | done | see L2 |
| T2 | S3 | inline | done | see L3 |
| T3 | S4 | inline | pending | |
| T4 | S5, S7 | inline | pending | |

## Log

- L1 (2026-11-01): "Sube y une y continúa con lo faltante". feat/reportes-y-notas-diseno pushed and main fast-forwarded ab9c820 → ca64909. The missing items were found by checking the plan against the code. Routes are inline: each unit is one understood change with no writer reason.
- L2 (2026-11-01): T1 done. The Estudiantes section of `AsistenciaDelGrupo` (key `estudiantes`) gets the header action "Todos presentes", visible only on the opening call; it fills every student of the call as present without saving, so the call rules still apply on save. The null-validada test now also asserts the old date is gone. Checks: composer check rc=0 (1950 tests).
- L3 (2026-11-01): T2 done. New Tramites contract `TramitesEnProceso` (`ContarTramitesEnProceso`). `ResumenDelEstudiante` passes the placement exams still to sit (pending and not expired, with the language from `CatalogoDeIdiomas`), the procedures in process and `Matricula\Support\PlazosDeLaSemana` (enrollment closings from `VentanasDeMatricula`, start and end of the acta window of the current group from `CursoDelGrupo`, only when they fall in the current week). Te toca a ti adds "Rinde tu examen de clasificación de X." with its last day; Le toca al CILN adds "Tu(s) trámite(s) … en revisión" with an "En revisión" badge; Esta semana prints the deadlines on their day, also for a student without classes.
  - The Tablero flake (explicit period number 9 against a random factory number in the same year) hit twice, so ReportesGeneralesTest now puts that extra period in 2028 (separate commit).
  - Checks: composer check rc=0 (1953 tests); home capture with no JS errors and no mobile overflow.
