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
| T1 | S4, D1 | inline | done | b1a412b |
| T2 | S1, S2, D6 | delegated (parallel W-A) | done | 8eb4eb4 |
| T3 | S1, S2, D2 | delegated (parallel W-B) | done | 964d77f |
| T4 | S1, S2, D3, D4 | delegated (parallel W-C) | done | a1b7c84 |
| T5 | S1, S3, S4, D6 | delegated (parallel W-D) | done | 34b5412 |
| T6 | S1 | inline | done | 6592513, d053e9c |

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
- L2: T1 inline: RED (permission enums missing) → GREEN; `composer check` 297/297.
- L3: T2–T5 by four parallel writers with disjoint surfaces; each got its own test database (`ciln_testing_wa..wd`) after the parent noticed shared `RefreshDatabase` runs would clobber each other. Writer reports: W-A 61/61 (RED: resource missing), W-B 48/48 (RED 0 of 48), W-C 40 action tests RED then GREEN plus panel tests, fixed a TimePicker shift (09:00 stored as 14:00 because the panel timezone is Lima), renamed a global helper colliding with `matricular()`; W-D 28/28. Parent changes at integration: language code fixed after creation (consistency with classrooms and concepts), tests adapted. Seam check: `composer check` 450/450, phpstan 0.
- L4: Writer choices accepted: cancelled groups free their classroom; duplicate group code per period as a business error; period and cycle fixed after a group is created; lock order group → classroom; a View page for groups; forms list only active classrooms and cycles; phone normalized in the `Persona` model. Open questions for the CILN: can a closed period's dates still be edited (today yes)?
- L5: Range 2d05f34..34b5412 is 3,905 lines: independent verifier (items 2 and 4) launched over the whole feature before the native review.
- L6: Verifier (items 2 and 4) over 2d05f34..34b5412: authorization matrix, strict mode, lock order and eight-process mixed load held; 3 blockers: B1 deactivating a language had no effect; B2 a deactivated classroom blocked editing the groups using it; B3 classroom capacity could drop below a group's quota. Correction 5b7b795 (RED 6 of 7 → GREEN; `ActualizarAula` locks the classroom). Recheck: all three fixed; capacity cut vs quota raise serialized in both orders.
- L7: Native review of the whole range refused (`lens_context_budget_exceeded`, 4,070 lines). User chose "Revisar por tramos": five reviews on temporary worktrees at each commit, all granted, approved and acknowledged (review-47a4c6fb58e46ff9, review-9fdafa0925d0cfeb, review-474770ae747c3e76, review-87b1b9d373be78d8, review-a19055ce24cab858). Advisories fixed in 6592513: cancelled/finished groups frozen (Action and hidden panel actions); concurrent duplicate on period edit as form error; `RestriccionUnica` and `NormalizaCodigo` replace eight string matches and three copied normalizers; language code rule create-only; EditAula keeps absent fields; int compare of cycle id; tests for renaming to a used group code, virtual → face-to-face clash and the schedule policy. Not adopted: SQLite-specific index detection (PostgreSQL only, ADR 0002), stale-action message test (Livewire re-reads records). `composer check` 464/464.
- L8: Closed. Next: student portal (Livewire) for receipt upload and enrollment.
