# ExploreX Tours 2.0 - Testing report

**Result: 173 / 173 automated checks passed** (run against a database created from `database/explorex_v2_fresh_install.sql` + `sample_data.sql`, PHP 8.3 built-in server, MariaDB 10.11). The suite is repeatable and cleans up after itself.

Run it yourself: see the *Testing* section of the README.

## Bugs found by testing and fixed before publishing
| Found by | Problem | Fix |
|---|---|---|
| Booking tests | Every booking was rejected: the shared package query did not select `max_guests` | Added to `PACKAGE_SELECT` |
| Booking tests | Traveller names were re-indexed, so traveller 2's name was lost during validation | Preserve array keys |
| Registration tests | Password errors reported the *last* rule broken instead of the first | Show the first problem |
| Admin tests | Admin login page lacked the CSRF meta tag used by scripted clients | Added |
| Review tests | Sample review state leaked between runs | Suite resets its fixtures |

## Manual / tool-assisted checks
- **Responsive:** Playwright screenshots at 1440 px (desktop), 820 px (tablet) and 390 px (mobile) of home, tours, details, booking, checkout, dashboards and admin pages (`docs/screenshots/`). Horizontal-overflow audit of 5 public pages x 3 widths: **0 px overflow everywhere**. The mobile menu, stacked cards and filter drawer were inspected visually.
- **Fresh-install and upgrade paths:** the v1 dump was imported and migrations 001-004 applied in order (4 bookings in -> 3 linked to users, 1 unmatched removed and preserved in `legacy_backup_bookings`); the combined fresh-install file was imported into an empty server; both produce the same schema (InnoDB, 14 foreign keys). Sample data import is idempotent (importing it twice adds nothing).
- **Server log:** no PHP warnings or notices during the final run.

## Not covered
- Not executed on MySQL 9.1 (WAMP's engine); only MariaDB 10.11 was available.
- Real browsers other than Chromium; screen-reader testing; load/performance testing.

## Full automated log
```
== 1. Public pages and discovery
  PASS  GET index.php returns 200 and content
  PASS  GET packages.php returns 200 and content
  PASS  GET about.php returns 200 and content
  PASS  GET contact.php returns 200 and content
  PASS  GET package_details.php?id=1 returns 200 and content
  PASS  Search "goa" finds Beach Paradise only
  PASS  Price filter < 20,000 excludes Paris
  PASS  Duration filter 5+ days returns Safari Adventure
  PASS  Category filter works
  PASS  No-result search shows empty state
  PASS  Sort by price high to low orders cards
  PASS  Unknown package returns 404
  PASS  Non-numeric package id returns 404
  PASS  Public admin registration page is gone
  PASS  hash.php helper removed

== 2. Security: injection, XSS, headers
  PASS  SQLi payload in search is harmless: ' OR '1'='1
  PASS  SQLi payload in search is harmless: 1; DROP TABLE us
  PASS  SQLi payload in search is harmless: " OR 1=1 --
  PASS  SQLi payload in search is harmless: %' UNION SELECT 
  PASS  SQLi in package id rejected
  PASS  ORDER BY injection whitelisted
  PASS  Reflected XSS escaped in search
  PASS  Security header X-Frame-Options present
  PASS  Security header X-Content-Type-Options present
  PASS  Security header Content-Security-Policy present
  PASS  Security header Referrer-Policy present
  PASS  Session cookie is HttpOnly + SameSite
  PASS  includes/ code is not leaking output (blank or error)
  PASS  Admin include file does not expose data

== 3. Customer: registration, login, session
  PASS  Weak password rejected
  PASS  Invalid email rejected
  PASS  Password mismatch rejected
  PASS  Registration succeeds and lands on dashboard
  PASS  Session ID regenerated after registration/login (fixation)
  PASS  User name is HTML-escaped on dashboard (stored XSS)
  PASS  Duplicate email rejected
  PASS  Logout (POST) ends session
  PASS  Login without CSRF token rejected (419)
  PASS  Wrong password shows generic error
  PASS  Unknown email gives the same generic error
  PASS  Open redirect via next= ignored
  PASS  Valid login honours safe next= path
  PASS  Dashboard requires login
  PASS  Booking page requires login
  PASS  Brute-force lockout after repeated failures

== 4. Customer: wishlist
  PASS  Guest wishlist toggle returns 401
  PASS  Add to wishlist
  PASS  Add a second package
  PASS  Wishlist page shows saved tours with detail links
  PASS  Toggle removes (no duplicate entries)
  PASS  Wishlist POST without CSRF rejected
  PASS  Wishlist for missing package -> 404
  PASS  Wishlist GET not allowed (405)

== 5. Booking workflow, payment simulation, ownership
  PASS  Past travel date rejected
  PASS  Invalid date rejected
  PASS  Zero guests rejected
  PASS  Too many guests rejected
  PASS  Invalid contact email rejected
  PASS  Invalid phone rejected
  PASS  Missing second traveller name rejected
  PASS  Non-numeric guests rejected
  PASS  Review step shows server-calculated total (₹30,000), ignoring tampered price
  PASS  Review step issues one-time submit token
  PASS  Confirm creates booking and redirects to checkout with reference
  PASS  Duplicate submission (same token) does NOT create a second booking
  PASS  Booking history lists exactly one new booking
  PASS  Booking details show status Pending, travellers and Unpaid
  PASS  Checkout shows demo banner and no card fields
  PASS  Simulated payment FAILURE recorded with transaction ref
  PASS  Payment history shows Failed attempt
  PASS  Simulated payment SUCCESS leads to confirmation page
  PASS  Already-paid booking cannot be paid twice
  PASS  Dashboard shows the upcoming trip
  PASS  OWNERSHIP: other customer cannot view booking
  PASS  OWNERSHIP: other customer cannot open checkout
  PASS  OWNERSHIP: other customer cannot open confirmation
  PASS  OWNERSHIP: other customer cannot cancel booking
  PASS  OWNERSHIP: other customer history does not list it
  PASS  OWNERSHIP: other customer cannot pay for it
  PASS  Cancellation request without CSRF rejected
  PASS  Customer can request cancellation
  PASS  Second cancellation request is refused
  PASS  Cannot review a non-completed booking

== 6. Reviews (eligibility + duplicates)
  PASS  Eligible completed booking shows review form
  PASS  Review validation (rating + length)
  PASS  Review submitted and awaits moderation
  PASS  Duplicate review for the same booking blocked
  PASS  OWNERSHIP: other customer cannot review someone else's booking
  PASS  Pending review is NOT public yet

== 7. Customer: profile management
  PASS  Profile update saved
  PASS  Profile validation (name too short)
  PASS  Cannot take another account's email
  PASS  Password change needs correct current password
  PASS  Password changed
  PASS  Login works with the new password

== 8. Contact form and newsletter
  PASS  Contact validation
  PASS  Contact POST without CSRF rejected
  PASS  Contact message sent
  PASS  Newsletter subscription works

== 9. Admin: authentication and authorisation
  PASS  Anonymous blocked from admin/dashboard.php
  PASS  Anonymous blocked from admin/manage_packages.php
  PASS  Anonymous blocked from admin/manage_bookings.php
  PASS  Anonymous blocked from admin/manage_users.php
  PASS  Anonymous blocked from admin/reviews.php
  PASS  Anonymous blocked from admin/view_messages.php
  PASS  Anonymous blocked from admin/payments.php
  PASS  Anonymous blocked from admin/admins.php
  PASS  Anonymous blocked from admin/add_package.php
  PASS  Anonymous POST to admin/booking_action.php does not act
  PASS  Anonymous POST to admin/package_action.php does not act
  PASS  Anonymous POST to admin/user_action.php does not act
  PASS  Anonymous POST to admin/review_action.php does not act
  PASS  Anonymous POST to admin/message_action.php does not act
  PASS  Customer session blocked from admin/dashboard.php
  PASS  Customer session blocked from admin/manage_packages.php
  PASS  Customer session blocked from admin/manage_users.php
  PASS  Admin credentials supplied for tests
  PASS  Admin wrong password rejected
  PASS  Admin login without CSRF rejected
  PASS  Admin login succeeds
  PASS  Dashboard shows real statistics blocks and Chart.js canvases
  PASS  Dashboard revenue equals the SQL sum of eligible bookings
  PASS  Admin state change without CSRF rejected
  PASS  Admin state change via GET refused (405)
  PASS  Package delete via GET refused (405)

== 10. Admin: package management and secure uploads
  PASS  Upload: PHP code disguised as PNG rejected
  PASS  Upload: .php file rejected
  PASS  Upload: polyglot with image header rejected
  PASS  Upload: oversize file rejected
  PASS  Upload: main image required on create
  PASS  Package validation (title + price)
  PASS  Package created with itinerary, hotel, inclusions
  PASS  Uploaded file stored with generated name in uploads/packages
  PASS  New package visible publicly with itinerary/hotel/inclusions
  PASS  New package searchable
  PASS  Package edit saved (image kept)
  PASS  Edit reflected on public page (price + itinerary replaced)
  PASS  Package can be hidden
  PASS  Hidden package is 404 for customers
  PASS  Package with bookings cannot be deleted (history preserved)
  PASS  Admin package search works
  PASS  Package without bookings deleted
  PASS  Image file removed from disk on delete

== 11. Admin: bookings, payments, users, reviews, messages
  PASS  Bookings list renders with references
  PASS  Booking search by reference
  PASS  Booking filter by status
  PASS  Cancellation-request filter lists the customer request
  PASS  Admin search is injection-safe
  PASS  Admin booking detail shows cancellation request, travellers, payments
  PASS  Pending booking cannot jump straight to Completed
  PASS  Admin confirms booking
  PASS  Cannot complete a trip that has not ended
  PASS  Customer sees Confirmed status
  PASS  Admin can decline cancellation request
  PASS  Invalid transition (Confirmed -> Pending) refused
  PASS  Admin cancels booking
  PASS  Cancelled booking is final
  PASS  Payments page lists simulated records with demo notice
  PASS  Payment filter by status
  PASS  Customer search
  PASS  Customer detail shows bookings and delete safeguard
  PASS  Deleting customer with active bookings refused
  PASS  Review moderation lists pending reviews
  PASS  Admin approves review
  PASS  Approved review now public on package page
  PASS  Contact messages listed (HTML escaped)
  PASS  Message can be marked read
  PASS  Super/administrator page reachable by admin
  PASS  Admin password change needs current password
  PASS  Admin logout works

== 12. Responsive markup sanity
  PASS  Viewport meta + skip link present
  PASS  CSS has tablet and mobile breakpoints
  PASS  Home page assets all load (no broken CSS/JS/images)

TOTAL: 173  PASSED: 173  FAILED: 0
```
