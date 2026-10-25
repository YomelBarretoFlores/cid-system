# CILN certificates and records with QR (M4.2)

Objective: staff issue, one by one or in bulk, enrollment records (constancias de matrícula), grade records (constancias de notas) and level completion certificates (certificados de culminación de nivel) as standardized institutional PDFs, each with a unique code and a QR that opens a public CILN page confirming its authenticity with the student's data and the level passed.
Branch: `feat/certificacion-documentos-qr` (from `main` 35d543b) · Delivery: one commit per unit on the feature branch, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Bien, entonces sigamos con el M4 actas finales, constancias, certificados QR" / "sube y une. Y podrás proseguir." (after M4.1 was merged).
- S2 (Anexo M4.2, functionality): "Emisión automatizada (masiva o individual) de Constancias de Matrícula, Constancias de Notas y Certificados de Culminación de Nivel." / "Documentos en PDF estandarizado con diseño institucional." / "Validación digital: Código QR único por documento que, al escanearse, redirige a un portal oficial del CILN/UNASAM que valida la autenticidad mostrando datos del alumno y nivel aprobado."
- S3 (Anexo M4.2, fields): "Datos del Documento: Código Único, Fecha de Emisión, Código QR." / "Datos de Acreditación: Nombres, Idioma, Nivel alcanzado, Total de horas lectivas, Nota final."
- S4 (Anexo M4.2, note): "la solicitud, el seguimiento y la notificación de estos documentos se gestionan mediante los pipelines de trámites del Módulo 6."
- S5 (Anexo, security): OWASP Top 10, "Tratamiento de datos personales conforme a la Ley N° 29733", "Pistas de auditoría (Log Triggers) inmutables para operaciones críticas".
- S6 (user rule, 2026-10-24): "Y ahora en adelante tomarás en cuenta esto de resilencia, etc." (Transaccion helper, lock order with race tests, indexes, masked rejection logs, audit).
- S7 (user rule, 2026-10-24): "llena de datos, pero solo en los flujos que ya completamos o cerramos."
- S8 (ADR 0008): screens use the design system tokens and components; PDFs use the shared institutional layout.

Assumptions (recorded in `docs/decisiones-pendientes.md`, to confirm with the CILN):

- D1: the unique code is `CILN-` plus 10 random Crockford base32 characters (not guessable, not sequential).
- D2: the M4.2 constancia de matrícula is issued by staff for an active enrollment, stored and verifiable; the M1 constancia de inscripción stays as the student's self-service PDF without QR.
- D3: the constancia de notas is per student and language and lists every closed acta of that language (cycle, period, final average, final state); actas under rectification do not count until closed again.
- D4: the certificado de culminación de nivel requires the four cycles of the level passed in closed actas; its final grade is the average of the four averages, rounded half up to hundredths; its teaching hours are the sum of those cycles; a student has at most one valid certificate per language and level.
- D5: an issued document is an immutable snapshot; staff void it with a reason (5–500 characters) and the public page then shows it as void.
- D6: the public page shows document type, code, issue date, full name, the document number partly hidden, language, level, teaching hours, final grade and whether it is valid or void; it is not indexed and is rate-limited.
- D7: permission `certificacion.documentos` lets panel users issue, void and download documents.
- D8: bulk issuing covers the certificates of a period's closed actas and the grade records of one acta; requests and notifications stay for M6.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, D2 | inline | done | see L3 |
| T2 | S2, S3, S5, S6, D1, D3–D5, D7 | inline | done | see L4 |
| T3 | S2, S3, S8 | inline | done | see L6 |
| T4 | S2, S5, D6 | inline | done | see L6 |
| T5 | S2, S8, D7, D8 | inline | done | see L8 |
| T6 | all | inline | pending | |

- T1: Matricula contracts for a person's enrollments and the data of one enrollment for a record. Risk: none beyond new contracts.
- T2: `cer_documentos` with audit and a lock trigger (only voiding changes it); code generator; Actions to issue each document type and to void; eligibility for certificates; unique valid certificate per student, language and level; permission migration; race test. Risk: items 1, 2, 4.
- T3: QR generation and the three PDF templates on the institutional layout. Risk: none.
- T4: public verification page (code in the URL or typed), throttled, masked document number, no indexing. Risk: item 2 (first public data page).
- T5: panel resource "Documentos emitidos" (list, filters, detail, individual issue, bulk issue, void, PDF) and "Emitir constancias de notas" from an acta. Risk: item 2.
- T6: demo data, docs, full suite, independent verifier, native review.

Route evidence: each unit builds on the previous one, so there is no parallel writer reason; all inline. Forecast about 2,200 authored changed lines; one commit per unit.

## Log

- L1 (2026-10-25, user): "sube y une. Y podrás proseguir."
- L2: Context: M4.1 merged (35d543b); `endroid/qr-code` 6.1 installed (constructor-based `Builder`, `SvgWriter`/`PngWriter`); Matricula exposes `DocumentoDeMatricula` but no contract for a person's enrollments; no public data page exists yet.
- L3: T1: contracts `MatriculasActivasDePersona` (options for the issue form) and `DocumentoDeMatriculaPorId` (reuses `ComponerDocumentoDeMatricula`); 2 tests, written with the implementation (thin wrappers over tested code).
- L4: T2: `cer_documentos` (snapshot of student, language, level, hours, final grade and detail; audit; `cer_documentos_bloqueo` allows only one voiding; partial unique index for one valid certificate per student, language and level), `CodigoDeDocumento` (CILN- + 10 Crockford base32), `HistorialDeNotas` (closed actas only, last passed result per cycle, average half up), Actions to issue each type, bulk issue per period and per acta, and void; every one checks `certificacion.documentos`; a repeated random code is retried and a duplicate certificate becomes a business message. 14 tests plus a race test (two certificates for the same student at once → one issued). Written implementation-first, then pinned by the tests.
- L5: T1–T2 reviewed with risk, resilience and reliability lenses: approved and acknowledged (lineage review-90831224f16c67aa). Advisories applied: a repeated bulk issue of an acta's grade records skips students who already got one after the acta closed; the period's bulk issue logs and skips a certificate issued meanwhile by someone else instead of stopping halfway (mutation check: without the guard the test fails); the race test no longer depends on output order. Left: the random-code retry has no test (a collision of 10 random base32 characters cannot be produced without injecting the generator).
- L6: T3 + T4 in one commit, because every PDF carries the URL of the public page: `CodigoQr` (endroid PNG data URI), `GenerarDocumentoPdf` (refuses a voided document), PDFs for the three types on `x-pdf.documento` with the shared `x-pdf.verificacion` block (QR, code, issue date, URL); public route `/verificar/{codigo?}` (`throttle:30,1`, `X-Robots-Tag` and meta robots `noindex, nofollow`, code normalized from lowercase or without dash, form redirect, 404 for unknown codes), showing type, name, document number with only its last four characters, language, level, hours, grade, summary, code, issue date and valid/void state, never the void reason. 11 tests; the certificate and grade record PDFs were rendered with DOMPDF and checked visually.
- L7: T3–T4 (with the earlier fix commit) reviewed with risk, reliability and readability lenses: approved and acknowledged (lineage review-427e6b141ed2c98c). Advisories applied in T5: PDF view and file name per type live on `TipoDocumento`; the public page normalizes the code once and validates the typed code before redirecting; the acta's bulk issue only looks up its own students; comments clarified. Left: `GenerarDocumentoPdf` has no authorization of its own; its only entry points (panel route) check the policy.
- L8: T5: panel resource "Constancias y certificados" (group Certificación; list with code, type, student, language and level, grade, state, issue date; filters by type and state; detail with the cycles or the enrollment, the voiding data; header actions "Emitir documento" (type, student search, then active enrollment, language with closed actas, or level) and "Certificados del periodo"; record actions PDF, public verification and "Anular" with a 5–500 reason and counter), `DocumentoPolicy`, route `panel.documentos.pdf`, and "Emitir constancias de notas" on a closed acta's page. 10 panel tests.
