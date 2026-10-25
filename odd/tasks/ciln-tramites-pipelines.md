# CILN trámite pipelines (M6.2)

Objective: students request the CILN trámites (constancias, certificado de nivel, traslado, retiro) from their portal and follow each one stage by stage with a progress bar; the flow waits until Pagos validates the fee, staff move it forward, observe it or finish it (issuing the document or applying the enrollment change), every state change is e-mailed and logged, and administrators configure each trámite's stages and conditions without code.
Branch: `feat/tramites-pipelines` (from `main` 32bc5e5) · Delivery: one commit per unit on the feature branch, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Sube y une y continúa" (after M6.1 was merged; the agreed next step is M6.2).
- S2 (Anexo M6.2, functionality): "Un flujo configurable por cada trámite (constancia, certificado de nivel, traslado o retiro), con etapas y estados definidos." / "Transiciones condicionadas por reglas: el flujo se bloquea hasta que el Módulo de Gestión de Pagos confirme la validación del pago de la tasa según el TUPA/TUSNE." / "Barra de progreso visible para el usuario (Ej. 0% Solicitud recibida → 33% Pago validado → 66% En firma de Dirección → 100% Listo para recojo/descarga)." / "Notificación automática por correo electrónico ante cada cambio de estado del trámite." / "Panel autogestionable para que el administrador configure etapas, estados y condiciones de cada trámite sin programación."
- S3 (Anexo M6.2, fields): "ID de Trámite (código único de seguimiento)." / "Estados del Flujo: Pendiente de Pago, En Proceso, Observado, Finalizado." / "Log de Notificaciones: registro de correos enviados (Fecha, Hora, Estado de entrega)."
- S4 (Anexo M4.2, note): "la solicitud, el seguimiento y la notificación de estos documentos se gestionan mediante los pipelines de trámites del Módulo 6."
- S5 (Anexo, security): OWASP Top 10, "Tratamiento de datos personales conforme a la Ley N° 29733", "Pistas de auditoría (Log Triggers) inmutables para operaciones críticas".
- S6 (user rule, 2026-10-24): "Y ahora en adelante tomarás en cuenta esto de resilencia, etc." (Transaccion helper, lock order with race tests, indexes, masked rejection logs, audit).
- S7 (user rule, 2026-10-24): "llena de datos, pero solo en los flujos que ya completamos o cerramos."
- S8 (ADR 0008 and design `Portal Estudiante v3`): "Trámites: Sigue cada solicitud etapa por etapa. Recibirás un correo en cada cambio de estado." — list with type, code, start date, state, stage bar and observation; "+" buttons to start a trámite; menu badge with the observed ones.

Assumptions (recorded in `docs/decisiones-pendientes.md`, to confirm with the CILN; D1 resolves decision #6 provisionally):

- D1: the four states are fixed and derived; the stages are configurable per trámite type, each with a progress percentage. Pendiente de pago until the fee is validated; En proceso while it moves through the stages; Observado when staff (or a rejected payment) leave an observation, until it is resolved; Finalizado when it reaches the last stage (100%).
- D2: a trámite type has code, name, requirements text, whether it needs a fee and of which concept type (constancia, certificado, trámite or otro; never matrícula, pensión or examen), its final action (none, issue enrollment record, issue grade record, issue level certificate, transfer, withdrawal) and its ordered stages. Each stage has name, percentage and a condition to enter it: "pago validado" (the system enters it when the fee is validated) or "acción del personal". The first stage is 0%, the last is 100%, percentages grow, and a type with a fee has exactly one "pago validado" stage, right after the first.
- D3: a trámite copies its type's stages when it is requested, so later configuration changes never alter trámites in progress.
- D4: the fee is linked automatically: on request, the oldest validated payment of the person and concept type not yet used by another trámite; otherwise, when Pagos validates one later, it goes to the person's oldest trámite waiting for that concept type. A payment serves one trámite. A rejected payment observes the waiting trámite; a voided payment returns an unfinished trámite to Pendiente de pago.
- D5: what the student provides depends on the final action: an active enrollment (enrollment record, transfer, withdrawal), a language with closed actas (grade record), an eligible language and level not yet certified (level certificate), plus an optional comment (up to 500 characters). Transfer and withdrawal need a reason.
- D6: staff with `tramites.gestionar` advance to the next "acción del personal" stage, observe (reason 5–500 characters), resume an observed trámite and finish it at the last stage, which runs the final action as that staff member (issuing a document still needs `certificacion.documentos`; the transfer destination is chosen when finishing). `tramites.configurar` manages the types and stages. Nobody deletes a trámite.
- D7: the tracking code is `TR-` + year + `-` + six-digit number; the student sees and downloads the issued document from the trámite.
- D8: each state or stage change sends one e-mail to the person's address through the queue (3 attempts with backoff); the log stores date and time, recipient, subject and delivery state (pendiente, enviado, fallido), never the message body. A failed delivery never undoes the change.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, S3, S5, S6, D1–D3, D7 | inline | pending | |
| T2 | S2, S8, D2 | inline | pending | |
| T3 | S2, S4, S8, D4, D5 | inline | pending | |
| T4 | S2, S6, D4 | inline | pending | |
| T5 | S2, S4, D6, D7 | inline | pending | |
| T6 | S2, S3, S6, D8 | inline | pending | |
| T7 | all | inline | pending | |

- T1: schema `tra_tipos_tramite`, `tra_etapas`, `tra_tramites` (stages snapshot, state, current stage, data, unique payment), `tra_movimientos`, `tra_notificaciones`; audit triggers, lock trigger (no deletes, finished trámites immutable), permissions, the five default types with the Anexo's four stages; configuration rules. Risk: items 1, 2.
- T2: panel "Tipos de trámite" (fields, ordered stages, conditions, validation, activate/deactivate). Risk: item 2.
- T3: request Action and student page "Trámites" (list, progress bar, observation, new request by type with its data), menu badge; contracts from Matricula and Certificacion for the student's options. Risk: item 2.
- T4: fee gate: link on request and on `PagoValidado`, observation on `PagoRechazado`, back to pending on `PagoAnulado`; lock order trámite → payment; race test. Risk: items 1, 4.
- T5: panel "Trámites" (list, filters, detail with timeline, advance, observe, resume, finish with the final action through Certificacion and Matricula contracts) and the student's download of the issued document. Risk: items 2, 3.
- T6: notifications: after-commit event, queued job and mailable, log with delivery state, log in the trámite detail. Risk: none beyond D8.
- T7: demo data, docs, full suite, independent verifier, native review.

Route evidence: each unit builds on the previous one's tables and Actions, so there is no parallel writer reason; all inline. Forecast about 3,500 authored changed lines; one commit per unit, high-risk units alone.

## Log

- L1 (2026-10-25, user): "Sube y une y continúa"
- L2: Context: M6.1 merged (32bc5e5). Pagos events carry only `pagoId` and are after-commit; `ConsultaDePago` returns no person or concept type; payments are consumed once per consumer table with a unique `pago_id`; Certificacion exposes no contracts and its emission Actions take the issuing `User` and check `certificacion.documentos`; Matricula's transfer and withdrawal Actions take the authorizing `User` and have no contracts; no mail, notification or listener exists yet; tests run the queue as `sync` and mail as `array`.
- L3: T1: `tra_tipos_tramite`, `tra_etapas`, `tra_tramites` (code default from a sequence, `TR-YYYY-000000` in Lima time; stages snapshot; one payment per trámite; checks for the stage range, observation and finish date), `tra_movimientos` (append-only), `tra_notificaciones` (no deletes); audit on types, stages and trámites; `tra_controlar_tramites` keeps the request fixed and a finished trámite closed; TRUNCATE rejected with rows (concurrency cleanup lifts it like the `cer_*` guards); permissions `tramites.gestionar`, `tramites.configurar`; the five Anexo types with its four stages (withdrawal without fee: three stages); `ReglasDeEtapas`. Written implementation-first, pinned by 25 tests (schema guards, defaults valid, code format, snapshot isolation, eight rule violations). Architecture, concurrency and database tests 73/73.
