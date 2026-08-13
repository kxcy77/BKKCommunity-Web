CREATE TABLE IF NOT EXISTS api_rate_limits (
    key_hash CHAR(64) PRIMARY KEY,
    scope VARCHAR(60) NOT NULL,
    request_count INT UNSIGNED NOT NULL DEFAULT 1,
    expires_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_rate_limit_expiry (expires_at)
);

DELETE FROM api_rate_limits WHERE expires_at <= UTC_TIMESTAMP();
