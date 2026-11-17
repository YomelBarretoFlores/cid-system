# Despliegue

> El entorno de la DSTI está pendiente de confirmar ([`decisiones-pendientes.md`](decisiones-pendientes.md) #8). Producción corre en servidores de la DSTI, no en Laravel Cloud, por una de dos rutas que elige la DSTI: **tradicional** (esta sección) o **[Docker](#producción-con-docker)**. Ambas usan la misma base de datos, las mismas variables y los mismos dos roles de PostgreSQL. La entrega formal a la DSTI es el Informe Técnico N.° 001-2026-YJBF, que resume esta guía.

## Responsabilidades

| Quién | Qué |
|---|---|
| La DSTI envía al proveedor | Solo la parte pública de la llave de despliegue (`ciln_despliegue.pub`, que no es secreta; sirve para la descarga inicial y para las actualizaciones automáticas), la ruta elegida (tradicional o Docker), el dominio definitivo y la confirmación de que el servidor llega a GitHub |
| La DSTI hace en sus servidores, sin enviar claves | Crea los roles de PostgreSQL y la base ([Roles de PostgreSQL](#roles-de-postgresql)), habilita el SMTP autenticado de `cid@unasam.edu.pe` (la contraseña la escribe en `MAIL_PASSWORD` quien la custodia), instala el sistema y decide si activa la [actualización automática](#actualización-automática), que exige aceptar que la clave del rol dueño quede guardada en el servidor; además, el certificado HTTPS y los respaldos |
| El proveedor | Registra la llave de despliegue en GitHub, publica cada versión con su etiqueta cuando CI está en verde, acompaña la instalación y la [verificación](#verificación) y, al cierre del servicio, entrega el repositorio a la UNASAM |

## Requisitos del servidor

| Componente | Requisito |
|---|---|
| PHP | 8.5 con `pdo_pgsql`, `pgsql`, `gd`, `intl`, `zip`, `bcmath`, `mbstring` y `pcntl` (sin `pcntl` el worker de colas no corta un trabajo que pasa de `--timeout`) |
| Base de datos | PostgreSQL 18 compilado con ICU (lo traen los paquetes oficiales), en el mismo servidor o en otro. La migración crea la intercalación `sin_tildes`, que permite buscar sin tildes ni mayúsculas en el panel; otra crea la extensión `btree_gist`. Ambas se cubren creando la base con el rol dueño como propietario (ver [Roles de PostgreSQL](#roles-de-postgresql)). |
| Servidor web | Nginx o Apache apuntando a `public/` |
| Procesos | Worker de colas supervisado (Supervisor o systemd) y cron del scheduler |
| Correo | SMTP institucional (ver [Correo](#correo)) |
| Build de assets | Node solo en el build (`npm ci && npm run build`); no se necesita en ejecución |

## Pasos

1. Obtener el código. El repositorio es privado: el servidor usa una **llave de despliegue de solo lectura**, y todo lo hace una misma cuenta del sistema dueña de la carpeta del sitio (por ejemplo `ciln`), que luego usa la [actualización automática](#actualización-automática). Crear la cuenta y la carpeta (`sudo useradd -m -s /bin/bash ciln`, `sudo install -d -o ciln /var/www/ciln`) y, como `ciln` (`sudo -iu ciln`), crear `~/.ssh` (`mkdir -p -m 700 ~/.ssh`) y generar la llave (`ssh-keygen -t ed25519 -N "" -C "servidor-ciln" -f ~/.ssh/ciln_despliegue`) y remitir al proveedor solo el `.pub`, que la registra en GitHub (*Settings → Deploy keys*, sin permiso de escritura). Cuando el proveedor confirme, clonar (`GIT_SSH_COMMAND="ssh -i ~/.ssh/ciln_despliegue" git clone git@github.com:YomelBarretoFlores/cid-system.git /var/www/ciln`), fijar la llave para las actualizaciones (`git -C /var/www/ciln config core.sshCommand "ssh -i ~/.ssh/ciln_despliegue"`) y pasar a la versión publicada más reciente (`git -C /var/www/ciln checkout v1.0.0`). La primera conexión con GitHub pide confirmar su huella: responder `yes`. La llave descarga código pero no puede modificarlo, y no requiere cuentas personales.
2. `composer install --no-dev --optimize-autoloader`
3. Configurar `.env` (ver abajo) y `php artisan key:generate` la primera vez.
4. Dar permiso de escritura al usuario del servidor web sobre `storage/` y `bootstrap/cache/`, y dejar `.env` legible solo por ese usuario.
5. Migrar con el rol **dueño** (ver [Base de datos y proxy](#base-de-datos-y-proxy)): `DB_USERNAME=<rol_dueño> DB_PASSWORD=<clave> php artisan migrate --force`. Las variables del comando tienen prioridad sobre `.env`, que conserva el rol de aplicación. Debe correr antes de `optimize`: con la configuración cacheada, el comando usaría el rol de `.env`. En la primera instalación, a continuación ejecutar el `REVOKE` sobre `aud_registros` de [Roles de PostgreSQL](#roles-de-postgresql).
6. `php artisan storage:link`
7. `npm ci && npm run build`
8. `php artisan optimize && php artisan filament:optimize` (cachea config, rutas, vistas, eventos y componentes del panel).
9. Worker: `php artisan queue:work --tries=3 --timeout=90`, supervisado, con al menos 100 s de espera al detenerlo (en Supervisor, `stopwaitsecs=100`) para que termine el trabajo en curso.
   Sin el worker se detienen: los correos de verificación de cuenta, de recuperación de contraseña y de cambio de trámite, y la reacción de los trámites cuando Pagos valida, rechaza o anula un pago.
10. Cron: `* * * * * cd /ruta && php artisan schedule:run >> /dev/null 2>&1`
11. Primera instalación: crear la primera cuenta de administración con `php artisan personal:crear-administrador <correo> "<nombre>"` (necesita el worker de colas para enviar el correo).

### Actualizar a una nueva versión

```bash
cd /var/www/ciln && git fetch --tags && git checkout v<versión>
composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan config:clear
DB_USERNAME=<rol_dueño> DB_PASSWORD=<clave> php artisan migrate --force
php artisan optimize && php artisan filament:optimize
php artisan queue:restart
```

`config:clear` va antes de migrar para que la migración tome el rol dueño del comando y no el de la configuración cacheada. `queue:restart` hace que el worker cargue el código nuevo al terminar su trabajo actual. `storage/app` no está en el repositorio y no se toca en la actualización.

### Actualización automática

El servidor instala solo las **versiones publicadas**: etiquetas `vX.Y.Z` que estén en `main`. Un `git push` normal no llega a producción, y una etiqueta creada en otra rama se ignora.

**Publicar una versión** (proveedor), cuando CI de `main` está en verde:

```bash
git tag -a v1.2.0 -m "Versión 1.2.0"
git push origin v1.2.0
```

**Instalar el mecanismo** (DSTI, una sola vez, con el repositorio ya clonado con la llave de despliegue):

1. Crear la cuenta que hace las actualizaciones, dueña de la carpeta del sitio (por ejemplo `ciln`; en la ruta Docker, además, en el grupo `docker`). Nunca `root`.
2. Copiar `deploy/actualizacion.conf.ejemplo` a `/etc/ciln/actualizacion.conf` y ajustar `CILN_RUTA` y `CILN_MODO` (`tradicional` o `docker`).
3. Crear `/etc/ciln/migraciones.env` con `DB_USERNAME` y `DB_PASSWORD` del rol **dueño** (en Docker, el archivo completo de variables de migración), con dueño `ciln` y permisos `600`.
4. Comprobar sin cambiar nada: `sudo -u ciln /var/www/ciln/deploy/actualizar.sh --simular`.
5. Copiar `deploy/ciln-actualizacion.service` y `deploy/ciln-actualizacion.timer` a `/etc/systemd/system/`, ajustar `User=` y la ruta de `ExecStart` si difieren, y activar: `systemctl daemon-reload && systemctl enable --now ciln-actualizacion.timer`.

Cada 10 minutos, `deploy/actualizar.sh` hace lo siguiente:

| Ruta | Pasos | Si algo falla |
|---|---|---|
| Tradicional | Modo mantenimiento, cambio a la etiqueta, `composer install`, `npm ci && npm run build`, `config:clear`, migración con el rol dueño, `optimize`, `filament:optimize`, `queue:restart`, fin del mantenimiento | Antes de migrar: vuelve a la versión anterior y sale del mantenimiento. Durante o después de migrar: queda en mantenimiento para revisión manual |
| Docker | Construye la imagen de la etiqueta, migra con el rol dueño en un contenedor aparte y recién entonces reemplaza el contenedor, conservando el volumen de archivos | El contenedor anterior sigue en servicio y la carpeta vuelve a la versión anterior; la siguiente pasada lo reintenta |

Los mensajes quedan en el registro del sistema: `journalctl -u ciln-actualizacion`. Dos actualizaciones nunca corren a la vez.

**Costo de automatizar:** la clave del rol dueño queda guardada en el servidor (`/etc/ciln/migraciones.env`, legible solo por la cuenta de actualización). Sin automatización solo se usa al migrar. Si la DSTI prefiere no guardarla, se omite el temporizador y se actualiza a mano con los pasos de arriba.

### Primera cuenta de administración

`php artisan personal:crear-administrador <correo> "<nombre>"` crea la cuenta (o, si el correo ya tiene una, la reactiva y le agrega permisos sin cambiar su nombre) con acceso al panel y **todos** los permisos, incluido `usuarios.gestionar`, y le envía el enlace para crear su contraseña: nadie escribe ni ve la contraseña. Desde ahí, esa persona crea las demás cuentas en el panel (Seguridad → Usuarios del personal). Sirve también para recuperar el acceso si ninguna cuenta activa puede gestionar usuarios. Sale con `1` si el correo no es válido; el log registra el ID de la cuenta.

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
SESSION_ENCRYPT=true
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

`SESSION_ENCRYPT=true` cifra con `APP_KEY` el contenido de cada sesión guardada en la base de datos (TdR II.2). Es el valor por defecto de `config/session.php`, pero `.env.example` todavía trae `SESSION_ENCRYPT=false`: un `.env` copiado de él lo desactiva, así que en producción debe quedar en `true`. Al activarlo, las sesiones abiertas se cierran una vez (no se pueden descifrar) y cada persona vuelve a ingresar: conviene hacerlo fuera del horario de atención. Las cabeceras de seguridad (CSP, HSTS y demás) no necesitan variables: ver [`seguridad.md`](seguridad.md#cabeceras-de-seguridad).

`SESSION_PANEL_LIFETIME` son los minutos sin actividad tras los que se cierra la sesión del panel. Al publicar esta versión, las sesiones del panel abiertas se cierran en su siguiente pedido (aún no tienen la marca del ingreso del panel): conviene publicarla fuera del horario de atención. `PANEL_DOBLE_FACTOR_OBLIGATORIO` (por defecto `false`) exige que toda cuenta del panel configure su aplicación autenticadora antes de entrar; si se activa, avisar antes al personal y regenerar la caché de configuración (`php artisan config:cache`), porque el valor no cambia hasta entonces. El secreto del doble factor se cifra con `APP_KEY`: al rotar la clave, dejar la anterior en `APP_PREVIOUS_KEYS`; si se pierde, hay que reiniciar el segundo factor de cada cuenta (`php artisan auth:reiniciar-doble-factor <correo>`). Los QR de las actas finales impresas llevan un enlace firmado con `APP_KEY` que no caduca: al rotar la clave, la anterior debe quedar en `APP_PREVIOUS_KEYS` o esos QR dejarán de verificar. El enlace usa `APP_URL` como dirección pública, así que debe ser el dominio definitivo antes de imprimir actas.

`APP_URL` debe ser la dirección pública con `https`: los enlaces de verificación de correo y de recuperación de contraseña los arma el proceso de la cola, que no conoce la petición original. La caché usa el almacén por defecto (`database`); la portada pública solo guarda objetos de valor autorizados en `config/cache.php` (`serializable_classes`).

## Base de datos y proxy

- Dos roles de PostgreSQL: **dueño** (ejecuta `php artisan migrate`) y **aplicación** (lo usa Laravel en ejecución, sin ser dueño de tablas). Detalle en [`seguridad.md`](seguridad.md) y el SQL en [Roles de PostgreSQL](#roles-de-postgresql).
- Si PostgreSQL está en otro servidor, abrir el puerto 5432 solo entre ese servidor y el de la aplicación.
- Si hay un proxy inverso o balanceador delante, definir `TRUSTED_PROXIES` (`config/trustedproxy.php`) con sus direcciones o rangos separados por comas, o `*` para confiar en el proxy que llama a la aplicación. Sin esa variable, la auditoría registra la IP del proxy en lugar de la del usuario y los enlaces salen con `http`.

### Roles de PostgreSQL

Lo ejecuta un superusuario de PostgreSQL una sola vez, antes de la primera migración. Probado sobre PostgreSQL 18: migraciones con el dueño, sistema en marcha con el rol de aplicación (portada, inicio de sesión, cola de correos y respaldo con `pg_dump`), y el rol de aplicación sin poder escribir en `aud_registros`, desactivar disparadores, vaciar tablas ni crear objetos.

```sql
CREATE ROLE ciln_dueno LOGIN PASSWORD '<clave del dueño>';
CREATE ROLE ciln_app LOGIN PASSWORD '<clave de la aplicación>';
-- El dueño de la base puede crear la intercalación sin_tildes y la extensión btree_gist de las migraciones.
CREATE DATABASE ciln OWNER ciln_dueno;
\c ciln
GRANT USAGE ON SCHEMA public TO ciln_app;
-- Toda tabla y secuencia que creen las migraciones, también las de versiones futuras, queda disponible para la aplicación.
ALTER DEFAULT PRIVILEGES FOR ROLE ciln_dueno IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO ciln_app;
ALTER DEFAULT PRIVILEGES FOR ROLE ciln_dueno IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO ciln_app;
```

Después de la **primera** migración, una sola vez:

```sql
\c ciln
REVOKE INSERT, UPDATE, DELETE, TRUNCATE ON aud_registros FROM ciln_app;
```

La auditoría se sigue escribiendo: la función `aud_registrar()` es `SECURITY DEFINER` y escribe con los permisos del dueño. El `.env` usa `ciln_app`; solo el comando de migración usa `ciln_dueno`.

## Correo

Sin SMTP no llegan el enlace de la primera cuenta de administración, la verificación de cuenta de un estudiante nuevo, la recuperación de contraseña, el aviso de cambio de contraseña ni los avisos de trámite. Todos salen por la cola, así que también necesitan el worker.

El dominio `unasam.edu.pe` usa Microsoft 365 (su registro SPF incluye `spf.protection.outlook.com`). Hay dos formas de enviar, y las define la DSTI como administradora del tenant:

| Forma | Configuración | Cuándo |
|---|---|---|
| SMTP autenticado (SMTP AUTH) | `MAIL_HOST=smtp.office365.com`, `MAIL_PORT=587`, `MAIL_USERNAME` y `MAIL_PASSWORD` de un buzón del CILN, `MAIL_FROM_ADDRESS` igual a ese buzón. La DSTI debe habilitar «SMTP autenticado» para el buzón | Pruebas de despliegue. Microsoft anunció que la autenticación básica de SMTP AUTH se desactiva por defecto desde fines de diciembre de 2026 (el administrador puede reactivarla) y se retira en una fecha de 2027 por anunciar |
| Conector de retransmisión de Microsoft 365 por IP | `MAIL_HOST=<dominio>.mail.protection.outlook.com`, `MAIL_PORT=25`, sin usuario ni contraseña; la DSTI crea en Exchange Online un conector que acepta la IP pública del servidor | Producción: no depende de la contraseña de un buzón ni de la autenticación básica |

El remitente es la cuenta institucional del CILN `cid@unasam.edu.pe`, con el «SMTP autenticado» habilitado por la DSTI (Informe Técnico N.° 001-2026-YJBF, numeral 3.4). Su contraseña se escribe solo en el `.env` del servidor; nunca va en documentos ni en el repositorio.

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=cid@unasam.edu.pe
MAIL_PASSWORD=<contraseña de cid@unasam.edu.pe>
MAIL_FROM_ADDRESS=cid@unasam.edu.pe
MAIL_FROM_NAME="CILN UNASAM"
```

`MAIL_FROM_ADDRESS` debe ser el mismo buzón autenticado: Microsoft 365 rechaza otro remitente. Para probar el envío, pedir en `/recuperar-clave` la recuperación de contraseña de una cuenta de prueba con el worker en marcha.

Conviene que el remitente sea un buzón del CILN y no el de una persona: la contraseña queda en el `.env` del servidor y los estudiantes responden a esa dirección.

## Archivos subidos

Se guardan en el disco del propio servidor, en `storage/app/private` (disco `local`), fuera de `public/`: fotografías en `fotos/` y recibos de pago en `recibos/<año>/`. Solo se sirven por controlador, al dueño o al personal con permiso. Los PDF no se guardan: se generan al pedirlos. No se usa ningún proveedor de almacenamiento externo.

- `storage/app` debe sobrevivir a cada actualización: en la ruta tradicional queda en la carpeta del sitio; en Docker, en un volumen.
- Las copias de seguridad de la DSTI deben incluir `storage/app` junto con la base de datos (`pg_dump`). Si se pierde, se pierden las fotografías y los recibos.

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

- **Sistemas de evaluación**: al menos uno general (sin idioma ni nivel) con sus componentes (las cuatro habilidades como grupo Habilidades y los exámenes parcial y final como grupo Exámenes) y la nota mínima aprobatoria. Sin un sistema vigente, el docente no puede registrar notas y el acta no se cierra ("pide a coordinación que lo registre").
- **Medios de pago** y **fuentes TUPA/TUSNE**: vienen precargados; se desactivan o renombran según el CILN.

## Producción con Docker

Alternativa a los [pasos tradicionales](#pasos) si la DSTI prefiere contenedores. Usa la imagen descrita en [Entorno de demostración con Docker](#entorno-de-demostración-con-docker) (PHP-FPM, Nginx, worker y scheduler en un contenedor), con estas diferencias:

| Aspecto | Producción |
|---|---|
| Variables | Las de [Variables clave de producción](#variables-clave-de-producción), con `APP_ENV=production`, `LOG_CHANNEL=stderr` y **sin** `DEMO_SEED` |
| Roles de PostgreSQL | El contenedor usa el rol de **aplicación**. Las migraciones de cada versión corren antes, en un contenedor aparte con el rol **dueño**. Al arrancar, el contenedor vuelve a ejecutar `migrate --force`: sin migraciones pendientes no hace nada, y si quedara alguna, el rol de aplicación no puede aplicarla y el contenedor no arranca |
| Archivos subidos | Volumen en `/var/www/html/storage/app` |
| Red | Puerto publicado solo hacia el propio servidor; el proxy de la DSTI termina HTTPS |
| Respaldos | La imagen no trae `pg_dump`: el respaldo de la base se hace en el servidor de PostgreSQL |

```bash
docker build -t ciln:<versión> .

# Migraciones con el rol dueño (ciln-migraciones.env: mismas variables con DB_USERNAME/DB_PASSWORD del dueño)
docker run --rm --env-file ciln-migraciones.env --entrypoint php ciln:<versión> artisan migrate --force

docker run -d --name ciln --restart unless-stopped \
  --env-file ciln.env --stop-timeout 100 \
  -v ciln-archivos:/var/www/html/storage/app \
  -p 127.0.0.1:8080:8080 ciln:<versión>

# Primera instalación
docker exec ciln php artisan personal:crear-administrador <correo> "<nombre>"
```

Para actualizar: construir la nueva imagen, migrar con el rol dueño y reemplazar el contenedor conservando el volumen `ciln-archivos`. Tras cambiar una variable hay que recrear el contenedor (`docker rm -f ciln` y el mismo `docker run`), porque Docker lee `--env-file` al crearlo; un `docker restart` conserva las variables anteriores. El arranque vuelve a cachear la configuración.

## Entorno de demostración con Docker

El `Dockerfile` de la raíz arma una imagen que sirve para [producción](#producción-con-docker) y para un entorno de demostración o de pruebas (staging), por ejemplo en un VPS con Dokploy. Esta sección describe la imagen y su uso en demostración.

| Aspecto | Detalle |
|---|---|
| Contenido | PHP 8.5-FPM, Nginx, worker de colas y scheduler en un solo contenedor, supervisados por `supervisord` (`docker/app/`) |
| Puerto | `8080`; chequeo de salud en `GET /up` |
| Arranque | Espera a PostgreSQL, ejecuta `migrate --force`, `storage:link` y `optimize` (`docker/app/entrypoint.sh`) |
| Datos de demostración | Con `DEMO_SEED=true`, se cargan completos solo si la base no tiene usuarios; un nuevo despliegue no los repite. Si la base ya tiene datos, cada arranque completa la demostración sin borrar nada (`ComplementoDemoSeeder`: delegados, clases con ambos llamados, notas completas e historial del estudiante demo); cada parte revisa primero lo que ya existe, así que repetirla no cambia nada. Ambas cargas son una sola transacción y se niegan a correr en producción. Si la carga completa falla, no deja datos a medias, el contenedor no arranca y el siguiente arranque la intenta de nuevo. Si falla el complemento, la demostración queda como estaba, se muestra un aviso y el contenedor arranca igual. El complemento no hace nada si la base no tiene las cuentas de demostración (`*@ciln.test`). En una demostración cargada con la versión anterior, las clases pasadas ya quedaron como registro posterior y no se toman de nuevo: para ver la confirmación del delegado hay que cargarla desde cero |
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
