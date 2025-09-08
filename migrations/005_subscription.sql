-- Subscriptions table (MercadoPago integration)
USE agendaflow;

CREATE TABLE IF NOT EXISTS subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    currency CHAR(3) DEFAULT 'ARS',
    amount DECIMAL(12,2) DEFAULT 8900.00,
    status VARCHAR(50),
    mp_preapproval_id VARCHAR(100),
    next_charge_at DATETIME,
    canceled_at DATETIME,
    raw_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_mp_preapproval (mp_preapproval_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;