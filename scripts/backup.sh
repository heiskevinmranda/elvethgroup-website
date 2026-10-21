#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Backup the WordPress database and the uploads folder.
#
#   APP_DIR=/opt/elvethgroup bash scripts/backup.sh
# ---------------------------------------------------------------------------
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/elvethgroup}"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="${BACKUP_DIR:-$APP_DIR/backups}"

cd "$APP_DIR"
# shellcheck disable=SC1091
set -a; . "$APP_DIR/.env"; set +a

mkdir -p "$BACKUP_DIR"

echo "[backup] dumping database ${DB_NAME}"
docker compose exec -T db mysqldump -uroot -p"$DB_ROOT_PASSWORD" \
    --single-transaction --quick --routines --events --default-character-set=utf8mb4 \
    "$DB_NAME" | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz"

echo "[backup] archiving uploads"
tar -czf "$BACKUP_DIR/uploads-$STAMP.tar.gz" -C "${UPLOADS_DIR%/}" . 2>/dev/null || true

# keep the last 7 days of backups
find "$BACKUP_DIR" -name 'db-*.sql.gz' -mtime +7 -delete 2>/dev/null || true
find "$BACKUP_DIR" -name 'uploads-*.tar.gz' -mtime +7 -delete 2>/dev/null || true

echo "[backup] finished: $BACKUP_DIR/db-$STAMP.sql.gz"
