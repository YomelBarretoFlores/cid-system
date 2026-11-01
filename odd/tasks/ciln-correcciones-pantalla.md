# CILN screen fixes: sidebar blink and general reports as tables

Objective: fix the two problems the user found before the visual redesign. This is delivery V1 of the plan `golden-rolling-hippo`.
Branch: `fix/sidebar-y-reportes`. Delivery: one commit per unit; the user decides push and merge. Test runner: Pest.

## Specs

- S1 (user, L1): "Y sobre en reportes, veo que aparece como hoja formal, tiene el mismo rendimiento que una tabla?, que pasa si son muchos datos?, tiene la funcionalidad de paginación. Sí se ve formal, pero yo me refería que eso salga así cuando se exporta recién. En el sistema se debe de ver normal como tabla, con sus filtros, etc."
  - On screen, each report section is a Filament table with search, sorting and pagination (10/25/50), one tab per section.
  - The formal sheet stays only in the PDF; the Excel is unchanged.
- S2 (user, L1): "creo que hay un bug en el sidebar o eso me parece, es que cuando cambio de pantallas hay un pestañeo en el sidebar, como que hace un parpadeo"
- S3 (user, L1): keep the design proposal in the repository as reference: `docs/diseno/propuesta-visual-2026-11/`.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1, S3 | inline | done | see L2 |
| T2 | S2 | inline | blocked: not reproduced, needs the user's screen | |

## Log

- L1 (2026-11-01): the user's messages, quoted in S1 and S2. Plan approved.
- L2 (2026-11-01): T1 done.
  - `SeccionDeReporteTable` gives each section an in-memory Filament table (columns c0..cN with their type format), following `DetalleDeRecaudacionTable`.
  - `ReporteGeneralDelPanel`:
    - is now `HasTable`;
    - keeps `$seccion` and memoizes the report per request;
    - rebuilds the table on a new section or new filters, because Filament builds it before Livewire applies the request.
  - The screen shows tabs and the table; the formal include is gone. The PDF and the Excel are unchanged.
  - RED: the new test failed first. Tablero: 56 pass.
  - Browser on the demo data: 36 results paginated by 10, no formal sheet, no JS errors, no horizontal overflow at 390 px.
  - Not reproduced in headless Chrome (S2): frame-by-frame width, visibility and opacity of the sidebar in the portal, the staff panel and the teacher panel show no change besides the active item.
