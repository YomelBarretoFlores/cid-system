# ADR 0001: Monolito modular en Laravel

- Estado: aceptado (2026-10-24)

## Contexto

El Anexo exige MVC o multicapa análoga, servicios desacoplados y escalabilidad. La DSTI criticó el código monolítico y acoplado del sistema actual. El equipo es pequeño y la DSTI heredará el mantenimiento.

## Decisión

Un solo despliegue Laravel organizado en `app/Modules/<Modulo>`, cada uno con MVC + Actions. Los módulos solo se comunican por `Contracts`, `Data`, `Enums` y `Events`, y tests de arquitectura de Pest hacen cumplir la regla.

## Alternativas

| Opción | Motivo del descarte |
|---|---|
| MVC plano de Laravel | Controladores y modelos crecen sin límite; repite el problema del sistema actual |
| Hexagonal completo | Va contra la forma natural de Laravel; sobreingeniería para este tamaño |
| Microservicios | Varios despliegues y red de por medio; problemas de escala que el CILN no tiene |
| `nwidart/laravel-modules` | Dependencia y convenciones propias que la DSTI tendría que aprender |

## Consecuencias

- Un módulo nuevo es una carpeta y una línea en `bootstrap/providers.php`.
- Si algún módulo necesitara separarse, ya habla por contratos y eventos.
- El Tablero es la excepción de solo lectura (consultas y vistas de PostgreSQL).
