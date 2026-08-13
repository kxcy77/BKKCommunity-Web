INSERT INTO event_categories (name, colour_hex)
VALUES ('Demonstration', '#007C83')
ON DUPLICATE KEY UPDATE colour_hex = VALUES(colour_hex);

INSERT INTO events (category_id, title, description, start_at, end_at, location, directions, status)
SELECT id,
       'BKK App Demonstration Event — Not a Real Event',
       'TEST CONTENT ONLY. This event exists so the group can verify event details and RSVP. Do not travel to attend it.',
       DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY),
       DATE_ADD(UTC_TIMESTAMP(), INTERVAL 31 DAY),
       'Demonstration only — do not travel',
       'No directions: this is not a real event.',
       'published'
FROM event_categories
WHERE name = 'Demonstration'
  AND NOT EXISTS (
      SELECT 1 FROM events
      WHERE title = 'BKK App Demonstration Event — Not a Real Event'
  );
