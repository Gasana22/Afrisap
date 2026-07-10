# Safarisap.com — Data Model

Maps directly to the tour spec doc. Run `schema.sql` then `seed.sql` (MySQL/MariaDB, InnoDB, utf8mb4).

## Domains

**Safari Tours** (`tour_categories` menu_group='safari' → `category_pages` → `destinations` → `tours`)
- `category_pages` is the one reusable landing-page template ("form for its data & applies for all") — one row per sub-menu item (Gorilla Trekking, Chimp Trekking, etc.), linked to relevant national parks via `category_page_parks`.
- `destinations` = national parks/game reserves, each in one country, with `destination_animals`, `destination_birds`, and `destination_activities` (the tile+box+gallery popular activities).
- `tours` = the bookable itinerary: 2+ destinations (`tour_destinations`), budget tier, price/days/discount/pax, hotel/vehicle/flight info, includes/excludes, one `tour_operators` record, `tour_faqs`, and `tour_activities` (itinerary activities, optionally linked to the master `activities` catalog).
- Public search/filter (no keyword search, per spec): Budget, Country, Days, Destination.

**Experiential Tours** (`experience_types` → `service_providers` / `experience_destinations` → `experience_tours`)
- Structurally separate from Safari, as the doc calls out. Five types: Cultural, Farm, Manufactural/Factory, Sports, Ghetto.
- `service_providers` are companies tagged to one experience type + region.
- `experience_destinations` are the type-specific "destinations" (tribes for Cultural, crop/livestock for Farm, sport for Sports, industry for Manufacturing, ghetto area for Ghetto) — seeded with the doc's lists, but the admin can add more (spec says "more can be added").
- `experience_destination_activities` are the tile+description boxes on a destination page (no day-by-day, per spec).
- `experience_tours` reuse one form across all 5 types: selecting 2+ destinations pulls in their activities, which become editable/removable copies in `experience_tour_activities` (not live references — so editing a tour never mutates the source destination).
- Search: by Destination/Tribe only (no budget filter — spec doesn't list one for this domain).

**Activities** (top-nav menu: Skydiving, Rafting, etc.)
- `activities` is the master catalog — own contact info (phone/email), duration, description, linked operator, linked destination, gallery.
- Related itineraries are derived by querying `tour_activities`/`destination_activities` rows that reference a given `activities.id` — no extra join table needed.

**Media** — one polymorphic `media` table (`entity_type` + `entity_id`) instead of a separate gallery table per entity, since nearly every entity needs one.

**Bookings/Quotes/About Us** — lighter-weight, since the spec names these sections but doesn't detail fields:
- `bookings`: polymorphic (tour or experience_tour), captures the customer + surfaces the linked tour operator's contact per spec ("when someone books the tour Agent company must also appear").
- `quote_requests` (Safari Quote / Experiential Quote), `agents` (Join the Agent Pool), `careers` + `career_applications`, `blog_posts`, `pages` (About, Uganda Travel Tips, Contact Us — simple CMS body).

## Open questions / assumptions to confirm

1. **Trip Tours & School Trips** — the spec details the Safari Tours category-page template and Search filters in depth, but doesn't spell out whether Trip Tours (Island trips, Camping trips, etc.) and School Trips get the same `category_pages` landing template + search, or just use `tours` directly without a landing page. Schema supports both (`tour_categories.menu_group`); I've left `category_pages` optional per category so we're not forced to build unused admin forms yet.
2. **Booking flow** — the spec only says the operator "must appear so he can be contacted" at booking; it doesn't specify if this site takes a real payment/booking or just a contact-request (lead) form. I modeled `bookings` as a lead-capture table (status: pending/confirmed/cancelled), not a payment transaction. Confirm that's right before we build checkout.
3. **About Us pages** (Blogs, Uganda Travel Tips, Gallery, Contact Us) — minimal fields assumed since spec only lists page names, no field breakdown. Will flesh out per page once we get there.
4. **Admin roles** — only `super_admin`/`editor` assumed; tell me if Tour Operators/Service Providers need their own login to manage their listings, or if admin enters everything on their behalf.

## Next step

Once this schema looks right, next up per your priority order: scaffold the plain-PHP project structure (`/includes/db.php` PDO connection, `/admin`, `/assets`, `/uploads`) and wire the admin panel login + first CRUD (Countries → Destinations → Tour Categories) against this schema.
