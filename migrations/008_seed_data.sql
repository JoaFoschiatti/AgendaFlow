-- Seed data for testing
USE agendaflow;

-- Demo user with active trial
INSERT INTO users (name, business_name, email, password_hash, trial_starts_at, trial_ends_at) VALUES
('Juan Pérez', 'Barbería El Estilo', 'demo@agendaflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY));

-- Get the user ID
SET @user_id = LAST_INSERT_ID();

-- Demo services
INSERT INTO services (user_id, name, price_default, duration_min, color, active) VALUES
(@user_id, 'Corte de Pelo', 3500.00, 30, '#4CAF50', 1),
(@user_id, 'Barba', 2000.00, 20, '#2196F3', 1),
(@user_id, 'Corte + Barba', 4500.00, 45, '#FF9800', 1),
(@user_id, 'Color', 5000.00, 60, '#9C27B0', 1);

-- Demo clients
INSERT INTO clients (user_id, name, phone) VALUES
(@user_id, 'Carlos García', '351-4123456'),
(@user_id, 'Miguel Rodríguez', '351-4234567'),
(@user_id, 'Roberto Martínez', '351-4345678');

-- Get service IDs
SET @service_corte = (SELECT id FROM services WHERE user_id = @user_id AND name = 'Corte de Pelo' LIMIT 1);
SET @service_barba = (SELECT id FROM services WHERE user_id = @user_id AND name = 'Barba' LIMIT 1);
SET @service_combo = (SELECT id FROM services WHERE user_id = @user_id AND name = 'Corte + Barba' LIMIT 1);

-- Get client IDs  
SET @client1 = (SELECT id FROM clients WHERE user_id = @user_id AND name = 'Carlos García' LIMIT 1);
SET @client2 = (SELECT id FROM clients WHERE user_id = @user_id AND name = 'Miguel Rodríguez' LIMIT 1);
SET @client3 = (SELECT id FROM clients WHERE user_id = @user_id AND name = 'Roberto Martínez' LIMIT 1);

-- Demo appointments for current week
INSERT INTO appointments (user_id, client_id, client_name, service_id, price, starts_at, ends_at, status, phone) VALUES
(@user_id, @client1, 'Carlos García', @service_corte, 3500.00, DATE_ADD(CURDATE(), INTERVAL 9 HOUR), DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 9 HOUR), INTERVAL 30 MINUTE), 'scheduled', '351-4123456'),
(@user_id, @client2, 'Miguel Rodríguez', @service_barba, 2000.00, DATE_ADD(CURDATE(), INTERVAL 10 HOUR), DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 10 HOUR), INTERVAL 20 MINUTE), 'scheduled', '351-4234567'),
(@user_id, NULL, 'Pedro Sánchez', @service_combo, 4500.00, DATE_ADD(CURDATE(), INTERVAL 11 HOUR), DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 11 HOUR), INTERVAL 45 MINUTE), 'scheduled', NULL),
(@user_id, @client3, 'Roberto Martínez', @service_corte, 3500.00, DATE_ADD(CURDATE(), INTERVAL 15 HOUR), DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 15 HOUR), INTERVAL 30 MINUTE), 'scheduled', '351-4345678'),
(@user_id, NULL, 'Juan López', @service_corte, 3500.00, DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 1 DAY), INTERVAL 9 HOUR), DATE_ADD(DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 1 DAY), INTERVAL 9 HOUR), INTERVAL 30 MINUTE), 'scheduled', NULL),
(@user_id, NULL, 'Diego Fernández', @service_barba, 2000.00, DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 1 DAY), INTERVAL 10 HOUR), DATE_ADD(DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 1 DAY), INTERVAL 10 HOUR), INTERVAL 20 MINUTE), 'scheduled', '351-4567890'),
(@user_id, @client1, 'Carlos García', @service_corte, 3500.00, DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 2 DAY), INTERVAL 14 HOUR), DATE_ADD(DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 2 DAY), INTERVAL 14 HOUR), INTERVAL 30 MINUTE), 'scheduled', '351-4123456'),
(@user_id, NULL, 'Andrés Gómez', @service_combo, 4500.00, DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 3 DAY), INTERVAL 16 HOUR), DATE_ADD(DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 3 DAY), INTERVAL 16 HOUR), INTERVAL 45 MINUTE), 'scheduled', NULL),
(@user_id, @client2, 'Miguel Rodríguez', @service_corte, 3300.00, DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 4 DAY), INTERVAL 17 HOUR), DATE_ADD(DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 4 DAY), INTERVAL 17 HOUR), INTERVAL 30 MINUTE), 'scheduled', '351-4234567'),
(@user_id, NULL, 'Fernando Silva', @service_barba, 1900.00, DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 5 DAY), INTERVAL 11 HOUR), DATE_ADD(DATE_ADD(DATE_ADD(CURDATE(), INTERVAL 5 DAY), INTERVAL 11 HOUR), INTERVAL 20 MINUTE), 'scheduled', NULL);

-- Default business hours settings (Monday to Saturday, 9:00 to 20:00)
INSERT INTO settings (user_id, day_of_week, open_time, close_time, slot_minutes, allow_overlaps, closed) VALUES
(@user_id, 0, NULL, NULL, 15, 0, 1), -- Sunday (closed)
(@user_id, 1, '09:00:00', '20:00:00', 15, 0, 0), -- Monday
(@user_id, 2, '09:00:00', '20:00:00', 15, 0, 0), -- Tuesday  
(@user_id, 3, '09:00:00', '20:00:00', 15, 0, 0), -- Wednesday
(@user_id, 4, '09:00:00', '20:00:00', 15, 0, 0), -- Thursday
(@user_id, 5, '09:00:00', '20:00:00', 15, 0, 0), -- Friday
(@user_id, 6, '09:00:00', '13:00:00', 15, 0, 0); -- Saturday (half day)