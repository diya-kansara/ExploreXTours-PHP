-- ExploreX Tours 2.0 | OPTIONAL sample data for demos and screenshots.
-- Creates 4 sample customers (password for all: Demo@1234), bookings, simulated payments
-- and reviews, with dates relative to the day you import this file.
-- Run AFTER the migrations. Safe to skip on a real site. Re-running is blocked by the sample refs.
SET NAMES utf8mb4;

INSERT IGNORE INTO users (name, email, phone, password) VALUES
('Aarav Mehta','aarav.mehta@example.com','+91 98765 43210','$2y$10$pr/0GBUSr7T6kOOIrwV1TeKftYEeaLIRJK1weiCGV5AURGOw4ytsC'),
('Sneha Iyer','sneha.iyer@example.com','+91 91234 56780','$2y$10$pr/0GBUSr7T6kOOIrwV1TeKftYEeaLIRJK1weiCGV5AURGOw4ytsC'),
('Rohan Das','rohan.das@example.com','+91 99887 76655','$2y$10$pr/0GBUSr7T6kOOIrwV1TeKftYEeaLIRJK1weiCGV5AURGOw4ytsC'),
('Meera Nair','meera.nair@example.com','+91 90000 11122','$2y$10$pr/0GBUSr7T6kOOIrwV1TeKftYEeaLIRJK1weiCGV5AURGOw4ytsC');

INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-001', u.id, p.id, u.name, u.email, u.phone, 2, DATE_ADD(CURDATE(), INTERVAL -120 DAY), DATE_ADD(CURDATE(), INTERVAL -120 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 2, 'Completed', DATE_SUB(NOW(), INTERVAL 150 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'Beach Paradise%' WHERE u.email = 'aarav.mehta@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-001');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-001' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO payments (booking_id, transaction_ref, amount, method, status, created_at)
SELECT b.id, 'TXN-DEMO-SAMPLE001', b.total_amount, 'Demo UPI', 'Success', DATE_ADD(b.created_at, INTERVAL 5 MINUTE) FROM bookings b
 WHERE b.booking_ref = 'EXSAMPLE-001' AND NOT EXISTS (SELECT 1 FROM payments y WHERE y.transaction_ref = 'TXN-DEMO-SAMPLE001');
INSERT IGNORE INTO reviews (booking_id, user_id, package_id, rating, title, comment, status, created_at)
SELECT b.id, b.user_id, b.package_id, 5, 'Perfect first Goa trip', 'Everything ran on time, the hotel was right on the beach and the itinerary left enough free time. Would book again.', 'Approved', DATE_ADD(b.return_date, INTERVAL 2 DAY) FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-001';
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-002', u.id, p.id, u.name, u.email, u.phone, 2, DATE_ADD(CURDATE(), INTERVAL -95 DAY), DATE_ADD(CURDATE(), INTERVAL -95 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 2, 'Completed', DATE_SUB(NOW(), INTERVAL 125 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'Backwaters Bliss%' WHERE u.email = 'sneha.iyer@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-002');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-002' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO payments (booking_id, transaction_ref, amount, method, status, created_at)
SELECT b.id, 'TXN-DEMO-SAMPLE002', b.total_amount, 'Demo Net Banking', 'Success', DATE_ADD(b.created_at, INTERVAL 5 MINUTE) FROM bookings b
 WHERE b.booking_ref = 'EXSAMPLE-002' AND NOT EXISTS (SELECT 1 FROM payments y WHERE y.transaction_ref = 'TXN-DEMO-SAMPLE002');
INSERT IGNORE INTO reviews (booking_id, user_id, package_id, rating, title, comment, status, created_at)
SELECT b.id, b.user_id, b.package_id, 5, 'Houseboat dreams', 'The houseboat night was the highlight of our year. Meals were fresh and the Munnar leg was beautifully paced.', 'Approved', DATE_ADD(b.return_date, INTERVAL 2 DAY) FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-002';
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-003', u.id, p.id, u.name, u.email, u.phone, 3, DATE_ADD(CURDATE(), INTERVAL -70 DAY), DATE_ADD(CURDATE(), INTERVAL -70 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 3, 'Completed', DATE_SUB(NOW(), INTERVAL 100 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'Royal Retreat%' WHERE u.email = 'rohan.das@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-003');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-003' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO payments (booking_id, transaction_ref, amount, method, status, created_at)
SELECT b.id, 'TXN-DEMO-SAMPLE003', b.total_amount, 'Demo Card', 'Success', DATE_ADD(b.created_at, INTERVAL 5 MINUTE) FROM bookings b
 WHERE b.booking_ref = 'EXSAMPLE-003' AND NOT EXISTS (SELECT 1 FROM payments y WHERE y.transaction_ref = 'TXN-DEMO-SAMPLE003');
INSERT IGNORE INTO reviews (booking_id, user_id, package_id, rating, title, comment, status, created_at)
SELECT b.id, b.user_id, b.package_id, 4, 'Royal and relaxed', 'Great heritage stay and a knowledgeable guide. Shopping day could have been a little shorter, otherwise wonderful.', 'Approved', DATE_ADD(b.return_date, INTERVAL 2 DAY) FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-003';
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-004', u.id, p.id, u.name, u.email, u.phone, 2, DATE_ADD(CURDATE(), INTERVAL -45 DAY), DATE_ADD(CURDATE(), INTERVAL -45 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 2, 'Completed', DATE_SUB(NOW(), INTERVAL 80 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'Bali Bliss%' WHERE u.email = 'meera.nair@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-004');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-004' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO payments (booking_id, transaction_ref, amount, method, status, created_at)
SELECT b.id, 'TXN-DEMO-SAMPLE004', b.total_amount, 'Demo UPI', 'Success', DATE_ADD(b.created_at, INTERVAL 5 MINUTE) FROM bookings b
 WHERE b.booking_ref = 'EXSAMPLE-004' AND NOT EXISTS (SELECT 1 FROM payments y WHERE y.transaction_ref = 'TXN-DEMO-SAMPLE004');
INSERT IGNORE INTO reviews (booking_id, user_id, package_id, rating, title, comment, status, created_at)
SELECT b.id, b.user_id, b.package_id, 5, 'Cliffs, temples and sunsets', 'The driver-guide made everything effortless and the Uluwatu sunset dance was unforgettable.', 'Approved', DATE_ADD(b.return_date, INTERVAL 2 DAY) FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-004';
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-005', u.id, p.id, u.name, u.email, u.phone, 4, DATE_ADD(CURDATE(), INTERVAL -25 DAY), DATE_ADD(CURDATE(), INTERVAL -25 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 4, 'Completed', DATE_SUB(NOW(), INTERVAL 60 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'Mountain Trek%' WHERE u.email = 'aarav.mehta@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-005');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-005' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO payments (booking_id, transaction_ref, amount, method, status, created_at)
SELECT b.id, 'TXN-DEMO-SAMPLE005', b.total_amount, 'Demo Net Banking', 'Success', DATE_ADD(b.created_at, INTERVAL 5 MINUTE) FROM bookings b
 WHERE b.booking_ref = 'EXSAMPLE-005' AND NOT EXISTS (SELECT 1 FROM payments y WHERE y.transaction_ref = 'TXN-DEMO-SAMPLE005');
INSERT IGNORE INTO reviews (booking_id, user_id, package_id, rating, title, comment, status, created_at)
SELECT b.id, b.user_id, b.package_id, 4, 'Cold, clear and worth it', 'Our guide kept the group safe and the Solang Valley day was fun. Pack warm.', 'Pending', DATE_ADD(b.return_date, INTERVAL 2 DAY) FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-005';
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-006', u.id, p.id, u.name, u.email, u.phone, 2, DATE_ADD(CURDATE(), INTERVAL -12 DAY), DATE_ADD(CURDATE(), INTERVAL -12 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 2, 'Completed', DATE_SUB(NOW(), INTERVAL 45 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'Parisian Romance%' WHERE u.email = 'sneha.iyer@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-006');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-006' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO payments (booking_id, transaction_ref, amount, method, status, created_at)
SELECT b.id, 'TXN-DEMO-SAMPLE006', b.total_amount, 'Demo Card', 'Success', DATE_ADD(b.created_at, INTERVAL 5 MINUTE) FROM bookings b
 WHERE b.booking_ref = 'EXSAMPLE-006' AND NOT EXISTS (SELECT 1 FROM payments y WHERE y.transaction_ref = 'TXN-DEMO-SAMPLE006');
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-007', u.id, p.id, u.name, u.email, u.phone, 2, DATE_ADD(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 25 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 2, 'Confirmed', DATE_SUB(NOW(), INTERVAL 30 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'Desert Safari%' WHERE u.email = 'rohan.das@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-007');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-007' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO payments (booking_id, transaction_ref, amount, method, status, created_at)
SELECT b.id, 'TXN-DEMO-SAMPLE007', b.total_amount, 'Demo UPI', 'Success', DATE_ADD(b.created_at, INTERVAL 5 MINUTE) FROM bookings b
 WHERE b.booking_ref = 'EXSAMPLE-007' AND NOT EXISTS (SELECT 1 FROM payments y WHERE y.transaction_ref = 'TXN-DEMO-SAMPLE007');
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-008', u.id, p.id, u.name, u.email, u.phone, 2, DATE_ADD(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 50 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 2, 'Confirmed', DATE_SUB(NOW(), INTERVAL 20 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'Alpine Escape%' WHERE u.email = 'meera.nair@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-008');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-008' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO payments (booking_id, transaction_ref, amount, method, status, created_at)
SELECT b.id, 'TXN-DEMO-SAMPLE008', b.total_amount, 'Demo Net Banking', 'Success', DATE_ADD(b.created_at, INTERVAL 5 MINUTE) FROM bookings b
 WHERE b.booking_ref = 'EXSAMPLE-008' AND NOT EXISTS (SELECT 1 FROM payments y WHERE y.transaction_ref = 'TXN-DEMO-SAMPLE008');
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-009', u.id, p.id, u.name, u.email, u.phone, 3, DATE_ADD(CURDATE(), INTERVAL 35 DAY), DATE_ADD(CURDATE(), INTERVAL 35 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 3, 'Pending', DATE_SUB(NOW(), INTERVAL 9 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'City Lights%' WHERE u.email = 'aarav.mehta@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-009');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-009' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-010', u.id, p.id, u.name, u.email, u.phone, 2, DATE_ADD(CURDATE(), INTERVAL 70 DAY), DATE_ADD(CURDATE(), INTERVAL 70 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 2, 'Pending', DATE_SUB(NOW(), INTERVAL 6 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'Safari Adventure%' WHERE u.email = 'sneha.iyer@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-010');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-010' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO payments (booking_id, transaction_ref, amount, method, status, created_at)
SELECT b.id, 'TXN-DEMO-SAMPLE010', b.total_amount, 'Demo UPI', 'Success', DATE_ADD(b.created_at, INTERVAL 5 MINUTE) FROM bookings b
 WHERE b.booking_ref = 'EXSAMPLE-010' AND NOT EXISTS (SELECT 1 FROM payments y WHERE y.transaction_ref = 'TXN-DEMO-SAMPLE010');
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-011', u.id, p.id, u.name, u.email, u.phone, 2, DATE_ADD(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 40 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 2, 'Pending', DATE_SUB(NOW(), INTERVAL 4 DAY), 0, NULL, NULL
  FROM users u JOIN packages p ON p.title LIKE 'Beach Paradise%' WHERE u.email = 'rohan.das@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-011');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-011' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO bookings (booking_ref, user_id, package_id, contact_name, contact_email, contact_phone, guests, travel_date, return_date, unit_price, total_amount, status, created_at, cancellation_requested, cancellation_reason, cancellation_requested_at)
SELECT 'EXSAMPLE-012', u.id, p.id, u.name, u.email, u.phone, 2, DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 15 + CAST(p.duration_days AS SIGNED) - 1 DAY), p.price, p.price * 2, 'Cancelled', DATE_SUB(NOW(), INTERVAL 35 DAY), 1, 'Change of plans', DATE_SUB(NOW(), INTERVAL 33 DAY)
  FROM users u JOIN packages p ON p.title LIKE 'Mountain Trek%' WHERE u.email = 'meera.nair@example.com' AND NOT EXISTS (SELECT 1 FROM bookings x WHERE x.booking_ref = 'EXSAMPLE-012');
INSERT INTO booking_travellers (booking_id, full_name, is_lead) SELECT b.id, b.contact_name, 1 FROM bookings b WHERE b.booking_ref = 'EXSAMPLE-012' AND NOT EXISTS (SELECT 1 FROM booking_travellers t WHERE t.booking_id = b.id);
INSERT INTO payments (booking_id, transaction_ref, amount, method, status, created_at)
SELECT b.id, 'TXN-DEMO-SAMPLE012', b.total_amount, 'Demo Card', 'Success', DATE_ADD(b.created_at, INTERVAL 5 MINUTE) FROM bookings b
 WHERE b.booking_ref = 'EXSAMPLE-012' AND NOT EXISTS (SELECT 1 FROM payments y WHERE y.transaction_ref = 'TXN-DEMO-SAMPLE012');

INSERT INTO contacts (name, email, subject, message, is_read)
SELECT 'Kavya Rao', 'kavya.rao@example.com', 'Custom itinerary for 6 people', 'Hello! We are a group of six friends looking at Kerala in March. Can you extend the Backwaters Bliss package by two days?', 0
 WHERE NOT EXISTS (SELECT 1 FROM contacts WHERE email = 'kavya.rao@example.com');
