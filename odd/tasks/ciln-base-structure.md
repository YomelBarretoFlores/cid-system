# CILN base structure

Objective: scaffold the CILN system base (Laravel 13, PHP 8.5, PostgreSQL 18, Filament 5, Livewire 4) with modules, tooling, docs, and Claude Code rules.
Branch: `feat/base-structure` · Delivery: ask-on-risk · Test runner: Pest (`php artisan test`).

## Specs

- S1: "quedamos en usa filament para lo que mencionaste y blade + livewire para la web pública y otros cierto? Usa las versiones recientes."
- S2: "armes ya toda la estrcutura base, crees quizás archivos de las reglas, arquitectura, tecnología, buenas prácticas, etc de lo que definimos?, las funcionalidades, lo que tomaste en cuenta en las conversaciones que compartí, etc."
- S3: "Colocar el anexo técnico y oficio en las sección correspondiente, etc?"
- S4: "que ya armes e instales todo lo necesario para la base de este sistema."
- S5: "no agregues comentarios excesivos en múltiples líneas de código, solo lo necesario." / "no quiero malas prácticas, como por ejemplo demasiados if o for anidados, archivos demasiado extensos, etc. Incluso sigue las buenas prácticas de desarrollo para este stack con php, laravel, etc."

## Tasks

| ID | Specs | Route | Status | Commit |
|----|-------|-------|--------|--------|
| T1 | S1, S4 | inline | done | (see Log) |
| T2 | S1, S4 | inline | done | (see Log) |
| T3 | S2, S4, S5 | inline | done | (see Log) |
| T4 | S4, S5 | inline | done | (see Log) |
| T5 | S2, S3 | inline | done | (see Log) |
| T6 | S2, S5 | inline | done | (see Log) |

- T1: Laravel 13 skeleton, PostgreSQL 18 via Docker Compose, `.env` for pgsql, locale es.
- T2: Packages: Filament 5, Livewire 4, Sanctum, spatie permission/activitylog/backup/laravel-pdf + dompdf, maatwebsite/excel, endroid/qr-code, scramble; dev: Pest, Larastan, cognitive-complexity, Rector, Boost.
- T3: `app/Modules/*` + `app/Shared/*`, base module provider, Filament admin panel discovery, architecture tests.
- T4: phpstan, rector, pint, phpunit on pgsql, CI workflow.
- T5: `docs/` (requirements files, architecture, technology, practices, modules, security, testing, open decisions, ADRs).
- T6: CLAUDE.md (<200 lines), `.claude/settings.json`, `.claude/rules/*`, Boost guidelines.

Route evidence: no writer trigger (single sequential scaffold, shared files like composer.json), so all inline.

## Log

- L1 (2026-10-23): "Bien, quedamos en usa filament para lo que mencionaste y blade + livewire para la web pública y otros cierto? Usa las versiones recientes. Ahora sería bueno que armes ya toda la estrcutura base, crees quizás archivos de las reglas, arquitectura, tecnología, buenas prácticas, etc de lo que definimos?, las funcionalidades, lo que tomaste en cuenta en las conversaciones que compartí, etc. Colocar el anexo técnico y oficio en las sección correspondiente, etc? O sea, que ya armes e instales todo lo necesario para la base de este sistema."
- L2: Environment: PHP 8.5.6, Composer 2.10, Node 22, Docker 29; no local PostgreSQL → Docker `postgres:18` (latest stable; 19 is RC). Laravel 13.38.0 skeleton ships CLAUDE.md/AGENTS.md with a Boost bootstrap block.
- L3: T1–T6 done. Evidence: `composer check` → Pint passed, Rector 0 changes, Larastan level 8 0 errors, Pest 20/20 (799 assertions) on PostgreSQL `ciln_testing`. Runtime: `/` 200, `/up` 200, `/admin/login` 200, `/admin` 302 (guest), `/docs/api` 200.
- L4: RED/GREEN evidence: boundary arch rule failed with a real cross-module Model import ("Expecting 'App\Modules\Pagos' not to use 'App\Modules\Matricula\Models'") and passed after removal; guest API request returned 500 (missing `login` route) → test added → fixed with `redirectGuestsTo` (API returns 401 JSON); sample `/api/user` route removed (returned raw User model).
- L5: Constraints: `.env*` reads are denied by user permissions, so `.env`/`.env.example` were NOT edited; user must set pgsql values (README quick path). Port 5432 is taken by another local project, so local `DB_PORT=5433`. Global gitignore excludes CLAUDE.md/.claude → negated in repo `.gitignore` so the team shares them. Stale skeleton `AGENTS.md` (Boost bootstrap) removed.
- L6: Not done (out of base scope): PostgreSQL audit triggers, module domain code, roles, browser tests, portal login. Next step: model the database starting with Academico, Personas and Matricula.
