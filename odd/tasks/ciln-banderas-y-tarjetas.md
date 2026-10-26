# CILN assignable language flags, flags in panel tables and card layouts

Objective: let each language choose its flag from a fixed set (instead of guessing it from the name), show the flag next to the language in the panel tables, and show Grupos and Docentes as cards like the design guide.
Branch: `feat/banderas-y-tarjetas` (from `main` a374e39) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1 (user, 2026-10-25): "Sobre esas dos mejoas sí me gustaría. Pero o sea, se mostraría la bandera junto con su nombre?" — flags next to the language name in panel tables (design recommendation 1) and cards for Grupos and Docentes (recommendation 3).
- S2 (user, 2026-10-25): "no hay cómo asignarle bandera a un idioma creado cierto?, eso cómo lo implementamos?, es posible?" then "Con respecto a tu pregunta, sí, hazlo." — option A: choose the flag from a predefined list with a preview when creating or editing a language; current languages keep their flag (migration fills it from the name).
- S3 (rules): tokens and `x-ciln.*` components (ADR 0008); a module only uses another module's `Contracts/`; synthetic data; tests on PostgreSQL.

Assumptions:

- A1: the list holds stylized flags that read well as a small leaf: United States (current English), United Kingdom, France, Italy, Germany, Brazil, Portugal, Spain, Russia, China, Japan, South Korea, Wiphala and a neutral leaf.
- A2: panel tables with a language get the flag: Grupos, Solicitudes de clasificación and Matrículas (through the group). Pagos has no language and stays as is.

## Tasks

| ID | Specs | Route | Status | Commit |
|---|---|---|---|---|
| T1 | S2, S3, A1 | inline (Risk: item 1) | done | next commit |
| T2 | S1, A2 | inline | pending | — |
| T3 | S1 | inline | pending | — |

- T1: nullable `bandera` column on `aca_idiomas` (no data change: a null flag falls back to the one suggested by the name), flag enum and lookup port in `Shared/Ui`, implementation in Academico, flag select with preview in the language form, `x-ciln.hoja-bandera` resolves the stored flag.
- T2: flag next to the language in the Grupos, Solicitudes de clasificación and Matrículas tables.
- T3: card grid for Grupos and Docentes (flag, main data, status).

## Log

- L1 (user, 2026-10-25): "Sobre esas dos mejoas sí me gustaría. Pero o sea, se mostraría la bandera junto con su nombre? Y una cosa más, no hay cómo asignarle bandera a un idioma creado cierto?, eso cómo lo implementamos?, es posible?" then, after the A/B proposal, "Con respecto a tu pregunta, sí, hazlo."
- L2 (exploration): `resources/views/components/ciln/hoja-bandera.blade.php` maps lowercase names (inglés, francés, italiano, alemán, portugués, quechua) to drawings; anything else is a neutral leaf. 19 call sites pass the language name, so the component keeps that API and looks the stored flag up by name.
- L3 (decision): no backfill in the migration; `BanderaDeIdioma::sugeridaPara()` keeps today's flags for null rows and pre-fills the form on edit, so existing data is untouched (smaller risk than an UPDATE). The lookup (`BanderasRegistradas`, scoped) is forgotten when an Idioma is saved.
- L4 (review, approved): the name fallback is wider than before (Español, Japonés, Ruso, Chino, Coreano, Aimara now get a flag instead of the neutral leaf), comments corrected; unknown flag values fall back to the neutral leaf; lookup also forgotten on delete; rename covered by the test.
