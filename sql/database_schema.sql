-- =====================================================
-- MetroLink Transit System — Full Database Schema
-- MySQL 8.0+  |  Bangladesh-based (BDT currency)
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS transit_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE transit_system;

-- Drop existing tables in reverse dependency order
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS live_tracking;
DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS pass_purchases;
DROP TABLE IF EXISTS passes;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS route_assignments;
DROP TABLE IF EXISTS bus_details;
DROP TABLE IF EXISTS train_details;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS fare_rules;
DROP TABLE IF EXISTS routes;
DROP TABLE IF EXISTS passenger_profiles;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. USERS & AUTHENTICATION
-- =====================================================

CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)    NOT NULL,
    email         VARCHAR(255)    UNIQUE NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,
    role          ENUM('admin','passenger') DEFAULT 'passenger',
    status        ENUM('active','inactive','suspended') DEFAULT 'active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE passenger_profiles (
    profile_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT             NOT NULL,
    phone         VARCHAR(20),
    address       TEXT,
    photo         VARCHAR(255),
    date_of_birth DATE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. ROUTES & FARE RULES
-- =====================================================

CREATE TABLE routes (
    route_id     INT AUTO_INCREMENT PRIMARY KEY,
    route_name   VARCHAR(100),
    origin       VARCHAR(100),
    destination  VARCHAR(100),
    distance_km  DECIMAL(8,2),
    status       ENUM('active','inactive') DEFAULT 'active',
    INDEX idx_status (status),
    INDEX idx_origin_destination (origin, destination)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fare_rules (
    fare_id        INT AUTO_INCREMENT PRIMARY KEY,
    route_id       INT             NOT NULL,
    passenger_type ENUM('student','senior','regular'),
    base_fare      DECIMAL(8,2),
    per_km_rate    DECIMAL(8,2),
    FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE CASCADE,
    INDEX idx_route_id (route_id),
    INDEX idx_passenger_type (passenger_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. VEHICLES & SCHEDULING
-- =====================================================

CREATE TABLE vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    type       ENUM('bus','train') NOT NULL,
    capacity   INT,
    status     ENUM('active','inactive','maintenance') DEFAULT 'active',
    INDEX idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bus_details (
    bus_id     INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT             NOT NULL,
    route_no   VARCHAR(50),
    sub_type   VARCHAR(50),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_route_no (route_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE train_details (
    train_id   INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT             NOT NULL,
    line_no    VARCHAR(50),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_line_no (line_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE route_assignments (
    assignment_id  INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id     INT             NOT NULL,
    route_id       INT             NOT NULL,
    schedule_time  TIME,
    days           VARCHAR(50),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    FOREIGN KEY (route_id)   REFERENCES routes(route_id)   ON DELETE CASCADE,
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_route_id (route_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. PASSES, PAYMENTS, TRIPS
-- =====================================================

CREATE TABLE passes (
    pass_id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT             NOT NULL,
    pass_type        ENUM('daily','weekly','monthly','trip-based'),
    valid_from       DATE,
    valid_to         DATE,
    remaining_trips  INT,
    status           ENUM('active','expired','cancelled') DEFAULT 'active',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_valid_dates (valid_from, valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT             NOT NULL,
    amount     DECIMAL(10,2),
    method     VARCHAR(50),
    status     ENUM('pending','completed','failed') DEFAULT 'pending',
    timestamp  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pass_purchases (
    purchase_id  INT AUTO_INCREMENT PRIMARY KEY,
    pass_id      INT,
    payment_id   INT,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pass_id)    REFERENCES passes(pass_id)    ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE,
    INDEX idx_pass_id (pass_id),
    INDEX idx_payment_id (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE trips (
    trip_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT,
    pass_id        INT,
    route_id       INT,
    vehicle_id     INT,
    start_time     DATETIME,
    end_time       DATETIME,
    fare_deducted  DECIMAL(8,2),
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(user_id)    ON DELETE SET NULL,
    FOREIGN KEY (pass_id)    REFERENCES passes(pass_id)   ON DELETE SET NULL,
    FOREIGN KEY (route_id)   REFERENCES routes(route_id)  ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_pass_id (pass_id),
    INDEX idx_start_time (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. TRACKING & NOTIFICATIONS
-- =====================================================

CREATE TABLE live_tracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id  INT UNIQUE,
    latitude    DECIMAL(10,7),
    longitude   DECIMAL(10,7),
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE CASCADE,
    INDEX idx_vehicle_id (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    notif_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    title      VARCHAR(255),
    message    TEXT,
    is_read    BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 6. DEFAULT ADMIN ACCOUNT
-- Email:    admin@transit.com
-- Password: admin123
-- (bcrypt hash for 'admin123')
-- =====================================================

INSERT INTO users (name, email, password_hash, role, status) VALUES
('Admin', 'admin@transit.com',
 '$2y$10$Nfh30SaUTW/gbnjIxFIg/eJze2yQ6UsAfeEsskO3ZCTtdRnfBKCJi',
 'admin', 'active');

-- =====================================================
-- 7. SAMPLE DHAKA ROUTES (optional — comment out if not needed)
-- =====================================================

INSERT INTO routes (route_name, origin, destination, distance_km, status) VALUES
('Route 1: Motijheel - Uttara',    'Motijheel',  'Uttara',     18.50, 'active'),
('Route 2: Gulshan - Mirpur',      'Gulshan',    'Mirpur',     12.30, 'active'),
('Route 3: Dhanmondi - Airport',   'Dhanmondi',  'Airport',    15.75, 'active'),
('Route 4: Sadarghat - Gabtoli',   'Sadarghat',  'Gabtoli',    10.20, 'active'),
('Route 5: Farmgate - Banani',     'Farmgate',   'Banani',     8.40,  'active');

-- =====================================================
-- 8. SAMPLE FARE RULES FOR EACH ROUTE (BDT)
-- =====================================================

INSERT INTO fare_rules (route_id, passenger_type, base_fare, per_km_rate) VALUES
(1, 'regular', 20.00, 1.50), (1, 'student', 10.00, 1.00), (1, 'senior', 15.00, 1.20),
(2, 'regular', 20.00, 1.50), (2, 'student', 10.00, 1.00), (2, 'senior', 15.00, 1.20),
(3, 'regular', 25.00, 1.80), (3, 'student', 12.00, 1.20), (3, 'senior', 18.00, 1.50),
(4, 'regular', 18.00, 1.40), (4, 'student',  9.00, 0.90), (4, 'senior', 13.00, 1.10),
(5, 'regular', 15.00, 1.30), (5, 'student',  8.00, 0.80), (5, 'senior', 11.00, 1.00);

-- =====================================================
-- END OF SCHEMA
-- =====================================================
