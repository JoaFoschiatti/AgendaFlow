-- Standardize column names across tables
USE agendaflow;

-- Rename duration_min to duration in services table
ALTER TABLE services
CHANGE COLUMN duration_min duration INT UNSIGNED DEFAULT 60;

-- Rename price_default to price in services table
ALTER TABLE services
CHANGE COLUMN price_default price DECIMAL(12,2) DEFAULT 0.00;

-- Rename active to is_active in services table
ALTER TABLE services
CHANGE COLUMN active is_active TINYINT(1) DEFAULT 1;

-- Add description column to services if it doesn't exist
ALTER TABLE services
ADD COLUMN IF NOT EXISTS description TEXT AFTER name;

-- Add client_phone column to appointments if it doesn't exist
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS client_phone VARCHAR(20) AFTER client_name;

-- Add canceled_at column to appointments if it doesn't exist
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS canceled_at DATETIME NULL;