-- Add missing fields to clients table
USE agendaflow;

-- Add email column if it doesn't exist
ALTER TABLE clients
ADD COLUMN IF NOT EXISTS email VARCHAR(100) UNIQUE AFTER name;

-- Add address column if it doesn't exist
ALTER TABLE clients
ADD COLUMN IF NOT EXISTS address VARCHAR(255) AFTER phone;

-- Add birth_date column if it doesn't exist
ALTER TABLE clients
ADD COLUMN IF NOT EXISTS birth_date DATE AFTER notes;

-- Add index for email searches
CREATE INDEX IF NOT EXISTS idx_email ON clients(email);