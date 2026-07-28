-- Adds NGO login support.
-- Import this AFTER foodbridge.sql, the same way (phpMyAdmin > foodbridge database > Import).

USE foodbridge;

ALTER TABLE ngos
  ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) DEFAULT NULL AFTER email;

-- After importing this file, open the following URL once in your browser to set a
-- starting password for every seeded NGO that doesn't have one yet:
--   http://localhost/FoodBridges-main/database/set_ngo_passwords.php
-- It will print the login email + temporary password for each NGO.
