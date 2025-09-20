CREATE DATABASE IF NOT EXISTS jel_aircon;
USE jel_aircon;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'technician') DEFAULT 'technician',
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Customers table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL, -- in minutes
    status TINYINT(1) DEFAULT 1
);

-- Technicians table
CREATE TABLE technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    specialization VARCHAR(100),
    status TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    technician_id INT,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'in-progress', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL
);

-- Payments table (with created_at included from the start)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'gcash', 'bank_transfer', 'card') DEFAULT 'cash',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Feedback table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, password, full_name, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert sample services
INSERT INTO services (name, description, price, duration) VALUES
('AC Installation', 'Complete air conditioning unit installation', 2500.00, 120),
('AC Cleaning', 'Thorough cleaning of AC unit', 800.00, 60),
('AC Repair', 'Diagnosis and repair of AC issues', 1500.00, 90),
('AC Maintenance', 'Routine maintenance service', 600.00, 45);


-- Notifications log table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    email_sent TINYINT(1) DEFAULT 0,
    sms_sent TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Add reminder_sent column to bookings table
ALTER TABLE bookings ADD COLUMN reminder_sent TINYINT(1) DEFAULT 0;