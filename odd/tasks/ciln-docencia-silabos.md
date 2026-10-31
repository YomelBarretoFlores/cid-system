# CILN structured syllabi (E5)

Objective: the teacher fills, per group, the syllabus of the course they teach during the whole period as a structured form (like the UNASAM SGA, improved), coordination sees which groups lack one, and the student reads it and downloads its PDF.
Branch: `feat/silabos` (stacked on `feat/reportes-estudiante` d053c46) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`). Plan: `~/.claude/plans/agile-orbiting-wreath.md` (E5).

## Specs

- S1 (user): "sobre los sílabos es que los docentes suben su sílabo del curso que va a dictar en todo el periodo." Then: "Cómo sabes que es un formulario? Y si es así quisiera que sea como el sga, un formulario, ya que el sistema de sga se usa muy bien para los estudiantes. Si es posible que revises qué métodos usa el sga entonces síguelo o mejóralos." (The SGA moved from a PDF upload in 2018 to a structured competency form: I Identificación, II Sumilla, III Programación per unit and week with Saber, Saber hacer, Saber ser, Actividades, Recursos, and an evaluation table; IV–VII investigación formativa, responsabilidad social, consejería, referencias.)
- S2 (plan E5, data, Docencia, audit trigger on each new table): `doc_silabos` (`grupo_id` unique, estado `borrador`/`publicado`, sumilla, competencias, capacidades, metodologia, bibliografia, `publicado_en`, `publicado_por`, timestamps); `doc_silabo_semanas` (silabo_id, semana, unidad, saber, saber_hacer, saber_ser, actividades, recursos; unique silabo_id + semana); `doc_silabo_evaluaciones` (silabo_id, componente_codigo, indicadores, instrumento, semana; unique silabo_id + componente_codigo).
- S3 (plan E5, teacher, tab "Sílabo" in `PestanasDelGrupo` of the teacher panel), a Filament form by sections, improved over the SGA:
  - I. Identificación filled automatically from the group (language, cycle, level, period, teaching hours, schedule, classroom, teacher); not typed.
  - II. Sumilla, competencias y capacidades.
  - III. Programación semanal: one row per week created from the period dates.
  - IV. Evaluación: components and weights from the group's `EsquemaEvaluacion` (no double entry); the teacher only fills indicadores, instrumento and semana.
  - V. Metodología y bibliografía.
  - The SGA sections investigación formativa, responsabilidad social and consejería are left out unless the CILN asks for them.
- S4 (plan E5, actions): save draft; "Copiar de un sílabo anterior" (published syllabi of the same cycle); "Publicar" validates every section and week is complete. After publishing it can still be corrected; every change is audited. The "Mis grupos" card shows the syllabus state: falta, borrador or publicado. Only the teacher in charge of the group edits it (404 otherwise, as the other group tabs).
- S5 (plan E5, coordination, admin panel): page "Sílabos": groups of the period with their syllabus state, filter "sin publicar", view and PDF. A Policy or permission consistent with `strictAuthorization`.
- S6 (plan E5, student): in `MisMatriculas`, a "Sílabo" button that opens the HTML view and the PDF, only when published; a draft or another student's group answers 404.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1–S4 | delegated (context backstop) | done | see L3 |
| T2 | S5, S6 | delegated (context backstop) + parent | done | see L4 |
| T3 | all | inline | pending | — |

T1 data, teacher form, copy, publish, Mis grupos state (Risk item 2: who edits). T2 coordination page and student view/PDF (Risk item 2: who reads), docs and decisions. T3 browser check (teacher fills, copies and publishes; student reads on a phone). T2 depends on T1's data.

## Log

- L1 (2026-10-31): "Sobre lo extra ya hay luz verde. Y además, sobre los sílabos es que los docentes suben su sílabo del curso que va a dictar en todo el periodo." … "Cómo sabes que es un formulario? Y si es así quisiera que sea como el sga, un formulario, ya que el sistema de sga se usa muy bien para los estudiantes. Si es posible que revises qué métodos usa el sga entonces síguelo o mejóralos."
- L2 (2026-10-31): plan approved; "Bien, sube y une. Y sigues con lo que falta."
- L3 (2026-10-31): T1 done by a bounded writer. Tables `doc_silabos`, `doc_silabo_semanas`, `doc_silabo_evaluaciones` with audit triggers and named CHECKs; Actions `GuardarSilabo`, `PublicarSilabo` (one message listing every missing part), `CopiarSilabo` (published, same `ciclo_id`, another group); page "Sílabo" at `/docente/grupos/{id}/silabo` with identification from the group, weeks from the period, evaluation from the scheme; "Mis grupos" badge Falta/Borrador/Publicado. Read API for T2: contracts `SilaboDelGrupo` (draft or published; check `estaPublicado()`) and `EstadosDeSilabos`. Assumptions: editable while the group is Programado, Abierto or Cerrado; required to publish are the five texts, each week's Saber and Actividades and each evaluation's Indicadores and Instrumento. RED caveat: the Actions were written just before their tests. Writer's `composer check` rc=0, 1751 tests; parent spot check 388/388.
- L4 (2026-10-31): T2 done. Writer: admin page "Sílabos" (`docencia.ver`, filters periodo/idioma/docente/"Sin publicar", "Ver" modal), student button in "Mis matrículas" only for a published syllabus of an active enrollment, routes `estudiante.matriculas.silabo` and `.silabo.pdf` (404 for another student, a draft or a withdrawn enrollment), Docencia contracts `LecturaDelSilabo` and `SilaboEnPdf`, PDF on `x-pdf.documento` (A4 landscape, "BORRADOR" when unpublished), docs and decisions #108–#112. It stopped at the admin PDF route (outside its surface); the parent added `/panel/silabos/{grupo}/pdf` (`panel.silabos.pdf`, `can:` admin panel and `docencia.ver`) with `DescargarSilaboParaPersonal` and the row action "PDF" (new tab), tests first (403, 404, BORRADOR). Advisory: Academico has no contract listing a period's groups, so `SilabosDelPeriodo` filters all groups in memory. `composer check` rc=0, 1778 tests.
