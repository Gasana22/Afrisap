-- Seed data for lookup values explicitly listed in the project spec.
-- Run after schema.sql.

INSERT INTO countries (name) VALUES
    ('Uganda'), ('Kenya'), ('Tanzania'), ('Rwanda'), ('Burundi'), ('South Sudan'), ('DR Congo');

INSERT INTO tour_categories (menu_group, name, slug, sort_order) VALUES
    ('safari', 'Gorilla Trekking Safaris', 'gorilla-trekking-safaris', 1),
    ('safari', 'Chimpanzee Trekking Safaris', 'chimpanzee-trekking-safaris', 2),
    ('safari', 'Wildlife/Game Drives Safaris', 'wildlife-game-drives-safaris', 3),
    ('safari', 'Birding Safaris', 'birding-safaris', 4),
    ('safari', 'Mixed Safaris', 'mixed-safaris', 5),
    ('safari', 'Adventure Safaris', 'adventure-safaris', 6),
    ('safari', 'East Africa Combined Safaris', 'east-africa-combined-safaris', 7),
    ('trip', 'Island Trips', 'island-trips', 1),
    ('trip', 'Camping Trips', 'camping-trips', 2),
    ('trip', 'Flying Experience Trips', 'flying-experience-trips', 3),
    ('trip', 'Shopping Trips (East Africa)', 'shopping-trips', 4),
    ('trip', 'Boat Cruise Trips', 'boat-cruise-trips', 5),
    ('trip', 'Beach Trips (Lake Victoria)', 'beach-trips', 6),
    ('trip', 'City Trips (East Africa)', 'city-trips', 7),
    ('school', 'School Trips', 'school-trips', 1);

INSERT INTO experience_types (name, slug) VALUES
    ('Cultural Experience', 'cultural-experience'),
    ('Farm Experience', 'farm-experience'),
    ('Manufactural/Factory Experience', 'manufactural-factory-experience'),
    ('Sports Experience', 'sports-experience'),
    ('Ghetto Experience', 'ghetto-experience');

-- Cultural Experience destinations: by tribe/ethnicity
INSERT INTO experience_destinations (experience_type_id, name)
SELECT id, tribe FROM experience_types
CROSS JOIN (
    SELECT 'Karamoja' AS tribe UNION ALL SELECT 'Buganda' UNION ALL SELECT 'Teso'
    UNION ALL SELECT 'Busoga' UNION ALL SELECT 'Ankole' UNION ALL SELECT 'Acholi'
    UNION ALL SELECT 'Tooro' UNION ALL SELECT 'Bugisu' UNION ALL SELECT 'Alur'
) tribes
WHERE experience_types.slug = 'cultural-experience';

-- Farm Experience destinations
INSERT INTO experience_destinations (experience_type_id, name)
SELECT id, farm FROM experience_types
CROSS JOIN (
    SELECT 'Coffee Farm' AS farm UNION ALL SELECT 'Cocoa Farm' UNION ALL SELECT 'Tea Farm'
    UNION ALL SELECT 'Banana Farm' UNION ALL SELECT 'Cassava Farm' UNION ALL SELECT 'Cattle Farm'
    UNION ALL SELECT 'Fish Farming' UNION ALL SELECT 'Cotton Farm' UNION ALL SELECT 'Vegetable Farms'
    UNION ALL SELECT 'Poultry Farm'
) farms
WHERE experience_types.slug = 'farm-experience';

-- Sports Experience destinations
INSERT INTO experience_destinations (experience_type_id, name)
SELECT id, sport FROM experience_types
CROSS JOIN (
    SELECT 'Football' AS sport UNION ALL SELECT 'Swimming' UNION ALL SELECT 'Cycling'
    UNION ALL SELECT 'Marathons' UNION ALL SELECT 'Basketball' UNION ALL SELECT 'Beach Sports'
    UNION ALL SELECT 'Walking' UNION ALL SELECT 'Volleyball' UNION ALL SELECT 'Golf'
    UNION ALL SELECT 'Rugby' UNION ALL SELECT 'Village Mud Wrestling'
) sports
WHERE experience_types.slug = 'sports-experience';

-- Manufactural/Factory Experience destinations
INSERT INTO experience_destinations (experience_type_id, name)
SELECT id, industry FROM experience_types
CROSS JOIN (
    SELECT 'Food Industries' AS industry UNION ALL SELECT 'Textile Industries'
    UNION ALL SELECT 'Motor Industries' UNION ALL SELECT 'Construction Material Industries'
    UNION ALL SELECT 'Agro Input Industries'
) industries
WHERE experience_types.slug = 'manufactural-factory-experience';

-- Ghetto Experience destinations
INSERT INTO experience_destinations (experience_type_id, name)
SELECT id, ghetto FROM experience_types
CROSS JOIN (
    SELECT 'Katanga Ghetto' AS ghetto UNION ALL SELECT 'Kamonkya' UNION ALL SELECT 'Kalwere'
    UNION ALL SELECT 'Bwaise'
) ghettos
WHERE experience_types.slug = 'ghetto-experience';

-- Activities master catalog (top-level Activities nav menu)
INSERT INTO activities (name) VALUES
    ('Skydiving'), ('White Water Rafting'), ('Horseback Riding'), ('Mountain Hiking'),
    ('Nature Walks'), ('Kayaking'), ('Boat Cruises'), ('Sport Fishing'),
    ('Bungee Jumping'), ('Cycling'), ('Quad Biking');

-- Starter content for the two fixed About Us CMS pages (editable in admin).
INSERT INTO pages (slug, title, body) VALUES
    ('about', 'About Safarisap',
     'Safarisap connects travellers to East Africa on two tracks: safari tours into the region''s national parks and game reserves, and experiential tours into the everyday life of the communities that call this region home.\n\nWe work with licensed, named tour operators and local service providers across Uganda, Kenya, Tanzania, Rwanda, Burundi, South Sudan and DR Congo, with branches in Kampala, Nairobi, Addis Ababa and London.'),
    ('uganda-travel-tips', 'Uganda Travel Tips',
     'Visas: most visitors can apply for a Uganda e-visa online before travel.\n\nCurrency: the Ugandan Shilling (UGX) is the local currency; US Dollars are widely accepted for larger payments like gorilla permits and hotel bills.\n\nHealth: a yellow fever vaccination certificate is required on arrival, and antimalarial medication is strongly recommended.\n\nPacking: neutral-coloured clothing, a light rain jacket and sturdy walking shoes cover most itineraries, especially forest treks.');
