# CILN student portal with the new design

Objective: the student portal follows the design proposal (`docs/diseno/propuesta-visual-2026-11/CILN - Portal Estudiante.html`) without changing its rules. This is delivery V4 of the plan `golden-rolling-hippo`.
Branch: `feat/portal-estudiante-diseno` (stacked on `feat/login-sga`). Delivery: one commit per unit; the user decides push and merge. Test runner: Pest.

## Specs

- S1 (user): "Tengo un nuevo export de diseño para estudiante, docente y documentos … lo que importa es el diseño, no es definitivo, puedes mejorarlo incluso si algo no te cuadra."
- S2 (plan): the home has a greeting with "Tienes N cosas por hacer", "Te toca a ti" and "Le toca al CILN", the week's agenda, compact language rows, payments and documents.
- S3 (plan): Matrícula has the 3 steps, the payment in use, a morning/afternoon/night filter, a grid of group cards with live vacancies and a bottom confirmation bar.
- S4 (plan, corrected in L4): languages keep their administrable flags (`BanderaDeIdioma` already offers Wiphala and Neutra for native languages); delegates get a "Confirmar clases" menu entry; the prototype button "Simular acciones del CILN" is left out.

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S2 | inline | done | see L2, L4 |
| T2 | S3 | inline | done | see L3 |
| T3 | S4 (delegate entry) | inline | done | see L5 |

## Log

- L1 (2026-11-01): "Avanza con todo lo faltante". Plan V1–V5 approved.
- L2 (2026-11-01): T1 done.
  - Home in two columns:
    - greeting with the date, and "Estás al día" or "Tienes N cosas por hacer";
    - "Te toca a ti": payment to register or rejected, group to choose, observed trámites via `TramitesObservados`;
    - "Le toca al CILN": a payment to validate, with "Lleva tu boleta original al CILN";
    - an enrollment row, and "Mis idiomas" in compact rows with `x-ciln.codigo-idioma`;
    - on the right: "Esta semana" (day and date, each class or "Sin clases"), "Mis pagos" counters and "Mis documentos".
  - The pinned order in `ResumenDelEstudianteTest` follows the new layout.
  - Portal, Personas, Docencia portal and Feature portal: 309 pass. Browser on the demo: no JS errors, no overflow at 390 px.
- L3 (2026-11-01): T2 done.
  - Matrícula has:
    - the 3 steps ("Pago validado" is done when a validated payment exists);
    - requirements in a collapsible block, open while one is pending;
    - the payment-in-use strip;
    - an Alpine filter Todos/Mañana/Tarde/Noche (by the first session's start: before 12:00, before 18:00, then night);
    - a card grid with the live vacancies (`wire:poll.15s.visible`);
    - a sticky dark bar with "Tu elección" and "Matricularme".
  - Every rule and message is unchanged. Matricula portal and Feature portal: 223 pass.
- L4 (2026-11-01): correction. The plan replaced flags with codes, arguing that native languages have no flag. That was wrong: languages already have an administrable flag (`aca_idiomas.bandera`, `BanderaDeIdioma`), including Wiphala and Neutra. The home and Matrícula use `x-ciln.hoja-bandera` again, and the code chip was removed.
- L5 (2026-11-01): T3 done.
  - New Docencia contract `ClasesPorConfirmarDelDelegado` (`ContarClasesPorConfirmarDelDelegado`, built on `BandejaDelDelegado`) returns null when the person is not a delegate.
  - The portal menu shows "Confirmar clases" with the pending count, only to delegates.
  - The home summary test for a delegate outside the window no longer expects the link to be absent, because a delegate keeps the menu entry. The new test pins that a non-delegate has none.
  - Mis pagos, Mis matrículas, Reportes, Clasificación and Trámites already use the portal components and palette of the proposal, so they keep their layout.
  - Docencia portal, Feature portal, Architecture and Matricula portal: 251 pass.

  V4 is complete.

