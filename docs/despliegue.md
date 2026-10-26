# Despliegue

> Borrador: el entorno de la DSTI está pendiente de confirmar ([`decisiones-pendientes.md`](decisiones-pendientes.md) #8). Producción corre en servidores de la DSTI, no en Laravel Cloud.

## Requisitos del servidor

| Componente | Requisito |
|---|---|
| PHP | 8.5 con `pdo_pgsql`, `pgsql`, `gd`, `intl`, `zip`, `bcmath`, `mbstring` |
| Base de datos | PostgreSQL 18 compilado con ICU (lo traen los paquetes oficiales). La migración crea la intercalación `sin_tildes`, que permite buscar sin tildes ni mayúsculas en el panel; el usuario de la aplicación necesita permiso `CREATE` en el esquema `public`. |
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
   Sin el worker se detienen: los correos de verificación de cuenta, de recuperación de contraseña y de cambio de trámite, y la reacción de los trámites cuando Pagos valida, rechaza o anula un pago.
8. Cron: `* * * * * php /ruta/artisan schedule:run >> /dev/null 2>&1`

## Variables clave de producción

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cid.unasam.edu.pe
APP_LOCALE=es
APP_FAKER_LOCALE=es_PE
DB_CONNECTION=pgsql
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_PANEL_LIFETIME=30
PANEL_DOBLE_FACTOR_OBLIGATORIO=false
QUEUE_CONNECTION=database
```

`SESSION_PANEL_LIFETIME` son los minutos sin actividad tras los que se cierra la sesión del panel. Al publicar esta versión, las sesiones del panel abiertas se cierran en su siguiente pedido (aún no tienen la marca del ingreso del panel): conviene publicarla fuera del horario de atención. `PANEL_DOBLE_FACTOR_OBLIGATORIO` (por defecto `false`) exige que toda cuenta del panel configure su aplicación autenticadora antes de entrar; si se activa, avisar antes al personal y regenerar la caché de configuración (`php artisan config:cache`), porque el valor no cambia hasta entonces. El secreto del doble factor se cifra con `APP_KEY`: al rotar la clave, dejar la anterior en `APP_PREVIOUS_KEYS`; si se pierde, hay que reiniciar el segundo factor de cada cuenta (`php artisan auth:reiniciar-doble-factor <correo>`).

`APP_URL` debe ser la dirección pública con `https`: los enlaces de verificación de correo y de recuperación de contraseña los arma el proceso de la cola, que no conoce la petición original. La caché usa el almacén por defecto (`database`); la portada pública solo guarda objetos de valor autorizados en `config/cache.php` (`serializable_classes`).

## Base de datos y proxy

- Dos roles de PostgreSQL: **dueño** (ejecuta `php artisan migrate`) y **aplicación** (lo usa Laravel en ejecución, sin ser dueño de tablas). Detalle en [`seguridad.md`](seguridad.md).
- Si hay un proxy inverso o balanceador delante, definir `TRUSTED_PROXIES` (`config/trustedproxy.php`) con sus direcciones o rangos separados por comas, o `*` para confiar en el proxy que llama a la aplicación. Sin esa variable, la auditoría registra la IP del proxy en lugar de la del usuario y los enlaces salen con `http`.

## Límites de sesión de PostgreSQL

Cada conexión abre con límites que evitan que una transacción trabada haga esperar al resto. Se envían en la cadena de conexión (`options`), así que también rigen tras una reconexión y en las conexiones de lectura y escritura.

| Variable | Por defecto | Efecto |
|---|---|---|
| `DB_LOCK_TIMEOUT` | `5s` | Espera máxima por un bloqueo; luego el usuario ve "Otra operación está modificando estos datos en este momento…" |
| `DB_STATEMENT_TIMEOUT` | `30s` | Duración máxima de una sentencia en web, colas y tareas programadas. `migrate`, `db:seed` y `schema:dump` corren sin este límite |
| `DB_IDLE_IN_TRANSACTION_TIMEOUT` | `60s` | Cierra una sesión que deja una transacción abierta sin actividad |

- Unidades válidas: `us`, `ms`, `s`, `min`, `h`, `d` (sin unidad = milisegundos). Un valor mal escrito no desactiva la protección: se usa el valor por defecto y se registra un error una vez por proceso.
- Una tarea en cola o programada mantiene el límite de 30 s. Si de verdad necesita más, ejecuta `SET LOCAL statement_timeout = 0` dentro de su transacción. Una migración lanzada con `Artisan::call` desde otro comando también conserva el límite.
- Con un pooler de conexiones (por ejemplo PgBouncer en modo transacción) el parámetro `options` puede rechazarse o ignorarse. En ese caso, fijar los mismos límites en el rol de la aplicación: `ALTER ROLE <rol_app> SET lock_timeout = '5s'` (y los otros dos), y vaciar las variables.

## Trazabilidad

- Cada respuesta HTTP trae `X-Request-Id`; el mismo ID aparece en cada línea de log (`solicitud_id`) y en `aud_registros.solicitud_id`. Un usuario que reporta un problema puede dar ese ID para ubicar sus logs y los cambios de datos que produjo.
- Las migraciones que agregan columnas o índices a `aud_registros` y `pag_pagos` bloquean sus escrituras mientras duran. Con las tablas actuales tarda milisegundos; con tablas grandes, desplegar en una ventana de mantenimiento. Si `migrate` falla por `lock_timeout`, se vuelve a ejecutar.

## Verificación

- `GET /up` responde 200.
- `php artisan about` muestra el entorno esperado.
- Revisar `failed_jobs` y los logs tras el despliegue.
- Respaldos programados (`spatie/laravel-backup`) y una restauración de prueba en staging.

## Entorno de demostración con Docker

El `Dockerfile` de la raíz arma una imagen para un entorno de demostración o de pruebas (staging), por ejemplo en un VPS con Dokploy. **No reemplaza la producción en la DSTI**, que sigue los pasos de arriba.

| Aspecto | Detalle |
|---|---|
| Contenido | PHP 8.5-FPM, Nginx, worker de colas y scheduler en un solo contenedor, supervisados por `supervisord` (`docker/app/`) |
| Puerto | `8080`; chequeo de salud en `GET /up` |
| Arranque | Espera a PostgreSQL, ejecuta `migrate --force`, `storage:link` y `optimize` (`docker/app/entrypoint.sh`) |
| Datos de demostración | Con `DEMO_SEED=true`, se cargan solo si la base no tiene usuarios; un nuevo despliegue no los repite |
| Base de datos | PostgreSQL 18 aparte (imagen oficial `postgres:18`, que trae ICU) |
| Persistencia | Montar un volumen en `/var/www/html/storage/app` para los archivos subidos |
| Logs | Salida estándar del contenedor (`LOG_CHANNEL=stderr`) |

Variables mínimas:

```dotenv
APP_ENV=staging
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://<subdominio>
APP_LOCALE=es
APP_FAKER_LOCALE=es_PE
DB_CONNECTION=pgsql
DB_HOST=<servicio de PostgreSQL>
DB_PORT=5432
DB_DATABASE=ciln
DB_USERNAME=ciln
DB_PASSWORD=...
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
LOG_CHANNEL=stderr
MAIL_MAILER=log
TRUSTED_PROXIES=*
DEMO_SEED=true
```

`APP_ENV` debe ser `staging`, no `production`: el seeder de demostración se niega a correr en producción. `APP_KEY` se genera una vez con `php artisan key:generate --show` y no se cambia entre despliegues.

Límites de este entorno frente a producción:

- **Solo datos sintéticos** (Ley 29733). Nunca cargar datos reales de estudiantes.
- **Contraseñas de demostración**: las cuentas `*@ciln.test` usan `password`. En una dirección pública, cambiarlas tras el primer despliegue o proteger el subdominio (por ejemplo, con Cloudflare Access).
- **Un solo rol de PostgreSQL**: la aplicación se conecta como dueña de las tablas, sin la separación de roles que exige [`seguridad.md`](seguridad.md).
- **Correo**: con `MAIL_MAILER=log` no se envían correos; la verificación de cuenta de un registro nuevo no llega. Para probarla, configurar un SMTP.
- **Recursos**: en un servidor compartido, fijar límites de CPU y memoria al contenedor.
