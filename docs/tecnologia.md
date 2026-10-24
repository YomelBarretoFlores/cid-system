# Tecnología

Versiones vigentes al 2026-10-24, todas con soporte oficial activo (Anexo II.1). La DSTI autorizó usar la versión más reciente de PHP.

## Stack

| Capa | Tecnología | Versión |
|---|---|---|
| Lenguaje | PHP | 8.5 |
| Framework | Laravel | 13.x (errores hasta Q3 2027, seguridad hasta 17/03/2028) |
| Base de datos | PostgreSQL | 18 |
| Panel administrativo y BI | Filament | 5.x |
| Portal público, estudiantes y docentes | Blade + Livewire | Livewire 4.x |
| Estilos | Tailwind CSS (Vite) | 4.x |
| API REST | Laravel Sanctum + JSON Resources | 4.x |

## Paquetes

| Necesidad | Paquete | Notas |
|---|---|---|
| Roles y permisos | `spatie/laravel-permission` | Un solo guard (`web`) con roles |
| Contexto de negocio en auditoría | `spatie/laravel-activitylog` | Complemento; la auditoría oficial son triggers de PostgreSQL ([`seguridad.md`](seguridad.md)) |
| Respaldos | `spatie/laravel-backup` | Probar la restauración periódicamente |
| PDF (fichas, actas, certificados) | `spatie/laravel-pdf` + `dompdf/dompdf` | Driver DOMPDF: sin Node ni Chromium en el servidor. CSS limitado: maquetar con tablas |
| Excel/CSV | `maatwebsite/excel` | 4.x |
| QR | `endroid/qr-code` | No usar `simplesoftwareio/simple-qrcode` (sin releases desde 2021) |
| Documentación de la API | `dedoc/scramble` | OpenAPI 3.1 generado del código, sin anotaciones |

## Desarrollo y calidad

| Herramienta | Uso | Comando |
|---|---|---|
| Pest 5 | Tests (unit, feature, arquitectura) | `php artisan test` |
| Larastan (nivel 8) + `tomasvotruba/cognitive-complexity` | Análisis estático y límite de complejidad (8 por método, 40 por clase) | `composer analyse` |
| Rector + `driftingly/rector-laravel` | Modernización y calidad de código | `composer lint` / `composer fix` |
| Pint | Formato (preset Laravel + `strict_types`) | `composer lint` / `composer fix` |
| Laravel Boost | Guías, skills y MCP para agentes de IA | `php artisan boost:update` |
| Docker Compose | PostgreSQL 18 local (`ciln`, `ciln_testing`) | `docker compose up -d` |

`composer check` corre lint, análisis y tests, en el mismo orden que el CI (`.github/workflows/ci.yml`).

## Descartado

| Opción | Motivo |
|---|---|
| Next.js / Angular como frontend separado | Dos despliegues, dos stacks y autenticación entre dominios; más difícil de mantener para la DSTI |
| Filament para el portal de estudiantes | Es un panel de administración; la DSTI criticó la experiencia de usuario (1.4) |
| `nwidart/laravel-modules` | Impone convenciones propias; la estructura manual logra lo mismo sin dependencia |
| Filament Studio | Almacenamiento EAV: pierde llaves foráneas, constraints y rendimiento en el M5 |
| `owen-it/laravel-auditing` | `activitylog` ya guarda valores antes y después; la auditoría oficial va en la base de datos |
| JWT | Hay servidor que renderiza; las sesiones con cookie son más seguras ([`adr/0004-sesiones-para-autenticacion.md`](adr/0004-sesiones-para-autenticacion.md)) |
