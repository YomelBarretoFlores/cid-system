# ADR 0005: DOMPDF como driver de PDF

- Estado: aceptado (2026-10-24)

## Contexto

Se generan fichas de matrícula, actas, constancias y certificados en PDF. El entorno de la DSTI no está confirmado y puede no permitir Node, Chromium ni Docker.

## Decisión

`spatie/laravel-pdf` con el driver DOMPDF, que genera el PDF solo con PHP.

## Alternativas

| Opción | Motivo del descarte |
|---|---|
| Browsershot (por defecto) | Necesita Node y Chromium en el servidor |
| Gotenberg | Fidelidad completa, pero necesita un servicio Docker; se reevalúa si la DSTI lo permite |

## Consecuencias

- El CSS de DOMPDF es limitado (flexbox y grid funcionan mal): las plantillas se maquetan con tablas y CSS simple.
- Cambiar de driver no exige reescribir el código, solo la configuración y las plantillas que lo necesiten.
