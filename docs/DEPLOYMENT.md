# Deployment Guide

## Environment variables

Copy `.env.example` to `.env` on the target server and set at minimum:

- `APP_ENV=production`, `APP_DEBUG=false` — disables inline error output (see
  `includes/bootstrap.php`'s exception handler, which prints stack traces only when `APP_DEBUG=true`)
- `APP_URL` — the real public URL (used by every `base_url()`/`asset_url()` call)
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` — production database credentials
- `APP_SECRET` — a random 64-character string, used as a general-purpose secret
- `MAIL_*` — real SMTP credentials, and set `MAIL_LOG_ONLY=false` so registration/invite/reset
  emails actually send instead of writing to `logs/mail.log`

## Docker

`docker-compose.yml` builds the app from `Dockerfile` (PHP 8.2 + Apache + the required
extensions) and runs MySQL 8 alongside it. For production, remove the `phpmyadmin` service and put
the app behind a reverse proxy/TLS terminator (nginx, Caddy, or a managed load balancer) — the
container itself serves plain HTTP on port 80.

```bash
docker compose -f docker-compose.yml up -d --build
docker compose exec app php database/migrate.php
docker compose exec app php database/seed.php   # only for a demo/staging environment - skip in real production
```

## Bare-metal / VM

1. `composer install --no-dev --optimize-autoloader`
2. Point Apache's document root at the repo root; enable `mod_rewrite` + `mod_headers`
3. `chown -R www-data:www-data storage logs`
4. Run `php database/migrate.php` once against the production database
5. Set up a real cron/queue for `logs/` rotation (the app just appends to `errors.log`,
   `access.log`, `mail.log` — nothing rotates them automatically)

## Database backups

`database/backups/` is a placeholder directory (gitignored) for `mysqldump` output. A minimal
daily cron:

```bash
0 2 * * * mysqldump -u sfmtp -p'...' farm | gzip > /path/to/database/backups/$(date +\%F).sql.gz
```

## Zero-downtime schema changes

Migrations in `database/migrations/` use `CREATE TABLE IF NOT EXISTS`, so re-running the full set
is always safe. For a genuinely new column/table added after initial launch, add a new
`011_*.php` file (don't edit the numbered files that already shipped) so `database/migrate.php`
picks it up on the next deploy.
