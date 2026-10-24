# Estrategia de pruebas

**Una herramienta (Pest 5), varios tipos de prueba.** La mayoría son feature tests: prueban el comportamiento real (HTTP, validación, permisos y base de datos) con el mejor balance entre costo y confianza.

## Quick path

```bash
docker compose up -d   # PostgreSQL 18 con la base ciln_testing
php artisan test       # o composer check para lint + análisis + tests
```

## Tipos

| Tipo | Dónde | Qué prueba |
|---|---|---|
| Unit | `tests/Unit` | Lógica pura: enums de estados, cálculos |
| Feature | `tests/Feature`, `tests/Modules/<Modulo>` | Flujos de punta a punta por HTTP, Livewire y Filament |
| Arquitectura | `tests/Architecture` | Desacople entre módulos, `strict_types`, presets de seguridad |
| Navegador (E2E) | Más adelante (`pest-plugin-browser` + Playwright) | Solo los ~5 flujos críticos |

## Reglas

- **Toda la suite corre en PostgreSQL**, nunca en SQLite: triggers, vistas, constraints y bloqueos son propios de PostgreSQL.
- **Datos sintéticos** con factories y seeders. Nunca datos personales reales (Ley 29733).
- **Concurrencia** (último cupo, doble validación): dos conexiones o procesos reales con `DatabaseTruncation`; `RefreshDatabase` usa una sola transacción y no prueba bloqueos.
- **Auditoría**: probar que `UPDATE`/`DELETE` sobre la tabla de auditoría falla y que los cambios por query builder se registran.
- Un test por regla pedida, y un test que confirme que el comportamiento existente no cambió cuando se toca código compartido.
- Verificar también con el sistema corriendo y datos simulados, no solo con tests verdes.

## Casos obligatorios por módulo

| Módulo | Casos |
|---|---|
| Matricula | Último cupo con dos matrículas simultáneas; prerrequisitos; tarifa por tipo de vínculo |
| Pagos | Recibo duplicado (medio de pago + número de operación); transiciones inválidas (Rechazado → Validado sin pago nuevo); solo caja valida |
| Docencia | Cruce de horarios; horas × tarifa con monto exacto |
| Certificacion | QR válido verifica; código inexistente o alterado se rechaza |
| Tablero | Cada KPI cuadra con un dataset de resultado conocido |
| Tramites | Token de recuperación expira; nadie ve el trámite de otra persona |
| Transversal | Una prueba de permisos por acción sensible; cada operación crítica queda auditada |

## Fuera de Pest

- `composer audit` / `npm audit`: dependencias vulnerables.
- `pest --mutate` en Pagos y Matrícula: confirma que los tests detectan errores.
- OWASP ZAP y k6 contra staging, con autorización de la DSTI.
- **UAT con el área usuaria**: matriz requisito del TdR → caso de prueba → resultado, anexada a cada entregable.

Meta: ~80 % de cobertura en Actions, Pagos y Matrícula. No se persigue 100 % global.
