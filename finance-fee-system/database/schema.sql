-- database/schema.sql

CREATE DATABASE IF NOT EXISTS finance_fee_system;
USE finance_fee_system;

-- Users Table (Handles Auth & Roles)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'registrar', 'faculty', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students Table (Linked to Users)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    course VARCHAR(100) NOT NULL,
    year_level INT NOT NULL,
    clearance_status ENUM('cleared', 'pending', 'hold') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Fees Structure Table
CREATE TABLE IF NOT EXISTS fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL, -- e.g., "Tuition Fee", "Lab Fee"
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    academic_year VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Invoices Table (Bills generated for students)
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_id INT NOT NULL,
    amount_due DECIMAL(10, 2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_id) REFERENCES fees(id) ON DELETE CASCADE
);

-- Payments Table (Logs transactions)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'online') NOT NULL,
    reference_number VARCHAR(100),
    receipt_url VARCHAR(255), -- Path to uploaded receipt image
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- Scholarships Table (Discounts applied to fees)
CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    discount_percentage DECIMAL(5, 2) DEFAULT 0.00,
    amount_deducted DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);