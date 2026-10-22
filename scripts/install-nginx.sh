#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Install / refresh the host nginx virtual host for elvethgroup.co.tz.
# The WordPress container is published on 127.0.0.1:${APP_PORT}.
#
#   bash scripts/install-nginx.sh
# ---------------------------------------------------------------------------
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/elvethgroup}"
DOMAIN="${DOMAIN:-elvethgroup.co.tz}"
NGINX_SRC="$APP_DIR/deploy/nginx/$DOMAIN.conf"
NGINX_AVAIL="/etc/nginx/sites-available/$DOMAIN.conf"
NGINX_ENABLED="/etc/nginx/sites-enabled/$DOMAIN.conf"

command -v nginx >/dev/null 2>&1 || { echo "nginx is not installed on this host" >&2; exit 1; }
[ -f "$NGINX_SRC" ] || { echo "missing $NGINX_SRC" >&2; exit 1; }

cd "$APP_DIR"
# shellcheck disable=SC1091
set -a; . "$APP_DIR/.env"; set +a

sed "s/__APP_PORT__/${APP_PORT:-8095}/g" "$NGINX_SRC" > "$NGINX_AVAIL"
chmod 644 "$NGINX_AVAIL"
ln -sfn "$NGINX_AVAIL" "$NGINX_ENABLED"
mkdir -p /var/www/certbot

# drop an older hand-installed copy (no .conf suffix) that would duplicate server_name
rm -f "/etc/nginx/sites-enabled/$DOMAIN" "/etc/nginx/sites-available/$DOMAIN"

# make sure the default site does not hijack the domain
if [ -L /etc/nginx/sites-enabled/default ] || [ -f /etc/nginx/sites-enabled/default ]; then
    rm -f /etc/nginx/sites-enabled/default
fi

nginx -t
systemctl reload nginx || systemctl restart nginx

echo "[nginx] virtual host installed for $DOMAIN -> 127.0.0.1:${APP_PORT}"
