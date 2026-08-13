-- ============================================================
-- Migration 002 — add door check-in log
-- ============================================================
-- Import via phpMyAdmin's SQL tab, same as previous migrations.
-- Safe to run once against a database that already has
-- database/schema.sql or migration 001 applied.
-- ============================================================

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
