# CILN teacher roster and academic load (M3.1, M3.2 first half)

Objective: keep the CILN teacher roster (profile, enabled languages and level, availability, status) and assign one teacher to each group opened in M1 with the hours computed and schedule clashes refused; show the teacher on the enrollment documents.
Branch: `feat/docencia-padron` (from `main` 2a141a4) · Delivery: one commit per unit on the feature branch, push and merge decided by the user (cached option A) · Test runner: Pest (`php artisan test`).

## Specs

- S1: "súbela y únela a main, luego sigue con Docencia, pero primero compactaré la conversación" / "Puedes proseguir"
- S2 (Anexo M3.1): "Mantenimiento del padrón de docentes (especialidad/idiomas y disponibilidad horaria)." / "Gestión del estado del docente (Activo, Inactivo, De Licencia)." / "Datos Personales: ID Docente, Nombres Completos, DNI/CE, Correo Institucional y Personal, Celular." / "Datos para el Expediente: RUC, Número de Cuenta Bancaria, Banco, Condición Laboral (Nombrado, Contratado, Tercero)." / "Perfil Académico: Idiomas habilitados para dictar, Nivel máximo autorizado."
- S3 (Anexo M3.2): "Vincular a un docente con los grupos/ciclos aperturados en el Módulo 1." / "Cálculo automático del total de horas asignadas en el periodo." / "Control de cruces de horarios para evitar asignaciones superpuestas." / "Detalle del Grupo: Código, Nivel, Idioma." / "Carga Horaria: Número de semanas, Fechas de inicio y fin, Días de dictado, Horas totales por grupo."
- S4 (Anexo M1): "Asignación: Docente, Aula (física o enlace virtual), Horario (días y rango de horas)."
- S5 (project rules and docs/modulos.md): "RUC, cuenta bancaria (cifrada), banco, condición laboral, nivel máximo autorizado"; "Vincula docente con grupos; total de horas; **sin cruces de horario**"; audit triggers on critical tables; strict policies; Docencia reads other modules only through their contracts.

Out of this feature (next flows): attendance and pre-actas with the teacher portal (M3.2 "Llamado de asistencia y registro de calificaciones (pre-actas)"), the hours report (M3.3).

Assumptions (to confirm with the CILN, recorded in docs/decisiones-pendientes.md):
- D1: A teacher is a Persona (Personas keeps names, DNI/CE, personal email, mobile) plus a Docencia profile; the profile adds the institutional email, RUC (11 digits), bank, account number (encrypted at rest, masked in lists), labor condition and status. "ID Docente" is a code `DOC-00001` derived from the record.
- D2: The maximum authorized level is per enabled language (a teacher may teach English up to Avanzado and Quechua up to Básico).
- D3: Availability is weekly windows (day, start, end). A teacher can be assigned only when Activo, the group's language is enabled up to at least the group's level, every session of the group falls inside the teacher's availability, and no session overlaps a session of another non-cancelled group of the same teacher whose period dates overlap. One teacher per group; reassigning replaces the teacher, terminal groups (Cancelado, Finalizado) do not change.
- D4: The academic load of a group: start and end dates are its period's; weeks = calendar weeks between them (rounded up); teaching days are its schedule days; total hours = every scheduled session between those dates × its duration. The teacher's period total is the sum over the assigned groups of that period.
- D5: Changing a group's schedule re-checks the assigned teacher's clashes and availability.
- D6: Permissions `docencia.ver` and `docencia.gestionar`; the demo administrator gets them.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, S5, D1, D2, D6 | inline | done | 8adec36 |
| T2 | S2, D3 | inline | pending | |
| T3 | S3, S5, D3, D4 | inline | pending | |
| T4 | S3, D5 | inline | pending | |
| T5 | S4 | inline | pending | |
| T6 | all | inline | pending | |

- T1: Permissions and policy, tables `doc_docentes` and `doc_docente_idiomas` with audit triggers, enums, Actions to register and update a teacher and change status, Filament resource "Docentes". Risk: items 1, 2.
- T2: Availability windows (`doc_disponibilidades`), Action and relation manager. Risk: item 1.
- T3: Academico contract with structured group schedules; `doc_cargas_academicas`; Actions to assign and unassign a teacher with group lock then teacher advisory lock; load calculation; relation manager "Carga académica" with period totals. Risk: items 1, 4.
- T4: `GuardarHorario` asks a Docencia contract to re-check the assigned teacher under the same lock order. Risk: item 4.
- T5: Docencia contract `DocentesDeGrupos`; teacher on the enrollment ficha, constancia and group view. Risk: item 3.
- T6: Docs, demo data, full suite, independent verifier, native review.

Forecast: about 1,700 authored lines in six commits.

## Log

- L1 (2026-10-24): "súbela y únela a main, luego sigue con Docencia, pero primero compactaré la conversación" — then "Puedes proseguir".
- L2: Map: no teacher concept exists (no `docente_id`, role, middleware, attendance or grade tables). `aca_horarios` holds weekly sessions and `Horario::seCruzaCon` is the overlap scope; `GuardarHorario` locks group then classroom. Groups have no dates or weeks: dates come from `aca_periodos`, nominal hours from `aca_ciclos.horas_lectivas`. Persona has one email (personal). No spatie roles; permissions per user. Lock order chosen: group row → teacher advisory lock, the same in assignment and in schedule changes.
- L3: T1 inline: RED 17/17 → GREEN; panel tests 8/8 (a reader's raw mount of "Cambiar estado" changes nothing; mutation without `authorize` makes it fail). `doc_docentes` (code `DOC-00001` as a stored generated column, encrypted account, CHECKs, audit) and `doc_docente_idiomas` (level per language). `FichaDeEstudiante` gains optional `correo` and `celular` (portal ficha test updated). `composer check` 811/811. Native review of 2a141a4..8adec36 (risk, reliability) granted, running.
