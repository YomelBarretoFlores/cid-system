# Formal reports like the previous system: evaluation register and statistical, monthly, administrative and financial reports

Objective: the grade register (pre-acta) and the management reports follow the formal, well organized layout of the previous system (institutional letterhead, thin black-and-grey tables, signatures), improved where useful, with no saturated colors. Branch `feat/reportes-formales`. Delivery: one commit per task (`ask-on-risk`). Runner: Pest on PostgreSQL 18 (`php artisan test --compact`, `composer check`).

## Specs

- S1. "Sí, y además, como te diste cuenta lo del sistema anterior se veía más formal. puedes seguirlo, pero mejorar lo que creas conveniente, quizás el logo como membrete o fondo difuminado de la hoja, etc. Pero debe de quedar formal y bien organizado, así como el del sistema anterior, no hay colores saturados, etc."
- S2. Pre-acta as the previous «Registro de evaluación»: header with the program (modalidad), curriculum, level, cycle, group and schedule (turno), teacher, number of students and date; grade columns with short names and a «Sistema de calificación» legend; signature boxes «Vº Bº Director(a) Académico» and «Firma del docente» (gap list of 2026-11-27).
- S3. Statistical report by language: per language and program, one table per level with cycle, teacher, group, enrolled, passed, failed and total, with subtotals.
- S4. Monthly report: per language, passed, failed and total for each program and overall.
- S5. Administrative report: the same rows with the payment modality and the place of payment per group.
- S6. Financial report grouped by language, program and level, with the payment modality.

## Tasks

- [x] T1 · S1, S2 · Pre-acta as the evaluation register (institutional letterhead, faded emblem, legend, signatures) · inline · 7c3a9d1a
- [x] T2 · S1, S3, S4 · Statistical report by language and program, and the monthly report · inline · 983b85e1
- [x] T3 · S1, S5, S6 · Administrative and financial reports by language, program and level · inline · f555ee23
- [x] T4 · all · Manuals · inline · personal 1.9 (4.4, 10.2), docente 1.6 (5.3)

## Log

- L1 (2026-11-27) Request: "Sí, y además, como te diste cuenta lo del sistema anterior se veía más formal. puedes seguirlo, pero mejorar lo que creas conveniente, quizás el logo como membrete o fondo difuminado de la hoja, etc. Pero debe de quedar formal y bien organizado, así como el del sistema anterior, no hay colores saturados, etc." It answers «¿Quieres que implemente estos faltantes empezando por la pre-acta?» after the comparison of the legacy reports (docs/sistema-actual.md).
- L2 Evidence: legacy screenshots of 2026-11-27 (registro de evaluación 2026-11, reporte estadístico por idioma, administrativo, financiero, reporte grupal, panel de reportes). Current system: pre-acta PDF lacks program, curriculum, schedule, number of students, legend and signatures, and uses a red «SIN VALOR OFICIAL» watermark; Reporte de notas has the counts per group without the language-program grouping; Recaudación has the payment detail without program or modality; no monthly or administrative report.
- L3 T1 (2026-11-27) `x-pdf.registro` (shield, Vicerrectorado Académico, centre, title; black and grey rules; CILN emblem at 6 % opacity behind the page). Pre-acta = «Registro de evaluación {periodo}»: docente, number of students, date; Idioma/Modalidad (program)/Currícula/Nivel/Ciclo/Grupo/Turno; columns by initials (`SiglasDeColumnas`, Escuchar E, Escribir ES), calculated columns shaded, cycle grade and state; legend in three columns; signature boxes; working-document note in grey instead of the red watermark. `FichaDeGrupo` gets `curriculo` (optional). RED observed. Browser: one landscape page for an 11-student group.
- L4 T2 (2026-11-27) Pages «Estadístico por idioma» and «Mensual por idioma» (permission certificacion.actas) on the shared report infrastructure; `SeccionDeReporte` gets totals and a printed heading, `ColumnaDeReporte` a shared heading for two-row headers, `ReporteGeneral` a `formal` flag that prints on the register sheet (portrait). Totals also in Excel and under the on-screen table. Counts shared with «Notas por periodo» (`ResultadosPorGrupo`). RED observed.
- L5 T3 (2026-11-27) Pages «Administrativo por idioma» and «Financiero por idioma» (pagos.reportar). The system has no payment modality (normal, beca, tercio superior): the fee's link type stands in, and the place of payment is the payment method; «Sin pago» for enrollments without a validated payment. Open product decision (docs/sistema-actual.md S5). RED observed.
