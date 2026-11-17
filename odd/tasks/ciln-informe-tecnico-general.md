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

## Tasks

| ID | Specs | Route | Status |
|----|-------|-------|--------|
| T1 | S2–S4, S6 | delegated (parallel unit, content of docs/manuales/informe-tecnico-final.md) | done |
| T2 | S3 | delegated (parallel unit, screenshots and diagrams) | done |
| T3 | S1, S5 | delegated after T1–T2 (Word/PDF builder) | done |

## Log

- L1 (2026-11-17, user): "Bien, pero vayamos generándolos nuevamente, actualizados. Mira, me comentan que la estructura debe de ser así como en los ejemplo se muestra, o sea, por ejemplo en el informe tiene un índice, etc. Se debe de mencionar, verifica o señalar que se cumpla con el Tdr. ... O sea, se debe de armar el documento técnico general haciendo referencia a todo eso, para que además se vea la validez de que se está cumpliendo."
- L2 (user): "Mira, lo de informe técnico despliegue no lo coloques. Y además, el código fuente lo tiene ya DSTI. Dicho esto, puedes comenzar con todo lo que mencionas. Lo dejas al final en mis decargas en docx y pdf."
- L3: (superseded by L4) authorship assumption kept 002-2026-YJBF.
- L4 (user): "Tiene que ir el nombre del contratista, no el mío." → author ELECTRODATA INGENIERIA & SISTEMAS S.A.C.; no personal name or YJBF code; title "Informe Técnico Final – Sistema de Gestión Académica y Administrativa del CILN" without an invented number.
- L5: T1–T3 done. Word + PDF (67 pages: cover, historial, uso, contenido, 44 tablas, 13 figuras) in ~/Downloads as "Informe Técnico Final - Sistema de Gestión Académica y Administrativa del CILN"; no personal name; metadata ELECTRODATA. Duplicate line in Anexo A removed and rebuilt. Table headers navy like the example.
