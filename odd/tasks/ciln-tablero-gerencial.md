# CILN management dashboard (M5)

Objective: one read-only management dashboard in the admin panel with the TdR financial and academic KPIs, filters by period, language and level, and PDF/Excel export, built on the "Panel Admin v3" design.
Branch: `feat/tablero-gerencial` (from `main` 35ba733) · Delivery: one commit per unit on the feature branch, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Bien, puedes continuar." (after M6 was merged; the agreed next module is M5).
- S2 (Anexo M5, objective): "Centralizar la información de los módulos académicos y financieros en un único tablero de control gerencial con indicadores descriptivos en tiempo real. El módulo se limita a la consolidación y visualización descriptiva de datos; no contempla analítica predictiva, machine learning ni proyección por series de tiempo."
- S3 (Anexo M5.1, financial KPIs): "Ingresos recaudados por periodo, idioma y nivel." / "Tasa de morosidad o pagos pendientes de validación." / "Rentabilidad básica por grupo (ingresos por matrículas frente a los costos por carga docente, según horas y tarifa)."
- S4 (Anexo M5.2, academic KPIs): "Número de matrículas y crecimiento mes a mes / año a año." / "Tasa de deserción por nivel y por idioma." / "Distribución de la demanda horaria (horarios más solicitados) para optimizar la asignación de aulas."
- S5 (Anexo M5.3, visualization): "Tablero único para el personal directivo, con gráficos y tablas de resumen, filtros por periodo, idioma y nivel, y exportación (PDF/Excel)."
- S6 (architecture): "Tablero es de solo lectura: consulta mediante queries dedicadas o vistas de PostgreSQL (materializadas si pesan), no mediante contratos."
- S7 (ADR 0008): "Las pantallas nuevas (M4, M5, M6) se construyen con los mismos componentes y quedan con el estilo final desde el inicio." and "El tablero completo del diseño espera los datos de M5".
- S8 (user rules, 2026-10-24): resilience and observability by default; demo data only in completed flows.

Assumptions (to record in `docs/decisiones-pendientes.md` and confirm with the CILN):

- D1 (decision #4): with payment before service there is no debt, so the rate is "pagos pendientes de validación": pending ÷ registered payments (pending, validated, rejected; voided excluded) whose payment date falls in the period's class window, for every language (a pending payment is not linked to a group yet). Next to it, the current backlog: every pending payment, its amount, the days since the oldest was registered and the five oldest (concept, amount, dates; no personal data).
- D2: revenue is validated payments. A payment linked to an enrollment belongs to that group's period, language and level; a placement-exam payment to its request's language; any other payment to the period whose class window contains its payment date, without language or level ("Sin idioma", "Sin nivel"). Language and level filters keep only the payments that carry them.
- D3: profitability per group of the period: income = validated enrollment-fee payments linked to the group's enrollments (withdrawn included, the money was collected); cost = hours taught recorded in Docencia × the hourly cost in force on each class date for the teacher's labor category; margin = income − cost. Hours without a cost in force are shown apart, never priced at zero silently.
- D4: enrollments are counted per period (active and withdrawn, in the period's groups). Periods are monthly cycles, so "mes a mes" compares with the previous period and "año a año" with the period of the same number the previous year.
- D5: desertion = withdrawn ÷ enrollments of the period, by level and by language. NSP grades are not desertion (they are a grade outcome).
- D6: hourly demand per schedule slot (start–end time) of the period's groups that are not cancelled: active enrollments, groups, days and occupation against the maximum capacity, most requested first.
- D7: access with a new permission `tablero.ver` (the demo administrator gets it). The dashboard only reads: the pending payments link to the payments screen, where validation keeps its own permission. The design's "Meta de matrícula" gauge is left out because no enrollment target exists in the TdR or the data.
- D8: filters: period (default the period in progress, else the latest started), language (all) and level (all). The growth series ends at the chosen period.
- D9: exports use the same filters: PDF (A4 landscape, summary tables) and Excel (.xlsx, one sheet per indicator). Figures are computed on each request (real time), without cache.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S3, S6, D1, D2, D7, D8 | inline | done | 7f897dd |
| T2 | S3, S6, D3 | inline | done | 16acccd |
| T3 | S4, S6, D4–D6 | inline | done | 8b19ef0 |
| T4 | S2, S5, S7, S8, D7, D8 | inline | done | (T4 commit) |
| T5 | S5, D9 | inline | pending | |
| T6 | all | inline | pending | |

- T1: permission `tablero.ver`, filter DTO, revenue by period/language/level and pending-validation rate. Risk: item 2 (new permission).
- T2: profitability per group (income vs teacher-load cost). Risk: none (read-only query).
- T3: enrollments with period-over-period and year-over-year growth, desertion by level and language, hourly demand. Risk: none (read-only queries).
- T4: Filament page "Tablero" (filters, KPI cards, charts and summary tables per the design), demo data check. Risk: item 2 (page access).
- T5: PDF and Excel export. Risk: none.
- T6: docs (decisiones, arquitectura, seguridad, modulos), full suite, independent verifier, native review.

Route evidence: every task reads the same filter DTO and T4/T5 render T1–T3, so there is no parallel writer reason; all inline. Forecast about 1,800 authored changed lines; one commit per unit.

## Log

- L1 (2026-10-25, user): "Bien, puedes continuar."
- L2: Mapping: Tablero is an empty skeleton; it may read other modules' tables with dedicated queries (arquitectura:86) and import only their Enums/Data. Payment → group only through `mat_matriculas.pago_id`; pensions only carry `persona_id`. `mat_matriculas.estado` is `activa|retirada`, a transfer overwrites `grupo_id`. Hours taught in `doc_clases_dictadas`, cost in `doc_costos_hora` by labor category and validity. Schedules in `aca_horarios`. No chart widgets or views exist; `maatwebsite/excel` 4.0.6 installed and unused; PDF via spatie/laravel-pdf on DOMPDF. Design "Panel Admin v3": header with language filter and PDF/Excel buttons, cards Recaudación / Por validar / Meta de matrícula, pending list, growth line chart, Deserción / Idioma líder / Demanda horaria mini-cards, enrollments by language bars, profitability per group bars.
- L3: T1 (7f897dd): `PermisoTablero::Ver` (`tablero.ver`, migration 150000, demo administrator), `FiltrosDelTablero`, `CalcularRecaudacion` (validated payments; enrollment fee by its group's period, language and level; placement exam by its request's language; other payments by date in the period's class window) and `CalcularPagosPorValidar` (rate in the class window, voided excluded; current backlog with amount, oldest age in Lima days and the five oldest without personal data). RED observed (classes missing), GREEN 5/5; architecture and database 45/45. Assessed medium, under budget.
- L4: T2: `CalcularRentabilidadPorGrupo`: groups of the period that are not cancelled; income = validated enrollment-fee payments of the group's enrollments (withdrawn included); cost = minutes taught per rate in force on each class date for the teacher's category, hours rounded once per rate like the teachers' hour reports; hours without a cost in force reported apart. RED observed (class missing), GREEN 7/7.
- L5: T3: `CalcularMatriculas` (enrollments of the period's groups that are not cancelled, active and withdrawn; series of up to 12 periods ending at the chosen one; growth against the previous period and the same number a year before, none when there is nothing to compare or the base is zero; enrollments by language), `CalcularDesercion` (withdrawn ÷ enrolled, total, by level and by language) and `CalcularDemandaHoraria` (slots by start–end time with days, groups, active enrollments and capacity, each group once per slot, most requested first). Shared `GruposDelPeriodo` and `Porcentaje`. RED observed (classes missing), GREEN 11/11.
- L6: T4: Filament page `TableroGerencial` (`/admin/tablero-gerencial`, first in the menu, `tablero.ver`) on `ArmarTablero`: filters period (default in progress, else the latest started), language and level; cards Recaudación / Por validar (with the period rate) / Matrículas (month-over-month and year-over-year); pending list; growth line chart (SVG with a screen-reader list); Deserción, Idioma líder, Demanda horaria; enrollments by language; profitability bars; summary tables (shared `x-ciln.tabla`). `x-ciln.cifra` gained an optional slot (existing uses unchanged: Pagos report and Certificación tests pass). Links to payments and the revenue report only with their permissions. Tests written after the page (no RED for the page itself): 5 page tests + presentation helpers. Browser check on the demo (`ciln_preview`, desktop and 390 px): no horizontal page scroll; wording fixed for "0 días" and for missing comparison periods; desertion table split by level and language.
