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
- S5 (user, verbatim): "No me gusta que las banderas de los idiomas estén como hojas." → flags as rounded rectangles on the public pages.
- S6 (user, verbatim): "Hay secciones que en las cards se sibreponen." and "creo que algunas secciones lo mejor es el logo sin fondo o no?" → no overlapping cards; the logo without a background card where it is decorative; no repeated decorative logo.
- S7 (user, verbatim): "en el hero la palabra de los idiomas que cambian podrían cambiar dependiendo del color del idiomas?, o quizás que la palabra sea con fondo de la bandera" → each rotating word filled with its flag colors.
- S8 (user, verbatim): "sigue las buenas prácticas de diseño. Es espaciado, alineamiento, bordes, gerarquías, etc." and "Al finalizar verifícalo visualmente y además verifica que sea totalmente responsivo."
- S9 (user, verbatim): "el login quisiera que siga el diseño que hay en la web pública, porque se ve mal que pase de un diseño a otro de repente cuando vaya al login o crear cuenta. Lo del login del personal déjalo así como está." → `acceso` layout (ingresar, registro, recuperar/restablecer clave, verificar correo) on the public header, colors and footer; Filament staff login untouched.
- S10 (user, verbatim): "creo que la fuente de la web es diferente al sistema? Si es así mejor llevemos la fuente de la web pública al sistema de docente y estudiante, lo de admin déjalo como está" → verified: same Figtree everywhere, so no font change; drop the synthesized italic of highlighted words.
- Checks: `composer check` rc=0; desktop and 390 px captures without horizontal overflow or JS errors; motion still under `prefers-reduced-motion`.

## Tasks

- [x] T1 · S1, S4 · inline · public palette tokens, shared public header and footer components, verification pages on them (commit 1).
- [x] T2 · S1–S3 · inline · home sections rebuilt (hero + ribbon, languages, stats, steps, offer, levels, modalities, fees, dates, FAQ, CTA) (commit 2).
- [x] T3 · S1–S4 · inline · browser verification desktop and mobile, adjustments: no horizontal overflow at 390 px, no JS errors on `/`, `/verificar`, `/verificar/{codigo}`; card leaf moved inside its panel; empty footer column removed on verification pages.
- [x] T4 · S5, S6, S7 · inline · flags as rounded rectangles, no overlapping cards, logo without background, modality cards instead of a repeated logo, hero word in flag colors, no faux italic.
- [x] T5 · S8 · inline · spacing, alignment, borders and hierarchy pass on every public section.
- [x] T6 · S9 · inline · sign-in, sign-up and password pages on the public design.
- [x] T7 · S8 · inline · visual check at 390, 768, 1024 and 1440 px on every public and access page.

## Log

- L1 (2026-11-01, verbatim): "Bien, ahora vamos a corregir por completo la web pública del sistema. En mis descargas hay dos imágenes screencapture-kidedu-framer-website-2026-11-01-21_24_18.png y screencapture-brightpath-wbs-framer-website-2026-11-01-21_23_40.png Creo que te podrías guiar de eso, sobre los colores, vas a usar color institucional del CILN, guíate de los colores de su logo del CILN. Si prefieres además revisar las páginas de esas dos imágenes son estas: https://brightpath-wbs.framer.website/ y https://kidedu.framer.website/"
- L2: public pages today: `/` (Tramites `PortadaPublica`, layout `components.layouts.base`), `verificar/{codigo?}` and `verificar/acta/{acta}` (Certificacion views). Sign-up and sign-in use the SGA-style `acceso` layout and stay as they are.
- L3: built inline (no writer trigger: one coherent view change). Floating leaves and underline strokes look missing in full-page CDP captures only (compositor animations); viewport captures show them, so checks use viewport shots for those.
- L4: affected tests green (Tramites, verification, health: 117 passed) before the full check.
- L5: composer check rc=0, 1961 tests passed.
- L6: gentle-ai review assess (base 3d53332, committed-only): risk medium, review_due false (under_budget); no native review started.
- L7: native review consent offered for the branch range (medium, 850 lines); the user chose "Omitir esta vez"; decline recorded (declined_this_candidate).
- L8 (2026-11-01, verbatim): "Se ve bien, pero hay cosas por mejorar. No me gusta que las banderas de los idiomas estén como hojas. [Image #2] Hay secciones que en las cards se sibreponen. Y creo que algunas secciones lo mejor es el logo sin fondo o no? Y además, en el hero la palabra de los idiomas que cambian podrían cambiar dependiendo del color del idiomas?, o quizás que la palabra sea con fondo de la bandera, me entiendes, o sea, la palabra? Y además, sigue las buenas prácticas de diseño. Es espaciado, alineamiento, bordes, gerarquías, etc. Al finalizar verifícalo visualmente y además verifica que sea totalmente responsivo. Y una cosa más, el login quisiera que siga el diseño que hay en la web pública, porque se ve mal que pase de un diseño a otro de repente cuando vaya al login o crear cuenta. Lo del login del personal déjalo así como está. Y creo que la fuente de la web es diferente al sistema? Si es así mejor llevemos la fuente de la web pública al sistema de docente y estudiante, lo de admin déjalo como está, eso no lo tocamos."
- L9: font verified: vite.config.js loads Figtree 400–700 normal only; base layout (public, portal, acceso) and AparienciaDelPanel (admin and docente panels) all use Figtree. The difference is weight/tracking and faux italic.
- L10: T4 RED→GREEN: PortadaPublicaTest "paints each rotating word…" failed before, passes after; ColoresDeBanderaTest pins two or more colors, never white. Flags are `forma="pill"`; stats card and logo sit side by side (no overlap, no background); steps and modalities use cards instead of a repeated logo; highlighted words lose the faux italic.
- L11: T5/T6: `.tema-publico` remaps the portal tokens (tinta, enlace, grays, focus) to the logo blues, used by the home, verification and the `acceso` layout (data-acceso-institucional kept). Header nav shows from 1280 px; below, a "Menú" button opens the sections (1024 px overflowed before).
- L12: T7: responsivo.mjs at 390/768/1024/1440 on /, /ingresar, /estudiante/registro, /recuperar-clave, /verificar: no horizontal scroll, no element outside the viewport, 0 JS errors. Ribbon keeps its aspect (min 1100 px wide, centered) so the text stays readable on phones.
- L13: native review review-8cf03ce18b46104a (readability, reliability) granted by the user, approved and acknowledged. Advisory, non-blocking: R2 neutral gradient hard-coded in the home (reuse BanderaDeIdioma::Neutra->colores()); R2 unused stroke markup in publico.css; R3-001 footer links to #fechas even when the home has no dates section; R3-002 ribbon loop divides by a text length that can be 0 before fonts load. Left as follow-ups.
- L14 (2026-11-01, verbatim): "Corrige las cuatro observaciones y luego sube y une". Fixed: BanderaDeIdioma::degradado() (neutral headline reuses Neutra), x-publico.subrayado shared by the title and the hero, dead motion CSS removed (ticker, palabra, trazo, aura), header/footer link to #fechas only when the home shows it (RED→GREEN test), ribbon waits while the text measures 0. composer check rc=0, 1977 tests.
- L15: review review-fb68125bb55d5919 of the four fixes (granted by the user) approved and acknowledged; one informational suggestion R2-001 on pie.blade.php:1 (props line).
