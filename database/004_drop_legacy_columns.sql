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
