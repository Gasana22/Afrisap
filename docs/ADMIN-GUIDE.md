# Platform Admin Guide

The platform admin panel (`admin/`) is for the SaaS operator, not for individual farms. Access it
by logging in at `public/login.php` with an account that has `users.is_admin = 1` (the demo seed
creates `admin@sfmtp.local` / `Password123!`).

## Dashboard

`admin/dashboard.php` shows platform-wide metrics: total organizations, active users, revenue, and
a tenant growth chart, plus a recent-activity feed and quick actions.

## Organizations (tenants)

`admin/tenants/` — list, create, edit, view, and suspend organizations. Suspending an organization
sets `organizations.subscription_status = 'suspended'`, which blocks its users from logging in
(checked in `includes/tenant.php`'s `resolve_tenant_by_slug()` and the login flow) without
deleting any of its data.

## Subscriptions

`admin/subscriptions/` — manage the four subscription plans (free/basic/professional/enterprise)
that control `max_farms`/`max_users` limits enforced in `includes/tenant.php`'s
`organization_within_plan_limits()`, view payments, and generate simple invoices.

## Platform users

`admin/users/` — every user across every organization, the default role/permission matrix, and a
platform-wide activity/audit log.

## Global settings

`admin/settings/` — site identity, email configuration notes, security defaults, and integration
placeholders. Note: actual SMTP credentials and API keys are read from `.env` at runtime; these
settings pages record intent/overrides in the `system_settings` table for a future release where
they take precedence over `.env`.

## Reports

`admin/reports/` — usage statistics (organizations by plan, totals across all tenants) and growth
trends over time.

## Data isolation reminder

Every tenant-owned table carries `organization_id` (or is one join away from a table that does).
The admin panel is the **only** place in the codebase that legitimately queries across all
tenants — every other page must scope by the current organization, using the `tenant_*()` helpers
in `includes/db.php` (`tenant_all`, `tenant_find`, `tenant_insert`, `tenant_update`, `tenant_delete`).
