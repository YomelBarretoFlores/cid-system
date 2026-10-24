# CILN core data model

Objective: PostgreSQL schema, models, enums and factories for Academico, Personas and Matricula, with database invariants tested.
Branch: `feat/core-data-model` (stacked on `feat/base-structure`) · Delivery: ask-on-risk · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Sí, arranquemos con el modelado de la base de datos"
- S2: "En el documento ya se habían mencionado algunos campos, pero solo eran de referencia, como tú sabes el contexto crea todo lo necesario y correcto."
- S3 (scope from L1 context): modules Academico, Personas and Matricula, following `docs/modulos.md`, `docs/arquitectura.md`, `docs/buenas-practicas.md` and `.claude/rules/database.md`.

Assumptions (marked, to confirm with the CILN):
- A1: Each language has a configurable curriculum of up to 12 cycles (I–XII), each tagged Básico, Intermedio or Avanzado; the split per level is data, not code.
- A2: Prerequisite = the student's enabled cycle per language (`mat_habilitaciones`): first-timers start at cycle 1, placement sets it, approval (future Certificacion event) advances it.
- A3: Teacher assignment to groups belongs to Docencia (M3), so `aca_grupos` has no teacher column yet.
- A4: Payment linkage for enrollment and placement is added with the Pagos module.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S3 | inline | done | 99aba9d |
| T2 | S1, S2, S3 | inline | done | 570b019 |
| T3 | S1, S2, S3 | inline | done | dce238b |
| T4 | S1, S2, S3 | inline | done | b0d4ccf |
| T5 | S2 | inline | done | this slice |

- T1: Module `Database/{Migrations,Factories,Seeders}` folders (PSR-4 safe on case-sensitive Linux) and boundary test exemption for factories/seeders.
- T2: Academico: idiomas, ciclos, periodos, aulas, grupos, horarios, esquemas and componentes de evaluación.
- T3: Personas: personas linked to users, document and vínculo rules.
- T4: Matricula: solicitudes de clasificación, habilitaciones, matrículas, movimientos (traslados/retiros).
- T5: `docs/modelo-de-datos.md` with Mermaid ER and constraint catalogue; dev seeder.

Route evidence: modules set the first conventions of the codebase and Matricula depends on the other two, so parallel writers would diverge; all inline.

## Log

- L1 (2026-10-24): "Sí, arranquemos con el modelado de la base de datos. En el documento ya se habían mencionado algunos campos, pero solo eran de referencia, como tú sabes el contexto crea todo lo necesario y correcto."
- L2: Evidence: `composer check` → Pint ok, Rector 0, Larastan level 8 0 errors, Pest 87/87 (266 assertions) on PostgreSQL. Runtime: `php artisan migrate` + `db:seed` on dev DB `ciln` → group ING-1-M with 10 enrolled and 15 vacancies, 28 persons.
- L3: RED/GREEN: removing `aca_grupos_matriculados_check` made its test fail, restoring it passed; removing the factories exemption made the Matricula boundary test fail; the composite FK caught a real factory bug (`evaluada()` created the assigned cycle in another language); production seeding guard test failed until the seeder was called directly (`db:seed` already aborts in production without confirmation).
- L4: Discoveries: PostgreSQL ignores `unsigned` (use CHECK) and truncates identifiers at 63 chars (name composite FKs explicitly); Larastan needs `databaseMigrationsPath` for module migrations; the agent deny rule blocked `migrate:fresh` as intended.
- L5: Not done (out of scope): enrollment Actions (vacancy reservation with row locks, prerequisite and payment checks), Docencia/Pagos tables, audit triggers. Assumptions A1–A4 recorded in `docs/decisiones-pendientes.md` rows 9–11.
