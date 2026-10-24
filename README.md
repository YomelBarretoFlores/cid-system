# CILN · Sistema de Gestión Académica y Administrativa

Sistema del Centro de Idiomas y Lenguas Nativas (CILN) de la UNASAM. Reemplaza al sistema actual en PHP 5.6 según el Anexo Técnico y el Informe Técnico N° 023-2026-FMAJ de la DSTI.

**Stack:** PHP 8.5 · Laravel 13 · PostgreSQL 18 · Filament 5 · Livewire 4 · Pest 5.

## Quick path

Requisitos: PHP 8.5 (`pdo_pgsql`, `gd`, `intl`, `zip`, `bcmath`), Composer 2, Node 22 y Docker.

1. `composer install && npm install`
2. `cp .env.example .env && php artisan key:generate`
3. En `.env`, configurar PostgreSQL:
   ```dotenv
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432        # usar otro puerto si 5432 está ocupado
   DB_DATABASE=ciln
   DB_USERNAME=ciln
   DB_PASSWORD=secret
   ```
4. `docker compose up -d` (crea las bases `ciln` y `ciln_testing`)
5. `php artisan migrate`
6. `composer dev` y abrir `http://localhost:8000` (panel administrativo en `/admin`)

## Comandos

| Comando | Qué hace |
|---|---|
| `composer check` | Lint (Pint + Rector), análisis estático (Larastan nivel 8 + complejidad) y tests. Igual que el CI |
| `composer fix` | Aplica Rector y Pint |
| `php artisan test` | Corre la suite de Pest sobre PostgreSQL |

## Documentación

Todo está en [`docs/`](docs/README.md): requisitos oficiales, arquitectura, tecnología, módulos, buenas prácticas, seguridad, pruebas, despliegue y decisiones pendientes.

## Propiedad intelectual

Todos los derechos del software, el código fuente, la base de datos y los manuales se ceden a la UNASAM (Anexo Técnico, sección IV).
