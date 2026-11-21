-- =====================================================================
-- ExploreX Tours 2.0  |  Migration 001: InnoDB conversion + new schema
-- Safe to run ONCE on an existing v1 `tours_travels` database.
-- Nothing is dropped here. Full copies of the original `bookings` and
-- `packages` tables are kept in legacy_backup_* tables first.
-- Compatible with MySQL 8/9 (WAMP) and MariaDB 10.5+.
-- =====================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version    VARCHAR(100) NOT NULL PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 0. Safety copies of the original data --------------------------------
CREATE TABLE legacy_backup_bookings LIKE bookings;
INSERT INTO legacy_backup_bookings SELECT * FROM bookings;
CREATE TABLE legacy_backup_packages LIKE packages;
INSERT INTO legacy_backup_packages SELECT * FROM packages;
ALTER TABLE legacy_backup_bookings ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE legacy_backup_packages ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 1. Convert every table to InnoDB + one collation ----------------------
ALTER TABLE admins   ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users    ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE packages ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE bookings ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE contacts ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. admins: roles ------------------------------------------------------
ALTER TABLE admins
  MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin' AFTER password,
  ADD COLUMN last_login_at DATETIME NULL;
UPDATE admins SET role = 'super_admin' ORDER BY id LIMIT 1;

-- 3. users --------------------------------------------------------------
UPDATE users SET name = 'Traveller' WHERE name IS NULL OR name = '';
UPDATE users SET password = '' WHERE password IS NULL;
ALTER TABLE users
  MODIFY name       VARCHAR(100) NOT NULL,
  MODIFY email      VARCHAR(150) NOT NULL,
  MODIFY password   VARCHAR(255) NOT NULL,
  MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN phone VARCHAR(20) NULL AFTER email,
  ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 4. categories ---------------------------------------------------------
CREATE TABLE categories (
  id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(80) NOT NULL,
  slug       VARCHAR(80) NOT NULL,
  tagline    VARCHAR(160) NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, slug, tagline, sort_order) VALUES
 ('Beaches & Islands',   'beach-islands',     'Sun, sand and slow mornings',        1),
 ('Adventure & Trekking','adventure',         'Peaks, trails and big skies',        2),
 ('Heritage & Culture',  'heritage-culture',  'Palaces, forts and living traditions',3),
 ('Wildlife & Safari',   'wildlife-safari',   'Wild places, up close',              4),
 ('Romantic Escapes',    'romantic-escapes',  'Made for two',                       5),
 ('City Breaks',         'city-breaks',       'Short, vivid urban getaways',        6),
 ('Backwaters & Nature', 'backwaters-nature', 'Green, quiet and unhurried',         7);

-- 5. packages -----------------------------------------------------------
UPDATE packages SET title    = 'Untitled package' WHERE title IS NULL OR title = '';
UPDATE packages SET location = 'Unknown'          WHERE location IS NULL OR location = '';
UPDATE packages SET price    = 0                  WHERE price IS NULL;
UPDATE packages SET image    = ''                 WHERE image IS NULL;

ALTER TABLE packages
  MODIFY title    VARCHAR(255)  NOT NULL,
  MODIFY location VARCHAR(255)  NOT NULL,
  MODIFY price    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  MODIFY image    VARCHAR(255)  NOT NULL DEFAULT '',
  MODIFY duration VARCHAR(50)   NULL COMMENT 'Display label e.g. 4 Days / 3 Nights',
  ADD COLUMN category_id   INT NULL AFTER location,
  ADD COLUMN duration_days TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER duration,
  ADD COLUMN max_guests    TINYINT UNSIGNED NOT NULL DEFAULT 10 AFTER duration_days,
  ADD COLUMN overview      TEXT NULL AFTER description,
  ADD COLUMN is_featured   TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN is_active     TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD KEY idx_packages_category (category_id),
  ADD KEY idx_packages_price (price),
  ADD KEY idx_packages_location (location),
  ADD KEY idx_packages_active_featured (is_active, is_featured),
  ADD CONSTRAINT fk_packages_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

UPDATE packages
   SET duration_days = IF(SUBSTRING_INDEX(duration, ' ', 1) REGEXP '^[0-9]+$' AND CAST(SUBSTRING_INDEX(duration, ' ', 1) AS UNSIGNED) BETWEEN 1 AND 60,
                          CAST(SUBSTRING_INDEX(duration, ' ', 1) AS UNSIGNED), 1);

-- 6. package child tables -----------------------------------------------
CREATE TABLE package_images (
  id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  package_id INT NOT NULL,
  image      VARCHAR(255) NOT NULL,
  caption    VARCHAR(150) NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  KEY idx_pimg_package (package_id, sort_order),
  CONSTRAINT fk_pimg_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE itinerary_days (
  id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  package_id  INT NOT NULL,
  day_number  TINYINT UNSIGNED NOT NULL,
  title       VARCHAR(200) NOT NULL,
  description TEXT NULL,
  UNIQUE KEY uq_itinerary_day (package_id, day_number),
  CONSTRAINT fk_itin_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hotels (
  id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  package_id  INT NOT NULL,
  name        VARCHAR(150) NOT NULL,
  city        VARCHAR(100) NULL,
  star_rating TINYINT UNSIGNED NULL,
  description TEXT NULL,
  sort_order  SMALLINT NOT NULL DEFAULT 0,
  KEY idx_hotels_package (package_id),
  CONSTRAINT fk_hotels_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
  CONSTRAINT chk_hotels_star CHECK (star_rating IS NULL OR star_rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_inclusions (
  id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  package_id INT NOT NULL,
  item       VARCHAR(255) NOT NULL,
  type       ENUM('inclusion','exclusion') NOT NULL DEFAULT 'inclusion',
  sort_order SMALLINT NOT NULL DEFAULT 0,
  KEY idx_incl_package (package_id, type, sort_order),
  CONSTRAINT fk_incl_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. contacts -----------------------------------------------------------
ALTER TABLE contacts
  MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0,
  ADD KEY idx_contacts_read (is_read, created_at);

-- 8. bookings: new columns (data is converted in migration 002) ----------
ALTER TABLE bookings
  CHANGE name  contact_name  VARCHAR(100) NULL,
  CHANGE email contact_email VARCHAR(150) NULL,
  CHANGE phone contact_phone VARCHAR(20)  NULL,
  ADD COLUMN booking_ref  VARCHAR(24) NULL AFTER id,
  ADD COLUMN travel_date  DATE NULL,
  ADD COLUMN return_date  DATE NULL,
  ADD COLUMN unit_price   DECIMAL(10,2) NULL,
  ADD COLUMN total_amount DECIMAL(10,2) NULL,
  ADD COLUMN cancellation_requested TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN cancellation_reason    VARCHAR(500) NULL,
  ADD COLUMN cancellation_requested_at DATETIME NULL,
  ADD COLUMN submit_token CHAR(32) NULL,
  ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 9. new transactional tables -------------------------------------------
CREATE TABLE booking_travellers (
  id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  full_name  VARCHAR(100) NOT NULL,
  age        TINYINT UNSIGNED NULL,
  is_lead    TINYINT(1) NOT NULL DEFAULT 0,
  KEY idx_travellers_booking (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id              INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  booking_id      INT NOT NULL,
  transaction_ref VARCHAR(30) NOT NULL,
  amount          DECIMAL(10,2) NOT NULL,
  method          ENUM('Demo Card','Demo UPI','Demo Net Banking') NOT NULL DEFAULT 'Demo Card',
  status          ENUM('Success','Failed') NOT NULL,
  failure_reason  VARCHAR(150) NULL,
  is_simulated    TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Always 1: demonstration payments only, no real money or card data',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_payments_txn (transaction_ref),
  KEY idx_payments_booking (booking_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wishlist (
  user_id    INT NOT NULL,
  package_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, package_id),
  KEY idx_wishlist_package (package_id),
  CONSTRAINT fk_wishlist_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
  id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  booking_id  INT NOT NULL,
  user_id     INT NOT NULL,
  package_id  INT NOT NULL,
  rating      TINYINT UNSIGNED NOT NULL,
  title       VARCHAR(120) NULL,
  comment     TEXT NOT NULL,
  status      ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reviews_booking (booking_id),
  KEY idx_reviews_package (package_id, status),
  KEY idx_reviews_user (user_id),
  CONSTRAINT fk_reviews_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_reviews_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  scope        ENUM('user','admin') NOT NULL,
  email        VARCHAR(150) NOT NULL,
  ip           VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempts_ip (ip, attempted_at),
  KEY idx_attempts_email (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE newsletter_subscribers (
  id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(150) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_newsletter_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schema_migrations (version) VALUES ('001_innodb_core_schema');
