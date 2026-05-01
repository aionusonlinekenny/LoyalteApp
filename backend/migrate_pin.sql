-- Run once on the server to add PIN support to customers
ALTER TABLE customers
    ADD COLUMN pin_hash VARCHAR(255) NULL DEFAULT NULL
    AFTER email;
