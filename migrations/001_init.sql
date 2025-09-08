-- AgendaFlow Database Schema
-- Version: 1.0.0
-- Date: 2025-01-05

CREATE DATABASE IF NOT EXISTS agendaflow
    DEFAULT CHARACTER SET utf8mb4 
    DEFAULT COLLATE utf8mb4_spanish_ci;

USE agendaflow;

-- Users table (main account table)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    business_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    timezone VARCHAR(50) DEFAULT 'America/Argentina/Cordoba',
    currency CHAR(3) DEFAULT 'ARS',
    trial_starts_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    trial_ends_at TIMESTAMP NULL,
    subscription_status ENUM('trialing', 'active', 'past_due', 'canceled') DEFAULT 'trialing',
    mp_preapproval_id VARCHAR(100),
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_subscription_status (subscription_status),
    INDEX idx_trial_ends (trial_ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;