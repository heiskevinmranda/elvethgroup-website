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

NGINX_SSL_SRC="$APP_DIR/deploy/nginx/$DOMAIN.ssl.conf"
CERT_DIR="/etc/letsencrypt/live/$DOMAIN"

install_vhost() {
    sed "s/__APP_PORT__/${APP_PORT:-8095}/g" "$1" > "$NGINX_AVAIL"
    chmod 644 "$NGINX_AVAIL"
    ln -sfn "$NGINX_AVAIL" "$NGINX_ENABLED"
    nginx -t
    systemctl reload nginx || systemctl restart nginx
}

mkdir -p /var/www/certbot

# drop an older hand-installed copy (no .conf suffix) that would duplicate server_name
rm -f "/etc/nginx/sites-enabled/$DOMAIN" "/etc/nginx/sites-available/$DOMAIN"

# make sure the default site does not hijack the domain
if [ -L /etc/nginx/sites-enabled/default ] || [ -f /etc/nginx/sites-enabled/default ]; then
    rm -f /etc/nginx/sites-enabled/default
fi

# Obtain the certificate over HTTP-01 (webroot) the first time. The vhost is
# managed from git, so certbot must not edit it (no --nginx installer).
if [ ! -f "$CERT_DIR/fullchain.pem" ]; then
    install_vhost "$NGINX_SRC"
    certbot certonly --webroot -w /var/www/certbot --non-interactive --agree-tos \
        --cert-name "$DOMAIN" -d "$DOMAIN" -d "www.$DOMAIN" \
    || certbot certonly --webroot -w /var/www/certbot --non-interactive --agree-tos \
        --cert-name "$DOMAIN" -d "$DOMAIN" \
    || echo "[nginx] certificate request failed - staying on HTTP" >&2
fi

if [ -f "$CERT_DIR/fullchain.pem" ] && [ -f "$NGINX_SSL_SRC" ]; then
    install_vhost "$NGINX_SSL_SRC"
    # renewals reuse the webroot; reload nginx so it picks up renewed certs
    mkdir -p /etc/letsencrypt/renewal-hooks/deploy
    printf '#!/bin/sh\nsystemctl reload nginx\n' > /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
    chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
    echo "[nginx] HTTPS virtual host installed for $DOMAIN -> 127.0.0.1:${APP_PORT}"
else
    install_vhost "$NGINX_SRC"
    echo "[nginx] HTTP virtual host installed for $DOMAIN -> 127.0.0.1:${APP_PORT}"
fi
