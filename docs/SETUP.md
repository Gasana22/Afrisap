# Setup Guide

## Requirements

- PHP 8.1+ with extensions: `pdo_mysql`, `mysqli`, `gd`, `zip`, `mbstring`, `json`
- MySQL 8.0 or MariaDB 10.6+
- Composer 2.x
- (Optional) Docker + Docker Compose for a one-command local environment

## The most common setup mistake

**Your web server's document root must point at the project root (the folder containing this
`docs/` directory, `admin/`, `org-admin/`, etc.) - NOT at the `public/` subfolder.**

Many PHP frameworks use `public/` as the web root by convention, so it's an easy assumption to
carry over here - but in this project `public/` is just the marketing site (landing page, pricing,
login), sitting *next to* `admin/`, `org-admin/`, `worker/`, not above them. If you point your
document root at `public/`, the landing page will load (since it's literally `public/index.php`)
but every other link on the site - login, the dashboards, everything - will 404, because e.g.
`admin/dashboard.php` doesn't exist inside `public/`.

If only the landing page works and clicking anything else 404s, this is almost certainly the
cause: fix your document root (or vhost/`.htaccess` root, or hosting control panel's "site
folder" setting) to point one level up, at the project root itself.

## Option A: XAMPP/WAMP/MAMP - no command line needed

If you're running locally with XAMPP (or WAMP/MAMP) and don't want to touch the command line for
the database part:

1. Extract the project into your web server's document root (e.g. `C:\xampp\htdocs\your-project\`)
2. Start Apache and MySQL
3. Open a terminal in the project folder and run `composer install` (this one step still needs a
   terminal - there's no PHP-dependency install without it)
4. Copy `.env.example` to `.env` and set `DB_HOST="localhost"`, `DB_USER="root"`, `DB_PASSWORD=""`
   (XAMPP's default MySQL root user has no password)
5. Open http://localhost/phpmyadmin, click **Import** in the top menu, choose
   `database/full-install.sql`, and click **Go**. This one file creates the database, every
   table, *and* the demo login data - no need to create the database yourself first, and no need
   to run migrate/seed as separate steps.
6. Visit the project in your browser (e.g. `http://localhost/your-project/public/index.php`)

## Option B: Docker

```bash
cp .env.example .env
# edit .env if you want to change credentials, then:
docker compose up -d --build
docker compose exec app php database/setup.php
```

The app is now at http://localhost:8080, phpMyAdmin at http://localhost:8081.

## Option C: Local PHP + MySQL (command line)

```bash
composer install
cp .env.example .env
# edit .env: set DB_HOST=localhost and your local MySQL credentials
php database/setup.php
php -S localhost:8080 router.php
```

`database/setup.php` runs every migration and every seeder in one step (equivalent to running
`database/migrate.php` then `database/seed.php` separately, if you'd rather do it that way).

Visit http://localhost:8080.

## Demo logins (created by `database/full-install.sql` or `database/setup.php`)

| Portal | URL | Email | Password |
|---|---|---|---|
| Platform Admin | `/admin/dashboard.php` (via `/public/login.php`) | admin@sfmtp.local | Password123! |
| Farm Owner (org-admin) | `/org-admin/index.php` (via `/public/login.php`) | owner@greenvalley.test | Password123! |
| Field Worker | `/worker/index.php` (via `/worker/login.php`) | worker@greenvalley.test | Password123! |
| Public traceability | `/org/green-valley-farms/index.php` | (no login required) | - |

## Production web server (Apache)

Point the document root at the repository root (not a `public/` subfolder — the app's own
`public/` directory is the marketing site, not the web root). `.htaccess` handles the
`org/{slug}/...` rewrite and blocks direct access to `config/`, `includes/`, `database/`, `logs/`,
`vendor/`. Ensure `mod_rewrite` and `mod_headers` are enabled (`a2enmod rewrite headers`).

`storage/uploads/`, `storage/exports/`, `storage/cache/`, and `logs/` must be writable by the web
server user (`chown -R www-data:www-data storage logs` on Debian/Ubuntu).

## Re-running the database setup

- `database/full-install.sql` **drops and recreates** the database every time you import it, so
  it's always safe to re-run if you want a clean slate.
- `database/setup.php` / `database/migrate.php` are idempotent (`CREATE TABLE IF NOT EXISTS`) and
  safe to re-run for the schema part. The seeders use `INSERT ... ON DUPLICATE KEY UPDATE` for
  reference data (plans, roles, permissions), but the demo tenant seeder is meant to be run
  **once** against a fresh database - running it twice will fail on unique constraints for rows
  that aren't upsert-guarded (e.g. tasks, animals). If you need to reset the demo data, drop the
  database and re-import `full-install.sql`, or re-run `database/setup.php` against a fresh
  database.
