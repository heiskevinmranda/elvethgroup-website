#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Deploy the Elveth Group WordPress stack on the target VPS.
# Called by GitHub Actions over SSH (or manually as root).
#
#   APP_DIR=/opt/elvethgroup bash scripts/deploy.sh
# ---------------------------------------------------------------------------
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/elvethgroup}"
cd "$APP_DIR"

log() { printf '\033[1;34m[deploy]\033[0m %s\n' "$*"; }
die() { printf '\033[1;31m[deploy] ERROR:\033[0m %s\n' "$*" >&2; exit 1; }

command -v docker >/dev/null 2>&1 || die "docker is not installed on this host"
docker compose version >/dev/null 2>&1 || die "docker compose v2 is not available"

[ -f "$APP_DIR/.env" ] || die "missing $APP_DIR/.env (copy .env.example and fill it in)"
[ -f "$APP_DIR/docker-compose.yml" ] || die "missing $APP_DIR/docker-compose.yml"

# shellcheck disable=SC1091
set -a; . "$APP_DIR/.env"; set +a

mkdir -p "$UPLOADS_DIR" "$APP_DIR/backups"
chmod 755 "$UPLOADS_DIR"

log "Pulling image ${IMAGE_NAME}:${IMAGE_TAG}"
docker compose pull wordpress

log "Starting / updating containers"
docker compose up -d --remove-orphans

log "Waiting for the application to answer on http://127.0.0.1:${APP_PORT}"
for i in $(seq 1 60); do
    if curl -fsS "http://127.0.0.1:${APP_PORT}/" >/dev/null 2>&1; then
        log "Application is up"
        break
    fi
    if [ "$i" -eq 60 ]; then
        docker compose logs --tail 50 wordpress || true
        die "application did not become healthy in time"
    fi
    sleep 2
done

# First-run database import (only when the database is still empty).
if [ -f "$APP_DIR/backups/seed.sql" ]; then
    log "Importing database seed"
    bash "$APP_DIR/scripts/restore-db.sh" "$APP_DIR/backups/seed.sql"
    rm -f "$APP_DIR/backups/seed.sql"
fi

log "Syncing site URL options"
docker compose exec -T wordpress wp option update home "$SITE_URL" --allow-root >/dev/null 2>&1 || true
docker compose exec -T wordpress wp option update siteurl "$SITE_URL" --allow-root >/dev/null 2>&1 || true

log "Flushing caches"
docker compose exec -T wordpress wp cache flush --allow-root >/dev/null 2>&1 || true
docker compose exec -T wordpress wp rewrite flush --allow-root >/dev/null 2>&1 || true

log "Current status"
docker compose ps

log "Deploy finished"
