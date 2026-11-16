#!/usr/bin/env bash
# Installs the newest release tag (vX.Y.Z) published on origin/main. A plain push never reaches production.
# Usage: actualizar.sh [--simular]   Configuration: /etc/ciln/actualizacion.conf (see actualizacion.conf.ejemplo).
set -Eeuo pipefail

CONFIGURACION="${CILN_CONFIGURACION:-/etc/ciln/actualizacion.conf}"
SIMULAR=false
[[ "${1:-}" == "--simular" ]] && SIMULAR=true

registrar() { printf '%s [ciln-actualizacion] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"; }
fallar() { registrar "ERROR: $*"; exit 1; }

# The checkout below replaces this file, and bash reads a script while it runs, so run from a private copy.
if [[ -z "${CILN_COPIA:-}" ]]; then
    copia="$(mktemp)"
    cp "$0" "$copia"
    CILN_COPIA="$copia" exec bash "$copia" "$@"
fi
trap 'rm -f "$CILN_COPIA"' EXIT

[[ -r "$CONFIGURACION" ]] || fallar "no se puede leer $CONFIGURACION"
# shellcheck source=/dev/null
source "$CONFIGURACION"

: "${CILN_RUTA:?Falta CILN_RUTA en la configuración}"
: "${CILN_MODO:=tradicional}"
: "${CILN_MIGRACIONES_ENV:?Falta CILN_MIGRACIONES_ENV en la configuración}"
[[ "$CILN_MODO" == "tradicional" || "$CILN_MODO" == "docker" ]] || fallar "CILN_MODO debe ser tradicional o docker"
[[ -r "$CILN_MIGRACIONES_ENV" ]] || fallar "no se puede leer $CILN_MIGRACIONES_ENV (credenciales del rol dueño)"

cd "$CILN_RUTA"

# One update at a time, even if the timer fires while a slow build is still running.
exec 9>"${CILN_CANDADO:-/tmp/ciln-actualizacion.lock}"
flock -n 9 || { registrar "otra actualización está en curso"; exit 0; }

git fetch --quiet --prune --tags --force origin
# Only a tag on the reviewed main branch is installed; a tag pushed from any other branch is ignored.
ultima=""
for etiqueta in $(git tag --list 'v[0-9]*.[0-9]*.[0-9]*' --sort=-v:refname); do
    if git merge-base --is-ancestor "${etiqueta}^{commit}" origin/main; then
        ultima="$etiqueta"
        break
    fi
done
[[ -n "$ultima" ]] || { registrar "no hay versiones publicadas en main"; exit 0; }
actual="$(git describe --tags --exact-match HEAD 2>/dev/null || echo 'ninguna')"

if [[ "$ultima" == "$actual" ]]; then
    exit 0
fi

registrar "versión instalada: $actual; versión nueva: $ultima ($CILN_MODO)"
if $SIMULAR; then
    registrar "simulación: no se cambió nada"
    exit 0
fi

anterior="$(git rev-parse HEAD)"

migrar_con_dueno() {
    (
        set -a
        # shellcheck source=/dev/null
        source "$CILN_MIGRACIONES_ENV"
        set +a
        "$@"
    )
}

actualizar_tradicional() {
    local fase="codigo"
    restaurar() {
        if [[ "$fase" == "codigo" ]]; then
            registrar "falló antes de migrar: se vuelve a la versión anterior"
            # Every step is attempted so the site always comes back up; each failure is logged.
            set +e
            trap - ERR
            local paso
            for paso in "git checkout --quiet --detach $anterior" \
                "composer install --no-dev --optimize-autoloader --no-interaction --quiet" \
                "npm ci --silent" "npm run build --silent" \
                "php artisan optimize" "php artisan filament:optimize" "php artisan up"; do
                ${paso} || registrar "ADVERTENCIA: falló «${paso}» al volver a la versión anterior"
            done
        else
            registrar "falló durante o después de migrar: el sistema queda en mantenimiento y requiere revisión manual"
        fi
        # The update failed either way: stop here, never continue with the remaining steps.
        exit 1
    }
    trap 'restaurar' ERR

    php artisan down --retry=60
    git checkout --quiet --detach "$ultima"
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
    # Separate lines: set -e ignores a failure on the left of &&.
    npm ci --silent
    npm run build --silent
    php artisan config:clear
    fase="migracion"
    migrar_con_dueno php artisan migrate --force
    php artisan optimize
    php artisan filament:optimize
    php artisan queue:restart
    php artisan up
    trap - ERR
}

actualizar_docker() {
    : "${CILN_DOCKER_ENV:?Falta CILN_DOCKER_ENV en la configuración}"
    local imagen="ciln:${ultima}"

    # Nothing running changes until the new image is built and migrated, so a failure here leaves the old container serving.
    git checkout --quiet --detach "$ultima"
    if ! docker build --quiet -t "$imagen" . >/dev/null; then
        git checkout --quiet --detach "$anterior"
        fallar "no se pudo construir $imagen; sigue en servicio la versión $actual"
    fi
    if ! docker run --rm --env-file "$CILN_MIGRACIONES_ENV" --entrypoint php "$imagen" artisan migrate --force; then
        git checkout --quiet --detach "$anterior"
        fallar "falló la migración de $ultima; sigue en servicio la versión $actual y requiere revisión manual"
    fi

    docker rm -f "${CILN_DOCKER_NOMBRE:-ciln}" >/dev/null 2>&1 || true
    docker run -d --name "${CILN_DOCKER_NOMBRE:-ciln}" --restart unless-stopped \
        --env-file "$CILN_DOCKER_ENV" --stop-timeout 100 \
        -v "${CILN_DOCKER_VOLUMEN:-ciln-archivos}:/var/www/html/storage/app" \
        -p "${CILN_DOCKER_PUERTO:-127.0.0.1:8080}:8080" "$imagen" >/dev/null
}

"actualizar_${CILN_MODO}"
registrar "versión $ultima instalada"
