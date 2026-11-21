-- =====================================================================
-- ExploreX Tours 2.0  |  Migration 002: convert legacy data
-- * splits free-text itinerary / hotel / inclusion columns into tables
-- * converts bookings (string dates -> DATE, status values, prices)
-- * links bookings to users by email (the old user_id was always 0)
-- * deletes legacy bookings that match no user or package
--   (approved by the project owner; the originals remain in
--    legacy_backup_bookings, so this can be undone)
-- =====================================================================
SET NAMES utf8mb4;

-- helper number table (1..60) used for splitting text lines ----------------
CREATE TEMPORARY TABLE seq (n INT NOT NULL PRIMARY KEY);
INSERT INTO seq (n) VALUES (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),(11),(12),(13),(14),(15),(16),(17),(18),(19),(20),(21),(22),(23),(24),(25),(26),(27),(28),(29),(30),(31),(32),(33),(34),(35),(36),(37),(38),(39),(40),(41),(42),(43),(44),(45),(46),(47),(48),(49),(50),(51),(52),(53),(54),(55),(56),(57),(58),(59),(60);

-- 1. itinerary: one row per line -------------------------------------------
INSERT INTO itinerary_days (package_id, day_number, title)
SELECT x.package_id, x.n,
       LEFT(TRIM(CASE WHEN LEFT(x.ln, 3) IN ('Day','day') AND LOCATE(':', x.ln) > 0
                      THEN SUBSTRING(x.ln, LOCATE(':', x.ln) + 1) ELSE x.ln END), 200)
FROM (
  SELECT p.id AS package_id, s.n AS n,
         TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.txt, CHAR(10), s.n), CHAR(10), -1)) AS ln
  FROM (SELECT id, REPLACE(itinerary, CHAR(13), '') AS txt FROM packages
        WHERE itinerary IS NOT NULL AND itinerary <> '') p
  JOIN seq s ON s.n <= 1 + CHAR_LENGTH(p.txt) - CHAR_LENGTH(REPLACE(p.txt, CHAR(10), ''))
) x
WHERE x.ln <> ''
ORDER BY x.package_id, x.n;

-- 2. hotels: "City: Hotel name – description" ------------------------------
INSERT INTO hotels (package_id, city, name, description)
SELECT y.id,
       CASE WHEN LOCATE(':', y.hotel_details) > 0 THEN LEFT(TRIM(SUBSTRING_INDEX(y.hotel_details, ':', 1)), 100) ELSE NULL END,
       LEFT(TRIM(SUBSTRING_INDEX(y.rest, ' – ', 1)), 150),
       CASE WHEN LOCATE(' – ', y.rest) > 0 THEN TRIM(SUBSTRING(y.rest, LOCATE(' – ', y.rest) + 3)) ELSE NULL END
FROM (
  SELECT id, hotel_details,
         TRIM(CASE WHEN LOCATE(':', hotel_details) > 0
                   THEN SUBSTRING(hotel_details, LOCATE(':', hotel_details) + 1)
                   ELSE hotel_details END) AS rest
  FROM packages WHERE hotel_details IS NOT NULL AND hotel_details <> ''
) y;
UPDATE hotels SET star_rating = 5 WHERE star_rating IS NULL AND description LIKE '%5-star%';
UPDATE hotels SET star_rating = 4 WHERE star_rating IS NULL AND description LIKE '%4-star%';
UPDATE hotels SET star_rating = 3 WHERE star_rating IS NULL AND description LIKE '%3-star%';

-- 3. inclusions: generic fallback = one item per sentence -------------------
INSERT INTO package_inclusions (package_id, item, type, sort_order)
SELECT z.package_id, LEFT(z.item, 255), 'inclusion', z.n
FROM (
  SELECT p.id AS package_id, s.n AS n,
         TRIM(TRAILING '.' FROM TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.txt, '. ', s.n), '. ', -1))) AS item
  FROM (SELECT id, REPLACE(REPLACE(inclusions_detail, CHAR(13), ' '), CHAR(10), ' ') AS txt FROM packages
        WHERE inclusions_detail IS NOT NULL AND inclusions_detail <> '') p
  JOIN seq s ON s.n <= 1 + (CHAR_LENGTH(p.txt) - CHAR_LENGTH(REPLACE(p.txt, '. ', ''))) / 2
) z
WHERE z.item <> '';

DROP TEMPORARY TABLE seq;

-- 4. bookings: statuses ------------------------------------------------------
UPDATE bookings SET status = 'Confirmed' WHERE status = 'Approved';
UPDATE bookings SET status = 'Pending'   WHERE status IS NULL OR status NOT IN ('Pending','Confirmed','Cancelled','Completed');

-- 5. bookings: parse "YYYY-MM-DD to YYYY-MM-DD" ------------------------------
UPDATE bookings
   SET travel_date = STR_TO_DATE(TRIM(SUBSTRING_INDEX(travel_dates, 'to', 1)), '%Y-%m-%d'),
       return_date = IF(LOCATE('to', travel_dates) > 0,
                        STR_TO_DATE(TRIM(SUBSTRING_INDEX(travel_dates, 'to', -1)), '%Y-%m-%d'), NULL);
UPDATE bookings SET travel_date = DATE(created_at) WHERE travel_date IS NULL;
UPDATE bookings b JOIN packages p ON p.id = b.package_id
   SET b.return_date = DATE_ADD(b.travel_date, INTERVAL p.duration_days - 1 DAY)
 WHERE b.return_date IS NULL OR b.return_date < b.travel_date;
UPDATE bookings b JOIN packages p ON p.id = b.package_id
   SET b.unit_price = p.price, b.total_amount = p.price * b.guests;

-- 6. link bookings to users by e-mail (old user_id was 0 everywhere) --------
UPDATE bookings b JOIN users u ON LOWER(u.email) = LOWER(b.contact_email)
   SET b.user_id = u.id
 WHERE b.user_id = 0 OR b.user_id NOT IN (SELECT id FROM users);

-- 7. remove legacy rows that cannot be linked (owner-approved; backed up) ----
DELETE FROM bookings
 WHERE user_id = 0 OR user_id NOT IN (SELECT id FROM users)
    OR package_id IS NULL OR package_id NOT IN (SELECT id FROM packages);

-- 8. finalise booking columns ------------------------------------------------
UPDATE bookings SET booking_ref = CONCAT('EX', DATE_FORMAT(created_at, '%y%m%d'), '-L', LPAD(id, 5, '0'))
 WHERE booking_ref IS NULL;
UPDATE bookings SET contact_name = 'Traveller' WHERE contact_name IS NULL;
UPDATE bookings SET contact_email = '' WHERE contact_email IS NULL;
UPDATE bookings SET contact_phone = '' WHERE contact_phone IS NULL;
-- trips that are already over and were confirmed count as completed
UPDATE bookings SET status = 'Completed' WHERE status = 'Confirmed' AND return_date < CURDATE();

ALTER TABLE bookings
  MODIFY booking_ref   VARCHAR(24)  NOT NULL,
  MODIFY contact_name  VARCHAR(100) NOT NULL,
  MODIFY contact_email VARCHAR(150) NOT NULL,
  MODIFY contact_phone VARCHAR(20)  NOT NULL,
  MODIFY package_id    INT NOT NULL,
  MODIFY user_id       INT NULL,
  MODIFY guests        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  MODIFY travel_date   DATE NOT NULL,
  MODIFY return_date   DATE NOT NULL,
  MODIFY unit_price    DECIMAL(10,2) NOT NULL,
  MODIFY total_amount  DECIMAL(10,2) NOT NULL,
  MODIFY status        ENUM('Pending','Confirmed','Cancelled','Completed') NOT NULL DEFAULT 'Pending',
  MODIFY created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  DROP INDEX fk_user_booking,
  ADD UNIQUE KEY uq_bookings_ref (booking_ref),
  ADD UNIQUE KEY uq_bookings_token (submit_token),
  ADD KEY idx_bookings_user (user_id, status),
  ADD KEY idx_bookings_package (package_id),
  ADD KEY idx_bookings_status_created (status, created_at),
  ADD KEY idx_bookings_travel (travel_date),
  ADD CONSTRAINT fk_bookings_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE SET NULL,
  ADD CONSTRAINT fk_bookings_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE RESTRICT,
  ADD CONSTRAINT chk_bookings_guests CHECK (guests >= 1);

ALTER TABLE booking_travellers
  ADD CONSTRAINT fk_travellers_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE;
ALTER TABLE payments
  ADD CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE;
ALTER TABLE reviews
  ADD CONSTRAINT fk_reviews_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE;

-- 9. every legacy booking gets its lead traveller row -----------------------
INSERT INTO booking_travellers (booking_id, full_name, is_lead)
SELECT id, contact_name, 1 FROM bookings;

INSERT INTO schema_migrations (version) VALUES ('002_migrate_legacy_data');
