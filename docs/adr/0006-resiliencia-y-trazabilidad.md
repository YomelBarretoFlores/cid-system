# ADR 0006: Resiliencia de transacciones y trazabilidad por solicitud

- Estado: aceptado (2026-10-24)

## Contexto

El sistema concentra operaciones concurrentes (matrícula de la última vacante, validación de pagos, asignación de docentes). Sin límites, una transacción trabada hace esperar sin fin al resto; un deadlock aislado se mostraba como error 500; y no había forma de unir los logs de una solicitud con las filas de auditoría que escribió. La recomendación 3.1 de la DSTI pide alta disponibilidad, trazabilidad y auditoría.

## Decisión

| Tema | Decisión |
|---|---|
| Límites de sesión | Cada conexión a PostgreSQL abre con `lock_timeout` 5 s, `statement_timeout` 30 s e `idle_in_transaction_session_timeout` 60 s, enviados en la cadena de conexión por un conector propio (`ConectorPostgres`). Las migraciones y los seeders no tienen límite de sentencia |
| Transacciones | Toda transacción de la aplicación pasa por `Transaccion::ejecutar`: reintenta hasta 3 veces un deadlock o un fallo de serialización (solo la transacción externa) y convierte una espera de bloqueo o una sentencia cancelada en un mensaje de negocio. Las páginas del panel que modifican un registro ya cargado no reintentan |
| Trazabilidad | Cada solicitud HTTP recibe un ULID generado por el servidor, devuelto en `X-Request-Id`, añadido a cada línea de log y guardado en `aud_registros.solicitud_id`. El ID que envíe un proxy solo se conserva en los logs como `solicitud_origen` |
| Logs | Los rechazos de negocio, reintentos y esperas agotadas se registran sin datos personales (se enmascaran números de 8 o más dígitos y correos) |
| Índices | Una prueba falla si una llave foránea no tiene un índice completo que empiece por su primera columna. Las búsquedas por muchos IDs incrustan los enteros (`whereIntegerInRaw`) y no superan el límite de 65 535 parámetros |

## Alternativas

| Opción | Motivo del descarte |
|---|---|
| `SET` de límites al abrir la conexión (`ConnectionEstablished`) | Se pierde al reconectar, solo cubre la conexión de lectura y suma consultas por solicitud |
| Reintentar en cada Action con `DB::transaction($cb, 3)` | Laravel no reintenta en transacciones anidadas ni traduce `lock_timeout`; repartiría la regla en 34 lugares |
| Aceptar el `X-Request-Id` del cliente como ID de auditoría | Un usuario podría elegir o repetir el ID que queda en la auditoría |
| Dividir las listas de IDs en bloques | Más consultas; `whereIntegerInRaw` es seguro porque convierte cada valor a entero |

## Consecuencias

- Un usuario que choca con otra operación ve "Otra operación está modificando estos datos en este momento. Inténtalo de nuevo en unos segundos." en lugar de un error técnico.
- Las tareas en cola y programadas mantienen el límite de 30 s; una tarea larga debe usar `SET LOCAL statement_timeout = 0` dentro de su transacción.
- Con PgBouncer en modo transacción, los límites se fijan en el rol de PostgreSQL (`docs/despliegue.md`).
- Agregar columnas o índices a tablas con escrituras frecuentes (`aud_registros`, `pag_pagos`) bloquea esas escrituras mientras dura la migración; en tablas grandes conviene una ventana de mantenimiento.
