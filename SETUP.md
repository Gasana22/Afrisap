# Safarisap — Local Setup

Plain PHP (PDO/MySQLi) + MySQL/MariaDB, no framework.

## 1. Database

```
mysql -u root -e "CREATE DATABASE safarisap CHARACTER SET utf8mb4;"
mysql -u root safarisap < database/schema.sql
mysql -u root safarisap < database/seed.sql
```

## 2. Create your first admin user

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
