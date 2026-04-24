-- Clover integration tables
-- Run once against the loyalteapp database

CREATE TABLE IF NOT EXISTS clover_config (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key  VARCHAR(64)  NOT NULL UNIQUE,
    config_val  TEXT         NOT NULL,
    updated_at  BIGINT       NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default config rows (will not overwrite existing)
INSERT IGNORE INTO clover_config (config_key, config_val, updated_at) VALUES
    ('app_id',          '',         0),
    ('app_secret',      '',         0),
    ('access_token',    '',         0),
    ('merchant_id',     '',         0),
    ('environment',     'sandbox',  0),
    ('points_per_dollar', '1',      0),
    ('enabled',         '0',        0);

CREATE TABLE IF NOT EXISTS clover_payment_logs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id    VARCHAR(64)  NOT NULL UNIQUE,
    merchant_id   VARCHAR(64)  NOT NULL,
    order_id      VARCHAR(64)  DEFAULT NULL,
    customer_id   VARCHAR(64)  DEFAULT NULL,   -- loyalteapp customer UUID
    phone         VARCHAR(32)  DEFAULT NULL,
    amount_cents  INT          NOT NULL DEFAULT 0,
    points_awarded INT         NOT NULL DEFAULT 0,
    status        ENUM('processed','no_customer','error') NOT NULL DEFAULT 'processed',
    note          TEXT         DEFAULT NULL,
    created_at    BIGINT       NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
