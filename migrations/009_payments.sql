-- Payments table for MercadoPago integration
USE agendaflow;

-- Create payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    mp_payment_id VARCHAR(100) UNIQUE,
    mp_preference_id VARCHAR(100),
    mp_preapproval_id VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'refunded', 'in_process') DEFAULT 'pending',
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ARS',
    payment_method VARCHAR(50),
    payment_type VARCHAR(50),
    payer_email VARCHAR(150),
    payer_identification VARCHAR(50),
    processed_at DATETIME,
    notification_received_at DATETIME,
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mp_payment_id (mp_payment_id),
    INDEX idx_user_status (user_id, status),
    INDEX idx_preference_id (mp_preference_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Create payment logs table for audit trail
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id INT UNSIGNED,
    event_type VARCHAR(50),
    event_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    headers JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    INDEX idx_payment_id (payment_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Add additional columns to subscriptions table if they don't exist
ALTER TABLE subscriptions 
ADD COLUMN IF NOT EXISTS starts_at DATETIME,
ADD COLUMN IF NOT EXISTS ends_at DATETIME,
ADD COLUMN IF NOT EXISTS last_payment_id INT UNSIGNED,
ADD COLUMN IF NOT EXISTS next_billing_date DATE,
ADD INDEX IF NOT EXISTS idx_ends_at (ends_at),
ADD INDEX IF NOT EXISTS idx_next_billing (next_billing_date);