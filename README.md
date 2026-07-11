# Afrisap SFMTP — Smart Farm Management & Traceability Platform

A multi-tenant web platform for managing farm operations end to end: farm
structure, crop lifecycle, livestock, workers, finance, procurement,
inventory, assets, full batch traceability with QR codes, analytics/reports/
maps, and notifications/alerts/compliance. Built with plain PHP, MySQL,
HTML, CSS and JavaScript (no framework) so the client's team can read and
extend every file.

## Two portals, two audiences

This is a SaaS-shaped app with two completely separate logins and UI shells,
sharing one database:

- **The farm app** (`/login`, `/signup`) — each Farm Owner signs up their own
  **organization**, which can own multiple farms. Every tenant user (Farm
  Owner, Farm Manager, Agronomist, Livestock Manager, Store Manager,
  Accountant, Field Worker, Supplier, Customer) only ever sees their own
  organization's data — every listing, every direct-by-ID route, is scoped
  to `organization_id`. A Farm Owner invites their own staff from `/team`,
  picking from a fixed role catalog; they cannot grant platform-level access.
- **The platform admin portal** (`/platform/login`) — Afrisap's own staff
  (Super Admin, Platform Manager, Platform Accountant) run the SaaS itself:
  view/suspend tenant organizations, manage platform staff, edit the RBAC
  role catalog, platform-wide settings, and a cross-tenant audit log. A
  platform credential does not work at `/login`, and a tenant credential
  does not work at `/platform/login` — enforced server-side, not just UI.
  Suspending an organization immediately blocks that org's users from
  logging in.

## What's implemented

- **Auth**: login, email-based MFA, password reset, RBAC (12 roles across
  the two scopes above, granular permissions), audit log (insert-only,
  never deleted, tagged with which organization each entry belongs to)
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

Default Super Admin login after seeding, at **`/platform/login`** (not the
regular `/login`): `admin@afrisap.test` / `ChangeMe123!` (MFA is off for this
seed account so you can get in immediately — turn it on and change the
password from Platform Staff → your own account right after first login).

To try the farm-tenant side, go to `/signup` and create a Farm Owner account
— there's no seeded tenant user, since signup is meant to be self-service.

## Project layout

One database, two portals. The folder structure says so directly — every
controller and view lives under an `Admin/` or `Public/` folder, so it's
obvious at a glance which portal a file belongs to:

```
public/                        Web root — front controller, .htaccess, CSS/JS/uploads
app/core/                      Router, Auth (RBAC + MFA), Database (PDO), SecurityHeaders,
                                Notifier, PdfExporter/ExcelExporter, QrGenerator, etc.
                                (shared by both portals — not part of either one)

app/controllers/Admin/         Afrisap staff-only platform portal (served at
                                /platform/*): organizations, platform staff,
                                roles & permissions, settings, audit log.
app/views/admin/               Its views. Layout: app/views/layouts/admin.php.

app/controllers/Public/        The farm-tenant product everyone else uses: auth
                                & signup, dashboard, farm/crop/livestock/worker
                                management, finance, procurement, inventory,
                                assets, traceability & the no-login QR scan
                                page, reports, maps, media, alerts, team.
app/views/public/               Its views. Layouts: app/views/layouts/app.php
                                (signed-in pages), auth.php (login/signup),
                                public.php (the no-login QR page).

app/views/layouts/, partials/, errors/   Shared chrome used by both portals.

app/models/                    Thin PDO data-access classes, one per entity
                                (shared by both portals — Admin manages
                                Organizations/Users/Roles, Public manages
                                everything a farm owns), plus a few read-only
                                aggregators (FinanceReport, AnalyticsReport,
                                AlertEngine, TraceBatch) that compute live from
                                existing tables instead of duplicating data.

database/migrations/           Numbered plain SQL files, applied in order by
                                migrate.php — all against the one database.
database/seeders/              Seed data (roles, permissions, default admin)
database/maintenance/          Cron-runnable scripts (backup, login_attempts pruning)
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
4. **Change the default Super Admin password immediately**, logging in at
   `/platform/login` (not `/login` — that's for farm tenants), and turn MFA
   on for that account. Real tenant (Farm Owner) accounts are created by
   the client's customers themselves at `/signup`, not seeded.

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
- **Tenant isolation**: every module's listing and direct-by-ID routes
  (`/farms/{id}`, `/livestock/{id}`, etc.) are scoped to the caller's own
  `organization_id` at the query level, not just hidden in the UI — fetching
  another organization's record by guessing its id returns "not found," not
  their data. `Auth::attemptLogin()` is scope-checked (`tenant` vs
  `platform`), so a platform credential simply doesn't work at the farm
  app's `/login` and vice versa, with the same generic error either way.

## Roadmap status

All 10 originally planned phases are complete (foundation, farm structure,
crop management, livestock, workers, finance/procurement/inventory, assets,
traceability & QR, analytics/reports/maps, notifications/media/alerts/
compliance, hardening/deployment), plus a follow-on multi-tenancy
re-architecture: tenant self-service signup, Farm-Owner-managed team
invites, organization-scoped data isolation across every module, and the
separate platform-admin portal described above.

Natural next steps beyond that: a proper email-invite-link flow for team
members (current pattern is admin-sets-a-temp-password, matching what
existed pre-multi-tenancy); per-tenant branding/logo and a tenant-facing
`/settings` page (today `default_currency`/`default_units` are still a
single platform-wide default, not per-organization); a mobile companion
app (offline-first, per the original SRS); and deeper carbon-accounting
once emission factors are modeled. All explicitly out of scope for this
engagement so far.
