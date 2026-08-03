CREATE DATABASE IF NOT EXISTS bkk_community
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bkk_community;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('member', 'admin') NOT NULL DEFAULT 'member',
    notifications_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    event_reminders_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    discount_alerts_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
);

CREATE TABLE auth_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auth_session_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_auth_session_active (user_id, expires_at, revoked_at)
);

CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE event_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    colour_hex CHAR(7) NOT NULL DEFAULT '#2E75B6'
);

CREATE TABLE events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    start_at DATETIME NOT NULL COMMENT 'UTC',
    end_at DATETIME NOT NULL COMMENT 'UTC',
    location VARCHAR(190) NOT NULL,
    directions TEXT NULL,
    status ENUM('draft', 'published', 'cancelled') NOT NULL DEFAULT 'published',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_category FOREIGN KEY (category_id) REFERENCES event_categories(id),
    INDEX idx_events_start_status (start_at, status)
);

CREATE TABLE attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    event_id BIGINT UNSIGNED NOT NULL,
    status ENUM('attending', 'cancelled') NOT NULL DEFAULT 'attending',
    confirmed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY uq_attendance_user_event (user_id, event_id),
    INDEX idx_attendance_event_status (event_id, status)
);

CREATE TABLE discount_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE discounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    store_name VARCHAR(160) NOT NULL,
    title VARCHAR(190) NOT NULL,
    details TEXT NOT NULL,
    eligibility VARCHAR(255) NOT NULL,
    claim_instructions TEXT NOT NULL,
    valid_from DATE NULL,
    valid_until DATE NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_discount_category FOREIGN KEY (category_id) REFERENCES discount_categories(id),
    INDEX idx_discount_active_category (is_active, category_id, valid_until)
);

CREATE TABLE local_services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('pharmacy', 'clinic', 'shop', 'support', 'transport') NOT NULL,
    name VARCHAR(160) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    directions TEXT NULL,
    opening_hours VARCHAR(190) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_local_service_type_active (type, is_active)
);

CREATE TABLE contact_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NULL,
    subject VARCHAR(120) NOT NULL DEFAULT 'General enquiry',
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'resolved') NOT NULL DEFAULT 'new',
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_contact_status_date (status, submitted_at)
);

CREATE TABLE devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    fcm_token VARCHAR(512) NOT NULL UNIQUE,
    platform VARCHAR(20) NOT NULL DEFAULT 'android',
    notifications_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_device_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_device_user_enabled (user_id, notifications_enabled)
);

CREATE TABLE notification_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    device_id BIGINT UNSIGNED NULL,
    event_id BIGINT UNSIGNED NULL,
    discount_id BIGINT UNSIGNED NULL,
    type ENUM('event_reminder', 'discount_alert', 'system') NOT NULL,
    title VARCHAR(160) NOT NULL,
    message VARCHAR(500) NOT NULL,
    dedupe_key VARCHAR(255) NOT NULL UNIQUE,
    delivery_status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    provider_message_id VARCHAR(255) NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_discount FOREIGN KEY (discount_id) REFERENCES discounts(id) ON DELETE SET NULL,
    INDEX idx_notification_delivery (delivery_status, created_at)
);
