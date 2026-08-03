USE bkk_community;

DROP PROCEDURE IF EXISTS add_web_contact_fields;
DELIMITER //
CREATE PROCEDURE add_web_contact_fields()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'contact_messages'
          AND column_name = 'phone'
    ) THEN
        ALTER TABLE contact_messages
            ADD COLUMN phone VARCHAR(30) NULL AFTER email;
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'contact_messages'
          AND column_name = 'subject'
    ) THEN
        ALTER TABLE contact_messages
            ADD COLUMN subject VARCHAR(120) NOT NULL DEFAULT 'General enquiry' AFTER phone;
    END IF;
END//
DELIMITER ;

CALL add_web_contact_fields();
DROP PROCEDURE add_web_contact_fields;
