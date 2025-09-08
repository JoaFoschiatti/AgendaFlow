-- Appointments table (core of the system)
USE agendaflow;

CREATE TABLE IF NOT EXISTS appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED,
    client_name VARCHAR(100) NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME,
    status ENUM('scheduled', 'completed', 'canceled', 'no_show') DEFAULT 'scheduled',
    notes TEXT,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    INDEX idx_user_starts (user_id, starts_at),
    INDEX idx_user_status (user_id, status),
    INDEX idx_starts_at (starts_at),
    INDEX idx_client_id (client_id),
    INDEX idx_service_id (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;