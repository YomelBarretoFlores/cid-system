# CILN general technical report (final deliverable)

Objective: rebuild the final technical report (author: the contractor ELECTRODATA) as the general technical report with the structure of the user's example (`~/Downloads/INFORME_FINAL_SW_ACADEMICO.docx`), proving compliance with the TdR (body + Anexo Técnico), Orden de Servicio N.° 0003091 and DSTI Informe 023-2026-FMAJ; deliver .docx and .pdf in ~/Downloads.
Branch: `feat/evaluacion-por-habilidades` (docs only) · Delivery: user decides push/merge · Checks: structural readback of the Word/PDF.

## Specs

- S1 (user): "me comentan que la estructura debe de ser así como en los ejemplo se muestra, o sea, por ejemplo en el informe tiene un índice, etc." Preliminaries like the example: cover, historial de versiones, uso de la información, contenido (TOC), índice de tablas, índice de figuras.
- S2 (user): "Se debe de mencionar, verifica o señalar que se cumpla con el Tdr." "te paso la orden de servicio también porque allí se menciona lo que se debe de cumplir." "haciendo referencia a oficio-166-2026-unasam-dsti-informe-023-2026-fmaj.pdf" "para que además se vea la validez de que se está cumpliendo."
- S3 (agreed index): 1 Introducción (antecedentes, objetivo general, específicos, alcance, base contractual y normativa, glosario); 2 Actividades realizadas (metodología, cronograma, actividades TdR 4.2); 3 Infraestructura tecnológica; 4 Fundamentación tecnológica (TdR 4.2: PHP 8.5, framework, MVC, PostgreSQL, API REST); 5 Arquitectura; 6 Marcos de trabajo y herramientas; 7 Modelo de datos; 8 Funcionalidad (módulos, roles, interfaces con capturas); 9 Seguridad; 10 Calidad; 11 Verificación del cumplimiento (OS, TdR cláusulas, matriz del Anexo, DSTI 023, relación de componentes entregados); 12 Conclusiones; 13 Recomendaciones; Anexos A (evidencia por requisito) y B (inventario de pruebas).
- S4 (user): "lo de informe técnico despliegue no lo coloques." "el código fuente lo tiene ya DSTI."
- S5 (user): "Lo dejas al final en mis decargas en docx y pdf."
- S6: content reflects the current system: no syllabus; evaluation by the four skills and exams (simple averages, only the final grade rounded to an integer).

- S7 (user, 2026-11-18): "te comento de una vez que el dominio oficial donde estará el sistema es https://ciln.unasam.edu.pe/" → the report names the official domain (3.4, physical architecture, 11.5).
- S8 (decision 2026-11-18, recorded with the user): manuals delivered → mark them Entregado and IV.1.b Cumple; delivered version v1.1.0; recapture figure 12 (acta, the state was truncated) with synthetic data only; TdR 6.7 conformity is an "Informe de Conformidad de Servicio" issued by the Dirección del CILN, and cesión works by TdR 6.1 itself → replace every "acta de conformidad y cesión".
- S9 (user): "sobre el diccionario de datos"; L8: "Para el diccionario creo que también se debe de seguir el mimso modelo o como el del ejemplo." (~/Downloads/Dicionario_Datos_v1.pdf) (L1 of the report request) → regenerate the data dictionary from the current schema (no syllabus tables; evaluation components grouped into skills and exams) as Word + PDF in ~/Downloads, author ELECTRODATA, same structure as v1.0.

## Tasks

| ID | Specs | Route | Status |
|----|-------|-------|--------|
| T1 | S2–S4, S6 | delegated (parallel unit, content of docs/manuales/informe-tecnico-final.md) | done |
| T2 | S3 | delegated (parallel unit, screenshots and diagrams) | done |
| T3 | S1, S5 | delegated after T1–T2 (Word/PDF builder) | done |
| T4 | S6–S8 | delegated (parallel unit: report update + Word/PDF) | done |
| T5 | S9 | delegated (parallel unit: data dictionary Word/PDF in the example's model) | done |

## Log

- L1 (2026-11-17, user): "Bien, pero vayamos generándolos nuevamente, actualizados. Mira, me comentan que la estructura debe de ser así como en los ejemplo se muestra, o sea, por ejemplo en el informe tiene un índice, etc. Se debe de mencionar, verifica o señalar que se cumpla con el Tdr. ... O sea, se debe de armar el documento técnico general haciendo referencia a todo eso, para que además se vea la validez de que se está cumpliendo."
- L2 (user): "Mira, lo de informe técnico despliegue no lo coloques. Y además, el código fuente lo tiene ya DSTI. Dicho esto, puedes comenzar con todo lo que mencionas. Lo dejas al final en mis decargas en docx y pdf."
- L3: (superseded by L4) authorship assumption kept 002-2026-YJBF.
- L4 (user): "Tiene que ir el nombre del contratista, no el mío." → author ELECTRODATA INGENIERIA & SISTEMAS S.A.C.; no personal name or YJBF code; title "Informe Técnico Final – Sistema de Gestión Académica y Administrativa del CILN" without an invented number.
- L5: T1–T3 done. Word + PDF (67 pages: cover, historial, uso, contenido, 44 tablas, 13 figuras) in ~/Downloads as "Informe Técnico Final - Sistema de Gestión Académica y Administrativa del CILN"; no personal name; metadata ELECTRODATA. Duplicate line in Anexo A removed and rebuilt. Table headers navy like the example.
- L6 (2026-11-18, user): "Ya acabó la demo, regresemos en donde nos quedamos." → T4 and T5 launched in parallel (disjoint surfaces: informe vs diccionario).
- L7: T4 done. Report .md and .docx updated in step: official domain `https://ciln.unasam.edu.pe` in 3.4, 5.4 and 11.5; user manuals Entregado and IV.1.b Cumple (matrix recounted: 94 Cumple + 2 parcial, IV.1.a and IV.4.a), totals fixed in 11.2, Tabla 23, Tabla 24 (IV.1.b row removed), conclusions and recommendations (numbering 6→5); delivered version v1.1.0 (Tabla 6, 9.1 closing clause, 11.5, recommendation 2) while the ZAP scan stays described as v1.0.1; every "acta de conformidad y cesión" replaced by TdR 6.7 Informe de Conformidad de Servicio (Dirección del CILN) and cesión by TdR 6.1. Figure 12 recaptured from synthetic acta ING-4-A (Octubre 2026, example.* emails), state column in plain text. Word fields refreshed; PDF re-exported (69 pages); docx core author/lastModifiedBy ELECTRODATA. Originals backed up in the session scratchpad.
- L8 (2026-11-18, user): "Para el diccionario creo que también se debe de seguir el mimso modelo o como el del ejemplo."
- L9: T5 done: v1.1 Word/PDF (62 pp) in the example's model, 59/59 tables. The repo .md stays generated by `php artisan docs:diccionario-de-datos` (single source). Stale comment cer_notas_finales.promedio ("Promedio ponderado") fixed by migration 2026_11_19_090000; DiccionarioDeDatosTest 2/2.
- L10 (2026-11-19, user): "Bien, vamos con el informe técnico, revisa a detalle y que quede todo listo para presentar, incluso puedes usar el navegador si requieres ver el sistema y capturar"
- L11: final review of the report. Verified: 283 cited paths exist; matrix 96 = 93 + 3 matches Tabla 23; 59 tables / 548 columns / 42 audited tables against PostgreSQL; 14 API routes; 30-day tokens; library versions; TOC and index page numbers; md ↔ docx text. Fixed in md and docx: suite run now cites the v1.1.1 CI (2 250 runs, 19/11) instead of 17/11 (2 224); `pgsql` extension added; 484 changes "hasta la versión entregada v1.1.1"; M3.1.b and M6.1.a observations reworded to current behavior (no "por decidir" / "hasta recibir"). Layout: keep-with-next removed from body rows of Tablas 29 and 31 (pages 45 and 48 were empty but for the heading), 83 → 81 pages. Figure 5 recaptured from /ingresar with the privacy-policy link. PDF: title, author, subject and 83 bookmarks added; docx author/last-modified ELECTRODATA re-applied after Word.
