# CILN documents with the new design and the administrable yearly denomination

Objective: issued documents follow the design proposal (`docs/diseno/propuesta-visual-2026-11/CILN - Documentos.html`), the yearly denomination becomes administrable, and the acta QR verifies the acta without exposing grades. This is delivery V2 of the plan `golden-rolling-hippo`.
Branch: `feat/documentos-diseno` (stacked on `fix/sidebar-y-reportes`). Delivery: one commit per unit; the user decides push and merge. Test runner: Pest.

## Specs

- S1 (user): "sobre descripción del año debería de ser administrable" and "Sección propia está bien".
  - A "Denominación del año" section under Configuración holds one text per year.
  - Each document keeps the text of its issue year; the acta uses its validation year.
  - The line is omitted when the year has none.
- S2 (user): "en el html de documentos hay imágenes de las insignias de la universidad y CILN, eso tú ya lo tienes incluso"
  - Documents follow the proposal using the existing `resources/images/pdf/unasam.png` and `emblema.png`.
  - Certificate: double gold frame, the denomination, "CERTIFICADO de culminación de nivel", "El Director … otorga", hours and grade in words, seal space, QR.
  - Constancias: UNASAM | CILN header, "HACE CONSTAR", the grade in words.
  - Acta: "ACTA N.º", a CERRADA box, compact course data, an "En letras" column, a summary of matriculados/aprobados/desaprobados/NSP, signatures with seal, and a QR.
  - Pre-acta: the same header with its mark.
- S3 (plan): the acta QR opens a signed, non-guessable verification page showing only that the acta exists, its group, period, state and closing date, never grades or students.
- S4 (user): "los formatos de horario y fichad de matrícula que tenemos son correctos". The schedule, the enrollment sheet and the inscription constancia are not touched.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1 | inline | done | see L2 |
| T2 | S2 (grade in words, certificate, constancias) | inline | done | see L3 |
| T3 | S2, S3 (acta, pre-acta, acta verification) | inline | done | see L4 |
| T4 | S2 (student reports: boleta, récord, avance, asistencia) | inline | pending | |

## Log

- L1 (2026-11-01): "Avanza con todo lo faltante, al final veremos el bug del parpadeo". Plan V1–V5 approved.
- L2 (2026-11-01): T1 done.
  - `cer_denominaciones`:
    - `anio` unique, between 2000 and 2100;
    - `texto` not blank, up to 250 characters;
    - audit trigger.
  - Model `DenominacionDelAnio` with its policy (`certificacion.documentos`).
  - Contract `Certificacion\Contracts\DenominacionDelAnio`, implemented by `ConsultarDenominacionDelAnio`.
  - `DenominacionDelAnioResource` under Configuración (sort 8).
  - `EmisionDeDocumento` freezes `detalle.denominacion`. `GenerarDocumentoPdf` reads it, and falls back to the issue year for older documents. `GenerarActaFinalPdf` uses the validation year.
  - `x-pdf.documento` gains a `denominacion` prop, printed as «…» above the title; the certificate prints it too.
  - RED: 3 new tests errored first. Certificacion, Tramites, Feature and Architecture: 540 pass.
- L3 (2026-11-01): T2 done.
  - `Formato::notaEnLetras` ("15.59" gives "Quince con cincuenta y nueve"; hundredths below ten keep "cero") and `Formato::numeroEnLetras` (0–999, feminine for hours).
  - Shared PDF components follow the design:
    - `x-pdf.documento`: UNASAM mark | "Centro de Idiomas y Lenguas Nativas / CILN" with the emblem, a gold double rule, the denomination, a spaced serif title and the number under it;
    - `x-pdf.firmas`: an optional dashed seal circle;
    - `x-pdf.verificacion`: QR, code, URL and issue date as a footer.
  - Certificate:
    - double gold frame with corners and the emblem as a watermark;
    - "La Dirección … otorga el presente certificado a" and the name underlined;
    - cycle range in Roman numerals, hours and grade in words;
    - QR with number and code, seal and signers.
  - Constancias: "LA DIRECCIÓN … que suscribe: HACE CONSTAR: Que, …". The notas constancia has an "En letras" column and a total/average row.
  - Real PDFs rendered and read back: the certificate fits one landscape page, the constancia one portrait page.
  - RED: 2 design tests and the unit tests failed first. Unit, Certificacion, Matricula and Feature: 774 pass.
- L4 (2026-11-01): T3 done.
  - Acta final, following the design:
    - "Acta N.º" under the title, the CERRADA · date box and compact course data;
    - an "En letras" column and failed conditions in bold;
    - a summary of matriculados, aprobados, desaprobados and NSP, plus the scale note;
    - QR, seal and signatures, all on one landscape page with 4 students.
  - Acta verification: `GenerarActaFinalPdf` puts `URL::signedRoute('verificacion.acta')` in the QR.
  - `VerificarActa` (`signed` + `throttle:30,1`, noindex) shows group, language, level and cycle, period, state, closing date and the number of students. It never shows names, documents, grades or the teacher.
  - Pre-acta: same letterhead plus a faint "SIN VALOR OFICIAL" watermark.
  - `x-pdf.firmas` gains an `espacio` prop.
  - RED: 3 new tests failed or errored first. Certificacion, Docencia, Feature and Architecture: 836 pass.
  - Real PDFs read back: the acta and the pre-acta fit one page.
  - Risk item 2 (a new public route): an independent verifier comes next.

