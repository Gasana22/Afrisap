# Afrisap Farm Management System

Multi-tenant farm management web app for **many farms and many organizations** (not one) — matching the SRS's "Smart Farm Management & Traceability Platform (SFMTP)" positioning. **Classic flat-file PHP** — no framework, no router, no front controller. Deployed as a subfolder (e.g. `xampp/htdocs/farm/`, or `/farm` under a domain root), so pages are hit directly by URL (e.g. `localhost/farm/admin/farms.php`).

## Two portals, one panel

There are two separate login doors, matching two different audiences, both landing in the same `admin/` panel afterward (the panel already adapts per request via `is_platform_user()`):

- **Admin Portal** (`/admin-login.php`) — Super Admin, Manager, Accountant (`roles.scope = 'platform'`, `organization_id IS NULL`). This is the small internal team that keeps the *platform itself* running: platform staff accounts, the shared role/permission catalog, and a read-only list of organizations. They deliberately have **no visibility into any organization's farm operations** — no crops, livestock, workers, finance, procurement, inventory, assets, traceability, media, reports, or compliance data. They operate the system; they don't take part in running anyone's farm. See "Admins vs. farm operations" below.
- **Farm Portal** (`/login.php`) — Farm Owner, Farm Manager, Agronomist, Livestock Manager, Store Manager, Farm Accountant, Field Worker, Viewer (`roles.scope = 'tenant'`, `organization_id` set). This is everyone at a given farm/organization, using the site to manage *their own* farm(s). One organization's users never see another organization's data.

`attempt_login()` takes an optional `$requiredScope` ('platform' or 'tenant') and rejects a correct password if it's for the other portal, with a message pointing at the right one. Both login pages cross-link to each other for anyone who lands on the wrong one. `verify-otp.php` (MFA) and `logout.php` are shared — the portal split only matters at the login step.

`signup.php` is public self-service registration for the Farm Portal only — it creates a new `organizations` row plus a `farm_owner`-scope user in one DB transaction, then logs the new user in via `attempt_login()` (so MFA still applies if enabled). There's no equivalent for the Admin Portal — platform staff accounts are created by an existing platform user via `admin/users.php`, never by self-signup.

`forgot-password.php` / `reset-password.php` are public, tenant-portal-only (the reset lookup in `includes/auth.php`'s `request_password_reset()` is scoped to `role_scope = 'tenant'`, so a platform account email produces no reset row — silently, so the success message doesn't leak whether an email exists or which portal it belongs to). Tokens are single-use (`password_resets.used_at`) and expire after `PASSWORD_RESET_TTL_MINUTES` (60). Delivery is dev-mode only, same pattern as OTP: `deliver_password_reset()` appends the full reset URL to `storage/logs/otp.log`.

> Note: an earlier version of this project used a router/front-controller/MVC pattern. That approach was abandoned in favor of this flat-file structure to match a standard XAMPP hosting layout. If you see any reference to `app/Core/Router.php` or similar elsewhere, ignore it — this structure is the current one.

## Stack & constraints

- Plain PHP, **flat files** — one `.php` per page, no routing layer.
- PDO for all DB access, via the `db()` helper. `PDO::ATTR_EMULATE_PREPARES` is off — **never mix named (`:foo`) and positional (`?`) placeholders in the same prepared statement**, it throws. When building a query with a dynamic `IN (...)` list via `in_placeholders()`, make every other placeholder in that same statement positional too (see `admin/crops.php`'s plot-ownership check for the pattern).
- No composer, no build step, no npm. Just PHP + MySQL, matching XAMPP defaults. The one exception: QR code images are rendered via a public QR image API (`api.qrserver.com`) called from an `<img src>` in the browser — no server-side dependency, no library.
- Config lives in `includes/config.php` as plain `define()` constants (not `.env` — simpler for shared/local hosting where people are used to editing one config file directly).

## Design system — "Ledger & Trail"

The visual identity is deliberately not generic SaaS (no cream background, no default green-and-white-cards look): a soil-dark ink (`--ink`) crossed with a harvest-gold accent (`--gold`), a serif display face (Fraunces) for headings, Work Sans for body copy, and IBM Plex Mono for anything that's actually data — batch codes, tracking codes, table figures, stat numbers. The signature motif is the **provenance trail** (Planted → Growing → Harvested → Verified), since traceability is the product's real differentiator, not a generic hero-metric template. All of this lives in `assets/css/site.css` (public + auth pages) and `assets/css/admin.css` (admin panel), as CSS custom properties on `:root` — change the palette/type in one place, not per page. Both stylesheets `@import` the fonts from Google Fonts; if that's ever blocked (offline install, restrictive network), the `font-family` fallback stacks (`Georgia, serif` / `system-ui, sans-serif` / `ui-monospace, monospace`) keep the page readable, just less distinctive.

- **`includes/trail_widget.php`**: renders the Planted/Growing/Harvested/Verified trail. Pass `$trailActive` (0-4) before requiring it — 0 for a decorative/inactive trail (auth pages), 4 for "fully verified" (homepage hero example). Reused at full size in the homepage hero and about page, and at a smaller size (`.trail-mini`) in the auth brand panel.
- **`includes/auth_panel.php`**: the dark brand panel shown beside every auth form (`login.php`, `admin-login.php`, `signup.php`, `forgot-password.php`, `reset-password.php`, `verify-otp.php`). Set `$authHeadline` / `$authCopy` (raw HTML, not passed through `e()` — write it directly in the calling page) before requiring it. Hidden below 860px so it never competes with the form on a phone.
- **`.status-pill`** (defined in both stylesheets): status text everywhere — trace batch status, approval status — gets a colored pill via `status-<value>` (e.g. `status-active`, `status-pending`, `status-recalled`), not plain text. The color buckets (verified/pending/closed/warn) are listed in the CSS; add a new bucket there if a new enum value needs one, don't invent an inline color.
- **`.reveal`**: sections that should fade in on scroll (see `assets/js/site.js`'s IntersectionObserver). Respects `prefers-reduced-motion` — content is never gated behind the animation, it's just instant instead of eased when motion is reduced.
- **Mini in-table bar charts** (`admin/reports.php`'s crop-yield report is the example): only ever used to compare values that share a real unit across rows of the *same* table — never two different measures on one scale, and never forced onto the dashboard stat tiles (those compare unrelated units — farms vs. crop cycles vs. workers — so they stay plain stat tiles, not a chart). Wire a new one by adding `data-mini-bar-group` to the `<table>` and `data-mini-bar-value="<raw number>"` to a `.mini-bar-cell` div per row; `assets/js/admin.js` scales all the bars in that group to the largest value present.

## Deployment path

This whole folder is meant to sit at:

```
xampp/htdocs/farm/
```

So the site loads at `http://localhost/farm/`. If deployed anywhere else, update `BASE_URL` in `includes/config.php` accordingly.

## How to run it locally (XAMPP)

1. Copy this folder to `xampp/htdocs/farm`.
2. Start Apache + MySQL in the XAMPP control panel.
3. Create a database (e.g. via phpMyAdmin) matching `DB_NAME` in `includes/config.php` (default: `afrisap`).
4. Apply the schema: `php database/migrate.php` (reads `database/migrations/*.sql` in order, tracks progress in the `migrations` table, safe to re-run).
5. Seed base data: `php database/seed.php` (roles + permissions for both portals, **two** demo organizations each with their own Farm Owner, platform Super Admin/Manager/Accountant, crop types, a season). Prints every seeded login and which portal it belongs to — change these passwords before any shared use. Safe to re-run.
6. Edit `includes/config.php` if your DB credentials differ from XAMPP defaults (`root` / empty password).
7. Visit `http://localhost/farm/` — the public homepage. Its "Login" button goes to the Farm Portal; the footer has a "Staff Login" link to the Admin Portal.

## Project structure

The app has three distinct zones, all under this one folder: the **public site** (no login), **auth** (login/verify/logout, also no login required to reach them), and the **admin panel** (`admin/`, login required). Nothing in `admin/` is reachable without a session; nothing in the public site touches tenant-private data.

```
index.php                 Public homepage — company blurb, aggregate stats (from settings/organizations/farms/etc.), module overview.
about.php                  Public — platform description + aggregate stats + how traceability works.
contact.php                Public — static contact info (no submission form; there's no table for storing messages yet).
trace.php                  Public — QR-scan / manual-code lookup landing page: batch info + product journey by public_token.
login.php                  Farm Portal login (tenant users only). Redirects to admin/dashboard.php if already logged in.
admin-login.php             Admin Portal login (platform staff only). Same shape as login.php, different scope check.
verify-otp.php             MFA one-time-code step (shown when a user's mfa_enabled = 1), shared by both portals
logout.php                 Destroys session, redirects to /login.php

includes/
  config.php                Constants: BASE_URL, DB_*, APP_*. Session start. Edit this per environment.
  db.php                     db() — shared PDO connection (emulated prepares OFF)
  auth.php                   is_logged_in(), current_user(), has_permission(), attempt_login(), log_user_in(), log_out(),
                              rate limiting, generate_and_send_otp()/deliver_otp() (MFA code issuance + dev-mode delivery)
  functions.php              e(), redirect(), flash(), require_permission(), notify(), notify_organization(),
                              create_trace_batch(), farm_or_404(), visible_farm_ids(), in_placeholders(), current_organization_name()
  bootstrap.php              Requires the four files above, in order — every page requires just this one file
  site_header.php / site_footer.php  Shared layout for the public site pages (nav: Home/About/Track a Product/Contact
                                       + Login-or-Dashboard link). login.php/verify-otp.php don't use these — they keep
                                       their own minimal auth-card layout with just a "back to home" link.

admin/
  includes/
    auth-check.php           Session guard — require this at the top of every protected admin page (redirects to /login.php)
    header.php                Sidebar nav (every module) + a portal badge (Admin Portal / Farm Portal) + topbar with
                                notification count and, for tenant users, their organization's name. Set $pageTitle / $activePage before requiring.
    footer.php                 Closes layout wrapper divs
  dashboard.php                Two different views off the same file, branching on is_platform_user(): tenant
                                 users get farm-operational stat cards (farms, active crop cycles, active
                                 animals, active workers, pending tasks); platform staff get platform-only
                                 stat cards (organizations, platform staff, Farm Portal users) -- no farm data.
  farms.php / farm-view.php    Farms list+add; blocks+plots nested CRUD per farm
  crop-types.php / seasons.php Shared reference lists (no organization_id — same for every tenant)
  crops.php / crop-view.php    Crop cycle list+add; detail page with inputs, nursery records, field activities,
                                monitoring, yield forecasts, harvests, sales, and status lifecycle control
  livestock.php / animal-view.php  Animal list+add; detail page with vaccinations, feedings, weights, treatments,
                                     breeding, production, plus mortality/sale outcome actions
  workers.php / worker-view.php / attendance.php  Worker list+add; tasks + payroll on the detail page;
                                                    daily bulk attendance marking per farm
  finance.php                  Income + expense recording and totals, farm-scoped
  suppliers.php / purchase-orders.php / purchase-order-view.php  Procurement: PO line items, deliveries, payments
  inventory.php                 Items, per-farm stock levels, stock in/out/transfer movements, low-stock notifications
  assets.php / asset-view.php  Asset list+add; maintenance history + status
  traceability.php / trace-view.php  Trace batches (auto-created on every crop cycle / animal), QR generation,
                                       documents, approvals, product journey
  notifications.php             Per-user notification inbox, mark read
  media.php                     Photos/receipts/contracts/certificates not tied to a specific record
  users.php                     Add/list users within your pool (platform staff, or your tenant's staff)
  reports.php                    Tabular reports (crop yield, livestock production, worker productivity, daily
                                   activities), tenant-scoped via visible_farm_ids(). Three render modes off the
                                   same query: default HTML table, ?format=csv (native fputcsv() download — pass
                                   the escape param explicitly, PHP 8.4 deprecates the implicit default and the
                                   notice corrupts the stream if you don't), ?format=print (standalone page with
                                   a window.print() button + @media print CSS, i.e. "save as PDF" via the browser
                                   instead of a PDF library).
  compliance.php                 Print-friendly full compliance reports for a single trace batch: organic,
                                   GAP, export, food-safety, carbon (?id=<trace_batch_id>&type=<one of those>).
                                   Same tenant-ownership check as trace-view.php. No CSV mode — these are
                                   narrative/document-style reports, not tables.
  roles.php / role-edit.php      Platform-only (hard is_platform_user() check, not just a permission gate,
                                   since the role/permission catalog is shared across every organization).
                                   Lists roles, and a checkbox editor per role synced against role_permissions
                                   via delete-then-insert in a transaction. POST is additionally gated by
                                   require_permission('roles.manage'). Farm Owner and Manager are deliberately
                                   never granted roles.manage in seed.php — otherwise a tenant user could edit
                                   the platform-wide permission catalog.
  audit-log.php                  Platform-only viewer over the audit_logs table (paginated, filterable by
                                   ?table=). See audit_log() below for what gets logged.
  organizations.php               Platform-only, read-only: every organization with its owner, farm count,
                                   active user count, status, created date -- aggregate numbers only, no farm
                                   operational data. This is what admins get instead of farms.php.

assets/
  css/site.css                Public site + auth pages design system: tokens, typography, all page components
  css/admin.css                Admin panel design system, same identity dialed down for a tool that's scanned
                                 not read (see "Design system" below)
  js/site.js                   Mobile nav toggle, scroll-reveal, animated ledger-strip counters, provenance
                                 trail fill-in, copy-to-clipboard on trace codes. Vanilla JS, no dependency.
  js/admin.js                  Mobile sidebar toggle, dismissible/auto-fading flash alerts, mini in-table
                                 bar charts (see reports.php below)

database/
  migrations/                 20 numbered SQL files (000-019), each runs exactly once — none use IF NOT EXISTS
                                by design, so don't run them by hand more than once; always go through migrate.php
  migrate.php                  Migration runner — tracks applied files in the `migrations` table
  seed.php                     Seed script — both role pools + permissions, two demo organizations (each with their
                                own users), platform Super Admin/Manager/Accountant, crop types, a season

storage/logs, storage/uploads
```

## The pattern every new admin page should follow

Copy `admin/farms.php` (simple list+add) or `admin/crop-view.php` (detail page with several nested record types) as the template. Every protected admin page:

```php
<?php
require_once __DIR__ . '/includes/auth-check.php';

// ... handle POST (create/update/delete), gated by require_permission('module.manage') ...
// ... fetch data, scoped by tenant (see below) ...

$pageTitle = 'Whatever';
$activePage = 'whatever'; // matches the href check in admin/includes/header.php
require __DIR__ . '/includes/header.php';
?>

<!-- page HTML here -->

<?php require __DIR__ . '/includes/footer.php'; ?>
```

Then add a nav link for it in `admin/includes/header.php`.

## Conventions to keep following

- **Escape all output** with `e()` — never echo raw `$_POST`, `$_GET`, or DB values into HTML.
- **All DB access uses prepared statements** via `db()`. No string interpolation into SQL, ever. **Don't mix `:named` and `?` positional placeholders in one statement** (see above).
- **Multi-tenancy is load-bearing.** Every tenant-scoped query must filter by `organization_id = current_organization_id()`. Use `farm_or_404()` for a single farm and `visible_farm_ids()` (with `in_placeholders()` for the `IN (...)` clause) for anything that spans farms — livestock, workers, inventory, finance, etc. all follow this. Getting this wrong is a cross-tenant data leak, not a cosmetic bug. The seed data deliberately creates **two** organizations so cross-tenant bugs show up immediately in testing rather than being masked by there only ever being one.
- **Admins vs. farm operations.** Platform staff (`is_platform_user()`) never see farm-operational data, full stop — not even read-only, not even their own organization's (they don't have one). Every farm-operational page (`admin/farms.php`, `farm-view.php`, `crops.php`, `crop-view.php`, `livestock.php`, `animal-view.php`, `workers.php`, `worker-view.php`, `attendance.php`, `finance.php`, `suppliers.php`, `purchase-orders.php`, `purchase-order-view.php`, `inventory.php`, `assets.php`, `asset-view.php`, `traceability.php`, `trace-view.php`, `media.php`, `reports.php`, `compliance.php`) calls `require_tenant_user()` (in `includes/functions.php`) as its first line, right after `auth-check.php` — a hard 403 for platform staff, mirroring how `admin/roles.php` already hard-blocks tenant users. Because of that guard, `farm_or_404()` and `visible_farm_ids()` no longer branch on `is_platform_user()` at all — they're tenant-only now, and assume the caller already checked. If you add a new farm-operational page, copy this pattern: guard first, scope every query by `current_organization_id()` same as any other tenant page. What admins *do* keep: `admin/users.php` (already correctly scoped to the platform staff pool only — never tenant users), `admin/roles.php`/`role-edit.php` (the shared permission catalog), `admin/audit-log.php`, and `admin/organizations.php` (a read-only list — org name, owner, farm *count*, active user *count*, status — aggregate numbers only, never a farm's actual data, same privacy bar as the public site's aggregate stats). `admin/crop-types.php` and `admin/seasons.php` stay open to admins too, since those are shared platform-wide reference catalogs with no `organization_id`, not any one organization's operational data — more like the roles catalog than like a farm record.
- **Permissions**: gate every mutating (POST) action with `require_permission('module.manage')` (checked against `role_permissions`/`permissions`) rather than checking `role_slug` directly. Reads stay open to any logged-in user, same as the rest of the admin panel. The permission codes and both role pools (platform: Super Admin/Manager/Accountant; tenant: Farm Owner/Farm Manager/Agronomist/Livestock Manager/Store Manager/Farm Accountant/Field Worker/Viewer) are seeded in `database/seed.php`, editable via `admin/roles.php` + `admin/role-edit.php` (platform-only — the catalog is shared across every organization, so a tenant user editing it would affect other tenants).
- **Audit logging**: call `audit_log($action, $table, $recordId, $old, $new)` (in `includes/functions.php`) right after any create/update/delete/status-change write — it records the acting user, their organization, an IP/device, and JSON-encoded before/after snapshots. It's wired into the primary write path of every module; nested sub-actions (e.g. individual line items) aren't exhaustively covered. View it at `admin/audit-log.php` (platform-only).
- **fputcsv() on PHP 8.4**: always pass the escape character explicitly — `fputcsv($out, $row, ',', '"', '\\')` — never call it with just two args. PHP 8.4 emits a deprecation notice for the omitted 5th param, and since it fires *after* headers are sent but *during* the CSV body, the notice text gets written straight into the downloaded file and corrupts it. Same failure shape as a PHP-version-mismatch bug found in reviewed code that used a PDF library; the general lesson is to actually open generated CSV/PDF output and check it's clean, not just check the HTTP status.
- **MFA**: `users.mfa_enabled` triggers the `verify-otp.php` step between password check and full login. `generate_and_send_otp()` (in `includes/auth.php`) issues the code and calls `deliver_otp()`, which currently just appends to `storage/logs/otp.log` and — only when `APP_DEBUG` is true — surfaces the code directly on the verify page. Swap `deliver_otp()` for a real SMS/email provider when one is chosen; nothing else in the login flow needs to change.
- **Rate limiting**: login attempts are logged to `login_attempts` and checked in `attempt_login()` (5 attempts / 15 min window, by email or IP).
- **Trace batches**: there's no DB trigger (flat-file app) linking crop_cycles/animals to trace_batches, so every insert path that creates a crop cycle or an animal must call `create_trace_batch()` right after — see `admin/crops.php` and `admin/livestock.php`.
- **BASE_URL**: always link/redirect using `BASE_URL . '/path'` (or the `redirect()` helper, which does this for you) — don't hardcode `/farm/...` directly, so the app still works if moved to a different subfolder or a domain root later.
- **Public site stays public-safe.** Pages outside `admin/` (index.php, about.php, contact.php, trace.php) never require `admin/includes/auth-check.php` — they're meant to be reachable by anyone. Only ever query aggregate/anonymized counts (`COUNT(*)` totals) or data that's explicitly designed to be public (a trace batch's info via its `public_token`, which a farm team generates and shares on purpose). Never list or name individual farms, organizations, users, or any other tenant-private record on a public page — that's a cross-tenant/public data leak, same severity as missing an `organization_id` filter in the admin panel.

## What's built

Everything in the original roadmap: migration runner + seed data, OTP delivery (dev-mode), farm/block/plot structure, crop cycle module (full lifecycle: inputs, nursery, field activities, monitoring, forecasts, harvests, sales), livestock module (vaccinations, feedings, weights, treatments, breeding, production, mortality, sales), workers module (attendance, tasks, payroll), finance, procurement (suppliers, POs, deliveries, payments), inventory (stock in/out/transfer with low-stock alerts), assets + maintenance, traceability (QR codes, documents, approvals, product journey, public verification page), notifications, media, and basic user management. Plus a separate public site (home, about, contact, product tracking) distinct from the admin panel, sharing only the database and CSS file, and two separate login portals (Admin Portal for platform staff, Farm Portal for tenant users) feeding the same panel.

Also ported in from a reference MVC-style implementation of the same product, adapted to the flat-file/no-dependency conventions above: self-signup (`signup.php`), forgot/reset password (`forgot-password.php` / `reset-password.php`), audit logging (`audit_log()` + `admin/audit-log.php`), a roles/permissions editor (`admin/roles.php` / `admin/role-edit.php`), a reports module with CSV export and browser-print-to-PDF in place of a PDF library (`admin/reports.php`), and compliance reports for organic/GAP/export/food-safety/carbon (`admin/compliance.php`). QR codes still use the external image API rather than the reference implementation's self-hosted QR library, matching this project's no-composer-dependency stance.

The public site, auth pages, and admin panel were redesigned from a generic green/white-card template into the "Ledger & Trail" identity described above, with vanilla JS added for a mobile nav/sidebar toggle, scroll-reveal, animated stat counters, dismissible flash alerts, and mini in-table bar charts — see "Design system" above and `assets/js/site.js` / `assets/js/admin.js`.

All 20 migrations, the seed script, and every module above were exercised end-to-end against a real MySQL/MariaDB instance during development (login on both portals, wrong-portal rejection, MFA, farm→block→plot→crop cycle→harvest→sale, livestock, worker attendance, finance, procurement, inventory movements with low-stock notification, asset maintenance, trace QR generation + public lookup, permission enforcement, cross-tenant isolation across two seeded organizations, and the public site pages all verified working). The ported features were exercised the same way in a later round: signup → MFA → dashboard; forgot-password on a tenant account (reset link logged) vs a platform account (correctly no reset row created); full reset-password round trip including single-use token rejection on reuse; roles list + permission checkbox toggle/save/audit-log entry, with a hard platform-only check confirmed against a tenant account; all 4 reports in HTML/CSV/print across two organizations, including confirming CSV output is clean of PHP 8.4 deprecation-notice corruption; and all 5 compliance report types against both a crop-cycle batch and an animal batch, including a platform user viewing a tenant's report and a different tenant correctly getting 404 on it.

## Things to flag rather than silently decide

- Any query touching tenant-scoped tables without an `organization_id` filter.
- Any schema change — add a new numbered migration file (without `IF NOT EXISTS`, matching the existing ones exactly) rather than a manual `ALTER TABLE`.
- Introducing a framework, composer dependency, or JS build step — these were deliberate choices to keep this a plain, XAMPP-friendly flat-file app. Check before changing that.
- Real OTP delivery (SMS/email) and real file uploads (currently `file_path` fields are plain text inputs, not actual upload handling) are the two most visible "not real yet" pieces if this goes toward production use.
