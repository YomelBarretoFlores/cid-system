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
9. Primera instalación: crear la primera cuenta de administración con `php artisan personal:crear-administrador <correo> "<nombre>"` (necesita el worker de colas para enviar el correo).

### Primera cuenta de administración

`php artisan personal:crear-administrador <correo> "<nombre>"` crea la cuenta (o, si el correo ya tiene una, la reactiva y le agrega permisos sin cambiar su nombre) con acceso al panel y **todos** los permisos, incluido `usuarios.gestionar`, y le envía el enlace para crear su contraseña: nadie escribe ni ve la contraseña. Desde ahí, esa persona crea las demás cuentas en el panel (Configuración → Usuarios del personal). Sirve también para recuperar el acceso si ninguna cuenta activa puede gestionar usuarios. Sale con `1` si el correo no es válido; el log registra el ID de la cuenta.

Al migrar una base que ya tenía cuentas, `usuarios.gestionar` se otorga automáticamente a las cuentas activas que ya tenían todos los demás permisos, así que no hace falta ejecutar el comando.

## Variables clave de producción

```dotenv
APP_NAME="CILN UNASAM"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_PREVIOUS_KEYS=
APP_URL=https://cid.unasam.edu.pe
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_PE

DB_CONNECTION=pgsql
DB_HOST=<servidor de PostgreSQL>
DB_PORT=5432
DB_DATABASE=ciln
DB_USERNAME=<rol de aplicación>
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=120
SESSION_PANEL_LIFETIME=30
PANEL_DOBLE_FACTOR_OBLIGATORIO=false

QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=<servidor SMTP>
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=<correo institucional del CILN>
MAIL_FROM_NAME="${APP_NAME}"

LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

TRUSTED_PROXIES=<IP o rango del proxy>

CILN_VALIDACION_POR_OTRA_PERSONA=true
CILN_RESPALDOS_PROGRAMADOS=false
BACKUP_ARCHIVE_PASSWORD=...
```

Variables que, si faltan, toman un valor por defecto que no sirve en producción:

| Variable | Valor por defecto | Consecuencia si falta |
|---|---|---|
| `APP_NAME` | `Laravel` | Es el remitente de los correos y el nombre de los respaldos |
| `DB_USERNAME` | `root` | Debe ser el rol de **aplicación**, no el dueño de las tablas ([Base de datos y proxy](#base-de-datos-y-proxy)) |
| `MAIL_MAILER` | `log` | No se envía ningún correo: la verificación de cuenta, la recuperación de contraseña, el aviso de cambio de contraseña y la primera cuenta de administración quedan solo en el log |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Los correos salen desde una dirección ajena al CILN y pueden ser rechazados o marcados como spam |
| `LOG_LEVEL` | `debug` | El log crece con detalle de depuración; en producción basta `warning` |
| `TRUSTED_PROXIES` | vacío | La auditoría registra la IP del proxy y los enlaces salen con `http` |
| `BACKUP_ARCHIVE_PASSWORD` | vacío | El respaldo se guarda sin cifrar; con valor, se cifra (AES-256) y esa contraseña hace falta para restaurarlo |

`CILN_VALIDACION_POR_OTRA_PERSONA` (por defecto `true`) impide que quien registró un pago lo valide ([decisión #117](decisiones-pendientes.md)). No se usan Redis, S3, Cloudflare ni Gotenberg: sus variables pueden omitirse. Los PDF usan `dompdf`, que no requiere configuración. Después de cambiar cualquier variable, regenerar la caché con `php artisan config:cache`.

`SESSION_PANEL_LIFETIME` son los minutos sin actividad tras los que se cierra la sesión del panel. Al publicar esta versión, las sesiones del panel abiertas se cierran en su siguiente pedido (aún no tienen la marca del ingreso del panel): conviene publicarla fuera del horario de atención. `PANEL_DOBLE_FACTOR_OBLIGATORIO` (por defecto `false`) exige que toda cuenta del panel configure su aplicación autenticadora antes de entrar; si se activa, avisar antes al personal y regenerar la caché de configuración (`php artisan config:cache`), porque el valor no cambia hasta entonces. El secreto del doble factor se cifra con `APP_KEY`: al rotar la clave, dejar la anterior en `APP_PREVIOUS_KEYS`; si se pierde, hay que reiniciar el segundo factor de cada cuenta (`php artisan auth:reiniciar-doble-factor <correo>`). Los QR de las actas finales impresas llevan un enlace firmado con `APP_KEY` que no caduca: al rotar la clave, la anterior debe quedar en `APP_PREVIOUS_KEYS` o esos QR dejarán de verificar. El enlace usa `APP_URL` como dirección pública, así que debe ser el dominio definitivo antes de imprimir actas.

`APP_URL` debe ser la dirección pública con `https`: los enlaces de verificación de correo y de recuperación de contraseña los arma el proceso de la cola, que no conoce la petición original. La caché usa el almacén por defecto (`database`); la portada pública solo guarda objetos de valor autorizados en `config/cache.php` (`serializable_classes`).

## Base de datos y proxy

- Dos roles de PostgreSQL: **dueño** (ejecuta `php artisan migrate`) y **aplicación** (lo usa Laravel en ejecución, sin ser dueño de tablas). Detalle en [`seguridad.md`](seguridad.md).
- Si hay un proxy inverso o balanceador delante, definir `TRUSTED_PROXIES` (`config/trustedproxy.php`) con sus direcciones o rangos separados por comas, o `*` para confiar en el proxy que llama a la aplicación. Sin esa variable, la auditoría registra la IP del proxy en lugar de la del usuario y los enlaces salen con `http`.

## Respaldos programados

Están **apagados por defecto** (`CILN_RESPALDOS_PROGRAMADOS=false`): el disco de destino, la retención y la prueba de restauración siguen pendientes con la DSTI ([decisión #5](decisiones-pendientes.md)).

| Tarea | Hora (Lima) | Qué hace |
|---|---|---|
| `backup:clean` | 01:00 | Borra los respaldos antiguos según la retención de `config/backup.php` |
| `backup:run` | 01:30 | Respalda la base de datos y los archivos de la aplicación (sin `vendor`, `node_modules` ni `storage/framework`), incluidos los archivos subidos de `storage/app` |

Para activarlos:

1. Configurar primero el disco de destino en `config/backup.php` (`backup.destination.disks`) y en `config/filesystems.php`. Con el disco `local` por defecto, el respaldo queda en el mismo servidor y no protege ante su pérdida.
2. Confirmar que el servidor tiene `pg_dump` de la misma versión mayor que PostgreSQL (18) y que el scheduler corre cada minuto (`php artisan schedule:run`).
3. Fijar `CILN_RESPALDOS_PROGRAMADOS=true` y regenerar la caché de configuración (`php artisan config:cache`).
4. Ejecutar una vez `php artisan backup:run` y restaurar ese respaldo en staging antes de darlo por bueno.

`php artisan schedule:list` muestra ambas tareas aunque estén apagadas; con la variable en `false` no se ejecutan.

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
- Respaldos programados (`spatie/laravel-backup`, ver [Respaldos programados](#respaldos-programados)) y una restauración de prueba en staging.

## Configuración del primer día

Antes de abrir la matrícula, la coordinación registra en el panel (Configuración):

- **Sistemas de evaluación**: al menos uno general (sin idioma ni nivel) con sus componentes, cuyos pesos suman 100, y la nota mínima aprobatoria. Sin un sistema vigente, el docente no puede registrar notas y el acta no se cierra ("pide a coordinación que lo registre").
- **Medios de pago** y **fuentes TUPA/TUSNE**: vienen precargados; se desactivan o renombran según el CILN.

## Entorno de demostración con Docker

El `Dockerfile` de la raíz arma una imagen para un entorno de demostración o de pruebas (staging), por ejemplo en un VPS con Dokploy. **No reemplaza la producción en la DSTI**, que sigue los pasos de arriba.

| Aspecto | Detalle |
|---|---|
| Contenido | PHP 8.5-FPM, Nginx, worker de colas y scheduler en un solo contenedor, supervisados por `supervisord` (`docker/app/`) |
| Puerto | `8080`; chequeo de salud en `GET /up` |
| Arranque | Espera a PostgreSQL, ejecuta `migrate --force`, `storage:link` y `optimize` (`docker/app/entrypoint.sh`) |
| Datos de demostración | Con `DEMO_SEED=true`, se cargan completos solo si la base no tiene usuarios; un nuevo despliegue no los repite. Si la base ya tiene datos, cada arranque completa la demostración sin borrar nada (`ComplementoDemoSeeder`: sílabos, delegados, clases con ambos llamados, notas completas e historial del estudiante demo); cada parte revisa primero lo que ya existe, así que repetirla no cambia nada. Ambas cargas son una sola transacción y se niegan a correr en producción. Si la carga completa falla, no deja datos a medias, el contenedor no arranca y el siguiente arranque la intenta de nuevo. Si falla el complemento, la demostración queda como estaba, se muestra un aviso y el contenedor arranca igual. El complemento no hace nada si la base no tiene las cuentas de demostración (`*@ciln.test`). En una demostración cargada con la versión anterior, las clases pasadas ya quedaron como registro posterior y no se toman de nuevo: para ver la confirmación del delegado hay que cargarla desde cero |
| Detención | Fijar un tiempo de gracia de al menos 100 s (en Swarm, `stop_grace_period`) para que el worker termine el trabajo en curso; con los 10 s por defecto, un trabajo cortado se reintenta |
| Red | No publicar el puerto `8080`: el contenedor solo debe recibir tráfico del proxy. Con `TRUSTED_PROXIES=*`, quien llegue directo podría falsear su IP y el `https` |
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
