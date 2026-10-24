# CILN academic offer and people screens (Filament)

Objective: let the CILN manage languages and cycles, classrooms, periods, groups with schedules and quotas, and people from the admin panel, so enrollment has a real offer.
Branch: `feat/academico-filament` (stacked on `feat/pagos-configuracion`) · Delivery: ask-on-risk, stacked branches, one commit per unit · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Te recomiendo seguir con las **pantallas de la oferta académica** en el panel: idiomas y ciclos, periodos, aulas, y grupos con sus horarios y cupos. También la de **personas**." → "Sí, arranquemos con la oferta académica y personas"
- S2 (Anexo M1): "Gestión de Ciclos: Año, Mes/Periodo, Fecha de Inicio y Fin." / "Código de Grupo, Idioma, Nivel (Básico, Intermedio, Avanzado), Ciclo (I a XII), Modalidad (Presencial, Virtual, Semipresencial; Acelerado)." / "Capacidad: Cupo Máximo, Cupo Mínimo de apertura, Vacantes Disponibles (en tiempo real)." / "Asignación: Docente, Aula (física o enlace virtual), Horario (días y rango de horas)."
- S3 (Anexo M1.1, Personas): "Tipo y Número de Documento (DNI/CE/Pasaporte), Apellidos, Nombres, Fecha de Nacimiento, Correo personal, Celular" / "Tipo de vínculo: Estudiante UNASAM, Docente/Administrativo UNASAM, Público General".
- S4 (project rules): Filament resources call Actions for business rules; policies in strict mode; records with dependents are deactivated or cancelled, never deleted; personal data handled under Ley 29733 (synthetic data only in tests).

Assumptions (to confirm with the CILN):
- D1: Permissions `academico.ver`, `academico.gestionar`, `personas.ver`, `personas.gestionar`. The demo administrator gets all; the demo cashier gets `personas.ver` and `personas.gestionar` (walk-in payers are registered at the cashier).
- D2: Period states move forward only: Planificado → Matrícula abierta → En curso → Cerrado.
- D3: Group states: Programado → Abierto ⇄ Cerrado → Finalizado; Programado, Abierto or Cerrado → Cancelado only without active enrollments.
- D4: Two groups of the same period cannot use the same classroom on the same day at overlapping hours; virtual groups need a link and no classroom; a classroom's capacity bounds the quota of a face-to-face group; the quota never drops below the enrolled count.
- D5: Grading schemes (configurable evaluation) are built with the Certificacion feature, not here.
- D6: Languages, cycles and classrooms are deactivated, not deleted; people are never deleted from the panel.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S4, D1 | inline | pending | |
| T2 | S1, S2, D6 | delegated (parallel W-A) | pending | |
| T3 | S1, S2, D2 | delegated (parallel W-B) | pending | |
| T4 | S1, S2, D3, D4 | delegated (parallel W-C) | pending | |
| T5 | S1, S3, S4, D6 | delegated (parallel W-D) | pending | |
| T6 | S1 | inline | pending | |

- T1: Permission enums and data migrations for Academico and Personas; policies for Idioma, Ciclo, Aula, Periodo, Grupo, Horario and Persona (shared denial trait); demo grants. Risk: item 2 → own commit.
- T2: `IdiomaResource` with a cycles relation manager (study plan) and `AulaResource`; activate/deactivate instead of delete.
- T3: `PeriodoResource` with forward-only state changes through `CambiarEstadoPeriodo` and the `EstadoPeriodo` transitions.
- T4: `GrupoResource` with a schedules relation manager, `CambiarEstadoGrupo` with `EstadoGrupo` transitions, and the D4 rules in Actions.
- T5: `PersonaResource`: search by document or name, create, edit and view; document and phone normalized; no delete, no export.
- T6: Docs and seam check (full suite), native reviews per unit.

Route evidence: after T1, T2–T5 are independent units with disjoint edit surfaces (separate resource folders, Actions, enums and test files), each several hundred lines; parallel writers; the parent owns git, formatting and the final suite. Context backstop also applies (long session).
Forecast: about 2,000 authored lines in five commits.

## Log

- L1 (2026-10-24): "Sí, arranquemos con la oferta académica y personas"
