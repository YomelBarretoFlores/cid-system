# CILN enrollment record per period, after the UNASAM SGA design

Objective: one enrollment record (Ficha de Matrícula) per student and academic period, laid out like the UNASAM SGA record, with a verification URL, a QR that opens the same record again, and a barcode.
Branch: `feat/ficha-por-periodo` (from `main` 626a1cb) · Delivery: one commit per task, push and merge decided by the user · Test runner: Pest (`php artisan test`), browser check on `ciln_preview`.

## Specs

- S1 (user, 2026-10-26): "Sería bueno tomar en cuenta ese diseño." — the SGA "Ficha de Matrícula 2026-2": logos left and right with the institution centered, title with the period, issue timestamp at the top right, student line, table of what was enrolled, totals, barcode, verification URL, QR and a closing validation line.
- S2 (user, 2026-10-26): "Incluso tiene un link de validez de la contancia, y ese QR cuando se escanea se muestra el mismo archivo nuevamente." — the verification URL and the QR open the same record again.
- S3 (user, 2026-10-26): "Sí, hazla por periodo." — one record per student and period, one row per enrollment of that period.
- S4 (rules): Ley 29733 (synthetic data only; the SGA sample is never copied); module boundaries; tokens and components; tests on PostgreSQL; browser check with the system running.

Assumptions (to confirm with CILN, recorded in `docs/decisiones-pendientes.md`):

- A1: the UNASAM logo is taken from the SGA record (800×215) until UNASAM provides the official file.
- A2: the public copy opened from the QR masks the document number like the certificate verification does; the copy the student or staff downloads shows it in full.
- A3: the existing per-enrollment links (`estudiante.matriculas.ficha`, `panel.matriculas.ficha`) open the record of that enrollment's period, so no link breaks; the constancia de inscripción stays per enrollment and takes the new header.
- A4: withdrawn enrollments stay in the table, marked "Retirada", and do not add to the total.

## Tasks

| ID | Specs | Route | Status | Commit |
|---|---|---|---|---|
| T1 | S1, S2 | inline | done | 3a16119 |
| T2 | S1, S3, A1, A3, A4 | inline (Risk: item 1, new table) | done, review approved | 7c1eb9d |
| T3 | S2, A2 | inline (Risk: item 2, public route) | done, review approved | 1ccd1bd, d5bd6dc |

- T1: QR helper moved from Certificacion to `app/Shared/Ui` (Certificacion keeps working), Code128 barcode as a PNG data URI for DOMPDF, unit tests.
- T2: `mat_fichas` (public code per person and period, unique), period record composed from every enrollment of the student in that period, new PDF layout with UNASAM and CILN logos, routes kept, portal "Mis matrículas" grouped by period with one record button per period, panel action unchanged, tests.
- T3: public route that opens the same record by its code (rate limited, noindex, masked document), QR + URL + barcode on the record, tests, docs.

## Log

- L1 (user, 2026-10-26): "En mis descargas hay un archivo llamado 2026-2.pdf ... es ficha de matrícula de la misma UNASAM ... Sería bueno tomar en cuenta ese diseño. ... tiene un link de validez de la contancia, y ese QR cuando se escanea se muestra el mismo archivo nuevamente." then "Sí, hazla por periodo." and "Tienes el logo de la unasam?"
- L2 (exploration): the ficha is built per enrollment (`ComponerDocumentoDeMatricula`, code = enrollment ULID) with spatie/laravel-pdf on DOMPDF; the period is reachable through `FichaDeGrupo::periodoId` from `Academico\Contracts\DetalleDeGrupos`; `mat_matriculas` has no period column; QR is `Certificacion\Support\CodigoQr` (endroid, PNG data URI); the public verification (`/verificar/{codigo}`) shows masked HTML for `cer_documentos` only; no barcode code or package exists; `DocumentoDeMatriculaPorId` (per enrollment) is used by Certificacion and Tramites and stays.
- L3 (review T2, approved): shared letterhead component for the ficha and the constancia; the Costo column shows only what adds to the total; the total test asserts its own cell; indentation fixed in "Mis matrículas"; the printed code becomes checkable with T3.
- L4 (evidence): browser check on `ciln_preview` (dropped) as the demo student: "Mis matrículas" grouped by period with one record button; the record lists Inglés (active, S/ 80.00) and Quechua (withdrawn, "—") with total S/ 80.00, UNASAM and CILN logos, barcode, verification address and QR; Chrome's BarcodeDetector read the Code 128 as the record code and the QR as its address; `/fichas/{código}` opened the same record with "DNI ••••0001". `composer check`: 1531/1531.
- L5 (review T3, approved): the public copy no longer shows the payment operation detail or the virtual class link (pinned by a test that fails if they come back); the withdrawn row's dash is asserted. Not changed: no cache for the public PDF render — it is throttled to 30 per minute per address and the code is unguessable.
