# CILN · Sistema de Gestión Académica y Administrativa

Sistema del Centro de Idiomas y Lenguas Nativas (CILN) de la UNASAM. Reemplaza al sistema actual en PHP 5.6 según el Anexo Técnico y el Informe Técnico N° 023-2026-FMAJ de la DSTI.

**Stack:** PHP 8.5 · Laravel 13 · PostgreSQL 18 · Filament 5 · Livewire 4 · Pest 5.

## Componentes

| Componente | Dirección | Usuarios |
|---|---|---|
| Portal público | `/` | Público en general |
| Portal del estudiante | `/estudiante` | Estudiantes |
| Panel docente | `/docente` | Docentes |
| Panel administrativo | `/admin` | Personal del CILN (coordinación, caja, reportes) |
| API REST | `/api/v1` | Integraciones |

## Quick path (desarrollo local)

Requisitos: PHP 8.5 (`pdo_pgsql`, `pgsql`, `gd`, `intl`, `zip`, `bcmath`, `mbstring`), Composer 2, Node 22 y Docker.

1. `composer install && npm install`
2. `cp .env.example .env && php artisan key:generate`. La plantilla ya apunta al PostgreSQL de `docker compose`; si el puerto 5432 está ocupado, cambiar `DB_PORT` en `.env`.
3. `docker compose up -d` (PostgreSQL 18 con las bases `ciln` y `ciln_testing`)
4. `php artisan migrate --seed`. El seeder carga datos de demostración sintéticos y se niega a correr en producción.
5. `composer dev` y abrir `http://localhost:8000`.

Cuentas de demostración (contraseña `password`): `admin@ciln.test` (panel administrativo), `caja@ciln.test` (caja), `docente@ciln.test` (panel docente) y `estudiante@ciln.test` (portal del estudiante). Se ingresa por `/ingresar`.

## Comandos

| Comando | Qué hace |
|---|---|
| `composer check` | Lint (Pint + Rector), análisis estático (Larastan nivel 8 + complejidad) y tests. Igual que el CI |
| `composer fix` | Aplica Rector y Pint |
| `php artisan test --compact` | Corre la suite de Pest sobre PostgreSQL |

## Despliegue

Producción corre en los servidores de la DSTI, por la ruta tradicional (PHP, Nginx y PostgreSQL instalados en el servidor) o con Docker. Ambas están en [`docs/despliegue.md`](docs/despliegue.md), con las variables de producción, los dos roles de PostgreSQL, el almacenamiento de archivos y los respaldos.

## Documentación

Todo está en [`docs/`](docs/README.md): requisitos oficiales, arquitectura, tecnología, módulos, buenas prácticas, seguridad, pruebas, despliegue y decisiones pendientes.

## Propiedad intelectual

Todos los derechos del software, el código fuente, la base de datos y los manuales se ceden a la UNASAM (Anexo Técnico, sección IV).
