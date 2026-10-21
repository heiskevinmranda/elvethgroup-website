# Elveth Group Website

Production repository for the Elveth Group WordPress site (**https://elvethgroup.co.tz**).

The site runs on the Elveth ERP VPS (`72.62.150.5`) inside Docker:

| Service | Image | Role |
|---|---|---|
| `elveth-db` | `mysql:8.0` | WordPress database (persistent volume `elveth_db_data`) |
| `elveth-wp` | `ghcr.io/heiskevinmranda/elvethgroup-website` | WordPress 7.1.3 on Apache + PHP 8.3 (published on `127.0.0.1:8080`) |
| host nginx | `nginx` | TLS termination + reverse proxy for `elvethgroup.co.tz` |

---

## Repository layout

```
docker/
  Dockerfile              # WP 7.1.3 + PHP 8.3 + extensions + WP-CLI
  entrypoint.sh           # renders wp-config.php from env vars
  php.ini                 # upload/memory limits, opcache
  apache/000-default.conf # vhost with X-Forwarded-Proto handling
deploy/nginx/
  elvethgroup.co.tz.conf  # host nginx virtual host (HTTP + ACME challenge)
scripts/
  deploy.sh               # pull image, recreate containers, flush caches
  install-nginx.sh        # install/refresh the nginx vhost
  restore-db.sh           # import a SQL dump into MySQL
  backup.sh               # daily DB + uploads backup
.github/workflows/
  deploy.yml              # build -> push to GHCR -> deploy over SSH
wp-content/               # themes, plugins, mu-plugins (media lives in uploads/)
```

> WordPress **core** is provided by the Docker image and is never committed with
> local modifications. `wp-content/` (themes, plugins, mu-plugins) is deployed
> from this repository. Media (`wp-content/uploads`) is synced to the server
> and stored in `/opt/elvethgroup/uploads` (bind mount) so it survives rebuilds.

---

## Local development

```bash
cp .env.example .env          # fill in real values
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

Local build overrides live in `docker-compose.local.yml`.

---

## Server layout

```
/opt/elvethgroup/
├── .env                   # secrets (chmod 600, never committed)
├── docker-compose.yml
├── docker/ scripts/ deploy/
├── uploads/               # wp-content/uploads (bind mount)
└── backups/               # db-*.sql.gz + uploads-*.tar.gz
```

---

## CI/CD

`/.github/workflows/deploy.yml` runs on every push to `main`:

1. Lint changed PHP files.
2. Build the Docker image and push it to GHCR (`:<sha>` and `:latest`).
3. Over SSH: rsync the stack configuration, the media folder and `.env`.
4. `docker compose pull` + `docker compose up -d`, flush WP caches, sync URLs.
5. Install/refresh the nginx virtual host and smoke-test the public URL.

### Required repository secrets

| Secret | Value |
|---|---|
| `VPS_HOST` | `72.62.150.5` |
| `VPS_USER` | `root` |
| `VPS_SSH_KEY` | private key whose public part is in `/root/.ssh/authorized_keys` on the VPS |
| `SERVER_ENV` | contents of `/opt/elvethgroup/.env` (DB credentials + WP salts) |

Optional repository variable: `SITE_URL` (default `https://elvethgroup.co.tz`).

> The VPS only accepts **public key** authentication, so `VPS_SSH_KEY` is mandatory.

---

## Operations

```bash
# status
cd /opt/elvethgroup && docker compose ps

# logs
docker compose logs -f --tail 100 wordpress

# deploy manually
bash scripts/deploy.sh

# backup
bash scripts/backup.sh

# restore database
bash scripts/restore-db.sh /opt/elvethgroup/backups/db-YYYYmmdd-HHMMSS.sql.gz

# wp-cli
docker compose exec wordpress wp <command> --allow-root
```

---

## DNS

| Record | Value |
|---|---|
| `elvethgroup.co.tz` | `A` -> `72.62.150.5` |
| `www.elvethgroup.co.tz` | `CNAME` -> `elvethgroup.co.tz` |
| `kpi.elvethgroup.co.tz` | `A` -> `72.62.150.5` (KPI portal) |
