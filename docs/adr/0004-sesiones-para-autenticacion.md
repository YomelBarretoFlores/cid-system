# ADR 0004: Sesiones con cookie para autenticación

- Estado: aceptado (2026-10-24)

## Contexto

Hay un servidor que renderiza las páginas (Blade, Livewire, Filament). El M6 pide login diferenciado de estudiantes y docentes y recuperación por token al correo.

## Decisión

- Sesiones con cookie `HttpOnly`, `secure`, `SameSite=lax`, guardadas en base de datos (`SESSION_DRIVER=database`).
- Un solo guard (`web`) con roles (`spatie/laravel-permission`).
- Sanctum: cookie para la web propia, tokens solo para clientes externos de la API.
- Recuperación de contraseña con `password_reset_tokens`.

## Alternativas

| Opción | Motivo del descarte |
|---|---|
| JWT (estilo Supabase) | Pensado para frontends que hablan directo con una API; en `localStorage` lo roba un XSS y es difícil revocarlo |
| Dos guards (estudiante y docente) | Complica el proyecto sin necesidad |

## Consecuencias

- Las sesiones en base de datos se comparten entre varios servidores (alta disponibilidad).
- La expiración por inactividad distinta por rol requiere un middleware propio.
