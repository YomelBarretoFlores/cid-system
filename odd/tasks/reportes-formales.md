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

- [ ] T1 · S1, S2 · Pre-acta as the evaluation register (institutional letterhead, faded emblem, legend, signatures) · inline
- [ ] T2 · S1, S3, S4 · Statistical report by language and program, and the monthly report · inline
- [ ] T3 · S1, S5, S6 · Administrative and financial reports by language, program and level · inline
- [ ] T4 · all · Manuals · inline

## Log

- L1 (2026-11-27) Request: "Sí, y además, como te diste cuenta lo del sistema anterior se veía más formal. puedes seguirlo, pero mejorar lo que creas conveniente, quizás el logo como membrete o fondo difuminado de la hoja, etc. Pero debe de quedar formal y bien organizado, así como el del sistema anterior, no hay colores saturados, etc." It answers «¿Quieres que implemente estos faltantes empezando por la pre-acta?» after the comparison of the legacy reports (docs/sistema-actual.md).
- L2 Evidence: legacy screenshots of 2026-11-27 (registro de evaluación 2026-11, reporte estadístico por idioma, administrativo, financiero, reporte grupal, panel de reportes). Current system: pre-acta PDF lacks program, curriculum, schedule, number of students, legend and signatures, and uses a red «SIN VALOR OFICIAL» watermark; Reporte de notas has the counts per group without the language-program grouping; Recaudación has the payment detail without program or modality; no monthly or administrative report.
