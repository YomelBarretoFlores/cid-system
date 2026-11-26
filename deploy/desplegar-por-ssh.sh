#!/usr/bin/env bash
# Builds a published release (a vX.Y.Z tag on origin/main) on this computer and uploads it over SSH to a server
# that keeps a plain copy of the app, without Git or GitHub access on the server.
# Usage: deploy/desplegar-por-ssh.sh vX.Y.Z [--simular | --solo-construir] [--sin-respaldo-bd]
# Configuration: deploy/despliegue-ssh.conf (see despliegue-ssh.conf.ejemplo).
set -Eeuo pipefail

cd "$(dirname "$0")/.."
RAIZ="$PWD"
CONFIGURACION="${CILN_CONFIGURACION_SSH:-$RAIZ/deploy/despliegue-ssh.conf}"
EXCLUSIONES="$RAIZ/deploy/despliegue-ssh.excluir"

registrar() { printf '%s [ciln-despliegue] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }
fallar() { registrar "ERROR: $*"; exit 1; }

VERSION="${1:-}"
shift || true
SIMULAR=false
SOLO_CONSTRUIR=false
SIN_RESPALDO_BD=false
for opcion in "$@"; do
    case "$opcion" in
        --simular) SIMULAR=true ;;
        --solo-construir) SOLO_CONSTRUIR=true ;;
        --sin-respaldo-bd) SIN_RESPALDO_BD=true ;;
        *) fallar "opción desconocida: $opcion" ;;
    esac
done
[[ "$VERSION" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] || fallar "uso: deploy/desplegar-por-ssh.sh vX.Y.Z [--simular | --solo-construir] [--sin-respaldo-bd]"

# Only a reviewed, published release reaches the server, never the working tree.
git fetch --quiet --tags origin main
git rev-parse -q --verify "refs/tags/$VERSION" >/dev/null || fallar "la etiqueta $VERSION no existe"
git merge-base --is-ancestor "${VERSION}^{commit}" origin/main || fallar "$VERSION no está en main: solo se despliegan versiones publicadas"

PAQUETE="$(mktemp -d)"
trap 'rm -rf "$PAQUETE"' EXIT
registrar "armando $VERSION"
git archive "$VERSION" | tar -x -C "$PAQUETE"
(
    cd "$PAQUETE"
    # Scripts run on the server (package:discover), where the real .env lives.
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --quiet
    npm ci --silent
    npm run build --silent
    rm -rf node_modules
    printf '%s\n' "$VERSION" > VERSION_DESPLEGADA
)
PHP_MINIMO="$(php -r '$r = json_decode(file_get_contents($argv[1]), true)["require"]["php"] ?? "8.5"; echo preg_replace("/[^0-9.].*$/", "", ltrim($r, "^~>= "));' "$PAQUETE/composer.json")"

if $SOLO_CONSTRUIR; then
    trap - EXIT
    registrar "paquete listo en $PAQUETE (requiere PHP $PHP_MINIMO o superior); no se subió nada"
    exit 0
fi

[[ -r "$CONFIGURACION" ]] || fallar "no se puede leer $CONFIGURACION (copia deploy/despliegue-ssh.conf.ejemplo)"
# shellcheck source=/dev/null
source "$CONFIGURACION"
: "${CILN_SSH_DESTINO:?Falta CILN_SSH_DESTINO en la configuración}"
: "${CILN_RUTA_REMOTA:?Falta CILN_RUTA_REMOTA en la configuración}"
: "${CILN_SSH_PUERTO:=22}"
: "${CILN_RESPALDOS_REMOTOS:=respaldos-ciln}"
: "${CILN_MIGRACIONES_ENV_REMOTO:=}"
: "${CILN_RESPALDOS_A_CONSERVAR:=5}"
[[ "$CILN_RESPALDOS_A_CONSERVAR" =~ ^[1-9][0-9]*$ ]] || fallar "CILN_RESPALDOS_A_CONSERVAR debe ser un número entero de 1 en adelante"

OPCIONES_SSH=(-p "$CILN_SSH_PUERTO" -o BatchMode=yes -o ConnectTimeout=15)
if [[ -n "${CILN_SSH_LLAVE:-}" ]]; then
    OPCIONES_SSH+=(-i "${CILN_SSH_LLAVE/#\~/$HOME}" -o IdentitiesOnly=yes)
fi
remoto() { ssh "${OPCIONES_SSH[@]}" "$CILN_SSH_DESTINO" "$@"; }
RUTA_Q="$(printf '%q' "$CILN_RUTA_REMOTA")"

registrar "comprobando el servidor"
remoto "bash -s -- $RUTA_Q $(printf '%q' "$PHP_MINIMO")" <<'REMOTO'
set -euo pipefail
ruta="$1"; minimo="$2"
[[ -d "$ruta" && -w "$ruta" ]] || { echo "ERROR: no existe $ruta o no se puede escribir en ella"; exit 1; }
[[ -f "$ruta/.env" ]] || { echo "ERROR: falta $ruta/.env"; exit 1; }
command -v rsync >/dev/null || { echo "ERROR: el servidor no tiene rsync"; exit 1; }
php -r 'exit(version_compare(PHP_VERSION, $argv[1], ">=") ? 0 : 1);' "$minimo" || { echo "ERROR: el servidor tiene PHP $(php -r 'echo PHP_VERSION;') y la versión requiere $minimo o superior"; exit 1; }
echo "PHP del servidor: $(php -r 'echo PHP_VERSION;')"
echo "versión instalada: $(cat "$ruta/VERSION_DESPLEGADA" 2>/dev/null || echo 'desconocida')"
command -v pg_dump >/dev/null && echo "pg_dump: disponible" || echo "pg_dump: no disponible"
REMOTO

RSYNC=(rsync -az --delete --exclude-from="$EXCLUSIONES" -e "ssh ${OPCIONES_SSH[*]}")

if $SIMULAR; then
    registrar "simulación: archivos que se borrarían en el servidor (no se cambia nada)"
    "${RSYNC[@]}" --dry-run --itemize-changes "$PAQUETE/" "$CILN_SSH_DESTINO:$CILN_RUTA_REMOTA/" | grep '^\*deleting' | head -50 || true
    registrar "simulación terminada: no se cambió nada"
    exit 0
fi

MARCA="$(date '+%Y%m%d-%H%M%S')"
EN_MANTENIMIENTO=false
# A Ctrl-C never restores anything on its own: it reports where the site was left and how to recover.
interrumpido() {
    trap - INT TERM
    if $EN_MANTENIMIENTO; then
        registrar "INTERRUMPIDO: el sitio sigue en mantenimiento y el código puede haber quedado a medias. Repite el despliegue de $VERSION para completarlo; si la base aún no se migró, también puedes restaurar ~/$CILN_RESPALDOS_REMOTOS/codigo-$MARCA.tar.gz y ejecutar 'php artisan up'"
    else
        registrar "INTERRUMPIDO antes de poner el sitio en mantenimiento: el sitio sigue funcionando con la versión anterior"
    fi
    exit 130
}
trap interrumpido INT TERM

registrar "respaldando el código y la base de datos actuales en ~/$CILN_RESPALDOS_REMOTOS (se conservan los $CILN_RESPALDOS_A_CONSERVAR más recientes)"
remoto "bash -s -- $RUTA_Q $(printf '%q' "$CILN_RESPALDOS_REMOTOS") $MARCA $SIN_RESPALDO_BD $CILN_RESPALDOS_A_CONSERVAR" <<'REMOTO'
set -euo pipefail
ruta="$1"; destino="$HOME/$2"; marca="$3"; sin_bd="$4"; conservar="$5"
umask 077
mkdir -p "$destino"
# The code backup includes the .env and vendor, so the previous version can be restored as it was.
tar -czf "$destino/codigo-$marca.tar.gz" --exclude=./storage -C "$ruta" .
if [[ "$sin_bd" != "true" ]]; then
    command -v pg_dump >/dev/null || { echo "ERROR: no hay pg_dump para respaldar la base; respáldala aparte y repite con --sin-respaldo-bd"; exit 1; }
    ( cd "$ruta" && php artisan backup:run --only-db --disable-notifications )
fi
# Code backup names sort by date; the database backups follow the app's own backup cleanup.
find "$destino" -maxdepth 1 -name 'codigo-*.tar.gz' -print | sort -r | tail -n +"$((conservar + 1))" | while IFS= read -r anterior; do
    rm -f -- "$anterior"
done
REMOTO

registrar "subiendo $VERSION (el sitio queda en mantenimiento)"
remoto "cd $RUTA_Q && php artisan down --retry=60"
EN_MANTENIMIENTO=true
restaurar_codigo() {
    registrar "falló antes de migrar: se restaura el código anterior y se sale del mantenimiento"
    remoto "bash -s -- $RUTA_Q $(printf '%q' "$CILN_RESPALDOS_REMOTOS") $MARCA" <<'REMOTO' || registrar "ADVERTENCIA: no se pudo restaurar; revisa el servidor a mano"
set -euo pipefail
ruta="$1"; respaldo="$HOME/$2/codigo-$3.tar.gz"
# Extract next to the backups, not in /tmp, and mirror only a complete copy: a partial one would delete the live app.
temporal="$(mktemp -d "$HOME/$2/restaurar-XXXXXX")"
trap 'rm -rf "$temporal"' EXIT
if ! tar -xzf "$respaldo" -C "$temporal" || [[ ! -f "$temporal/artisan" || ! -f "$temporal/.env" || ! -d "$temporal/vendor" ]]; then
    echo "ERROR: la copia de $respaldo está incompleta; no se tocó la carpeta y el sitio sigue en mantenimiento"
    exit 1
fi
rsync -a --delete --exclude=/storage/ "$temporal/" "$ruta/"
cd "$ruta"
php artisan optimize || true
php artisan up
REMOTO
    exit 1
}

"${RSYNC[@]}" "$PAQUETE/" "$CILN_SSH_DESTINO:$CILN_RUTA_REMOTA/" || restaurar_codigo

# Exit codes: 2 before migrating (code is restored), 3 while migrating, 4 after migrating (both stay in maintenance);
# 255 is ssh itself losing the connection, so the step reached is unknown and nothing is restored.
set +e
remoto "bash -s -- $RUTA_Q $(printf '%q' "$CILN_MIGRACIONES_ENV_REMOTO")" <<'REMOTO'
set -uo pipefail
ruta="$1"; migraciones="$2"
cd "$ruta" || exit 2
php artisan package:discover --ansi >/dev/null || exit 2
php artisan config:clear >/dev/null || exit 2
if [[ -n "$migraciones" ]]; then
    ( set -a; source "$migraciones"; set +a; php artisan migrate --force ) || exit 3
else
    php artisan migrate --force || exit 3
fi
[[ -e public/storage ]] || php artisan storage:link || exit 4
php artisan optimize || exit 4
php artisan filament:optimize || exit 4
php artisan queue:restart || exit 4
php artisan up || exit 4
REMOTO
resultado=$?
set -e

case "$resultado" in
    0) registrar "versión $VERSION instalada" ;;
    2) restaurar_codigo ;;
    255) fallar "se perdió la conexión SSH durante la instalación y no se sabe en qué paso quedó: el sitio queda en mantenimiento; revisa el servidor y repite el despliegue de $VERSION (respaldo con marca $MARCA)" ;;
    3) fallar "falló la migración: el sitio queda en mantenimiento; el respaldo está en ~/$CILN_RESPALDOS_REMOTOS (marca $MARCA)" ;;
    *) fallar "falló un paso después de migrar: el sitio queda en mantenimiento para revisión manual (marca $MARCA)" ;;
esac
