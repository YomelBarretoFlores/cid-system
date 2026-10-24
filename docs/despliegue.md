# Despliegue

> Borrador: el entorno de la DSTI está pendiente de confirmar ([`decisiones-pendientes.md`](decisiones-pendientes.md) #8). Producción corre en servidores de la DSTI, no en Laravel Cloud.

## Requisitos del servidor

| Componente | Requisito |
|---|---|
| PHP | 8.5 con `pdo_pgsql`, `pgsql`, `gd`, `intl`, `zip`, `bcmath`, `mbstring` |
| Base de datos | PostgreSQL 18 |
| Servidor web | Nginx o Apache apuntando a `public/` |
| Procesos | Worker de colas supervisado (Supervisor o systemd) y cron del scheduler |
| Correo | SMTP institucional |
| Build de assets | Node solo en el build (`npm ci && npm run build`); no se necesita en ejecución |

## Pasos

1. `composer install --no-dev --optimize-autoloader`
2. Configurar `.env` (ver abajo) y `php artisan key:generate` la primera vez.
3. `php artisan migrate --force`
4. `php artisan storage:link`
5. `npm ci && npm run build`
6. `php artisan optimize` (cachea config, rutas, vistas y eventos).
7. Worker: `php artisan queue:work --tries=3 --timeout=90`, supervisado.
8. Cron: `* * * * * php /ruta/artisan schedule:run >> /dev/null 2>&1`

## Variables clave de producción

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_LOCALE=es
APP_FAKER_LOCALE=es_PE
DB_CONNECTION=pgsql
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
```

## Base de datos y proxy

- Dos roles de PostgreSQL: **dueño** (ejecuta `php artisan migrate`) y **aplicación** (lo usa Laravel en ejecución, sin ser dueño de tablas). Detalle en [`seguridad.md`](seguridad.md).
- Si hay un proxy inverso o balanceador delante, configurar `trustProxies(at: ...)` en `bootstrap/app.php`; si no, la auditoría registra la IP del proxy en lugar de la del usuario.

## Límites de sesión de PostgreSQL

Cada conexión abre con límites que evitan que una transacción trabada haga esperar al resto. Se envían en la cadena de conexión (`options`), así que también rigen tras una reconexión y en las conexiones de lectura y escritura.

| Variable | Por defecto | Efecto |
|---|---|---|
| `DB_LOCK_TIMEOUT` | `5s` | Espera máxima por un bloqueo; luego el usuario ve "Otra operación está modificando estos datos en este momento…" |
| `DB_STATEMENT_TIMEOUT` | `30s` | Duración máxima de una sentencia en web, colas y tareas programadas. `migrate`, `db:seed` y `schema:dump` corren sin este límite |
| `DB_IDLE_IN_TRANSACTION_TIMEOUT` | `60s` | Cierra una sesión que deja una transacción abierta sin actividad |

- Unidades válidas: `us`, `ms`, `s`, `min`, `h`, `d` (sin unidad = milisegundos). Un valor mal escrito no desactiva la protección: se usa el valor por defecto y se registra un error una vez por proceso.
- Con un pooler de conexiones (por ejemplo PgBouncer en modo transacción) el parámetro `options` puede rechazarse o ignorarse. En ese caso, fijar los mismos límites en el rol de la aplicación: `ALTER ROLE <rol_app> SET lock_timeout = '5s'` (y los otros dos), y vaciar las variables.

## Verificación

- `GET /up` responde 200.
- `php artisan about` muestra el entorno esperado.
- Revisar `failed_jobs` y los logs tras el despliegue.
- Respaldos programados (`spatie/laravel-backup`) y una restauración de prueba en staging.
