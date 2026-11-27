# Public site fully manageable by the administrator

Objective: the administrator edits all public site content (texts, titles, buttons, cover photo, footer, contact links, section visibility) from the panel, previews it before saving, and reaches each section's form from the public page. Branch `feat/portada-administrable` (stacked on `feat/matricula-en-ventanilla`). Delivery: one commit per task. Runner: Pest on PostgreSQL 18.

## Specs

- S1. Everything on the public site is manageable: "ahora pasemos a que todo la web pública sea administrable por el administrador." Approved recommendation: extend «Contenido de la portada» (no free page builder, no new dependency) with the cover photo upload, every section title, eyebrow label and intro text, button texts, the hero subtitle, the footer text, contact data and social links, and show/hide per section. Design, order, accessibility and the formal style stay fixed in code. Data-driven parts (languages, schedules, fees, dates, institutional names) keep coming from their catalogs. "Sí, me gusta la recomendación."
- S2. Visual editing, middle ground: "por qué no hacerlo visualmente para editar?, tipo por ejemplo figma que tienes el diseño pero le das click a tales componentes y puedes editar." Agreed: a «Vista previa» button on the panel form shows the public page with the unsaved changes; when a staff member who may edit the content is signed in and visits the public page, each section shows a small «Editar» link to that section of the panel form. Editing itself stays in the panel. "Sí, me gusta esa propuesta, arma el plan y aplica."

## Tasks

- [x] T1 · S1 · Extend the portal content (texts, titles, buttons, footer, contact, social links, visibility, cover photo) and render it · delegated (context backstop)
- [ ] T2 · S2 · «Vista previa» of unsaved changes and «Editar» shortcuts on the public page · delegated (context backstop), after T1
- [ ] T3 · all · Staff manual and data dictionary · inline

## Log

- L1 (2026-11-27) "Bien, ahora pasemos a que todo la web pública sea administrable por el administrador. Podría hacerse?, hay herramientas para eso?"
- L2 (2026-11-27) "Sí, me gusta la recomendación. Pero yo pensaba en algo, por qué no hacerlo visualmente para editar?, tipo por ejemplo figma que tienes el diseño pero le das click a tales componentes y puedes editar. O no es lo mejor para nuestro caso?"
- L3 (2026-11-27) "Sí, me gusta esa propuesta, arma el plan y aplica."
- L4 Current state: `EditarContenidoDePortada` (Tramites) edits presentation, enrollment and placement steps, learning path and FAQ (`tra_textos_portada`, `ContenidoDePortada`); hardcoded: cover image `public/images/portada/portada.jpg`, hero subtitle, section titles and eyebrows, button texts, footer text, top bar links; no section visibility.
- L5 T1 writer: tra_textos_portada gains cover photo, hero texts, per-section texts and visibility (jsonb keyed by `SeccionDePortada`), footer description, email, office hours and social links (defaults equal today's texts); FotoDePortada (public disk `portada/`, JPG/PNG/WebP by content, 5 MB, old file deleted after commit); panel form moved to Schemas/FormularioDeContenidoDePortada with section ids `seccion-<key>`. Choices: language list in the hero stays automatic, period names stay appended to «Oferta»/«Calendario», address and phone come from institutional data. Tests were written after the code (RED not observed; mutation check on visibility done). Parent spot check: tests/Modules/Tramites + tests/Feature/Portal 368/368. Open: other public pages (privacy, verification, sign-in) still render the header/footer with fixed values; folded into T2.
