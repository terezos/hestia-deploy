# HestiaCP Provisioning Panel

Laravel 12 app that provisions and deploys websites onto one or more HestiaCP servers over SSH.
Standalone repo, extracted from an internal monolith.

## Stack

Laravel 12 · Jetstream/Livewire/Fortify (Blade) · Horizon + Redis queues · MariaDB ·
spatie/laravel-permission · phpseclib (SSH) · Tailwind + Vite.
Docker Compose gives `hestiacp_web` / `hestiacp_db` / `hestiacp_redis`.

## What it does

- **Multi-server** — HestiaCP servers live in `hestia_servers`, managed at `/servers`.
  Panel URL, API keys, SSH host/user/private key and GitLab token are per-server DB
  columns (encrypted casts), *not* env vars. Every site belongs to one server.
- **Provisioning** — creates the Hestia user (or attaches to an existing one), web domain,
  MySQL database and PHP pool, then clones the repo into the web root.
- **Git** — generates a per-domain SSH deploy keypair on the target server and exposes the
  public key; lists remote branches before site creation; supports branch switch + redeploy.
- **Webhooks** — `POST /webhook/{site}/{token}` runs `git fetch` + `git reset --hard origin/<branch>`.
- **SSL** (Let's Encrypt issue + renew), suspend/unsuspend, retry-failed.
- **Backups** — queued database and images backups per site, downloadable, pruned weekly
  by `app:prune-old-backups`.
- **Frameworks** — opencart_octopus, opencart_default, wordpress, laravel. Templates in
  `storage/config-templates/{opencart,wordpress,laravel}/`.

## Where things live

| Path | What |
| --- | --- |
| `app/Services/ProvisioningService.php` | ~1200 lines. All SSH + Hestia API work. Heart of the app. |
| `app/Http/Controllers/SiteController.php` | Site CRUD, webhook, logs, backups, branch switching |
| `app/Http/Controllers/HestiaServerController.php` | Server CRUD |
| `app/Jobs/` | `DatabaseBackupJob`, `ImagesBackupJob` |
| `app/Console/Commands/` | `MakeAdmin`, `PruneOldBackups` |
| `app/Models/` | `Site`, `HestiaServer`, `SiteBackup`, `User` |
| `config/hestia.php` | App-specific config |
| `routes/web.php` | All routes, incl. the unauthenticated webhook |

## Config

- `HESTIA_ALLOWED_EMAIL_DOMAINS` — comma-separated domains allowed to self-register
  (empty = any domain). Enforced in `app/Actions/Fortify/CreateNewUser.php`.
- `HESTIA_WEBHOOK_HEADER_TOKEN` — shared secret expected in the `X-Gitlab-Token` header
  (default `HESTIACP`). **Still on the default — change it before this serves public traffic.**

## Roles

`admin` and `maintainer`, seeded by `RolesAndPermissionsSeeder`. `role:admin` guards site
deletion, branch switching, SSL renewal and all `/servers` routes. There is **no user
management UI** — promote with `php artisan hestia:make-admin <email>`.

## Running it

```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate && php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan horizon    # required — provisioning and backups are queued
php artisan test
```

## Gotchas

- Provisioning is dispatched `afterResponse()`, backups run via Horizon. Nothing happens
  without a queue worker.
- `2026_07_07_204632_add_laravel_to_sites_framework_enum.php` uses raw
  `ALTER TABLE ... MODIFY ENUM`, guarded to mysql/mariadb so the sqlite test suite runs.
  Any new ENUM migration needs the same guard.
- Site provisioning logs are files on the local disk (`storage/app/private/logs/{domain}.log`)
  via `Site::addLog()`, not DB rows.
- Hestia usernames cap at 20 chars, MySQL usernames at 32, and HestiaCP prefixes DB
  name/user with `{hestia_username}_`. Hence the short random tokens in
  `SiteController::generateDbToken()` — don't make them domain-derived.
- The webhook success path is untested (it does real SSH); only the three 403 rejection
  paths are covered in `tests/Feature/DeployWebhookTest.php`.
- Never commit server credentials, SSH keys or panel passwords — they belong in the
  `hestia_servers` table (encrypted casts) or `.env`, never in the repo.
