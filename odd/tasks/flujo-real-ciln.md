Align the system with how CILN actually works today (documents, curriculum, modalities, fees), from material the user shares.
Branch: feat/flujo-real-ciln · Delivery: ask-on-risk (release v1.2.0) · Runner: Pest on PostgreSQL 18.
Plan: ~/.claude/plans/misty-crafting-finch.md (approved 2026-11-25). Accelerated = one group covers two consecutive cycles with ONE grade for both.

## Specs

- S1. Grade certificate (constancia de notas) follows the real CILN format (photo of "CONSTANCIA DE NOTAS N° 2026 - 01125", 2026-11-16):
  - Header: UNASAM shield left, Centro de Idiomas logo right, "UNIVERSIDAD NACIONAL SANTIAGO ANTUNEZ DE MAYOLO", motto "Una Nueva Universidad para el Desarrollo", "CENTRO DE IDIOMAS"; footer "Prolongación Simón Bolívar S/N – Huaraz – Telf: 043-640020 / Anexo 3105", "Web: cid.unasam.edu.pe / Facebook: cidunasam".
  - Opening: "EL DIRECTOR DEL CENTRO DE IDIOMAS DE LA UNIVERSIDAD NACIONAL "SANTIAGO ANTÚNEZ DE MAYOLO" QUIEN SUSCRIBE AL FINAL, HACE CONSTAR LA SIGUIENTE:"
  - Title with yearly number: "CONSTANCIA DE NOTAS N° 2026 - 01125".
  - Data: ALUMNO, IDIOMA with program ("ITALIANO (JÓVENES Y ADULTOS)"), CÓDIGO, CURRICULO. User: "lo del código no sabemos cómo sale, entonces creo que allí iría dni".
  - Table: ITEM | NIVEL | CICLO (roman) | AÑO | MES | NOTA | FORMA DE APROBACIÓN ("MATRÍCULA").
  - One level per certificate (fee is "S/ 15.00 (POR CADA NIVEL)").
  - "Programa Curricular" block: cycles per level of that language (Italian: BÁSICO 4 Ciclos, INTERMEDIO 4 Ciclos, AVANZADO 4 Ciclos).
  - "Huaraz, 16 de Noviembre de 2026", signature "DIRECTOR (e) CID - UNASAM".
  - Assumption: keep the QR verification block the system already prints.
- S2. Curriculum per language and level (cycles = months, ciclo regular), configurable. User: "para inglés la currícula es básico 10, intermedio 10 y avanzado 6. Italiano 4 en todos los niveles." Poster: Inglés 10/10/6, Francés 6/6/6, Italiano 4/4/4, Quechua 6/6/6, Alemán 6/6/6, Portugués 6/6/6.
- S3. Cycle modality regular vs acelerado. User: "todo eso es para ciclo regular, cuando es acelerado es la mitad de cada uno" (poster handwriting: Inglés 5/5/3, Francés 3/3/3, Italiano 2/2/2).
- S4. Postgraduate program. User: "hay modalidad para postgrado, para ellos siempre es ciclo acelerado."
- S5. Everything configurable. User: "todos esos valores que has visto son actuales, pero igualmente todo debe de ser configurable, los precios, etc."
  Current fees (poster "Ciclo regular"): Matrícula S/ 10.00; Pensión mensual S/ 100.00; Matrícula extemporánea 20%; Examen de suficiencia S/ 180.00; Examen de clasificación o reclasificación S/ 40.00; payments at "TESORERÍA LOCAL CENTRAL UNASAM, AV. CENTENARIO N° 200 - DISTRITO DE INDEPENDENCIA - HUARAZ". Schedules: 8:00, 9:30, 11:00, 2:00, 3:30, 5:00, 6:30, 8:00 PM.
- S6. Certificates (poster "Certificado de estudio"):
  - Constancia de notas: "PAGO: S/ 15.00 (POR CADA NIVEL)", "FUT".
  - Certificado requirements: "FUT", "CONSTANCIA DE NOTAS AUTENTICADAS EN SECRETARIA GENERAL UNASAM", "COPIA DNI SIMPLE", "02 FOTOGRAFIAS CON TERNO TAMAÑO CARNÉ A COLORES (POR CADA NIVEL)".
  - Certificado fees: Nivel Básico S/ 40.00, Intermedio S/ 50.00, Avanzado S/ 60.00.
- S7. General request form (FUT, "FORMATO UNICO DE TRAMITE") that students fill depending on the procedure:
  - Addressed "SEÑOR DIRECTOR DEL CENTRO DE IDIOMAS DE LA UNASAM / S. D."
  - "El suscrito: … (Apellidos y Nombres del Estudiante) … D.N.I. …", "Alumno del idioma … Programa Jóvenes/Adultos ( ) Niños ( )".
  - "Solicitar:" Constancia de Notas ( ) Nivel; Certificado de Estudio ( ) Nivel; Acreditación ( ) Nivel; Examen de Suficiencia ( ) Nivel; Examen de Clasificación ( ) Nivel "(Para alumnos nuevos)"; Examen de Reclasificación ( ) Nivel "(Para alumnos del Centro de Idiomas que dejaron de estudiar 2 ó más meses)"; Media Beca ( ) Modalidad; Otro: … Cel: …
  - "Y expongo que: …" (free text), "Por lo tanto: Pido a usted Señor Director acceder a mi petición por ser de justicia.", "Huaraz, … de … de 20…", "Firma del Alumno o Apoderado".
  - Detachable "CARGO": Apellidos y Nombres, Motivo, Fecha, Hora, Folios, Recepción.
  - Filled examples ("MODELO PARA …"): Certificado (Quechua, nivel básico); Acreditación written under "Otro: Acreditación Básico", exposition asks for "Mención: Maestría / Dr en Educación Superior"; Constancia de notas (Italiano) naming the teacher and months studied ("en los meses de Enero a Junio en el año 2020"); Examen de clasificación (Inglés, "pido se me evalúe el nivel básico"); Examen de suficiencia (Francés).
- S8. Requirements per procedure (newer posters, new logo; prices differ from the older poster of S6 and are assumed current):
  - Certificado, paso 1 Constancia de notas: FUT "(se descarga de http://cid.unasam.edu.pe/)", "COPIA DE DNI SIMPLE (legible)", pagos Básico S/20.00, Intermedio S/20.00, Avanzado S/20.00.
  - Certificado, paso 2 Certificado de estudios: FUT, "COPIA DE LA CONSTANCIA DE NOTAS AUTENTIFICADA (secretaría general - UNASAM)", copia de DNI, "02 FOTOGRAFIAS TAMAÑO CARNET POR NIVEL (fondo blanco, con terno)", pagos Básico S/50.00, Intermedio S/60.00, Avanzado S/70.00.
  - Modalidad post grado, paso 1 Acreditación: FUT with "Importante: Indicar la MENCIÓN del grado MAESTRIA O DOCTORADO.", copia de DNI, pagos S/50.00 each level.
  - Modalidad post grado, paso 2 Certificado de estudio: FUT, "COPIA DE ACREDITACION AUTENTIFICADA. (secretaria general -UNASAM CENTRAL)", copia de DNI, "02 FOTOGRAFIAS TAMAÑO CARNET POR NIVEL. (fondo blanco, con terno a colores)", pagos Básico S/100.00, Intermedio S/110.00, Avanzado S/120.00.
- S9. The certificate no longer requires the constancia authenticated at Secretaría General. User: "Hagamos que ya no haga falta." The system validates the closed actas itself and the documents carry the QR.

## Current system (checked 2026-11-25)

- Constancia de notas is a Trámite (`CONSTANCIA-NOTAS`, requirement "Al menos un ciclo con acta final cerrada en el idioma y el pago de la tasa"), issued by Certificacion (`pdf/constancia-de-notas.blade.php`): all levels of the language, DNI, hours, grade in words, condition, total/average, QR; says "LA DIRECCIÓN DEL CENTRO DE IDIOMAS Y LENGUAS NATIVAS". No año/mes, forma de aprobación, currículo or programa curricular.
- Cycles per level are already configurable: each language has its own `aca_ciclos` rows with `nivel` (contract `CiclosDelNivel`), so 10/10/6 vs 4/4/4 is data. One curriculum per language: no regular/acelerado variant.
- `Modalidad` enum is Presencial/Virtual/Semipresencial; there is no regular/acelerado/posgrado program concept.
- Fees are configurable: `pag_conceptos` with `pag_tarifas` by validity dates (admin Conceptos → Tarifas).
- Trámite types today: Constancia de matrícula, Constancia de notas, Certificado de culminación de nivel (picks idioma + nivel), Traslado, Retiro (motivo). Types, requirements text and stages are rows (`tra_tipos_tramite`, `tra_etapas`); the online request plus its tracking code replace the paper FUT and CARGO.
- Groups already have `es_acelerado` (flag only: shown in the offer, no effect on curriculum, grades or documents).
- Fees vary only by concept and `tipo_vinculo`; no per-level or per-program fee. A procedure accepts any validated payment whose concept type matches (`tipo_concepto`), not a specific amount. No late-enrollment surcharge.
- Document number prints as `N.º 000123-2026`; the real constancia uses `N° 2026 - 01125`.
- Placement exam (clasificación) exists in Matricula (`mat_solicitudes_clasificacion`). No code for: examen de suficiencia, reclasificación, acreditación (posgrado), media beca, programa Niños vs Jóvenes/Adultos, generic "Otro" request, uploaded attachments (DNI copy, photos, authenticated constancia).

## Tasks

Forecast: well above 400 authored lines; delivered as one commit (or more) per task.

- T1 · S2,S4,S5 · Programs catalog (JA, Niños, Posgrado; regimen), aca_grupos.programa_id, aca_idiomas.curriculo · route: delegated (writer, context backstop) · done a6507b6 (RED 17 errors+2 failures → GREEN; Academico 310, related 716 tests)
- T2 · S3,S4 · Accelerated group = two consecutive cycles, one grade; acta snapshot ciclo_*_hasta; history expands; habilitación +2 · Risk: item 1 (migration on actas) · route: delegated (writer) · done a7f39a4 (RED 11/12 → GREEN; Academico+Certificacion+Matricula+Docencia 1195, concurrency 36, full 2291/2293)
- T3 · S1 · Forma de aprobación; cer_aprobaciones (suficiencia, clasificación); year/month from period · Risk: item 1 · route: delegated (writer) · done 002a0d5 (RED 7 errors+1 failure → GREEN; full 2304/2305)
- T4 · S1,S5 · Institutional data settings; constancia per level, version 2 template, number format · route: delegated (writer) · done (RED 7 failures+11 errors → GREEN; full 2326/2327)
- T5 · S5,S6,S8 · Fees per level/program; trámite type pays a specific concept · Risk: items 1,3 (payments) · route: delegated (writer) · done c9bb67a (RED 18 errors → GREEN; Pagos+Tramites+Matricula+Concurrency 760, full 2349/2351 then API test fixed)
- T6 · S7,S9 · Configurable FUT: fields, prerequisite, programs, attachments · route: delegated (writer) · done c694e81 (RED 4 failures+18 errors → GREEN; full 2378/2379)
- T7 · S7,S8 · New procedures: suficiencia, reclasificación, acreditación (+document), certificado posgrado, media beca, otro · pending
- T8 · S5 · Late enrollment window + surcharge % · pending
- T9 · all · Manuals, data dictionary, decisiones pendientes, demo seed, release v1.2.0 · pending

## Log

- L1 (2026-11-25, user): "Mira eso, es el formato de la constancia de notas para un alumno. ... Y además te paso esa información de ese afiche, dime qué es lo que logras entender."
- L2 (2026-11-25, user): "Nuestro sistema o el flujo es así como se ve?, creo que eso va para trámites cierto? Ahora lo del código no sabemos cómo sale, entonces creo que allí iría dni, sobre currículo, lo entendiste. Por ejemplo, para inglés la currícula es básico 10, intermedio 10 y avanzado 6. Italiano 4 en todos los niveles. Pero todo eso es para ciclo regular, cuando es acelerado es la mitad de cada uno, en el afiche puedes ver que lo apunte colocando un / Ahora, hay modalidad para postgrado, para ellos siempre es ciclo acelerado. Todo lo que te comento es el cómo trabajan, ahora, todos esos valores que has visto son actuales, pero igualmente todo debe de ser configurable, los precios, etc. Vaya teniendo todo eso en cuenta. Ahora, te voy a pasar más cosas de lo que hacen actualmente, ok?, todo esto vaya apuntando."
- L3 (2026-11-25, user): "Mira esas imágenes, hay un FUT general que llenan los estudiantes dependiendo del trámite que requieren. Además, te he enviado ejemplo de cómo llenan algunos el FUT. Y además algunos requisitos, analiza."
- L4 (2026-11-25, user): "Hagamos que ya no haga falta. Y ahora me dices cómo implementarías todo. Entendiste todo?, como te dije los valores que ves no definitivamente son fijos, por eso quiero que todo sea configurable."
- L5 (2026-11-25, user): "Es la opción a." (accelerated = one month covers two consecutive regular cycles); answer to grading: "Una nota para ambos". Plan approved.
- L6: calendar fix committed on main as 1a25162; branch feat/flujo-real-ciln created from it.
- L7: T1 review (risk + reliability) approved and acknowledged, lineage review-857a976bf85f1d49. Advisory, not applied: changing a program's regimen does not recheck its existing groups; editing a group of an inactive program has no explicit test; HistorialDemoSeeder copies the program without rechecking the regimen.
- L8: T2 committed a7f39a4. Also 9a311db: aca_programas got its audit trigger (T1 had added the label without the trigger; ConsultaDeAuditoriaTest caught it). Public offer page shows "Ciclos I y II" for accelerated groups. Migration: existing accelerated groups without a next cycle in their level become regular (rollback does not restore the flag).
- L9: T2 review (risk + reliability) approved and acknowledged, lineage review-17b5e773dfd3d770. Advisory R1-002/R3 (deactivated second cycle breaks progression) checked: false, the catalog order keeps inactive cycles; pinned by a test. Advisory not applied: migration demotes accelerated groups without a next cycle (no Posgrado groups can exist yet in production, since programs arrive in the same release); untested branch keeping a deactivated second cycle on edit.
- L10: T3 committed 002a0d5. Assumptions to confirm with CILN: placement-skipped cycles count toward the level certificate and carry no hours; lowering a placement by correction keeps rows already recorded (insert-only table). API NotasPorIdiomaResource does not expose forma/año/mes yet.
- L11: T3 review approved and acknowledged, lineage review-6959e97698f82f01. Two advisory warnings accepted as real and being fixed as follow-up: (R1-001) a lowering correction left wrongly approved cycles that could unlock a certificate → void rows (anulación, never delete); (R3-001) later placement rows replaced graded acta results → graded result always wins within a cycle. Also R3-002 seeder nulls.
- L12: T3 follow-up d3fa01d review approved and acknowledged. Advisory follow-ups (open, edge cases): listener voids by the event's cycle, so out-of-order corrections could void what the latest grants (read the request's current cycle instead); two placements for the same language overlapping cycles; failed acta sorts after a later placement row; down() of the voiding migration fails once voids exist; voiding columns mass-assignable.
- L13: T4 committed. Parent fix before commit: number pattern frozen into detalle.formato_numero at issue (documents issued earlier keep N.º {numero:06}-{anio}); writer had applied patterns live. Assumptions to confirm: constancia lists only passed cycles (no constancia without a passed cycle in the level); roman numerals across the whole language; constancia trámites in progress without a level must be closed by staff. Header/footer texts are read live (not frozen) on reprint.
- L14: T4 review: first pass required one correction (R3-001: level-less constancia trámites requested before the change could not finish) → 4830f66 issues the whole-language v1 constancia for them; recovered review (risk, readability, reliability) approved and acknowledged, lineage review-fa55e238675c0bec. Applied advisory: padding width capped at two digits. Open advisories: duplicated v2 check, defaults copied in several places, NivelesConNotas reuses NivelPorCertificar, DemoSeederTest accepts 5 or 6 records, document string without a space leaves DNI empty.
- L15: T5 committed c9bb67a. Decisions: the trámite type's concept derives its fee type; only concept and level are enforced when applying a payment (program only selects the fee, Tramites cannot know the student's program); program field optional so regular students fall back to the per-level fee. Demo concept codes: CON-01 constancia de matrícula, CON-NOT constancia de notas; suficiencia seeded as type otro. API /api/v1/tarifas adds nivel and programa.
- L16: T5 review (risk, resilience, reliability) approved and acknowledged. Open advisories: down() of the fee migration fails once per-level fees exist; Programa column N+1 in the fee table; MisPagos decides the level requirement with today's fees while RegistrarPago uses fecha_pago; editing a trámite type linked to a deactivated concept fails; per-level payments need datos.nivel (T6 must keep that key); no test for the refusal message in aplicar() nor the ReanudarTramite path.
- L17: T6 committed c694e81. Shipped flow change: CERTIFICADO-NIVEL now requires a finished CONSTANCIA-NOTAS of the same language and level (a level-less legacy constancia counts for every level); CILN can remove it in admin. Attachments: private disk, PDF/JPG/PNG ≤ 5 MB, staff download needs trámite permission, students cannot download their own. Cargo at /estudiante/tramites/{codigo}/cargo. Shared upload component now reads via $wire.$get().
