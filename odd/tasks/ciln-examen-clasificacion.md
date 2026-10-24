# CILN placement test (M1.1)

Objective: let an applicant request the placement test from the portal with a validated payment, let the academic coordination record the result from the admin panel, and set the applicant's enabled cycle automatically so enrollment offers that cycle.
Branch: `feat/examen-clasificacion` (from `main`) · Delivery: ask-on-risk, one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Sí, arranca con el examen de clasificación"
- S2 (Anexo M1.1): "Registro de postulantes nuevos que desean ingresar a un nivel superior al básico." / "Gestión de solicitudes para rendir el examen de clasificación." / "Ingreso de resultados por la coordinación académica y asignación automática del nivel en el perfil del usuario." / "Datos del Examen: Idioma, Fecha, Resultado (Puntaje), Nivel Asignado (Ej. Intermedio II)." / "Estado de Solicitud: Pendiente de Evaluación, Evaluado, Expirado."
- S3 (Anexo M2.1): "Desbloqueo automático de los módulos condicionados (matrícula, constancias, solicitud de exámenes) una vez validado el pago."
- S4 (project rules): Actions with fixed lock order; strict policies; audit by triggers; each student sees only their own requests.

Assumptions (to confirm with the CILN):
- D1: A request needs a validated, unused payment of the "Examen de clasificación" concept and an active language; one pending request per person and language. The registered applicant is the existing person record (self-registration already covers "Registro de postulantes").
- D2: A request expires 30 days after it is made if it was not evaluated; a daily scheduled task marks it "Expirado". The payment of an expired request is not reusable.
- D3: The coordination records exam date (not in the future), score (0 to 100), the assigned cycle of that language (for example "Ciclo VI · Intermedio") and optional notes. The system then sets the person's enabled cycle for that language (origin "Examen de clasificación"), replacing any previous one; the score does not pick the cycle by itself.
- D4: Permissions `clasificacion.ver` (consult requests) and `clasificacion.evaluar` (record results). The demo administrator gets both.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S4, D4 | inline | done | see L3 |
| T2 | S2, S3, S4, D1-D3 | inline | pending | |
| T3 | S2, S3, S4, D1 | pending | pending | |
| T4 | S2, D3, D4 | pending | pending | |
| T5 | all | inline | pending | |

- T1: `PermisoClasificacion` and data migration, `SolicitudClasificacionPolicy`, demo grants, audit triggers on `mat_solicitudes_clasificacion` and `mat_habilitaciones`. Risk: items 1 and 2.
- T2: Academico contract `CatalogoDeIdiomas` (languages with their cycles); Actions `SolicitarExamenDeClasificacion` (person lock → payment → insert), `RegistrarResultadoDeClasificacion` (person lock → request → enablement upsert), `ExpirarSolicitudesDeClasificacion` and its daily schedule. Risk: item 4.
- T3: Portal page `/estudiante/clasificacion` (`estudiante.clasificacion`): own requests with state, exam date, score and assigned level; request form with language and an unused validated placement payment; nav link and home card.
- T4: Filament `SolicitudClasificacionResource`: list with student search and state/language filters, view, "Registrar resultado" action.
- T5: Docs, full suite, independent verifier, native reviews.

Forecast: about 1,200 authored lines in five commits.

## Log

- L1 (2026-10-24): "Sí, arranca con el examen de clasificación"
- L2: Map: `mat_solicitudes_clasificacion` (estado, vence_en, fecha_examen, puntaje 0-100, ciclo_asignado_id with a composite FK to the language's cycles, evaluado_por/en, observaciones, pago_id unique, one pending per person and language) and `mat_habilitaciones` (one per person and language, origen) already exist with CHECKs; `VerificacionDePago` checks owner, validated state and concept type under a row lock; no scheduler task yet.
- L3: T1 inline: RED 3 of 3 → GREEN; permissions migration, strict policy (`evaluar`), audit triggers on requests and enablements, demo grants. `composer check` green.
