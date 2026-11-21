-- ============================================================================
-- ExploreX Tours 2.0 - FRESH INSTALL for a NEW / EMPTY database.
-- Creates database `tours_travels`, loads the original v1 data, then applies
-- migrations 001-004. WARNING: it contains DROP TABLE statements, so do NOT import
-- it into a database whose data you want to keep. To upgrade an existing v1
-- database, run 001, 002, 003 and 004 in order instead (see README).
-- ============================================================================
CREATE DATABASE IF NOT EXISTS `tours_travels` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tours_travels`;

-- ---------- part 1: original v1 export (unchanged data) ----------
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 16, 2026 at 09:05 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tours_travels`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'diya kansara', 'diya1211@gmail.com', '$2y$10$CWQbAhv4shs5aCxBV2XKwOosU6i1eZ0FYEaLM.eQZ1EOMIOnv/OxO', '2025-09-05 12:43:46');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `guests` int NOT NULL DEFAULT '1',
  `package_id` int DEFAULT NULL,
  `travel_dates` varchar(50) DEFAULT NULL,
  `special_requests` text,
  `booking_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) DEFAULT 'Pending',
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_booking` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `name`, `email`, `phone`, `guests`, `package_id`, `travel_dates`, `special_requests`, `booking_date`, `created_at`, `status`, `user_id`) VALUES
(1, 'DEMO', 'demo@123.com', '01231231234', 1, 1, '2025-11-01 to 2025-11-04', '', '2025-09-18 19:45:33', '2025-09-18 19:45:33', 'Approved', 0),
(2, 'DEMO', 'demo@123.com', '01231231234', 1, 3, '2025-11-01 to 2025-11-04', '', '2025-09-18 20:51:27', '2025-09-18 20:51:27', 'Approved', 0),
(3, 'PRIYANSHI', 'PRII123@GMAIL.COM', '1234123411', 2, 4, '2025-11-01 to 2025-11-04', '', '2025-12-02 13:33:06', '2025-12-02 13:33:06', 'Cancelled', 0),
(4, 'priya patel', 'priya@gmail.com', '01122334455', 5, 4, '2025-12-12 to 2025-12-13', '', '2025-12-03 05:53:24', '2025-12-03 05:53:24', 'Pending', 0);

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'Noah Benette', 'noah123@gmail.com', 'inquiry about my booked package of mountain trek', 'inquiry about my booked package of mountain trek', '2025-09-07 11:09:45'),
(2, 'Noah Benette', 'noah123@gmail.com', 'inquiry about my booked package of mountain trek', 'ABC...', '2025-12-02 13:50:50');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
CREATE TABLE IF NOT EXISTS `packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `itinerary` text,
  `hotel_details` text,
  `inclusions_detail` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `title`, `location`, `description`, `price`, `image`, `duration`, `itinerary`, `hotel_details`, `inclusions_detail`) VALUES
(1, 'Beach Paradise', 'Goa', 'Enjoy sun, sand, and sea.', 15000.00, 'beach.jpg', '4 Days / 3 Nights', 'Day 1: Arrival in Goa and leisure at beach\nDay 2: Visit Fort Aguada and Baga Beach\nDay 3: Optional Cruise + shopping\nDay 4: Return', 'Goa: Hotel Sea View – A 4-star beachside resort with pool, spa, and continental breakfast.', 'This package includes daily buffet breakfast, guided sightseeing tours, pickup and drop to the airport, and access to private beaches. All taxes and service charges are included.'),
(2, 'Mountain Trek', 'Manali', 'Breathtaking Himalayan adventure.', 18000.00, 'mountains.jpg', '3 Days / 2 Nights', 'Day 1: Arrival and acclimatization\nDay 2: Trek to Solang Valley\nDay 3: Snow activities and return', 'Manali: Himalayan Heights Hotel – Cozy rooms with mountain views and heater facilities.', 'Includes accommodation, trekking guide, snow gear rental, daily breakfast and dinner, and transportation from Chandigarh.'),
(3, 'Desert Safari', 'Jaisalmer', 'Camel rides and golden dunes.', 14000.00, 'desert.jpg', '3 Days / 2 Nights', 'Day 1: Arrival in Jaisalmer and fort visit\nDay 2: Camel safari and desert camp\nDay 3: Return', 'Jaisalmer: Desert Pearl Camp – Luxury tents with cultural performances and bonfire dinner.', 'Includes camel rides, meals, camp stay, traditional Rajasthani dance show, and sightseeing transport.'),
(4, 'Backwaters Bliss', 'Kerala', 'Cruise through lush backwaters.', 17000.00, 'backwaters.jpg', '4 Days / 3 Nights', 'Day 1: Arrival in Kochi\nDay 2: Houseboat ride in Alleppey\nDay 3: Munnar tea gardens\nDay 4: Return', 'Kerala: Lakeview Resort – Houseboat stay with all meals, and a night at Munnar\'s hill resort.', 'Includes all meals on houseboat, air-conditioned transport, sightseeing tickets, and driver allowance.'),
(5, 'City Lights', 'Mumbai', 'Explore the vibrant city life.', 13000.00, 'mumbai.jpg', '3 Days / 2 Nights', 'Day 1: Arrival and Marine Drive walk\nDay 2: Gateway of India, Colaba market\nDay 3: Bollywood studio tour and return', 'Mumbai: Taj Gateway – Luxurious rooms with Arabian Sea view and central location.', 'Includes hotel stay, local guide, breakfast, city tour, and airport transfers.'),
(6, 'Royal Retreat', 'Udaipur', 'Experience regal luxury.', 16000.00, 'udaipur.jpg', '4 Days / 3 Nights', 'Day 1: Arrival and Lake Pichola boat ride\nDay 2: City Palace, Jag Mandir\nDay 3: Leisure and shopping\nDay 4: Return', 'Udaipur: Palace View Hotel – A heritage stay with lake views and royal decor.', 'Includes palace entry fees, deluxe room stay, breakfast, and guided tours.'),
(7, 'Alpine Escape', 'Switzerland', 'Snowy peaks and scenic trains.', 45000.00, 'swiss.jpg', '4 Days / 3 Nights', 'Day 1: Arrival in Zurich\nDay 2: Interlaken and Jungfrau region\nDay 3: Scenic train to Lucerne\nDay 4: Lake cruise and return', 'Switzerland: Swiss Lodge – Chalet-style accommodation near ski lifts.', 'Includes Swiss Rail Pass, hotel stays, breakfast, mountain excursions, and travel insurance.'),
(8, 'Parisian Romance❤', 'Paris', 'Eiffel Tower and cozy cafés.', 60000.00, 'paris.jpg', '4 Days / 3 Nights', 'Day 1: Arrival and Seine River cruise\nDay 2: Eiffel Tower and Louvre Museum\nDay 3: Versailles Day Trip\nDay 4: Return', 'Paris: Hotel Belle Vie – Boutique hotel near Eiffel Tower with complimentary wine evenings.', 'Includes breakfast, metro pass, museum entry, Seine cruise, and English-speaking guide.'),
(9, 'Safari Adventure', 'South Africa', 'Wildlife safaris and nature.', 55000.00, 'safari.jpg', '5 Days / 4 Nights', 'Day 1: Arrival in Johannesburg\nDay 2: Kruger Safari Day 1\nDay 3: Kruger Safari Day 2\nDay 4: Cultural village visit\nDay 5: Return', 'South Africa: Kruger Camp Resort – Luxury tented safari accommodation with daily game drives.', 'Includes all meals, park entry fees, safari guides, transportation, and airport pickup.'),
(10, 'Bali Bliss', 'Bali', 'Beaches, temples, and sunsets.', 38000.00, 'bali.jpg', '4 Days / 3 Nights', 'Day 1: Arrival and beach leisure\nDay 2: Uluwatu Temple and Kecak Dance\nDay 3: Waterfalls and rice terraces\nDay 4: Return', 'Bali: Ocean Cliff Resort – Infinity pool overlooking the Indian Ocean, spa, and yoga sessions.', 'Includes 3-night stay, breakfast, private driver-guide, entrance fees, and daily bottled water.');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(4, 'DEMO', 'demo@123.com', '$2y$10$LoXRcjxjbm.04qnE2mxNM.nH/woRoZaG01sMyVJqzP46519rmplH6', '2025-09-18 19:44:44'),
(5, 'rita kansara', 'rita@gmail.com', '$2y$10$EgDmThrK1JBp3KVtfgIpN.q1NujCgA1e/CZ6x/8YViAMMEHBRkEF6', '2025-12-03 05:06:23'),
(6, 'priya patel', 'priya@gmail.com', '$2y$10$PyHt6jO/DBfdS1dFDfNk9.OZJNkiTvL56xba9aVDGgaM9ZxR2zIEy', '2025-12-03 05:51:25');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

SET time_zone = 'SYSTEM';
SET SQL_MODE = '';
SET NAMES utf8mb4;

-- ---------- part: migration 001_innodb_core_schema ----------
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

-- ---------- part: migration 002_migrate_legacy_data ----------
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

-- ---------- part: migration 003_enrich_demo_catalog ----------
-- =====================================================================
-- ExploreX Tours 2.0  |  Migration 003: enrich the 10 original packages
-- Matches rows BY TITLE, so it is harmless on databases where those
-- packages were renamed or removed. Adds category, overview text,
-- featured flag, guest limit and curated inclusions / exclusions.
-- =====================================================================
SET NAMES utf8mb4;

-- category, featured flag and overview -------------------------------------
UPDATE packages p JOIN categories c ON c.slug='beach-islands'
   SET p.category_id=c.id, p.is_featured=1, p.max_guests=8,
       p.overview='Four relaxed days on Goa''s sunlit coast. Spend mornings on quiet stretches of sand, afternoons exploring Portuguese-era forts and lively beach shacks, and evenings by the sea with fresh seafood. A comfortable base, a private airport transfer and a flexible plan make this an easy first trip to Goa.'
 WHERE p.title='Beach Paradise';
UPDATE packages p JOIN categories c ON c.slug='adventure'
   SET p.category_id=c.id, p.max_guests=12,
       p.overview='A short, high-altitude escape into the Himalayas. Acclimatise in Manali, trek through pine forests to Solang Valley and finish with a day of snow activities. Guides, gear and warm stays are all arranged so you can focus on the mountains.'
 WHERE p.title='Mountain Trek';
UPDATE packages p JOIN categories c ON c.slug='heritage-culture'
   SET p.category_id=c.id, p.max_guests=10,
       p.overview='Golden sandstone, camel caravans and a night under the desert stars. Explore Jaisalmer''s living fort, ride across the dunes at sunset and enjoy folk music and a bonfire dinner at a comfortable desert camp.'
 WHERE p.title='Desert Safari';
UPDATE packages p JOIN categories c ON c.slug='backwaters-nature'
   SET p.category_id=c.id, p.is_featured=1, p.max_guests=8,
       p.overview='Drift through Kerala''s palm-lined backwaters on a private houseboat, then climb to the misty tea estates of Munnar. Slow, green and quietly luxurious, with every meal and transfer taken care of.'
 WHERE p.title='Backwaters Bliss';
UPDATE packages p JOIN categories c ON c.slug='city-breaks'
   SET p.category_id=c.id, p.max_guests=10,
       p.overview='Mumbai in three days: the Marine Drive promenade at dusk, colonial landmarks, the Colaba markets and a behind-the-scenes look at Bollywood. A well-paced city break with a local guide who knows the shortcuts.'
 WHERE p.title='City Lights';
UPDATE packages p JOIN categories c ON c.slug='heritage-culture'
   SET p.category_id=c.id, p.is_featured=1, p.max_guests=8,
       p.overview='Udaipur is the most romantic of Rajasthan''s royal cities. Stay in a heritage hotel above Lake Pichola, tour the City Palace and Jag Mandir, and leave time to wander the old-town bazaars at your own pace.'
 WHERE p.title='Royal Retreat';
UPDATE packages p JOIN categories c ON c.slug='adventure'
   SET p.category_id=c.id, p.max_guests=10,
       p.overview='Switzerland''s greatest hits in four days: the Jungfrau region, panoramic rail journeys and a lake cruise into Lucerne. Chalet-style stays, a Swiss Travel Pass and travel insurance are bundled in.'
 WHERE p.title='Alpine Escape';
UPDATE packages p JOIN categories c ON c.slug='romantic-escapes'
   SET p.category_id=c.id, p.is_featured=1, p.max_guests=6,
       p.overview='Paris for two, unhurried: a Seine cruise at golden hour, the Eiffel Tower and the Louvre, and a day at the Palace of Versailles. A boutique hotel, an English-speaking guide and museum entries keep the logistics invisible.'
 WHERE p.title LIKE 'Parisian Romance%';
UPDATE packages p JOIN categories c ON c.slug='wildlife-safari'
   SET p.category_id=c.id, p.max_guests=8,
       p.overview='Five days in and around Kruger National Park. Two full days of guided game drives, nights in a luxury tented camp and a cultural village visit give you the full South African safari experience.'
 WHERE p.title='Safari Adventure';
UPDATE packages p JOIN categories c ON c.slug='beach-islands'
   SET p.category_id=c.id, p.is_featured=1, p.max_guests=8,
       p.overview='Bali''s cliff-top temples, waterfalls and rice terraces, with beach time in between. A private driver-guide handles the roads while you enjoy an infinity pool, spa and sunset Kecak dance.'
 WHERE p.title='Bali Bliss';

-- curated inclusions / exclusions (replaces the generic split of migration 002)
DELETE pi FROM package_inclusions pi JOIN packages p ON p.id = pi.package_id
 WHERE p.title IN ('Beach Paradise','Mountain Trek','Desert Safari','Backwaters Bliss','City Lights',
                   'Royal Retreat','Alpine Escape','Safari Adventure','Bali Bliss')
    OR p.title LIKE 'Parisian Romance%';

INSERT INTO package_inclusions (package_id, item, type, sort_order)
SELECT p.id, v.item, v.type, v.so FROM packages p JOIN (
  SELECT 'Beach Paradise' t, 'Daily buffet breakfast' item, 'inclusion' type, 1 so UNION ALL
  SELECT 'Beach Paradise','Guided sightseeing tours','inclusion',2 UNION ALL
  SELECT 'Beach Paradise','Airport pickup and drop','inclusion',3 UNION ALL
  SELECT 'Beach Paradise','Access to private beaches','inclusion',4 UNION ALL
  SELECT 'Beach Paradise','All taxes and service charges','inclusion',5 UNION ALL
  SELECT 'Beach Paradise','Flights to and from Goa','exclusion',1 UNION ALL
  SELECT 'Beach Paradise','Lunch, dinner and personal expenses','exclusion',2 UNION ALL
  SELECT 'Beach Paradise','Optional cruise and water sports','exclusion',3 UNION ALL
  SELECT 'Mountain Trek','Accommodation with heating','inclusion',1 UNION ALL
  SELECT 'Mountain Trek','Experienced trekking guide','inclusion',2 UNION ALL
  SELECT 'Mountain Trek','Snow gear rental','inclusion',3 UNION ALL
  SELECT 'Mountain Trek','Daily breakfast and dinner','inclusion',4 UNION ALL
  SELECT 'Mountain Trek','Transportation from Chandigarh','inclusion',5 UNION ALL
  SELECT 'Mountain Trek','Travel to Chandigarh','exclusion',1 UNION ALL
  SELECT 'Mountain Trek','Lunches and personal expenses','exclusion',2 UNION ALL
  SELECT 'Mountain Trek','Adventure sports fees (paragliding, skiing)','exclusion',3 UNION ALL
  SELECT 'Desert Safari','Camel safari','inclusion',1 UNION ALL
  SELECT 'Desert Safari','All meals during the stay','inclusion',2 UNION ALL
  SELECT 'Desert Safari','Desert camp stay in luxury tents','inclusion',3 UNION ALL
  SELECT 'Desert Safari','Traditional Rajasthani dance show','inclusion',4 UNION ALL
  SELECT 'Desert Safari','Sightseeing transport','inclusion',5 UNION ALL
  SELECT 'Desert Safari','Travel to and from Jaisalmer','exclusion',1 UNION ALL
  SELECT 'Desert Safari','Camera fees at monuments','exclusion',2 UNION ALL
  SELECT 'Desert Safari','Personal expenses and tips','exclusion',3 UNION ALL
  SELECT 'Backwaters Bliss','All meals on the houseboat','inclusion',1 UNION ALL
  SELECT 'Backwaters Bliss','Air-conditioned transport','inclusion',2 UNION ALL
  SELECT 'Backwaters Bliss','Sightseeing tickets','inclusion',3 UNION ALL
  SELECT 'Backwaters Bliss','Driver allowance','inclusion',4 UNION ALL
  SELECT 'Backwaters Bliss','Hotel stay in Munnar','inclusion',5 UNION ALL
  SELECT 'Backwaters Bliss','Flights or trains to Kochi','exclusion',1 UNION ALL
  SELECT 'Backwaters Bliss','Meals during Munnar stay','exclusion',2 UNION ALL
  SELECT 'Backwaters Bliss','Personal expenses and tips','exclusion',3 UNION ALL
  SELECT 'City Lights','Hotel stay','inclusion',1 UNION ALL
  SELECT 'City Lights','Local guide','inclusion',2 UNION ALL
  SELECT 'City Lights','Daily breakfast','inclusion',3 UNION ALL
  SELECT 'City Lights','City tour','inclusion',4 UNION ALL
  SELECT 'City Lights','Airport transfers','inclusion',5 UNION ALL
  SELECT 'City Lights','Flights to and from Mumbai','exclusion',1 UNION ALL
  SELECT 'City Lights','Studio tour entry upgrades','exclusion',2 UNION ALL
  SELECT 'City Lights','Lunch, dinner and personal expenses','exclusion',3 UNION ALL
  SELECT 'Royal Retreat','Palace entry fees','inclusion',1 UNION ALL
  SELECT 'Royal Retreat','Deluxe room stay in a heritage hotel','inclusion',2 UNION ALL
  SELECT 'Royal Retreat','Daily breakfast','inclusion',3 UNION ALL
  SELECT 'Royal Retreat','Guided tours','inclusion',4 UNION ALL
  SELECT 'Royal Retreat','Travel to and from Udaipur','exclusion',1 UNION ALL
  SELECT 'Royal Retreat','Lunch and dinner','exclusion',2 UNION ALL
  SELECT 'Royal Retreat','Camera fees and personal expenses','exclusion',3 UNION ALL
  SELECT 'Alpine Escape','Swiss Travel Pass','inclusion',1 UNION ALL
  SELECT 'Alpine Escape','Hotel stays','inclusion',2 UNION ALL
  SELECT 'Alpine Escape','Daily breakfast','inclusion',3 UNION ALL
  SELECT 'Alpine Escape','Mountain excursions','inclusion',4 UNION ALL
  SELECT 'Alpine Escape','Travel insurance','inclusion',5 UNION ALL
  SELECT 'Alpine Escape','International flights','exclusion',1 UNION ALL
  SELECT 'Alpine Escape','Schengen visa fees','exclusion',2 UNION ALL
  SELECT 'Alpine Escape','Lunch, dinner and personal expenses','exclusion',3 UNION ALL
  SELECT 'Parisian Romance','Daily breakfast','inclusion',1 UNION ALL
  SELECT 'Parisian Romance','Metro pass','inclusion',2 UNION ALL
  SELECT 'Parisian Romance','Museum entry','inclusion',3 UNION ALL
  SELECT 'Parisian Romance','Seine river cruise','inclusion',4 UNION ALL
  SELECT 'Parisian Romance','English-speaking guide','inclusion',5 UNION ALL
  SELECT 'Parisian Romance','International flights','exclusion',1 UNION ALL
  SELECT 'Parisian Romance','Schengen visa fees','exclusion',2 UNION ALL
  SELECT 'Parisian Romance','Lunch, dinner and travel insurance','exclusion',3 UNION ALL
  SELECT 'Safari Adventure','All meals','inclusion',1 UNION ALL
  SELECT 'Safari Adventure','Park entry fees','inclusion',2 UNION ALL
  SELECT 'Safari Adventure','Safari guides and game drives','inclusion',3 UNION ALL
  SELECT 'Safari Adventure','Ground transportation','inclusion',4 UNION ALL
  SELECT 'Safari Adventure','Airport pickup','inclusion',5 UNION ALL
  SELECT 'Safari Adventure','International flights and visas','exclusion',1 UNION ALL
  SELECT 'Safari Adventure','Travel insurance','exclusion',2 UNION ALL
  SELECT 'Safari Adventure','Tips and personal expenses','exclusion',3 UNION ALL
  SELECT 'Bali Bliss','3-night resort stay','inclusion',1 UNION ALL
  SELECT 'Bali Bliss','Daily breakfast','inclusion',2 UNION ALL
  SELECT 'Bali Bliss','Private driver-guide','inclusion',3 UNION ALL
  SELECT 'Bali Bliss','Entrance fees','inclusion',4 UNION ALL
  SELECT 'Bali Bliss','Daily bottled water','inclusion',5 UNION ALL
  SELECT 'Bali Bliss','International flights','exclusion',1 UNION ALL
  SELECT 'Bali Bliss','Visa on arrival fee','exclusion',2 UNION ALL
  SELECT 'Bali Bliss','Lunch, dinner and personal expenses','exclusion',3
) v ON (p.title = v.t OR (v.t = 'Parisian Romance' AND p.title LIKE 'Parisian Romance%'));

INSERT INTO schema_migrations (version) VALUES ('003_enrich_demo_catalog');

-- ---------- part: migration 004_drop_legacy_columns ----------
-- =====================================================================
-- ExploreX Tours 2.0  |  Migration 004: remove columns replaced by
-- normalised tables. Run only after checking migration 002/003 results.
-- The original data stays available in legacy_backup_packages and
-- legacy_backup_bookings (drop those two tables manually when happy).
-- =====================================================================
ALTER TABLE packages
  DROP COLUMN itinerary,
  DROP COLUMN hotel_details,
  DROP COLUMN inclusions_detail;

ALTER TABLE bookings
  DROP COLUMN travel_dates,
  DROP COLUMN booking_date;

INSERT INTO schema_migrations (version) VALUES ('004_drop_legacy_columns');
