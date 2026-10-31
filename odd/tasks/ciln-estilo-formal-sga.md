# CILN formal SGA style: syllabus and weekly schedule

Objective: the syllabus and the weekly schedule look like formal UNASAM SGA documents (reference: the SGA "Horario personalizado" PDF), on the page, the PDF and the print view, and later reports follow the same layout.
Branch: `feat/estilo-formal` (stacked on `feat/demo-completo`) · Delivery: one commit per unit, push and merge decided by the user · Test runner: Pest (`php artisan test`).

## Specs

- S1 (user, L1): "Ya vi el sílabo y no me gusta para nada, se ve demasiado generado con IA, no se ve nada formal como por ejemplo el sga. Desde ahora en adelante debe de ser formal, como el sga en algunos de sus reportes." The reference is the SGA "Horario personalizado" PDF. It holds the user's personal data and is never copied into the repo.
- S2 (style read from the reference):
  - Header: institution logo and name on the left, the issuing office on the right in small grey text, and a thin rule below.
  - Title in plain text with the period in grey. Generation date and time, plus the code, at the top right.
  - The person's name and code, then bold labels with values.
  - Plain section titles; tables with thin grey borders, small sans font and bold uppercase headers.
  - Colour only as flat pastel course fills in the grid.
  - No rounded cards, shadows, icons, badges or chatty helper text, on the page or in the PDF.
- S3 (user, earlier turn): "para el horario no lo hiciste como el sga, sga lo hace de esa manera y se ve bien". The agreed proposal:
  - Legend "Cursos y docentes": colour plus [01] code, language, cycle, level, group, teacher, classroom or Virtual, and modality.
  - Grid "Horario personalizado": Monday to Saturday, with Sunday only when used; 30-minute rows from the first to the last class.
  - Cells "[01] [A-204]", repeated on every row of the class as in the SGA.
  - Empty stretches of 2 hours or more collapse into a grey "SIN CLASES" separator, like "REFRIGERIO".
  - Consistent palette colours with contrast.
  - Shown in Reportes → Horario semanal on the page, the PDF and the print view. The home keeps its compact view, plus a "Ver horario completo" link.
  - On a phone the grid scrolls horizontally inside its box.
- S4 (syllabus, assumption on the UNASAM format): the syllabus reads as a formal document with numbered sections:
  - I. DATOS GENERALES (1.1, 1.2 …)
  - II. SUMILLA
  - III. COMPETENCIA
  - IV. CAPACIDADES
  - V. PROGRAMACIÓN DE CONTENIDOS (table per week)
  - VI. ESTRATEGIAS METODOLÓGICAS
  - VII. EVALUACIÓN (table)
  - VIII. REFERENCIAS BIBLIOGRÁFICAS

  The same structure is used on the student page, the admin view and the PDF. The teacher form keeps its fields (no data change) and uses the same section names, with sober wording. The demo syllabus text is rewritten in a formal register.
- S5 (scope): official documents that already have their own institutional template (ficha, constancia, certificados, actas) are not changed.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2 | inline | done | see L2 |
| T2 | S2, S4 | inline | done | see L2 |
| T3 | S2, S3 | inline | done | see L3 |
| T4 | all | inline | done | see L3 |

T1: shared formal report layout, as a PDF and print component and a portal sheet component with a formal table utility. T2: formal syllabus, covering the PDF, the student page, the admin view, the teacher form wording and the demo text. T3: SGA schedule, covering the grid data, page, PDF, print view and the home link. T4: browser check on desktop and phone, plus opening the PDFs.

## Log

- L1 (2026-10-31): "Bien, continúa. Ya vi el sílabo y no me gusta para nada, se ve demasiado generado con IA, no se ve nada formal como por ejemplo el sga. Desde ahora en adelante debe de ser formal, como el sga en algunos de sus reportes. En descargas he colocado el archivo Horario personalizado - [código y nombre omitidos].pdf, para que lo tomes como referencia o guía."
- L2 (2026-10-31): T1 and T2 done inline. No writer trigger applies: the units share the new layout, and parallel writers cannot share `ciln_testing`.
  - Shared layout:
    - `x-pdf.reporte`: SGA letterhead (`unasam.png`, "Centro de Idiomas y Lenguas Nativas / Coordinación Académica"), title with the period in grey, issue time and code on the right.
    - `x-portal.hoja`.
    - `resources/css/ciln/formal.css` (`tabla-formal`, `titulo-formal`), imported by the portal and the admin theme.
  - Syllabus: one body, `docencia::documentos.silabo`, is shared by the PDF (landscape, with the teacher's signature line), the student page and the coordination modal. Sections I. Datos generales (1.1 …) through VIII. Referencias bibliográficas (numbered).
  - Teacher form: uses the same section names; the helper wording is sober.
  - Demo text rewritten in the register of a UNASAM syllabus.
  - Tests: RED 4 tests on the new structure; Docencia plus syllabus plus demo tests: 390 pass.
  - Browser on `ciln_preview`: page at 1280 and 390 wide (no horizontal overflow, no JS errors), PDF read back, teacher form checked.
- L3 (2026-10-31): T3 and T4 done.
  - `ArmarHorarioSemanal` builds the SGA layout:
    - Courses are sorted by code, numbered [01], [02]… and colored from `PaletaCiln::CURSO`, with eight pastels that keep dark text readable. Tokens `--color-curso-N` sit in `:root`, because `@theme` drops variables it sees unused. `PaletaCilnTest` keeps both equal.
    - `CuadriculaDelHorario` builds 30-minute rows from the first class to the last, Monday to Saturday, and adds Sunday only when used. Cells read "[01] [A-204]" or "[02] [VIRTUAL]". An empty stretch of 2 hours or more becomes one "SIN CLASES" row; shorter gaps stay as empty rows.
  - One body, `matricula::documentos.horario` ("Cursos y docentes" and "Mi horario"), is used by the PDF, the print view and the reports page. Hex colors go in the PDF and CSS variables on the page.
  - The home "Tu horario semanal" links "Ver horario completo".
  - `FilaDelHorario` was replaced by `FranjaDelHorario` and `CeldaDelHorario`; only Matricula used it.
  - Tests: RED 4 of 6 new tests. Matricula and palette tests: 294 pass. `composer check` rc=0, 1793 tests.
  - Browser on `ciln_preview`: reports page at 1280 (every course colored) and at 390 (the grid scrolls inside its box, no page overflow, no JS errors). PDF and print view read back.
- L4 (2026-10-31): `review assess` of aebfad8..3ced939: risk medium, review_due false (under_budget), so no native review ran. Preview DB dropped. Push and merge are the user's decision: `feat/demo-completo` and `feat/estilo-formal` are stacked and neither is pushed.
