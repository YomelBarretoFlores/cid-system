# CILN student reports and teacher grades with the new design

Objective: finish what the visual plan `golden-rolling-hippo` left pending after V1–V5: the student Reportes page with tabs and a download panel, the teacher grades page with progress and acta stages, and the teacher acta pages.
Branch: `feat/reportes-y-notas-diseno` (from main ab9c820). Delivery: one commit per unit; the user decides push and merge. Test runner: Pest (`/private/tmp/claude-501/ciln-v-OYLV/pest.sh`).

## Specs

- S1 (user): "Avanza con todo lo faltante, al final veremos el bug del parpadeo" and "Bien, continúa con lo faltante."
- S2 (plan V4): "Reportes: pestañas "¿Cómo voy?", "Horario" y "Lo que aprobé", con el panel "Descargar en PDF". El horario conserva su formato actual."
- S3 (plan V5): "Notas (`PreActaDelGrupo`): avance "29 de 36 notas" y etapas del acta (Registra, Revisa, Valida y cierra), con la ventana de fechas."
- S4 (plan V5): "Acta final y Mis actas finales: encabezado y tabla al estilo nuevo."
- S5 (user, standing): no "tardanza"; language flags stay administrable; the schedule format stays as it is.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, S5 | inline | done | see L2 |
| T2 | S3 | inline | done | see L3 |
| T3 | S4 | inline | done | see L4 |

## Log

- L1 (2026-11-01): "Bien, continúa con lo faltante." Remaining items after V1–V5 merged to main ab9c820. Routes are inline: each unit is one understood change with no writer reason.
- L2 (2026-11-01): T1 done. `MisReportes` gets `pestana` bound to `?ver=` (avance, horario, aprobado; anything else falls back to avance) and renders only the active tab.
  - ¿Cómo voy?: one card per enrollment with the flag, group, teacher and acta state; grades with a bar per component and the partial or final average; attendance with the percentage over held classes, "D de P clases dictadas · F faltas", one square per programmed class and the percentage over programmed classes.
  - Horario: the existing formal sheet, unchanged. Lo que aprobé: per language, each cycle with level, period, grade and state, the missing cycles and the informative notice.
  - "Descargar en PDF" side panel: boleta, avance and asistencia per group, horario (and the print version), récord per language and complete.
  - The home "Ver horario completo" opens `?ver=horario`. Checks: Matricula suite 297 passed; desktop and 390 px captures with no overflow and no JS errors.
- L3 (2026-11-01): T2 done. New `Docencia\Data\EtapasDelActa::de()` builds the stages Registra las notas ("R de E notas"), Revisa el acta and Valida y cierra (window "Del … al …", "Hasta el …" while open, "Cerrada", "Sin fechas de actas") as done/current/pending. `PreActaDelGrupo` counts saved grades of active students per component (`NotasParcialesDelGrupo`), reads the acta state (`EstadosDeActas`) and the window (`CursoDelGrupo`), shows the stages with the steps view and a "Revisar el acta final" button when review is current, and replaces the weight badges with per-component progress cards. Checks: Docencia suite 395 passed; capture of /docente/grupos/1/notas with no JS errors.
- L4 (2026-11-01): T3 done. The steps markup moves to the shared `x-ciln.pasos` (done steps show a check), used by the attendance calls, the pre-acta and the acta page. `ActaFinalDelGrupo` shows the acta stages (`EtapasDelActa` from the acta rows, its state and the course window) above its state message; stage 2 now reads "Revisa los promedios antes de validar" so it fits both pages. `MisActasFinales` becomes a table with Grupo (code, language and cycle, approved count), Periodo, Estado, Validada (Lima date) and the actions Abrir and PDF (closed actas only). Checks: composer check rc=0 (1948 tests; an earlier run hit the pre-existing Tablero period-number flake, see follow-ups); Certificacion + Docencia 541 passed; captures of /docente/actas, /docente/grupos/1/acta and /docente/grupos/1/asistencia with no JS errors and no mobile overflow.
