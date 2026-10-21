#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Import a WordPress SQL dump into the Docker MySQL container.
#
#   bash scripts/restore-db.sh /path/to/dump.sql[.gz]
# ---------------------------------------------------------------------------
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/elvethgroup}"
DUMP="${1:-}"

[ -n "$DUMP" ] || { echo "usage: restore-db.sh <dump.sql|dump.sql.gz>" >&2; exit 1; }
[ -f "$DUMP" ] || { echo "dump not found: $DUMP" >&2; exit 1; }

cd "$APP_DIR"
# shellcheck disable=SC1091
set -a; . "$APP_DIR/.env"; set +a

echo "[restore] importing $DUMP into database ${DB_NAME}"

case "$DUMP" in
    *.gz) gzip -dc "$DUMP" | docker compose exec -T db mysql -uroot -p"$DB_ROOT_PASSWORD" "$DB_NAME" ;;
    *)    docker compose exec -T db mysql -uroot -p"$DB_ROOT_PASSWORD" "$DB_NAME" < "$DUMP" ;;
esac

echo "[restore] done"
