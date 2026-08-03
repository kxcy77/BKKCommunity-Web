USE bkk_community;

INSERT INTO users (full_name, email, phone, password_hash, role) VALUES
('Andrew (Demo Admin)', 'admin@bkk.demo', '072 888 5030', '$2y$12$6.lfppQ3bet0nSrhz2.zU.n4aZ2nsOExUPJ0cplnmZvZeNiSJOB.C', 'admin'),
('Thandiwe Nkosi', 'member@bkk.demo', '082 123 4567', '$2y$12$AMRzkbo5a3RVMNT3Ydmwfeapw3VhSDtjBV21P3QOQGPPlN8Sx2dj.', 'member')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

INSERT INTO events (title, event_date, event_time, end_time, location, category, tone, description) VALUES
('Morning Movement Session', '2026-08-05', '09:00', '10:00', 'BKK Community Hall, Block B', 'Wellness', 'green', 'A gentle, chair-friendly movement class followed by tea and conversation.'),
('Social Lunch Gathering', '2026-08-07', '12:00', '14:00', 'BKK Hall, Main Road', 'Social', 'blue', 'Meet neighbours and enjoy a relaxed community lunch. Please confirm attendance for catering.'),
('Health Talk: Managing Diabetes', '2026-08-10', '14:00', '15:30', 'Clinic Room 2', 'Health', 'red', 'A practical discussion with a local health worker, with time for questions.'),
('Community Safety Meeting', '2026-08-14', '10:00', '11:30', 'BKK Main Hall', 'Community', 'gold', 'Local safety updates, emergency contacts and an open community question session.'),
('Chair Yoga and Tea', '2026-08-18', '09:00', '10:30', 'Recreation Room', 'Wellness', 'teal', 'Low-impact chair yoga suitable for beginners, followed by refreshments.');

INSERT INTO discounts (store_name, category, deal, eligibility, claim_instructions, tone) VALUES
('Clicks Pharmacy', 'Pharmacy', '10% off selected wellness items for customers aged 60+.', 'Customers aged 60 and over', 'Ask at the dispensary and show a valid identity document.', 'blue'),
('Checkers', 'Grocery', 'Selected Tuesday savings for senior shoppers.', 'Participating stores and selected products', 'Ask the service desk whether the offer is active before shopping.', 'green'),
('Dis-Chem', 'Pharmacy', 'Benefit savings on selected pharmacy and wellness purchases.', 'Benefit programme members', 'Present your benefit card before payment.', 'teal'),
('Pick n Pay', 'Grocery', 'Selected Smart Shopper pensioner benefits and coupons.', 'Smart Shopper members; terms vary', 'Load available offers and scan your Smart Shopper card.', 'green');

INSERT INTO local_services (name, category, phone, address, hours, description, tone) VALUES
('BKK Community Hall', 'Community', '072 888 5030', 'BKK Community Hall', 'Monday-Friday, 08:00-16:00', 'Community events, referrals and general member support.', 'blue'),
('SASSA Information Line', 'Government', '0800 60 10 11', 'Telephone information service', 'Weekdays during office hours', 'General social-grant enquiries and official process guidance.', 'gold'),
('Emergency Services', 'Emergency', '112', 'National mobile emergency number', '24 hours', 'Call from a mobile phone when urgent police, fire or medical help is required.', 'red');

