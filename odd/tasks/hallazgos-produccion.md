Errors found while exploring production (https://ciln.unasam.edu.pe/admin) on 2026-11-25, read-only (no records created or changed).
Branch: main · Delivery: one fix release (v1.1.2) · Runner: Pest on PostgreSQL 18, plus a PostgreSQL 17 check for S1.

## Specs

- S1. Every search in the panel fails in production with "los ordenamientos no determinísticos no están soportados para LIKE": table searches (Personas, Matrículas, Pagos and every list), searchable selects (for example Docente → Persona in /admin/docentes/create) and the person search. Cause: production runs PostgreSQL older than 18, where LIKE rejects the nondeterministic collation `sin_tildes` used through `search_collation` (Filament) and in `Persona::coincideCon`. Searches must work on PostgreSQL 16, 17 and 18.
- S2. A non-numeric record key in the URL answers 500 instead of 404: /admin/tramites/create, /admin/actas-finales/create, /admin/documentos/create, /admin/auditoria/create, /admin/tramites/abc ("la sintaxis de entrada no es válida para tipo bigint: «create»").
- S3 (server, DSTI). `storage/logs/laravel-<date>.log` is created by an account other than the web server's, so every logged event raises "could not be opened in append mode: Permission denied" and turns into a 500, including handled rejections such as "No podemos crear una cuenta con ese documento…".
- S4 (server, DSTI). `APP_DEBUG=true` in production: error pages expose SQL, headers, cookies and paths.
- S5 (server, DSTI). Production is not running v1.1.1: /privacidad answers 404 and the registration form has no privacy-policy link (both exist since v1.1.1).
- S7. Teacher pre-acta page offers "Descargar pre acta (PDF)" on groups whose acta is already closed; the link answers 404 ("El acta del grupo ya está cerrada; descarga el acta final.").
- S8. Teacher pre-acta page keeps showing the previous rejection ("La nota 21 no es válida…") next to "Notas parciales guardadas." after the corrected grades are saved.
- S9. A daily log file the web server cannot write turns every logged event into a 500 (S3); the application must keep serving the request.
- S6 (server, DSTI). Production PostgreSQL is older than 18 (follows from S1), unlike the deployment guide and the report; `pg_dump` must match the server major version.

## Tasks

- T1 · S1 · inline · person search with translate() + ILIKE (Persona::coincideCon) · done, uncommitted
- T2 · S1 · inline · migration 2026_11_25_120000 recreates `sin_tildes` as deterministic on PostgreSQL < 18 (searches work, accent-sensitive there) · done, uncommitted
- T3 · S2 · inline · trait ClaveDeRegistroValidada on the 26 panel resources · done, uncommitted
- T4 · S3–S6 · DSTI note ~/Downloads/Pendientes para la DSTI - Sistema CILN.txt · done
- T5 · S7 · inline · hide the action while the acta is closed (PreActaDelGrupo) · done, uncommitted
- T6 · S8 · inline · resetErrorBag('notas') before saving · done, uncommitted
- T7 · S9 · inline · `daily` becomes a stack with ignore_exceptions over `diario` (permission 0664); deployment guide: cron as the web server user · done, uncommitted
- T8 · PostgreSQL 17 suite · ConfiguracionDePagosTest expected PostgreSQL 18 wording of the FK error; now asserts 'foreign key constraint', shared by PostgreSQL 17 (23503) and 18 (23001, RESTRICT) · done, uncommitted

## Log

- L1 (2026-11-25, user): "quiero que revises https://ciln.unasam.edu.pe/admin/tablero-gerencial, ya lo tengo iniciado sesión. Y quiero que explores y hagas pruebas, quizás haya más errores de los que me he encontrado. Todo lo que encuentres lo apuntas y luego para corregirlos."
- L2: production database has almost no records (no payments, enrollments or minutes), so flows that need data were not exercised in production.
- L3: S1 reproduced on a postgres:17-alpine container ("nondeterministic collations are not supported for LIKE"); all migrations ran there and `sin_tildes` became deterministic; Personas + BuscadorDelPanel tests on PostgreSQL 17: 134/135, the failing one expects accent-insensitive panel search ("frances" → "Francés"), which needs PostgreSQL 18. Person search stays accent-insensitive on every version (translate + ILIKE).
- L4: S2 test tests/Feature/ClavesDeRegistroEnElPanelTest.php: RED without the trait (22P02/22003 on 23 resources), GREEN with it.
- L5 (2026-11-25, user): "debemos de asegurar que el flujo principal esté funcional, por ejemplo asistencias, estudiantes, pre acta, actas. Panel de estudiante, docente. ... quiero que no haya errores en el flujo principal ya para que lo comiencen a usar el sistema los docentes y estudiantes."
- L6: full suite on PostgreSQL 17 (2257): 2253 pass; failures = accent-insensitive panel search (needs PG18), ProxiesDeConfianza (local env), 2× FK message wording (T8). Demo data seeds completely on PostgreSQL 17.
- L7: browser on localhost:8001 (PostgreSQL 17 + demo data): student portal 50 links incl. 19 PDFs OK (receipt links 404 only because demo payments have fake file paths); teacher portal 39 links OK except S7; pre-acta save + validation message OK (found S8); delegate dialog opens; admin 39 lists + 127 record/create/edit pages OK; table and global search OK; payment validation OK. Asistencia and Acta final pages do not have S8 (tests passed without changes).
- L8: native 4-lens review approved and acknowledged (lineage review-77b23d8e58e398c1). Advisory, not applied: no fallback sink when the daily file is unwritable (logs of that day are lost; DSTI fixes ownership); `sin_tildes` stays accent-sensitive on PostgreSQL < 18 despite its name; pre-acta PDF is hidden only for `Cerrada`, the only state where GenerarPreActaPdf refuses.
