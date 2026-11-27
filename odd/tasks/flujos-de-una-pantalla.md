# Admin panel flows in one screen: person hub, new teacher, window enrollment, group with teacher, search

Objective: common office tasks in the admin panel are done from one screen, without hopping between sections, following UI/UX good practice (hierarchy, alignment, clear labels). Branch `feat/flujos-de-una-pantalla` (based on the unmerged `feat/evaluacion-configurable`). Delivery: one commit per task (`ask-on-risk`). Runner: Pest on PostgreSQL 18 (`php artisan test --compact`, `composer check`).

## Specs

- S1. Fewer steps, one screen per task: "para crear un docente primero debo de registrarlo como persona, después moverme de sección a docentes para traerlo como docente, y luego si quiero cambiar de contraseña o usuario a ese docente irme a la sección del final a cuentas de docentes. Y seguramente eso pasa igual en otros flujos más, es laborioso para el usuario y poco instuitivo."
- S2. Person page as the hub: it shows the person's matrículas, pagos, account, teacher role and documents, with actions to enroll, register a payment and make the person a teacher (approved order, item 1).
- S3. New teacher in one screen: look up by document; if the person does not exist, their data is entered in the same form; teacher data; optional account (the document as user plus a password). The account actions (create, change password, deactivate, reactivate) live on the teacher's page (item 2).
- S4. Window enrollment complete in one screen: find or register the student by document, the payment, the enrollment (item 3).
- S5. Group form with its schedule and teacher together, as in the previous system, where «Programación de horario» registered group, schedule, teacher and room in one form (item 4).
- S6. Global search finds teachers by name or document (item 5).
- S7. UI quality: "Sigue además las buenas práctiacs de diseño ui/ux, jerarquía de componentes, alineamiento, etc. Quiero que el usuario se sienta cómodo, intuitivo y fácil de usar." Scope now: "Comienza primero con el panel de administrador por el momento."

## Tasks

- [ ] T1 · S1, S3, S4, S7 · Personas: `RegistrarPersona` action (extracted from the create page) and contract `FormularioDePersona` (document lookup that shows the found person or the fields to register a new one) for other modules' forms · inline
- [ ] T2 · S1, S3, S7 · New teacher in one screen (person lookup or inline registration, optional account) and account actions plus account status on the teacher page · inline
- [ ] T3 · S2, S7 · Person page as hub: tabs contributed by each module (matrículas, pagos, docente/cuenta, documentos) and header shortcuts (Matricular, Registrar pago, Hacerlo docente) · inline
- [ ] T4 · S4, S7 · Window enrollment with person lookup or inline registration and payment in the same form · inline
- [ ] T5 · S5, S7 · Group form with schedule and teacher · inline
- [ ] T6 · S6 · Global search: teachers by name or document · inline
- [ ] T7 · all · Manuals (personal) and data dictionary if the schema changes · inline

## Log

- L1 (2026-11-27) Original request: "Bien, por mientras hablemos sobre el manejo del sistema. Te parece que es poco intuitivo y muy trabajaso para algunos casos? Por ejemplo, para crear un docente primero debo de registrarlo como persona, después moverme de sección a docentes para traerlo como docente, y luego si quiero cambiar de contraseña o usuario a ese docente irme a la sección del final a cuentas de docentes. Y seguramente eso pasa igual en otros flujos más, es laborioso para el usuario y poco instuitivo. Recuerdas cómo era el sistema anterior cuando lo revisaste?"
- L2 (2026-11-27) Approved order (1 person hub, 2 new teacher, 3 window enrollment, 4 group with schedule and teacher, 5 search): "Bien, sí, pero primero voy a comprimir la conversación."
- L3 (2026-11-27) "Bien, puedes empezar con todo. Sigue además las buenas práctiacs de diseño ui/ux, jerarquía de componentes, alineamiento, etc. Quiero que el usuario se sienta cómodo, intuitivo y fácil de usar. Comienza primero con el panel de administrador por el momento."
- L4 Evidence (panel audit 2026-11-27): no inline create anywhere; the person page has no related records; a teacher takes 3 sections (Personas, Docentes, Seguridad › Cuentas de docentes); window enrollment takes Personas → Pagos (register + validate) → Matrículas; a teacher is assigned to a group only from the teacher page (Cargas); global search finds teachers only by code.
- L5 Design: modules may only use each other's Contracts/Data/Enums/Events, so cross-module form fields follow the existing `FormularioDePagoAnterior` pattern (the owning module returns its Filament fields and parses the state). Order of work: T1/T2 first, since the person lookup is the base the other flows reuse; T3 after.
