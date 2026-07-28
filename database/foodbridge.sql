-- FoodBridge database schema
-- Import this file in phpMyAdmin or run: mysql -u root -p < database/foodbridge.sql

CREATE DATABASE IF NOT EXISTS foodbridge
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE foodbridge;

CREATE TABLE IF NOT EXISTS ngos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description VARCHAR(500) NOT NULL,
  city VARCHAR(100) NOT NULL,
  service_area VARCHAR(255) DEFAULT NULL,
  contact_phone VARCHAR(30) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ngos_name (name),
  INDEX idx_ngos_verified_active (is_verified, is_active),
  INDEX idx_ngos_city (city)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS donations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donor_name VARCHAR(100) NOT NULL,
  donor_phone VARCHAR(30) NOT NULL,
  food_type ENUM('Vegetarian meals', 'Non-vegetarian meals', 'Packaged food', 'Dry rations') NOT NULL,
  servings INT UNSIGNED NOT NULL,
  pickup_deadline DATETIME NOT NULL,
  pickup_location VARCHAR(255) NOT NULL,
  food_notes TEXT DEFAULT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  matched_ngo_id INT UNSIGNED DEFAULT NULL,
  status ENUM('pending', 'matched', 'accepted', 'collected', 'distributed', 'cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_donations_ngo
    FOREIGN KEY (matched_ngo_id) REFERENCES ngos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_donations_status (status),
  INDEX idx_donations_deadline (pickup_deadline),
  INDEX idx_donations_ngo (matched_ngo_id)
) ENGINE=InnoDB;

-- Seed verified NGO partners shown on the homepage.
INSERT INTO ngos (name, description, city, service_area, contact_phone, email, is_verified, is_active)
VALUES
  ('Seva Hands Foundation', 'Community meal distribution and last-mile support across Kolkata.', 'Salt Lake', 'Salt Lake and Kolkata', '+91 90000 10001', 'hello@sevahands.example', 1, 1),
  ('Annadaata Hub', 'Rescuing surplus food from functions and hospitality partners.', 'Kolkata', 'Kolkata metropolitan area', '+91 90000 10002', 'hello@annadaata.example', 1, 1),
  ('Neighbourhood Hope', 'Connecting hot meals with underserved communities every day.', 'New Town', 'New Town and nearby areas', '+91 90000 10003', 'hello@neighbourhoodhope.example', 1, 1)
ON DUPLICATE KEY UPDATE
  description = VALUES(description),
  city = VALUES(city),
  service_area = VALUES(service_area),
  contact_phone = VALUES(contact_phone),
  email = VALUES(email),
  is_verified = VALUES(is_verified),
  is_active = VALUES(is_active);
