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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
