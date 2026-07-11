# Afrisap SFMTP — Smart Farm Management & Traceability Platform

A web platform for managing farm operations end to end: farm structure, crop
lifecycle, livestock, workers, finance, procurement, inventory, assets, and
full batch traceability with QR codes. Built with plain PHP, MySQL, HTML,
CSS and JavaScript (no framework) so the client's team can read and extend
every file.

This repository is being built in phases — see `/root/.claude/plans` history
or ask for the current roadmap. **Phase 1 (this release)** delivers:

- User accounts, login, email-based MFA, password reset, profile management
- Role-based access control (10 roles, granular permission codes)
- Admin Panel: user management, role/permission management, company
  settings, audit log viewer, system health snapshot
- Farm structure: farms → blocks → plots, with GPS capture on an
  OpenStreetMap/Leaflet map
- Full audit trail on every write (who, what, when, old → new value)

## Requirements

- PHP 8.1+ with `pdo_mysql` extension
- MySQL 5.7+ / MariaDB 10.3+
- Composer

## Setup

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

## Project layout

```
public/        Web root — front controller, .htaccess, CSS/JS/uploads
app/core/      Router, Auth (RBAC + MFA), Database (PDO), Validator, etc.
app/controllers/  One per module (Admin/ subfolder for admin panel)
app/models/    Thin PDO data-access classes, one per entity
app/views/     Server-rendered PHP views, grouped by module
database/migrations/  Numbered plain SQL files, applied in order by migrate.php
database/seeders/     Seed data (roles, permissions, default admin)
```

## Deployment (shared/cPanel hosting)

1. Upload everything except `app/config/config.php`, `vendor/`, and
   `.git` via your usual deploy method, then run `composer install
   --no-dev` on the server (or upload `vendor/` if Composer isn't
   available via SSH).
2. Point the domain/subdomain's document root at `public/`.
3. Copy `app/config/config.example.php` to `app/config/config.php` and fill
   in real DB + SMTP credentials.
4. Run `php database/migrate.php` then `php database/seeders/seed.php` once.
5. Confirm `.htaccess` rewriting works (Apache `mod_rewrite` must be
   enabled — standard on cPanel).
6. Change the default admin password immediately.

## Roadmap

Crop management, livestock, worker management, finance/procurement/
inventory, asset management, full traceability & QR codes, reports/
analytics/maps, notifications, and hardening/deployment polish follow in
subsequent phases, each building on this foundation.
