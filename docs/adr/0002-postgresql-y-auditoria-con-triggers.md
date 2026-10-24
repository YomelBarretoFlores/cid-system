# ADR 0002: PostgreSQL y auditoría con triggers

- Estado: aceptado (2026-10-24)

## Contexto

El Anexo pide una base de datos relacional optimizada y pistas de auditoría **inmutables** ("Log Triggers") para notas, pagos y actas. Los paquetes de auditoría de Laravel guardan filas normales y solo capturan cambios hechos con Eloquent.

## Decisión

- PostgreSQL 18, usando a propósito sus funciones: llaves foráneas, `CHECK`, `UNIQUE`, `timestamptz`, vistas y triggers.
- La auditoría oficial la escriben triggers en las tablas críticas. La tabla de auditoría rechaza `UPDATE`/`DELETE` y el usuario de la aplicación solo tiene `INSERT`/`SELECT` sobre ella. Usuario e IP llegan por `set_config` en cada petición.
- `spatie/laravel-activitylog` queda como complemento para mostrar contexto de negocio.
- Toda la suite de tests corre sobre PostgreSQL.

## Consecuencias

- No se busca portabilidad entre motores de base de datos.
- Las migraciones de triggers viven en `app/Shared/Audit`.
- La integridad no depende solo del código: la base la garantiza.
