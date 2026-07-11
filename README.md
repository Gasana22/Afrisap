# Afrisap SFMTP — Smart Farm Management & Traceability Platform

A web platform for managing farm operations end to end: farm structure, crop
lifecycle, livestock, workers, finance, procurement, inventory, assets, full
batch traceability with QR codes, analytics/reports/maps, and notifications/
alerts/compliance. Built with plain PHP, MySQL, HTML, CSS and JavaScript (no
framework) so the client's team can read and extend every file.

## What's implemented

- **Auth & Admin**: login, email-based MFA, password reset, RBAC (10 roles,
  granular permissions), user/role management, audit log (insert-only,
  never deleted)
- **Farm structure**: farms → blocks → plots with GPS
- **Crop management**: full lifecycle (planning → procurement → nursery →
  field ops → monitoring → harvest → sale), auto-generated batch codes
- **Livestock**: animal profiles with permanent history (vaccination,
  feeding, weight, treatment, breeding, production), auto-generated animal
  IDs
- **Workers**: profiles, GPS+photo attendance, task assign→verify workflow,
  payroll
- **Finance, Procurement, Inventory**: auto-aggregated income/expense
  reporting, purchase orders, per-farm stock with movement ledger
- **Assets**: vehicles/machinery/buildings/irrigation with maintenance
  history
- **Traceability & QR**: unified batch identity across crops/livestock,
  public no-login QR scan page, documents, approvals, chain-of-custody
- **Analytics, Reports & Maps**: Chart.js dashboard, PDF/Excel exports,
  interactive Leaflet farm map
- **Notifications, Media, Alerts, Compliance**: in-app notifications,
  document library, automated alerts (missing records, expired inputs,
  disease outbreaks, low stock, unverified tasks), per-batch compliance
  PDFs (organic, GAP, export, food safety, carbon)
- **Hardening**: DB-backed login rate limiting, security headers (CSP,
  X-Frame-Options, etc.), CSRF on every form, targeted indexes,
  pagination on high-volume lists, PHPUnit test suite
- **Design system**: Fraunces (headings) + IBM Plex Sans (body) + IBM Plex
  Mono (batch/animal codes) — all self-hosted under `public/assets/fonts/`
  (OFL-1.1 licensed, no external font CDN dependency); brand forest-green
  overrides Bootstrap's default blue app-wide; a gold "scan-frame" corner
  motif on the logo nods to the platform's QR/traceability core

## Requirements

- PHP 8.1+ with `pdo_mysql`, `gd` (for QR codes) extensions
- MySQL 5.7+ / MariaDB 10.3+
- Composer

## Local setup

```bash
composer install
cp app/config/config.example.php app/config/config.php   # then edit DB/mail credentials
php database/migrate.php                                 # creates all tables
php database/seeders/seed.php                             # seeds roles, permissions, default admin
php -S localhost:8000 -t public                           # local dev server
```

Default admin login after seeding: `admin@afrisap.test` / `ChangeMe123!`
(MFA is off for this seed account so you can get in immediately — turn it on
and change the password from Admin Panel → Users right after first login).

## Running tests

The test suite uses a **separate database** (`sfmtp_test`) so it never
touches development data, and wraps every test in a transaction that's
rolled back afterward.

```bash
mysql -u root -e "CREATE DATABASE sfmtp_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT ALL PRIVILEGES ON sfmtp_test.* TO 'sfmtp_app'@'localhost';"
APP_TESTING=1 php database/migrate.php   # migrate the test database once
php vendor/bin/phpunit
```

`phpunit.xml` sets `APP_TESTING=1` automatically for the test run itself,
which points `Database::connection()` at `app/config/config.testing.php`
(committed — it holds no real secrets, just local test-DB defaults matching
`config.example.php`). Tests live in `tests/Unit` (pure logic, e.g.
`Validator`) and `tests/Integration` (DB-backed, e.g. batch-code format,
finance aggregation, alert detection).

## Project layout

```
public/            Web root — front controller, .htaccess, CSS/JS/uploads
app/core/           Router, Auth (RBAC + MFA), Database (PDO), SecurityHeaders,
                     Notifier, PdfExporter/ExcelExporter, QrGenerator, etc.
app/controllers/    One per module (Admin/ subfolder for admin panel)
app/models/         Thin PDO data-access classes, one per entity, plus a few
                     read-only aggregators (FinanceReport, AnalyticsReport,
                     AlertEngine, TraceBatch) that compute live from existing
                     tables instead of duplicating data into parallel ones
app/views/          Server-rendered PHP views, grouped by module
database/migrations/   Numbered plain SQL files, applied in order by migrate.php
database/seeders/      Seed data (roles, permissions, default admin)
database/maintenance/  Cron-runnable scripts (backup, login_attempts pruning)
tests/              PHPUnit suite (Unit + Integration)
```

## Deployment (shared/cPanel hosting)

### 1. Upload the code

Upload everything **except** `app/config/config.php`, `vendor/`, `.git`,
`storage/backups/`, and `storage/testing-uploads/` — either via Git (if your
host supports it) or FTP/File Manager.

### 2. Install dependencies

If you have SSH access:

```bash
composer install --no-dev --optimize-autoloader
```

If not, upload the `vendor/` directory as-is (it's produced by `composer
install` locally and works as a plain directory copy).

### 3. Point the document root at `public/`

In cPanel: **Domains** → your domain/subdomain → set **Document Root** to
the `public/` folder inside your upload, not the project root. This keeps
`app/`, `database/`, and `vendor/` outside the web-servable path even
without relying on `.htaccess` alone.

### 4. Create the database and configure the app

1. cPanel → **MySQL Databases**: create a database and a user with full
   privileges on it.
2. `cp app/config/config.example.php app/config/config.php` and fill in:
   - `db`: the host/database/username/password from step 1 (host is
     usually `localhost` on shared hosting)
   - `mail`: your SMTP credentials (cPanel → Email Accounts, or a
     transactional provider) — MFA codes and password resets need this to
     actually deliver; without it they're logged to `storage/logs/app.log`
     instead of emailed, which is fine for testing but not production
   - `app.url`: your real domain (used to build QR code links)
   - `app.debug`: set to `false` in production
3. Run once (via SSH, or cPanel's **Cron Jobs** "run once" trick if no
   shell access):
   ```bash
   php database/migrate.php
   php database/seeders/seed.php
   ```
4. **Change the default admin password immediately** (Admin Panel → Users),
   and turn MFA on for that account.

### 5. Confirm rewriting works

`public/.htaccess` rewrites all non-file requests to `index.php`. This
needs Apache `mod_rewrite`, which is enabled by default on virtually all
cPanel hosting. If you get raw 404s on every page except `/`, check that
`AllowOverride All` is set for the document root (contact your host if you
can't set this yourself — most shared-hosting cPanel setups already allow
it).

### 6. File permissions

`public/uploads/` and `storage/logs/` need to be writable by the web server
user (typically already true after upload; if not, `chmod 755` on the
directories is usually sufficient — avoid `777`).

### 7. HTTPS

Enable SSL (cPanel → **SSL/TLS Status**, most hosts offer free AutoSSL/Let's
Encrypt). The session cookie's `secure` flag is set automatically based on
`$_SERVER['HTTPS']`, so once HTTPS is active, sessions require it.

### 8. Cron jobs (cPanel → Cron Jobs)

None of these are required for the app to function — they're operational
hygiene. Add whichever apply:

| Schedule | Command | Why |
|---|---|---|
| Daily | `php /home/USER/path-to-app/database/maintenance/backup.php` | Database backup via `mysqldump`, keeps the last 14 days. **Only works if your host allows `shell_exec`** — many shared plans disable it. If the script exits with an error about `shell_exec` being disabled, use cPanel's own **Backup Wizard** (MySQL Databases export) instead, or ask your host to enable scheduled account backups. |
| Daily | `php /home/USER/path-to-app/database/maintenance/prune_login_attempts.php` | Deletes login rate-limit records older than 30 days. Safe to skip — the table just grows slowly otherwise (it's not an audit trail; `audit_logs`/`trace_audits` are never pruned or deleted anywhere in this app). |

Use the full absolute path cPanel shows in its cron job PHP version
selector, not a relative one.

## Security notes

- **CSRF**: every POST form includes a token, checked globally in
  `public/index.php` before any route runs.
- **Rate limiting**: login attempts are tracked in the `login_attempts`
  table (not the session), keyed by email and IP with a rolling time
  window — can't be bypassed by dropping cookies.
- **Headers**: `SecurityHeaders::apply()` sets a CSP, `X-Frame-Options`,
  `X-Content-Type-Options`, `Referrer-Policy`, and `Permissions-Policy` on
  every response. The CSP allows only `cdn.jsdelivr.net`, `unpkg.com`, and
  OpenStreetMap tiles as external origins — the app's only external
  dependencies (Bootstrap, Chart.js, Leaflet, map tiles).
- **SQL**: PDO prepared statements throughout; no user input is ever
  concatenated into a query string.
- **Passwords**: `password_hash`/`password_verify` (bcrypt).
- **Audit trail**: `audit_logs` and `trace_audits`-equivalent records are
  insert-only by convention — no delete code path exists for them anywhere
  in the app.

## Roadmap status

All 10 originally planned phases are complete: foundation/admin, farm
structure, crop management, livestock, workers, finance/procurement/
inventory, assets, traceability & QR, analytics/reports/maps,
notifications/media/alerts/compliance, and hardening/deployment. Natural
next steps beyond the original plan would be a mobile companion app
(offline-first, per the original SRS) and deeper carbon-accounting once
emission factors are modeled — both explicitly out of scope for this
engagement so far.
