---
paths:
  - "docs/**"
  - "README.md"
---

# Documentation

- `docs/` is written in neutral, professional Spanish for the CILN and the DSTI. Code, rules and commit messages stay in English.
- Lead with the decision or outcome, then details. Prefer tables, checklists and short sections over long prose.
- `docs/requisitos/` holds the official sources (`.docx`, `.pdf`) and their Markdown transcriptions; the official file wins on any difference.
- Record each significant decision as an ADR in `docs/adr/NNNN-titulo.md` (context, decision, alternatives, consequences).
- When a pending item in `docs/decisiones-pendientes.md` is resolved, record the decision and remove the row.
- Diagrams use Mermaid inside Markdown. The REST API is documented by Scramble, not by hand.
- User and technical manuals go in `docs/manuales/` as Markdown, converted to Word/PDF with Pandoc.
