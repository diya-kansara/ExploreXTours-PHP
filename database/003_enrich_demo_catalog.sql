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
