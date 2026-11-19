# CILN gaps found against the signed Anexo Técnico

Objective: close three gaps found in the 2026-11-19 compliance check of the signed TdR/Anexo against the code.
Branch: `fix/brechas-del-anexo` (from main 230c64b) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`DB_PORT=5433 DB_USERNAME=ciln DB_PASSWORD=secret vendor/bin/pest`), `composer check`.

## Specs

- S1 (Anexo III, Módulo 4.1, verbatim): "Interfaz exclusiva para el docente asignado, habilitada solo durante las fechas del calendario académico." Today `RegistrarNotasParciales` accepts partial grades any day, even after the acta is closed. Assumption: the calendar window for partial grades runs from the period's `fecha_inicio` to its `actas_fin` (inclusive, America/Lima dates); outside it, or once the group's acta is closed, saving is refused with a clear message and the teacher's pre-acta screen shows it read-only with the window.
- S2 (Anexo II.2 and IV.2, verbatim): "Tratamiento de datos personales conforme a la Ley N° 29733." Today there is only a consent sentence at sign-up and no privacy notice; people registered by staff have no recorded consent. Scope: a public privacy-notice page (titular UNASAM–CILN, purpose, data collected, retention, transfers only by legal mandate, ARCO rights and how to exercise them), linked from the sign-up consent checkbox, the public footer and the sign-in page. Assumption: when staff register a person, the form records consent with a checkbox "La persona aceptó el tratamiento de sus datos personales" (signed in person), which sets `tratamiento_aceptado_en`.
- S3 (Anexo III, Módulo 4.2, verbatim): "Emisión automatizada (masiva o individual) de Constancias de Matrícula, Constancias de Notas y Certificados de Culminación de Nivel." Today Constancias de Matrícula are issued one at a time. Scope: bulk issue of Constancias de Matrícula (for example for all active enrollments of a group or of a period), reusing the existing document generator and its rules, like the existing bulk issue of constancias de notas and certificados.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1 | delegated (parallel unit, Docencia) | done | 7d91b02 |
| T2 | S2 | delegated (parallel unit, Personas/public pages) | done | a622f16 |
| T3 | S3 | delegated (parallel unit, Certificacion) | done | 22365d0 |

## Log

- L1 (2026-11-19, user): "Corrige en código la 1, 2 y 3" (gaps 1–3 of the compliance check: grade window, privacy notice, bulk constancias de matrícula).
- L2: three independent units with disjoint surfaces → three parallel writers; docs/manuales and the report are updated by the parent at the end.
- L3: T3 done (22365d0): "Constancias de matrícula del grupo" header action in Documentos, per group, reuses EmitirConstanciaDeMatricula, skips enrollments with a valid constancia, advisory lock per group; Certificacion 187/187.
- L4: T1 done (7d91b02): VentanaDeNotasParciales (fecha_inicio..actas_fin, Lima; any acta of the group freezes the pre-acta); DatosDelCurso gains fechaInicio; Docencia 361/361, shared users 519/519. A period without actas_fin now refuses partial grades.
- L5: T2 done (a622f16): GET /privacidad (route `privacidad`), links from sign-up, /ingresar and public footer; staff checkbox `acepta_tratamiento` never clears consent; docs/seguridad.md updated. Personas+Feature 551/552, the failure is ProxiesDeConfianzaTest (local env trustedproxy `*`), unrelated.
