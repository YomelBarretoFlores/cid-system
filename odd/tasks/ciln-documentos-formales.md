# CILN formal documents: signers, yearly numbers, formal certificate, constancias, acta and pre-acta

Objective: issued documents look serious and institutional, with named signers, a yearly number and verification, and the pre-acta can be printed. Delivery D5 of the approved plan. The CILN will send its official models later; this leaves the design ready to adapt.
Branch: `feat/documentos-formales` (stacked on `feat/catalogos-administrables`) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1 (user, L1): "Sobre los certificados, debe de ser un certificado serio y formal. Actualmente solo parece una hoja pdf con contenido, debe de ser certificados formales como da google, microsoft, etc. Más adelante te pasaría un modelo de cómo lo hace actualmente el CILN, pero por el momento podrías mejorarlo."
  - The level certificate is A4 landscape, with:
    - a border, the institutional header, the title "CERTIFICADO", and "Otorgado a" with the name large;
    - the text with language, level, hours and final grade;
    - place and date, and the signature lines of the current signers;
    - QR, code and yearly number.
- S2 (user, L1): "Sobre las actas y pre actas de notas de igual manera, se ve todo genérico y generado por una IA. Mejóralo, pero más adelante te pasaré algún formato."
  - Constancias read as formal letters ("hace constar") with their signers, QR, code and number.
  - The acta final carries signature lines for the teacher and the acta signers.
  - The pre-acta (an open group's current grades) gets a PDF marked "PRE ACTA — documento de trabajo, sin valor oficial". The group's teacher downloads it from the teacher panel; coordination downloads it from the panel.
- S3 (plan): signers are administrable.
  - Each signer has a position, a name, which documents they sign (certificates, constancias, actas) and a validity.
  - A document prints the signers in force on its issue date.
- S4 (plan): every new document gets a number per type and year ("N.º 000123-2027"), assigned when it is issued, never repeated (counter row locked plus a UNIQUE constraint). Documents issued before this change have no number.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S3, S4 | inline | done | see L3 |
| T2 | S1, S2 (certificate, constancias, acta) | inline | done | see L4 |
| T3 | S2 (pre-acta) | inline | pending | |

## Log

- L1 (2026-10-31): "Sobre los certificados, debe de ser un certificado serio y formal. Actualmente solo parece una hoja pdf con contenido, debe de ser certificados formales como da google, microsoft, etc. Más adelante te pasaría un modelo de cómo lo hace actualmente el CILN, pero por el momento podrías mejorarlo. Sobre las actas y pre actas de notas de igual manera, se ve todo genérico y generado por una IA. Mejóralo, pero más adelante te pasaré algún formato."
- L2 (2026-11-01): plan approved. Mapping came from one read-only explorer.
  - Constraints found:
    - `cer_controlar_documentos()` blocks any UPDATE besides voiding, so the number is set at INSERT;
    - every document is inserted by `EmisionDeDocumento::emitir`;
    - `ActaPrevia::armar` gives the open group's acta;
    - a pre-acta download from Docencia or Academico pages must go through a Certificacion route.
  - Route: inline.
- L3 (2026-11-01): T1 done.
  - Migration `2026_11_01_130000`:
    - `cer_firmantes` (CHECK: signs at least one kind; validity; audit);
    - `cer_correlativos` (PK tipo+year);
    - `cer_documentos.anio_numero` and `numero` (CHECK, partial unique `cer_documentos_numero_unique`).
  - `NumeracionDeDocumentos::siguiente` upserts the counter row and reads it FOR UPDATE inside `EmisionDeDocumento`'s transaction. A retried code collision rolls the counter back too.
  - `Documento::numeroOficial()` gives "N.º 000123-2027".
  - `Firmante::firmanEn(GrupoDeFirma, fecha)`. `FirmanteResource` lives under Configuración.
  - RED: 5 new tests failed first (classes missing). Certificacion, Tramites, Feature and Concurrency: 547 pass.
- L4 (2026-11-01): T2 done.
  - The level certificate is its own A4 landscape page:
    - double frame in the CILN gold, the UNASAM mark and the CILN emblem;
    - "CERTIFICADO / de culminación de nivel", "Otorgado a" and the name large;
    - formal text, "Huaraz, 10 de abril de 2027", the signers' lines;
    - QR, number and code at the foot.
  - `GenerarDocumentoPdf` passes `firmas` (the signers in force on the issue date for the document's group), `lugarYFecha`, and landscape for the certificate.
  - Constancias open "La Dirección del Centro … HACE CONSTAR que", close with "Se expide la presente…", and show the number in the header and the signature block.
  - The acta final has signature lines for the teacher ("Docente del curso") and the acta signers in force on its validation date.
  - New `x-pdf.firmas` component: with no signers it leaves one line for "Dirección del Centro de Idiomas y Lenguas Nativas".
  - Real PDFs rendered and read back: the certificate fits one page, and the constancia and acta show their signers.
  - RED: 4 new tests failed first. Certificacion, Tramites, Matricula and Feature: 809 pass.

