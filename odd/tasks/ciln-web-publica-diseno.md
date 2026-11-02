# CILN · Public website redesign

Objective: redesign the public website (home `/`, document and acta verification) after the two Framer references, in the CILN logo colors.
Branch: `feat/web-publica-diseno` from main 3d53332. Delivery: ask-on-risk (forecast ~900 authored lines, three commits). Runner: Pest (`/private/tmp/claude-501/ciln-v-OYLV/pest.sh`), `composer check`.

## Specs

- S1 (user, verbatim): "Bien, ahora vamos a corregir por completo la web pública del sistema. En mis descargas hay dos imágenes screencapture-kidedu-framer-website-2026-11-01-21_24_18.png y screencapture-brightpath-wbs-framer-website-2026-11-01-21_23_40.png Creo que te podrías guiar de eso, sobre los colores, vas a usar color institucional del CILN, guíate de los colores de su logo del CILN."
  - References: https://brightpath-wbs.framer.website/ and https://kidedu.framer.website/ (layout: centered hero with pill, highlighted word with hand-drawn underline, curved text ribbon, pastel program cards, dark stat card, "how it works" stacked steps, levels grid, modality band with chips, pricing cards with a dark middle card, FAQ accordion, CTA band, dark footer with a scalloped edge).
  - Colors: the logo (`public/images/ciln/logo.webp`): navy `#0a2e52`–`#14508a`, royal blue `#1d62a8`, sky blue of the leaves `#3d8fdb`, silver `#c5cad3`; flag leaves give the pastel tints of the language cards.
- S2 (assumption): only real CILN data is shown. No invented testimonials, teacher photos, ratings or statistics; the references' photo areas become illustrations built from the logo (emblem, flag leaves).
- S3 (assumption): every fact and test-pinned text of the current home stays (period pill, offer filters, vacancies, fees per link with codes, dates, four steps, verification form, staff sign-in link, sr-only headline, no private group data).
- S4: the document and acta verification pages share the new public header, footer and colors.
- Checks: `composer check` rc=0; desktop and 390 px captures without horizontal overflow or JS errors; motion still under `prefers-reduced-motion`.

## Tasks

- [x] T1 · S1, S4 · inline · public palette tokens, shared public header and footer components, verification pages on them (commit 1).
- [x] T2 · S1–S3 · inline · home sections rebuilt (hero + ribbon, languages, stats, steps, offer, levels, modalities, fees, dates, FAQ, CTA) (commit 2).
- [x] T3 · S1–S4 · inline · browser verification desktop and mobile, adjustments: no horizontal overflow at 390 px, no JS errors on `/`, `/verificar`, `/verificar/{codigo}`; card leaf moved inside its panel; empty footer column removed on verification pages.

## Log

- L1 (2026-11-01, verbatim): "Bien, ahora vamos a corregir por completo la web pública del sistema. En mis descargas hay dos imágenes screencapture-kidedu-framer-website-2026-11-01-21_24_18.png y screencapture-brightpath-wbs-framer-website-2026-11-01-21_23_40.png Creo que te podrías guiar de eso, sobre los colores, vas a usar color institucional del CILN, guíate de los colores de su logo del CILN. Si prefieres además revisar las páginas de esas dos imágenes son estas: https://brightpath-wbs.framer.website/ y https://kidedu.framer.website/"
- L2: public pages today: `/` (Tramites `PortadaPublica`, layout `components.layouts.base`), `verificar/{codigo?}` and `verificar/acta/{acta}` (Certificacion views). Sign-up and sign-in use the SGA-style `acceso` layout and stay as they are.
- L3: built inline (no writer trigger: one coherent view change). Floating leaves and underline strokes look missing in full-page CDP captures only (compositor animations); viewport captures show them, so checks use viewport shots for those.
- L4: affected tests green (Tramites, verification, health: 117 passed) before the full check.
- L5: composer check rc=0, 1961 tests passed.
