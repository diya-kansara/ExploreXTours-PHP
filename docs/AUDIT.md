# ExploreX Tours v1 - audit report

Scope: the original ZIP and the GitHub repository at the time of the audit (they were identical; the repository had a single commit on `main`). About 2,000 lines of PHP, one 128-line stylesheet, an empty `js/main.js`, 11 photos, a MyISAM SQL export.

## Bugs that broke functionality
| # | Problem | Resolution in 2.0 |
|---|---|---|
| 1 | `book.php` inserted bookings without `user_id`, a `NOT NULL` column with no default (fails on strict MySQL). All 4 stored bookings had `user_id = 0`, matching no user | Bookings always store the authenticated user's id; migration 002 re-links old rows by email and a foreign key now enforces the relationship |
| 2 | Customer dashboard looked bookings up **by email**, so bookings made with another email were invisible and typing someone else's email exposed their bookings | Everything is keyed on the session user id; ownership is checked inside the SQL (`WHERE user_id = ?`) |
| 3 | Admin add/edit package wrote to `hotels` and `inclusions` but the table has `hotel_details` and `inclusions_detail`; edit read undefined keys | Hotels, inclusions/exclusions, itinerary and gallery moved to proper child tables, and the admin form was rebuilt on them |
| 4 | Every link and asset used a hard-coded `/Online-Tours-and-Travels-Website/` path | Base path is auto-detected (optional override in config) |
| 5 | Asset folder `CSS/` requested as `css/` (works on Windows, breaks on Linux) | Assets moved to `assets/css`, `assets/js`, `assets/img` |
| 6 | Status values did not match the intended workflow (`Approved`) | `Pending / Confirmed / Cancelled / Completed` enum; legacy `Approved` becomes `Confirmed` |
| 7 | Travel dates stored as free text ("2025-11-01 to 2025-11-04"); only the first date was validated; no guest limit | `DATE` columns, server-side validation, guest limits per package, return date computed from the duration |
| 8 | Booking form posted a hidden `package_id` that was never used | Replaced by a validated booking workflow |

## Security findings
- **Public admin registration** (`admin/admin_register.php`): anyone could create an admin. Removed; admins are created by a super admin or by CLI.
- **No CSRF protection** and **state changes over GET** (approve, cancel, delete links). All state changes are now POST with tokens.
- **SQL built from variables** in several pages (e.g. `contact.php`, `package_details.php`, booking and user admin pages). Every query now uses prepared statements.
- **Unsafe image upload**: client-supplied MIME type trusted, original extension kept, no size limit, stored in the public images folder. Now: size limit, real MIME sniffing, image re-encoding, random names, execution disabled in `uploads/`.
- **Unescaped output** in places (image name, status). All output is escaped.
- **Weak sessions/passwords**: no cookie hardening, 6-character minimum, error messages revealed whether an email exists. Hardened sessions, 8+ characters with letter and number, generic messages, login throttling.
- **Credentials committed** (`includes/db.php` with root/empty password) and a committed `hash.php` helper. Now `config/config.php` (git-ignored); `hash.php` removed.
- **Deleting a user deleted their bookings** with no safeguard; now guarded and history is kept.

## Database findings
- All tables MyISAM (no foreign keys, no transactions); only an index named `fk_user_booking` with no constraint.
- Itinerary, hotels and inclusions were text blobs on `packages`; no categories, numeric duration or gallery.
- Duplicate timestamp columns on `bookings`; nullable prices and package ids.

## UI findings
Mixed Bootstrap 4 and 5, inline-styled hero, minimal stylesheet, no JavaScript, no search/filter, no wishlist, reviews, payments or analytics. Replaced by a custom responsive design system (ivory / forest green / sage).
