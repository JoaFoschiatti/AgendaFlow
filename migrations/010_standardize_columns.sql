-- Standardize column names across tables
USE agendaflow;

-- Note: Column renames have already been applied in earlier migrations
-- Only adding missing columns if they don't exist

-- Add description column to services if it doesn't exist
ALTER TABLE services
ADD COLUMN IF NOT EXISTS description TEXT AFTER name;

-- Add client_phone column to appointments if it doesn't exist
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS client_phone VARCHAR(20) AFTER client_name;

-- Add canceled_at column to appointments if it doesn't exist
ALTER TABLE appointments
ADD COLUMN IF NOT EXISTS canceled_at DATETIME NULL;