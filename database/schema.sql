-- ============================================================
-- SFFF 2026 — Minimal Backend Schema
-- ============================================================
-- Import this directly through phpMyAdmin (SQL tab -> paste/import).
-- This is intentionally separate from sfff2026_database.sql (the
-- 23-table content schema) — see database/README.md for why.
--
-- Creates exactly 3 tables:
--   admins                 - private dashboard accounts
--   passport_reservations  - "Billetterie" checkout submissions
--   applications            - "Espace Professionnels" submissions
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+01:00';

-- ============================================================
--  1. ADMINS
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email         VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  2. PASSPORT RESERVATIONS
-- ============================================================
-- payment_status is independent from `status` (which is the org's own
-- fulfillment workflow): a reservation can be `status=pending` while
-- payment_status moves unpaid -> paid on its own, driven only by the
-- gateway webhook — never by the browser redirect alone.
CREATE TABLE IF NOT EXISTS passport_reservations (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference       VARCHAR(20)  NOT NULL,
    full_name       VARCHAR(200) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    phone           VARCHAR(30)  NOT NULL,
    quantity        TINYINT UNSIGNED NOT NULL,
    passport_type   VARCHAR(40)  NOT NULL,
    status          ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    amount_due      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency        CHAR(3)       NOT NULL DEFAULT 'TND',
    payment_status  ENUM('unpaid','processing','paid','failed','refunded') NOT NULL DEFAULT 'unpaid',
    payment_provider    VARCHAR(30)  NULL,
    payment_reference   VARCHAR(100) NULL,
    paid_at         DATETIME     NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_passport_reference (reference),
    INDEX idx_passport_status (status),
    INDEX idx_passport_payment_status (payment_status),
    INDEX idx_passport_email (email),
    INDEX idx_passport_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  2b. PAYMENT EVENTS (audit trail)
-- ============================================================
-- One row per webhook/callback received from the payment provider, kept
-- even for events that don't change anything (duplicates, out-of-order
-- deliveries) — this is what you check first when a payment is disputed
-- or a webhook seems to have been missed.
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

-- ============================================================
--  2c. CHECK-IN LOG (door scans)
-- ============================================================
-- One row per successful door scan. Deliberately append-only (not a single
-- checked_in_at column) since this is a 15-night festival — the same paid
-- passport is expected to scan in on multiple different nights, not just
-- once ever. A staff member's device sends the reference; the endpoint
-- only requires payment_status='paid', it does not block re-entry.
CREATE TABLE IF NOT EXISTS checkin_log (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_id  INT UNSIGNED NOT NULL,
    scanned_by_admin_id INT UNSIGNED NULL,
    scanned_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_checkin_reservation (reservation_id),
    INDEX idx_checkin_scanned_at (scanned_at),
    FOREIGN KEY (reservation_id) REFERENCES passport_reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (scanned_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  3. APPLICATIONS (Espace Professionnels)
-- ============================================================
CREATE TABLE IF NOT EXISTS applications (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name        VARCHAR(200) NOT NULL,
    email            VARCHAR(255) NOT NULL,
    phone            VARCHAR(30)  NULL,
    application_type VARCHAR(60)  NOT NULL,
    message          TEXT         NULL,
    status           ENUM('pending','reviewing','approved','rejected','completed') NOT NULL DEFAULT 'pending',
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_app_status (status),
    INDEX idx_app_email (email),
    INDEX idx_app_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
