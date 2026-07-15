# API Reference (api/v1/)

All endpoints are session-authenticated (the same PHP session used by the org-admin/worker web
UI — log in via `public/login.php` or `worker/login.php` first) and return JSON. There is no
separate API-token auth yet; `org-admin/settings/integration.php` exposes a placeholder API key
per organization for future use.

## Conventions

- Base path: `/api/v1/{resource}.php`
- `GET` — list or fetch a single record via `?id=`
- `POST` — create; body is JSON (`Content-Type: application/json`)
- `PUT` — update a record via `?id=`; JSON body
- `DELETE` — delete a record via `?id=`
- Every mutating request must include `csrf_token` in the JSON body (read from
  `<meta name="csrf-token">` client-side, or `$_SESSION['csrf_token']` server-side — see
  `assets/js/api.js`'s `SFP` client, which attaches it automatically)
- Responses: `{"success": true, "data": ...}` or `{"success": false, "message": "..."}` with a
  non-2xx HTTP status on failure

## Endpoints

| File | Resource |
|---|---|
| `auth.php` | Current session state (`GET`) |
| `farms.php` | Farms CRUD |
| `crops.php` | Crop cycles CRUD |
| `livestock.php` | Animals CRUD |
| `workers.php` | Workers CRUD |
| `tasks.php` | Tasks CRUD + status transitions |
| `inventory.php` | Inventory items CRUD + `?action=stock_in`/`stock_out` |
| `finance.php` | Financial transactions CRUD |
| `traceability.php` | Traceability batches (read + create) |
| `reports.php` | Read-only analytics/report numbers |
| `dashboard.php` | Read-only "what's new" widget feed |
| `upload.php` | Generic authenticated file upload |
| `sync.php` | Offline action queue flush (used by the worker portal's offline mode) |

## Example

```bash
curl -b cookies.txt https://your-domain/api/v1/farms.php
curl -b cookies.txt -H "Content-Type: application/json" \
  -d '{"name":"North Field","size":40,"csrf_token":"..."}' \
  https://your-domain/api/v1/farms.php
```

See each file's inline comments for the exact field list it accepts — they mirror the equivalent
`org-admin/*` form fields documented in the USER-GUIDE.
