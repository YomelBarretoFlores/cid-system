# ADR 0003: Filament para administración, Blade + Livewire para portales

- Estado: aceptado (2026-10-24); modificado el 2026-10-31: el portal docente pasa a un panel Filament propio (decisión pendiente #95, cerrada)

## Contexto

Se necesitan paneles de gestión y BI, un portal público y portales de estudiantes y docentes con buena experiencia de usuario (la DSTI criticó la UX actual). El Anexo además exige una API REST.

## Decisión

- **Filament 5**: panel administrativo (administración, coordinación, caja, BI) y panel docente en `/docente` (mis grupos, asistencia, pre-acta, acta final y actas finales).
- **Blade + Livewire 4 con diseño propio**: portal público y portal del estudiante.
- **API REST** con Sanctum y JSON Resources en `/api/v1`, como entregable independiente.
- Web, API y Filament llaman a las mismas Actions.

## Alternativas

| Opción | Motivo del descarte |
|---|---|
| Next.js / Angular separado | Dos despliegues, dos stacks, autenticación entre dominios; más carga para la DSTI |
| Filament también para estudiantes | Se ve como panel de administración; la matrícula es la pantalla de más tráfico |
| Inertia + React | Válido, pero agrega un segundo paradigma; queda como alternativa solo para el portal de estudiantes |

## Consecuencias

- Un solo stack (PHP + Livewire) para mantener.
- Filament no cubre la API: se construye aparte.
- El panel docente comparte con el administrativo la apariencia y el flujo de cada solicitud (`AparienciaDelPanel`), pero no su ingreso: el docente entra por `/ingresar` como el estudiante, conserva la sesión del portal y no usa el doble factor del personal. Sus páginas viven en `app/Modules/<Modulo>/Filament/Docente/Pages` y solo las descubre ese panel. Desde el menú de usuario, "Mi portal de estudiante" lleva al portal del estudiante; todo docente lo ve, porque su persona ya cuenta como ficha de estudiante.
