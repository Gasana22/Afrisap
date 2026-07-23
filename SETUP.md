# Safarisap — Local Setup

Plain PHP (PDO/MySQLi) + MySQL/MariaDB, no framework.

## 1. Database

```
mysql -u root -e "CREATE DATABASE safarisap CHARACTER SET utf8mb4;"
mysql -u root safarisap < database/schema.sql
```

`schema.sql` is the whole database in one file -- structure and starter data (categories, activities, experience types, sample destinations, and a default admin login) together. One import, nothing else to run.

## 2. Admin login

A default admin user comes with the import. To add another (or change the password), generate a hash and insert it:

```
php -r "echo password_hash('choose-a-password', PASSWORD_DEFAULT);"
```

```sql
INSERT INTO admin_users (name, email, password_hash, role)
VALUES ('Your Name', 'you@safarisap.com', '<hash from above>', 'super_admin');
```

## 3. Configure DB credentials

Defaults in `config/config.php` assume `root` with no password on `127.0.0.1` (typical local XAMPP/WAMP). Override with environment variables if needed: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_BASE_PATH` (set this if serving from a subfolder, e.g. `/afrisap`).

## 4. Run

```
php -S 127.0.0.1:8000
```

Visit `http://127.0.0.1:8000/admin/login.php`.

## Build order

Countries → Tour Categories → Destinations, then Tours/Activities/Experiential content on top. See `database/DATA_MODEL.md` for the full schema walkthrough and open questions.

## Testing

```
php tests/smoke.php
```

Requires a MySQL/MariaDB server the current user can create databases on (same requirement as the app itself). This spins up an isolated `safarisap_test` database, seeds fixture data, starts a temporary PHP dev server on port 8098, and exercises the public site and admin panel end-to-end — page loads, form submissions (booking/quote/contact, verified by checking the database, not just the HTTP response), CSRF rejection, admin login/CRUD, and role-based access control. Everything is torn down automatically when it finishes, pass or fail.

Run this after making changes to catch regressions before they reach a client-facing branch.
