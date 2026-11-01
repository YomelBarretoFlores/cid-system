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
| T1 | S1, S3 | inline | done | 703faeb (S3), 47c99e7 (S1); see L2, L3 |
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
- L3 (2026-11-01): the review of the whole candidate exceeded the context budget, because the design HTML is 3 MB.
  - The commit was split: 703faeb holds the design reference (S3, passive) and 47c99e7 the code.
  - Native review of 703faeb..47c99e7 (lenses reliability and readability, lineage review-db31efdad5e014b0) was granted by the user, approved and acknowledged.
  - Applied:
    - a filter change clears the table search and keeps a valid section;
    - empty cells sort first in both numeric and text columns;
    - the period and the scope show above the table again;
    - the docblock says search covers text columns.
  - Tablero, Feature and Architecture: 363 pass. One earlier run showed a single error that did not repeat; watch for flakiness.
- L4 (2026-11-01): user: "Lo vi en el panel de administración, uso brave."
  - Investigated in the staff panel.
  - Filament's sidebar store puts the menu scroll back in a `requestAnimationFrame` after `livewire:navigated`. At that event the new menu is still hidden (no size), and font preload links are appended to `<head>` again on every SPA navigation.
  - A swap-time scroll restore was written and tested, but real composited frames (a normal Chrome window, screencast) showed no jump with or without it. It was discarded as unproven.
  - Collapsed-menu navigation could not be driven reliably.
  - Next: a screen recording from the user (Brave), noting which option was clicked and whether the menu was scrolled or collapsed.

