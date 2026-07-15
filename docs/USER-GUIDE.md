# User Guide

## Getting started

1. Visit the public site and click **Start Free Trial** (`public/register.php`), or log in with
   an existing account at `public/login.php`.
2. After registering, you land on the **org-admin dashboard** (`org-admin/index.php`) as the
   `owner` of your new organization.

## Farm structure

Farms are organized as **Farm → Block → Plot**:

- **Farms** (`org-admin/farms/index.php`) — top-level properties, with GPS location, size, and
  district/village.
- **Blocks** (`org-admin/farms/blocks.php?farm_id=`) — sub-divisions of a farm.
- **Plots** (`org-admin/farms/plots.php?block_id=`) — the smallest unit, where crops are actually
  assigned and planted.

## Crop lifecycle

A **crop cycle** (`org-admin/crops/`) moves through stages: planning → procurement → nursery →
field → monitoring → harvest → sales → completed. Each cycle gets a unique batch ID
(`CROP-YYYYMMDD-XXXXXX`) used to link field operations, input procurement, harvest records, and
sales together, and can be connected to a **traceability batch** for QR-code tracking.

## Livestock

Each animal (`org-admin/livestock/`) has its own profile with a timeline of health/breeding/
production events and farm-to-farm movements.

## Workers & tasks

Add workers under `org-admin/workers/`, then assign tasks (`org-admin/workers/assign.php`) that
show up on the assigned worker's mobile portal (`worker/`). Workers log in separately at
`worker/login.php` using their own email/password — a worker account is a `users` row linked via
`organization_users.role = 'worker'` and a `workers` row.

## Inventory & procurement

Track stock levels (`org-admin/inventory/`), suppliers, and purchase orders
(`org-admin/procurement/`). Receiving a purchase order updates inventory automatically.

## Finance

Record income/expenses (`org-admin/finance/`), review cash flow and profit & loss, and set
budgets per category/fiscal year.

## Traceability & QR codes

Create a traceability batch (`org-admin/traceability/batches.php`), log its journey stages and
chain-of-custody events, then generate a QR code from the batch detail page. The QR encodes a
public URL (`/org/{your-slug}/trace.php?batch=...`) that any customer can scan to see the full
journey — no login required.

## Roles & permissions

| Role | Typical access |
|---|---|
| `owner` | Everything |
| `manager` | Farms, crops, livestock, workers, inventory, procurement, reports |
| `agronomist` | Crops + reports |
| `livestock_manager` | Livestock + reports |
| `store_manager` | Inventory + procurement |
| `accountant` | Finance + reports |
| `viewer` | Read-only across most modules |
| `worker` | Mobile portal only (tasks, attendance, activity logs) |

Manage members and roles under `org-admin/settings/users.php`.
