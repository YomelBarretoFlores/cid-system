# CILN demo: data to see every new feature

Objective: the demo at ciln.mesaamesa.com shows E1–E5 with real-looking synthetic data (syllabi, two roll calls confirmed or observed by the delegate, complete grades, closed actas of earlier periods for the record, the boleta and the next cycle), on a fresh seed and on the existing demo database, without deleting anything.
Branch: `feat/demo-completo` (from `main` c175838) · Delivery: one commit, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1 (user): "Dejemos así, pero, quiero que llenes datos para poder verlo completo, como por ejemplo llenar el sílabo, etc."
- S2 (user choice): "Completar sin borrar (Recommended)": an idempotent complement seeder that the demo container runs on every deploy (change to `docker/app/entrypoint.sh`, only with `DEMO_SEED=true`), keeping what already exists in the demo.
- S3 (data, through the existing Actions only, never raw inserts of business rows): for the demo teacher's groups (`docente@ciln.test`) in the current period:
  - syllabi: one group published with complete, realistic Spanish content for a language course (sumilla, competencias, capacidades, metodología, bibliografía, every week, every evaluation row), another as a draft, the rest missing; plus a published syllabus in another teacher's group of the same cycle if one exists, so "Copiar de un sílabo anterior" offers something;
  - class control: a delegate for every current group without one (`estudiante@ciln.test` stays the delegate of their English group), and for the last past classes of the demo student's English group: two confirmed, one observed with a comment, one closed but never answered ("sin confirmar"); taken with both calls through `RegistrarAsistencia`, `RegistrarLlamadoDeCierre` and `ConfirmarClaseDictada` with the clock moved to each class time (`Date::setTestNow`/`CarbonImmutable::setTestNow`, always restored);
  - grades: complete pre-acta grades (every component) for the demo student in their current groups through `RegistrarNotasParciales`;
  - history: the demo student passed cycles 1 and 2 of a language they are not taking now (for example Portuguese) in two earlier closed periods, closed through `CerrarActa` (clock inside each period's grade calendar), so the record, the boleta of those periods, "Faltan los ciclos 3 y 4 del nivel Básico" and the habilitation of cycle 3 by approval appear.
- S4: idempotent: running it again changes nothing (every piece checks first); a fresh `db:seed` calls it at the end; it refuses production like `DatabaseSeeder`.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1–S4 | delegated (context backstop) | pending | — |
| T2 | all | inline | pending | — |

T1 complement seeder, entrypoint, tests (Risk item 5: deploy entrypoint). T2 browser check on a preview seeded the old way and then completed, plus a fresh seed.

## Log

- L1 (2026-10-31): "¿En qué secciones veo los cambios en el demo? Y además, ya completaste todas las implementaciones que mencionaste en el plan?" … "Dejemos así, pero, quiero que llenes datos para poder verlo completo, como por ejemplo llenar el sílabo, etc." Choice: "Completar sin borrar (Recommended)".
