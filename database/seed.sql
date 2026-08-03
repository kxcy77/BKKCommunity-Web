USE bkk_community;

-- DEMONSTRATION CONTENT ONLY. Replace with client-approved BKK data before UAT.
INSERT INTO event_categories (name, colour_hex) VALUES
    ('Exercise', '#315C24'),
    ('Social', '#2E75B6'),
    ('Health', '#B00020'),
    ('Meeting', '#BF7600')
ON DUPLICATE KEY UPDATE colour_hex = VALUES(colour_hex);

INSERT INTO discount_categories (name) VALUES
    ('Pharmacy'), ('Grocery'), ('Restaurant'), ('Transport')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO events (category_id, title, description, start_at, end_at, location, directions)
SELECT id, 'Morning Exercise', 'A gentle, guided exercise session suitable for all mobility levels.',
       '2026-08-03 07:00:00', '2026-08-03 07:45:00', 'BKK Community Hall', 'Main hall entrance, Block B.'
FROM event_categories WHERE name = 'Exercise';

INSERT INTO events (category_id, title, description, start_at, end_at, location, directions)
SELECT id, 'Social Lunch Gathering', 'Share lunch and connect with other community members.',
       '2026-08-05 10:00:00', '2026-08-05 11:30:00', 'BKK Hall', 'Main Road entrance.'
FROM event_categories WHERE name = 'Social';

INSERT INTO events (category_id, title, description, start_at, end_at, location, directions)
SELECT id, 'Health Talk: Managing Diabetes', 'A practical health information session with time for questions.',
       '2026-08-07 12:00:00', '2026-08-07 13:30:00', 'Clinic Room 2', 'Use the reception entrance.'
FROM event_categories WHERE name = 'Health';

INSERT INTO discounts (category_id, store_name, title, details, eligibility, claim_instructions, is_active)
SELECT id, 'Clicks', '10% off selected prescriptions', 'Selected prescription items qualify for a pensioner discount.',
       'Customers aged 60+ with valid identification.', 'Ask at the pharmacy counter and show your ID card.', TRUE
FROM discount_categories WHERE name = 'Pharmacy';

INSERT INTO discounts (category_id, store_name, title, details, eligibility, claim_instructions, is_active)
SELECT id, 'Checkers', 'Tuesday senior savings', 'Save 5% on qualifying grocery purchases every Tuesday.',
       'Customers aged 60+.', 'Present your ID before the cashier completes the transaction.', TRUE
FROM discount_categories WHERE name = 'Grocery';

INSERT INTO discounts (category_id, store_name, title, details, eligibility, claim_instructions, is_active)
SELECT id, 'Wimpy', 'Senior breakfast special', 'A reduced-price breakfast before 10:00 on weekdays.',
       'Customers aged 60+.', 'Ask for the senior breakfast menu before ordering.', TRUE
FROM discount_categories WHERE name = 'Restaurant';

INSERT INTO local_services (type, name, address, phone, directions, opening_hours) VALUES
    ('clinic', 'BKK Community Clinic', '12 Main Road, BKK', '011 555 0101', 'Opposite the community hall.', 'Mon–Fri 08:00–16:00'),
    ('pharmacy', 'Community Pharmacy', '18 Main Road, BKK', '011 555 0102', 'Next to the grocery store.', 'Mon–Sat 08:00–18:00'),
    ('support', 'BKK Community Support Desk', 'BKK Community Hall, Main Road', '072 888 5030', 'Reception desk inside the main entrance.', 'Weekdays 09:00–15:00');

