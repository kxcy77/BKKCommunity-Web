DROP PROCEDURE IF EXISTS harden_password_reset_codes;
DELIMITER //
CREATE PROCEDURE harden_password_reset_codes()
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'password_reset_tokens'
          AND column_name = 'failed_attempts'
    ) THEN
        ALTER TABLE password_reset_tokens
            ADD COLUMN failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER token_hash;
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'password_reset_tokens'
          AND index_name = 'idx_reset_user_active'
    ) THEN
        ALTER TABLE password_reset_tokens
            ADD INDEX idx_reset_user_active (user_id, expires_at, used_at);
    END IF;
END//
DELIMITER ;

CALL harden_password_reset_codes();
DROP PROCEDURE harden_password_reset_codes;
