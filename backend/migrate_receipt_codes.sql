-- Run this in phpMyAdmin to add receipt code support
USE loyalteapp;

CREATE TABLE IF NOT EXISTS receipt_codes (
    id         VARCHAR(36)  NOT NULL PRIMARY KEY,
    code       VARCHAR(100) NOT NULL UNIQUE,
    points     INT          NOT NULL,
    expires_at BIGINT       NOT NULL,
    claimed_by VARCHAR(36)           DEFAULT NULL,
    claimed_at BIGINT                DEFAULT NULL,
    created_by INT          NOT NULL,
    created_at BIGINT       NOT NULL,
    note       VARCHAR(255)          DEFAULT NULL,
    FOREIGN KEY (claimed_by) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES staff_accounts(id),
    INDEX idx_code       (code),
    INDEX idx_claimed_by (claimed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
