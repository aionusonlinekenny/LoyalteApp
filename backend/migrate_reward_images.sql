-- Run once on the server to add image support to rewards
ALTER TABLE rewards
    ADD COLUMN image_url VARCHAR(512) NULL DEFAULT NULL
    AFTER category;
