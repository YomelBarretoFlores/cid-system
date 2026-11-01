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
| T2 | S2 | inline | pending | |
| T3 | S3 | inline | pending | |

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

