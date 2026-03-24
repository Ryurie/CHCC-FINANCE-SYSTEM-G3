-- database/seed.sql
USE finance_fee_system;

-- --------------------------------------------------------
-- 1. Insert Users
-- Note: The password for ALL users below is simply: password
-- The string starting with $2y$10$ is the secure bcrypt hash for "password".
-- --------------------------------------------------------
INSERT INTO users (id, name, email, password, role) VALUES
(1, 'System Admin', 'admin@system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'University Registrar', 'registrar@system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'registrar'),
(3, 'Jane Faculty', 'faculty@system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty'),
(4, 'Juan Dela Cruz', 'student@system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');


-- --------------------------------------------------------
-- 2. Insert Student Profile
-- We link this to Juan Dela Cruz (user_id = 4)
-- --------------------------------------------------------
INSERT INTO students (id, user_id, student_number, course, year_level, clearance_status) VALUES
(1, 4, 'STU-2026-001', 'BS Information Technology', 3, 'pending');


-- --------------------------------------------------------
-- 3. Insert Sample Fees
-- --------------------------------------------------------
INSERT INTO fees (id, name, amount, description, academic_year) VALUES
(1, 'Tuition Fee', 15000.00, 'Standard tuition fee for regular semester', '2025-2026'),
(2, 'Lab Fee', 2500.00, 'Computer laboratory maintenance fee', '2025-2026'),
(3, 'Miscellaneous Fee', 1500.00, 'Library, medical, and ID fees', '2025-2026');


-- --------------------------------------------------------
-- 4. Insert Sample Invoices for the Student
-- Linking student_id = 1 to various fees
-- --------------------------------------------------------
INSERT INTO invoices (id, student_id, fee_id, amount_due, due_date, status) VALUES
(1, 1, 1, 15000.00, '2026-05-01', 'pending'),
(2, 1, 2, 2500.00, '2026-05-01', 'partial');


-- --------------------------------------------------------
-- 5. Insert a Sample Payment Log
-- Let's say Juan paid 1,000 towards his Lab Fee (invoice_id = 2)
-- --------------------------------------------------------
INSERT INTO payments (id, invoice_id, amount_paid, payment_method, reference_number, status) VALUES
(1, 2, 1000.00, 'bank_transfer', 'REF-987654321', 'verified');


-- --------------------------------------------------------
-- 6. Insert a Sample Scholarship
-- --------------------------------------------------------
INSERT INTO scholarships (id, student_id, name, discount_percentage, amount_deducted) VALUES
(1, 1, 'Academic Dean''s Lister', 10.00, 1500.00);