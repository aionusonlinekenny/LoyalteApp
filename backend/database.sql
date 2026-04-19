-- LoyalteApp MySQL Schema
-- Run this once on your XAMPP MySQL (phpMyAdmin or CLI)

CREATE DATABASE IF NOT EXISTS loyalteapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE loyalteapp;

-- ─── Tables ──────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS customers (
    id          VARCHAR(36)  NOT NULL PRIMARY KEY,
    member_id   VARCHAR(20)  NOT NULL UNIQUE,
    name        VARCHAR(100) NOT NULL,
    phone       VARCHAR(20)  NOT NULL UNIQUE,
    email       VARCHAR(100)          DEFAULT NULL,
    tier        ENUM('BRONZE','SILVER','GOLD','PLATINUM') NOT NULL DEFAULT 'BRONZE',
    points      INT          NOT NULL DEFAULT 0,
    qr_code     VARCHAR(50)  NOT NULL UNIQUE,
    created_at  BIGINT       NOT NULL,
    updated_at  BIGINT       NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id          VARCHAR(36)  NOT NULL PRIMARY KEY,
    customer_id VARCHAR(36)  NOT NULL,
    type        ENUM('EARNED','REDEEMED','ADJUSTED') NOT NULL,
    points      INT          NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    created_at  BIGINT       NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_created (customer_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rewards (
    id              VARCHAR(36)  NOT NULL PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    description     VARCHAR(255) NOT NULL DEFAULT '',
    points_required INT          NOT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    category        ENUM('FOOD','DRINK','DISCOUNT','OTHER') NOT NULL DEFAULT 'OTHER',
    created_at      BIGINT       NOT NULL,
    INDEX idx_active_points (is_active, points_required)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS redemptions (
    id          VARCHAR(36)  NOT NULL PRIMARY KEY,
    customer_id VARCHAR(36)  NOT NULL,
    reward_id   VARCHAR(36)  NOT NULL,
    points_used INT          NOT NULL,
    redeemed_at BIGINT       NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id)   REFERENCES rewards(id)   ON DELETE RESTRICT,
    INDEX idx_customer_redeemed (customer_id, redeemed_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff_accounts (
    id           INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name         VARCHAR(100) NOT NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_tokens (
    token       VARCHAR(64)  NOT NULL PRIMARY KEY,
    staff_id    INT          NOT NULL,
    expires_at  BIGINT       NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_accounts(id) ON DELETE CASCADE,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Default staff account  (password: admin123 — CHANGE IN PRODUCTION) ──────
INSERT INTO staff_accounts (email, password_hash, name) VALUES
('admin@loyalte.app', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin')
ON DUPLICATE KEY UPDATE id=id;

-- ─── Rewards ─────────────────────────────────────────────────────────────────
INSERT INTO rewards (id, name, description, points_required, is_active, category, created_at) VALUES
('rwd-00000001', 'Free Coffee',        'Any regular-size coffee or hot drink',            100,  1, 'DRINK',    UNIX_TIMESTAMP()*1000),
('rwd-00000002', '10% Discount',       '10% off your entire order',                       150,  1, 'DISCOUNT', UNIX_TIMESTAMP()*1000),
('rwd-00000003', 'Free Appetizer',     'Choose any appetizer from our menu',              250,  1, 'FOOD',     UNIX_TIMESTAMP()*1000),
('rwd-00000004', 'Free Drink Upgrade', 'Upgrade any drink to large, any flavour',         200,  1, 'DRINK',    UNIX_TIMESTAMP()*1000),
('rwd-00000005', 'Free Dessert',       'Any dessert item from our dessert menu',          300,  1, 'FOOD',     UNIX_TIMESTAMP()*1000),
('rwd-00000006', '20% Discount',       '20% off your entire bill',                        400,  1, 'DISCOUNT', UNIX_TIMESTAMP()*1000),
('rwd-00000007', 'Free Main Course',   'One complimentary main course of your choice',    500,  1, 'FOOD',     UNIX_TIMESTAMP()*1000),
('rwd-00000008', 'Birthday Cake Slice','A free slice of our signature cake',             1000,  1, 'FOOD',     UNIX_TIMESTAMP()*1000)
ON DUPLICATE KEY UPDATE id=id;

-- ─── Seed Customers ──────────────────────────────────────────────────────────
SET @now = UNIX_TIMESTAMP() * 1000;
SET @day = 86400000;

INSERT INTO customers (id, member_id, name, phone, email, tier, points, qr_code, created_at, updated_at) VALUES
('cst-00000001', 'LYL-000001', 'John Smith',        '+14155551001', NULL,                 'BRONZE',   450,  'LYL-000001', @now - 7*7*@day,  @now - 7*7*@day),
('cst-00000002', 'LYL-000002', 'Sarah Johnson',     '+14155551002', NULL,                 'SILVER',   750,  'LYL-000002', @now - 14*7*@day, @now - 14*7*@day),
('cst-00000003', 'LYL-000003', 'Michael Chen',      '+14155551003', NULL,                 'GOLD',    1200,  'LYL-000003', @now - 21*7*@day, @now - 21*7*@day),
('cst-00000004', 'LYL-000004', 'Emily Davis',       '+14155551004', 'emily@example.com',  'PLATINUM',3000,  'LYL-000004', @now - 28*7*@day, @now - 28*7*@day),
('cst-00000005', 'LYL-000005', 'Robert Wilson',     '+14155551005', NULL,                 'SILVER',   850,  'LYL-000005', @now - 35*7*@day, @now - 35*7*@day),
('cst-00000006', 'LYL-000006', 'Jennifer Martinez', '+14155551006', NULL,                 'BRONZE',   125,  'LYL-000006', @now - 42*7*@day, @now - 42*7*@day),
('cst-00000007', 'LYL-000007', 'David Anderson',    '+14155551007', 'david@example.com',  'GOLD',    1750,  'LYL-000007', @now - 49*7*@day, @now - 49*7*@day),
('cst-00000008', 'LYL-000008', 'Lisa Thompson',     '+14155551008', NULL,                 'BRONZE',   320,  'LYL-000008', @now - 56*7*@day, @now - 56*7*@day),
('cst-00000009', 'LYL-000009', 'James Garcia',      '+14155551009', NULL,                 'GOLD',    2100,  'LYL-000009', @now - 63*7*@day, @now - 63*7*@day),
('cst-00000010', 'LYL-000010', 'Maria Rodriguez',   '+14155551010', 'maria@example.com',  'PLATINUM',4200,  'LYL-000010', @now - 70*7*@day, @now - 70*7*@day),
('cst-00000011', 'LYL-000011', 'William Brown',     '+14155551011', NULL,                 'SILVER',   680,  'LYL-000011', @now - 77*7*@day, @now - 77*7*@day),
('cst-00000012', 'LYL-000012', 'Jessica Taylor',    '+14155551012', NULL,                 'BRONZE',    95,  'LYL-000012', @now - 84*7*@day, @now - 84*7*@day)
ON DUPLICATE KEY UPDATE id=id;

-- ─── Sample transactions ──────────────────────────────────────────────────────
INSERT INTO loyalty_transactions (id, customer_id, type, points, description, created_at) VALUES
('txn-c1-001', 'cst-00000001', 'EARNED',   200, 'Purchase reward', @now - 7*7*@day),
('txn-c1-002', 'cst-00000001', 'EARNED',   150, 'Purchase reward', @now - 5*7*@day),
('txn-c1-003', 'cst-00000001', 'EARNED',   100, 'Purchase reward', @now - 3*7*@day),
('txn-c2-001', 'cst-00000002', 'EARNED',   300, 'Purchase reward', @now - 14*7*@day),
('txn-c2-002', 'cst-00000002', 'EARNED',   250, 'Purchase reward', @now - 10*7*@day),
('txn-c2-003', 'cst-00000002', 'REDEEMED',-100, 'Redeemed: Free Coffee', @now - 9*7*@day),
('txn-c2-004', 'cst-00000002', 'EARNED',   300, 'Purchase reward', @now - 6*7*@day),
('txn-c3-001', 'cst-00000003', 'EARNED',   500, 'Purchase reward', @now - 21*7*@day),
('txn-c3-002', 'cst-00000003', 'EARNED',   400, 'Purchase reward', @now - 15*7*@day),
('txn-c3-003', 'cst-00000003', 'REDEEMED',-100, 'Redeemed: Free Coffee', @now - 14*7*@day),
('txn-c3-004', 'cst-00000003', 'EARNED',   400, 'Purchase reward', @now - 7*7*@day),
('txn-c4-001', 'cst-00000004', 'EARNED',   800, 'Purchase reward', @now - 28*7*@day),
('txn-c4-002', 'cst-00000004', 'EARNED',   700, 'Purchase reward', @now - 20*7*@day),
('txn-c4-003', 'cst-00000004', 'EARNED',   750, 'Purchase reward', @now - 12*7*@day),
('txn-c4-004', 'cst-00000004', 'REDEEMED',-250, 'Redeemed: Free Appetizer', @now - 10*7*@day),
('txn-c4-005', 'cst-00000004', 'EARNED',  1000, 'Purchase reward', @now - 4*7*@day),
('txn-c5-001', 'cst-00000005', 'EARNED',   400, 'Purchase reward', @now - 35*7*@day),
('txn-c5-002', 'cst-00000005', 'EARNED',   300, 'Purchase reward', @now - 25*7*@day),
('txn-c5-003', 'cst-00000005', 'REDEEMED',-100, 'Redeemed: Free Coffee', @now - 20*7*@day),
('txn-c5-004', 'cst-00000005', 'EARNED',   250, 'Purchase reward', @now - 10*7*@day)
ON DUPLICATE KEY UPDATE id=id;
