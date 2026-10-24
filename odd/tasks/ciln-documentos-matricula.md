# CILN enrollment documents (ficha de matrícula and constancia de inscripción)

Objective: generate the enrollment record (ficha de matrícula) and the enrollment certificate (constancia de inscripción) as PDFs, which the student downloads from "Mis matrículas".
Branch: `feat/documentos-matricula` (stacked on `feat/portal-estudiante`) · Delivery: ask-on-risk, stacked branches, one commit per unit · Test runner: Pest (`php artisan test`).

## Specs

- S1: "Bien, sigue entonces." (after the proposal "Por el TdR, lo siguiente sería generar la ficha de matrícula y la constancia en PDF (M1)")
- S2 (Anexo M1.3): "Generación automática de la Ficha de Matrícula en PDF." / "Datos de Inscripción: ID Estudiante, Grupo, Fecha y Hora de Matrícula." / "Validación de Pago: Estado (Verificado/Pendiente) según el Módulo de Gestión de Pagos." / "Validación Académica: Condición (Regular, Repitente)." / "Documentos Generados: Ficha de Matrícula (PDF), Constancia de Inscripción."
- S3 (ADR 0005): PDFs with DOMPDF; templates laid out with tables and simple CSS.
- S4 (DSTI 1.2, Ley 29733): each student downloads only the documents of their own enrollments; another student's enrollment answers 404.

Assumptions (to confirm with the CILN):
- D1: Documents are rendered on request from current data, so a schedule or classroom change shows up; the enrollment ULID is printed as the document code. Digital validation with a QR code belongs to M4 (certificates).
- D2: The constancia de inscripción is free and immediate for an active enrollment; a withdrawn enrollment has no constancia. The ficha stays available for any enrollment and shows its state.
- D3: The ficha shows the payment state from Pagos ("Verificado" when validated, otherwise the payment's own state, such as "Anulado"). The teacher is not shown until Docencia (M3) assigns teachers to groups.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2, S3, S4, D1-D3 | inline | done | ffcdea9 |
| T2 | all | inline | done | see L3 |

- T1: Contracts `FichaDePersona` (Personas) and `ConsultaDePago` (Pagos); Action `ComponerDocumentoDeMatricula` (Matricula); PDF templates for the ficha and the constancia; owner-only routes `estudiante.matriculas.ficha` and `estudiante.matriculas.constancia`; download links in "Mis matrículas". Risk: item 2 (record-level access).
- T2: Docs (`modulos.md`, decisiones-pendientes), full suite, native review.

Forecast: about 600 authored lines in two commits.

## Log

- L1 (2026-10-24): "Bien, sigue entonces."
- L2: Map: spatie/laravel-pdf 2.14 with DOMPDF (`PdfGenerationTest`), `Pdf::fake()` and `assertRespondedWithPdf`; `FichaDeGrupo` from `DetalleDeGrupos`; `Matricula` route key `ulid`; no teacher assignment yet.
- L3: T1 inline: RED 10 of 10 (routes missing) → GREEN 10/10; owner-lookup mutation (drop `persona_id`) killed by 2 tests; templates checked visually (A4, tables, DejaVu Sans); real DOMPDF render tested for both documents; `BuscarMatriculaDelEstudiante` answers 404 for another student's enrollment; error flash added to the portal layout. T2: docs (`modulos.md`, decisiones-pendientes rows 29-30). `composer check` 610/610, phpstan 0.
- L4: Independent verifier (item 2) over 4c7b4fc..ffcdea9: S1-S4 and D1-D3 met, no blockers; 23 probes committed as regression tests (other student's ULID, numeric id and changed-case ULID 404, unverified and guest blocked on both routes, withdrawn enrollment, no payment, pending/voided labels, virtual group, Lima date boundary, escaped names, snapshot replay); `/estudiante/matriculas` output unchanged except the new links. Advisory for the CILN: the ficha prints the identity document as "ID Estudiante" (no student code exists).
