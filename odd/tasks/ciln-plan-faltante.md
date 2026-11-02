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
| T3 | S4 | inline | done | see L4 |
| T4 | S5, S7 | inline | done | see L5 |

## Log

- L1 (2026-11-01): "Sube y une y continúa con lo faltante". feat/reportes-y-notas-diseno pushed and main fast-forwarded ab9c820 → ca64909. The missing items were found by checking the plan against the code. Routes are inline: each unit is one understood change with no writer reason.
- L2 (2026-11-01): T1 done. The Estudiantes section of `AsistenciaDelGrupo` (key `estudiantes`) gets the header action "Todos presentes", visible only on the opening call; it fills every student of the call as present without saving, so the call rules still apply on save. The null-validada test now also asserts the old date is gone. Checks: composer check rc=0 (1950 tests).
- L3 (2026-11-01): T2 done. New Tramites contract `TramitesEnProceso` (`ContarTramitesEnProceso`). `ResumenDelEstudiante` passes the placement exams still to sit (pending and not expired, with the language from `CatalogoDeIdiomas`), the procedures in process and `Matricula\Support\PlazosDeLaSemana` (enrollment closings from `VentanasDeMatricula`, start and end of the acta window of the current group from `CursoDelGrupo`, only when they fall in the current week). Te toca a ti adds "Rinde tu examen de clasificación de X." with its last day; Le toca al CILN adds "Tu(s) trámite(s) … en revisión" with an "En revisión" badge; Esta semana prints the deadlines on their day, also for a student without classes.
  - The Tablero flake (explicit period number 9 against a random factory number in the same year) hit twice, so ReportesGeneralesTest now puts that extra period in 2028 (separate commit).
  - Checks: composer check rc=0 (1953 tests); home capture with no JS errors and no mobile overflow.
- L4 (2026-11-01): T3 done. New `resources/css/filament/docente/theme.css` imports the staff theme and adds the teacher-only styles (present/absent as rounded pills with touch-sized buttons, bolder student names). `AparienciaDelPanel::aplicar` takes the theme name (default admin); `DocentePanelProvider` passes docente; Vite builds both. Docs: arquitectura.md and ADR 0008. Checks: composer check rc=0 (1954 tests); /docente loads docente/theme.css with no JS errors.
- L5 (2026-11-01): T4 done, with no logic change. The screens already followed the proposal's two-column layout; the remaining differences were closed:
  - `x-portal.archivo` (only Mis pagos uses it) is now a dashed drop area labelled "Elegir archivo" with the accepted formats ("JPG, PNG o PDF · hasta 5 MB"); the real file input stays focusable (sr-only, `peer-focus-visible`) and the area shows the chosen file name. Browser check: a file set through CDP reached `livewire/upload-file` and its name appeared.
  - Clasificación: an evaluated request shows "Puntaje N de 100" and a highlighted "Empiezas en <ciclo>", with "Examen del <fecha>" below.
  - Mis matrículas: the state badge sits next to the title, as in the proposal.
  - Trámites: the proposal has no screen for it; it stays as it is.
  - Checks: composer check rc=0 (1955 tests); desktop and 390 px captures with no JS errors or overflow.
- L6 (2026-11-01): every task of the feature is done; the sidebar blink still waits for the user's recording.
