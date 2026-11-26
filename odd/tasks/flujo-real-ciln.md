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
- T2 · S3,S4 · Accelerated group = two consecutive cycles, one grade; acta snapshot ciclo_*_hasta; history expands; habilitación +2 · Risk: item 1 (migration on actas) · pending
- T3 · S1 · Forma de aprobación; cer_aprobaciones (suficiencia, clasificación); year/month from period · Risk: item 1 · pending
- T4 · S1,S5 · Institutional data settings; constancia per level, version 2 template, number format · pending
- T5 · S5,S6,S8 · Fees per level/program; trámite type pays a specific concept · Risk: items 1,3 (payments) · pending
- T6 · S7,S9 · Configurable FUT: fields, prerequisite, programs, attachments · pending
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
