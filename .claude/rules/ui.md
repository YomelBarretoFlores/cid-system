---
paths:
  - "app/Modules/*/Livewire/**"
  - "app/Modules/*/Filament/**"
  - "app/Modules/*/resources/views/**"
  - "app/Providers/Filament/**"
  - "resources/**"
---

# UI: Filament and Livewire

- Filament 5 runs the staff panel (administration, coordination, cashier, BI) and the teacher panel at `/docente` (decision #95). Public and student portals use Blade + Livewire 4 with our own design.
- Filament Resources, actions and Livewire components call module Actions. No business rules inside closures or components.
- Resources live in `app/Modules/<Modulo>/Filament/Resources`; the admin panel discovers them automatically. Teacher screens live in `app/Modules/<Modulo>/Filament/Docente/Pages`, discovered only by the teacher panel.
- Interfaces must be responsive, accessible (labels, contrast, keyboard) and mobile-first: the DSTI flagged the current UI for this (Informe 1.4).
- Blade escapes output with `{{ }}`. Never use `{!! !!}` with user data.
- Use Tailwind CSS 4 utilities; reuse Blade components instead of duplicating markup.
- PDF templates (DOMPDF) use tables and simple CSS: flexbox and grid render poorly.
- Styles come from the shared design tokens in `resources/css/ciln/tokens.css` (ADR 0008). Use the semantic token utilities (`bg-tarjeta`, `text-secundario`, `rounded-tarjeta`) and the `x-ciln.*` / `x-portal.*` components; never hard-code hex colors or copy class strings between views. A pattern used in two views becomes a component or an `@utility`.
- The Filament palettes live in `App\Shared\Ui\PaletaCiln` and must match `tokens.css` (`tests/Unit/PaletaCilnTest.php`).
