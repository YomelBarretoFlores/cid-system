# Tablero

M5: tablero gerencial descriptivo (KPIs financieros y académicos). Solo lectura: consultas dedicadas sobre las tablas de los demás módulos (solo importa sus `Enums` y `Data`), sin contratos propios. Cada indicador es una Action; `ArmarTablero` los reúne para la página, el PDF y el Excel.

Public API for other modules: `Contracts/`, `Data/` (DTOs), `Events/` and `Enums/`. Everything else is internal. See `docs/arquitectura.md`.
