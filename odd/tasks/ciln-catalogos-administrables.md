# CILN administrable catalogs: evaluation schemes, employment conditions, academic degrees and hourly rates by degree

Objective: the CILN keeps its evaluation schemes and teacher catalogs from the panel, and the hourly rate follows the teacher's academic degree. Delivery D4 of the approved plan.
Branch: `feat/catalogos-administrables` (stacked on `feat/cruces-carga`) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1 (user, L1): "quisiera que todo sea administrable … si es que no las hay revisa que todo sea administrable." and "Como dije, administrable, y cubre los huecos."
  - Evaluation schemes get a panel screen, in Configuración, for academic managers. A scheme has name, optional language and level, maximum and minimum passing grade, validity, and components (code, name, weight).
  - Rules, enforced by the Action and the database:
    - weights sum exactly 100 ("Los pesos de los componentes deben sumar 100; ahora suman 90.");
    - at least one component;
    - component codes are unique;
    - two schemes for the same language and level cannot have overlapping validity.
  - Once a scheme's validity has started, only its name and its end date change: "El sistema «X» ya está vigente; para cambiar componentes, pesos o notas, cierra su vigencia y crea uno nuevo."
  - Both scheme tables get an audit trail.
- S2 (user, L1): employment condition (Nombrado, Contratado, Tercero, as TdR l.97) becomes a catalog with its own screen. Teachers keep their condition.
- S3 (user, L1): "para el pago del docente es por horas que tiene asignado, y su pago es dependiendo si solo tiene certificado, bachiller, titulado, magister."
  - Academic degree becomes a catalog seeded with Certificado, Bachiller, Titulado, Magíster and Doctor. Every teacher can record a degree.
  - Hourly rates are registered by degree.
  - Rates registered before by employment condition stay as history: a class uses the degree rate in force on its date, and otherwise the earlier condition rate, so past hour reports do not change.
  - A teacher without a rate shows "Sin costo vigente", as today.
  - The management dashboard's group profitability uses the same rate rule.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1 | inline | done | see L3 |
| T2 | S2 | inline | done | see L4 |
| T3 | S3 | inline | done | see L4 |

## Log

- L1 (2026-10-31): "Ahora, quisiera que todo sea administrable, por ejemplo en select de condición laboral hay items para elegir, pero me pregunto si hay una sección en donde se edite, elimine o cree más items, o sí las hay?, si es que no las hay revisa que todo sea administrable." · "Ahora, te comento, para el pago del docente es por horas que tiene asignado, y su pago es dependiendo si solo tiene certificado, bachiller, titulado, magister." · (2026-11-01) "6. Como dije, administrable, y cubre los huecos."
- L2 (2026-11-01): plan approved. Mapping came from one read-only explorer.
  - Deviation from the plan: no migration seeds a "default" scheme with invented weights. Weights are a CILN decision, and the new screen lets coordination register the real scheme on day one. The existing messages ("pide a coordinación que lo registre") cover a group without one. Logged in `docs/despliegue.md` as a first-day step.
  - The "already in force" rule replaces "used by a group with grades", because Academico cannot read Docencia's grades without crossing a module boundary. A scheme in force may already be used by any group.
  - Route: inline. The units share the Docente form and factories.
- L3 (2026-11-01): T1 done.
  - `GuardarEsquemaDeEvaluacion`, with `ComponentesDelEsquema` validating codes, names and weights that sum 100, plus the grade and validity rules.
  - An advisory lock per language and level keeps two saves from both missing an overlap. A scheme already in force changes only its name and end date.
  - `EsquemaDeEvaluacionResource` lives under Configuración, with a components repeater, guarded by `OfertaAcademicaPolicy`.
  - Migration `2026_11_01_120000`: audit triggers on both tables, plus a deferred constraint trigger requiring at least one component and weights of 100 at commit.
  - Fixtures and the demo seeder that commit now build the scheme in one transaction; the demo goes through the Action.
  - Real seed into `ciln_preview`: the demo scheme sums 100.
  - Docs: `modulos.md`, `despliegue.md` (first-day configuration), #121.
  - RED: 11 new tests failed first. `composer check` rc=0, 1872 tests.
- L4 (2026-11-01): T2 and T3 done together. They share the Docente form, `doc_costos_hora` and the factories, so one commit (about 800 lines with tests).
  - Migration `2026_11_01_120100`:
    - creates `doc_condiciones_laborales` and `doc_grados_academicos` (seeded, audited);
    - moves `doc_docentes.condicion_laboral` to `condicion_laboral_id` and adds `grado_academico_id`;
    - gives `doc_costos_hora` exactly one of `condicion_laboral_id` or `grado_academico_id`, with one GiST exclusion each;
    - stored rates and teachers keep their condition; the down/up test proves it.
  - `RegistrarCostoPorHora(gradoAcademicoId, …)` locks per degree; `EliminarCostoPorHora` locks per the row's own key.
  - `ConsolidarHorasDictadas` lists the degree rates first, then the condition rates, so a class takes the first rate covering its date.
  - `CalcularRentabilidadPorGrupo` takes `coalesce(degree rate, condition rate)` per class date.
  - New screens in Configuración: Condiciones laborales and Grados académicos (`docencia.gestionar`, `CatalogoDeDocenciaPolicy`). The Docente form has Condición laboral and Grado académico. The rate list shows "Grado académico", or "Condición (tarifa anterior)".
  - The demo gives each teacher a degree and fictitious rates per degree.
  - Docs: #49 resolved, #122, `modulos.md`.
  - RED: 6 new tests failed first (classes missing). Docencia, Tablero and Feature: 720 tests pass.
  - `composer check` rc=0, 1878 tests.
  - Demo data: seeded `main` and migrated with this branch. The 3 teachers keep their condition and the 3 condition rates keep amount and validity.
- L5 (2026-11-01): the review of all of D4 did not fit the reviewer budget, so it was split. T1 (aade970..ffc87b9; lenses risk and reliability; lineage review-2d80853560e47bb8) was granted by the user, approved and acknowledged.
  - Applied after a RED test: a scheme in force cannot be closed before today ("Un sistema vigente no puede terminar antes de hoy (dd/mm/aaaa)."), which would leave graded days without a scheme.
  - Left for later: a new scheme can still start in the past to fill an uncovered gap (needed for first-day setup).

