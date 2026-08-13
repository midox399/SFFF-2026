-- ============================================================
-- Migration 001 — add payment support
-- ============================================================
-- Run this if passport_reservations already exists (e.g. you already
-- imported the original database/schema.sql before payments were added).
-- Safe to run once; re-running is harmless thanks to IF NOT EXISTS /
-- the ADD COLUMN guards below failing loudly instead of duplicating.
--
-- Import via phpMyAdmin's SQL tab, same as the original schema.
-- ============================================================

ALTER TABLE passport_reservations
    ADD COLUMN amount_due DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER status,
    ADD COLUMN currency CHAR(3) NOT NULL DEFAULT 'TND' AFTER amount_due,
    ADD COLUMN payment_status ENUM('unpaid','processing','paid','failed','refunded') NOT NULL DEFAULT 'unpaid' AFTER currency,
    ADD COLUMN payment_provider VARCHAR(30) NULL AFTER payment_status,
    ADD COLUMN payment_reference VARCHAR(100) NULL AFTER payment_provider,
    ADD COLUMN paid_at DATETIME NULL AFTER payment_reference,
    ADD INDEX idx_passport_payment_status (payment_status);

CREATE TABLE IF NOT EXISTS payment_events (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_id     INT UNSIGNED NOT NULL,
    provider           VARCHAR(30)  NOT NULL,
    provider_event_id  VARCHAR(150) NULL,
    event_type         VARCHAR(50)  NOT NULL,
    raw_payload        TEXT         NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_payment_events_reservation (reservation_id),
    UNIQUE KEY uq_provider_event (provider, provider_event_id),
    FOREIGN KEY (reservation_id) REFERENCES passport_reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
