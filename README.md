# HestiaCP Panel

Laravel control panel for provisioning and deploying sites onto one or more
HestiaCP servers over SSH.

## What it does

- **Multiple HestiaCP servers** — register any number of servers (`/servers`),
  each with its own host, API credentials and SSH private key. Every site is
  bound to one server.
- **Site provisioning** — creates the Hestia user (or attaches the site to an
  existing one), the web domain, the MySQL database and the PHP pool, then
  clones the repository into the web root.
- **Git deploy keys** — generates a per-domain SSH keypair on the target server
  and exposes the public key so you can add it as a deploy key on
  GitHub/GitLab. Remote branches are listed straight from the repo before the
  site is created.
- **Deploy webhooks** — each site gets a rotatable token and a
  `POST /webhook/{site}/{token}` endpoint that runs `git fetch` +
  `git reset --hard origin/<branch>` on the server.
- **Branch switching** — change the deployed branch from the UI and redeploy.
- **SSL** — Let's Encrypt issuance on provision, plus manual renewal.
- **Suspend / unsuspend** and **retry** for failed provisioning runs.
- **Framework presets** — OpenCart (Octopus and default), WordPress and
  Laravel, each with its own config templates in `storage/config-templates`.
- **Backups** — queued database and images backups per site, downloadable, with
  automatic weekly pruning (`app:prune-old-backups`, scheduled in `routes/console.php`).
- **Live logs** — provisioning log streamed into the site page, plus tailing the
  domain's nginx/apache access and error logs.

## Requirements

- PHP 8.2+
- MariaDB/MySQL
- Redis (queues + Horizon)
- A HestiaCP server reachable over SSH with an `admin` API user

## Setup

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
```

Register the first user through `/register`, then promote them:

```bash
php artisan hestia:make-admin you@example.com
```

Run the queue worker — provisioning and backups both depend on it:

```bash
php artisan horizon
```

### Docker

```bash
docker compose up -d
```

Brings up `hestiacp_web` (PHP 8.4 + Apache), `hestiacp_db` (MariaDB) and
`hestiacp_redis`. Point `DB_HOST=hestiacp_db` and `REDIS_HOST=hestiacp_redis`
in `.env`.

## Configuration

`config/hestia.php`, driven by two env keys:

| Key | Default | What |
| --- | --- | --- |
| `HESTIA_ALLOWED_EMAIL_DOMAINS` | empty | Comma-separated domains allowed to self-register. Empty = any domain. |
| `HESTIA_WEBHOOK_HEADER_TOKEN` | `HESTIACP` | Shared secret expected in the `X-Gitlab-Token` header on deploy webhooks. Change it. |

Per-server settings (panel URL, API keys, SSH host/user/key, GitLab token) live
in the `hestia_servers` table and are edited at `/servers`, not in `.env`.

## Roles

Two roles, seeded by `RolesAndPermissionsSeeder`:

- `admin` — everything, including deleting sites, switching branches, renewing
  SSL and managing servers.
- `maintainer` — view and create sites.

There is no in-app user management screen; assign roles with
`hestia:make-admin` or via tinker.

## Webhook

Configure the repository webhook to `POST` to:

```
https://<panel>/webhook/{site_id}/{webhook_token}
```

with the header `X-Gitlab-Token: <HESTIA_WEBHOOK_HEADER_TOKEN>`. Rotate the
per-site URL token from the site page.

The URL token is the per-site secret; the header token is shared by every site,
so a leak of the header alone is not enough to trigger a deploy. Both are
compared with `hash_equals`.

## Layout

| Path | What |
| --- | --- |
| `app/Services/ProvisioningService.php` | All SSH/Hestia API work: users, domains, databases, git, SSL, backups |
| `app/Http/Controllers/SiteController.php` | Site CRUD, webhook, logs, backups, branch switching |
| `app/Http/Controllers/HestiaServerController.php` | Server CRUD |
| `app/Jobs/` | Queued database and images backups |
| `storage/config-templates/` | Per-framework config files written into new sites |
