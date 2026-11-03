-- Doctor Appointment System Sample Data
-- Database Name: doctor_appointment_system
-- Note: Contains only Admin, Doctors, and Patient demo accounts.
-- Appointments table is intentionally left empty so you can create your own bookings.

USE `doctor_appointment_system`;

-- Clear any existing appointments
TRUNCATE TABLE `appointments`;

-- Insert Admin, Doctors, and Patients
-- Passwords:
-- admin@doccare.com: admin123
-- doctor.*@doccare.com: doctor123
-- *.@example.com: patient123

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
-- 1. Admin / Receptionist
(1, 'Admin Receptionist', 'admin@doccare.com', '9876543210', '$2y$12$nlpUTUe857NQeIbiARaq/urN67w.zBPVyFFyH1SJlKVTz5ELlHJ42', 'admin', NOW()),

-- 2-5. Doctors
(2, 'Dr. Rajesh Sharma', 'doctor.sharma@doccare.com', '9876543211', '$2y$12$cRy6YLluWj5UBem2q9sh3e0JM0EeMNtMTfgQx/9NSzc4l01zrPx66', 'doctor', NOW()),
(3, 'Dr. Ananya Patel', 'doctor.patel@doccare.com', '9876543212', '$2y$12$cRy6YLluWj5UBem2q9sh3e0JM0EeMNtMTfgQx/9NSzc4l01zrPx66', 'doctor', NOW()),
(4, 'Dr. Vikram Malhotra', 'doctor.malhotra@doccare.com', '9876543213', '$2y$12$cRy6YLluWj5UBem2q9sh3e0JM0EeMNtMTfgQx/9NSzc4l01zrPx66', 'doctor', NOW()),
(5, 'Dr. Sneha Kulkarni', 'doctor.kulkarni@doccare.com', '9876543214', '$2y$12$cRy6YLluWj5UBem2q9sh3e0JM0EeMNtMTfgQx/9NSzc4l01zrPx66', 'doctor', NOW()),

-- 6-8. Patients
(6, 'Rahul Verma', 'rahul.verma@example.com', '9876543220', '$2y$12$LT1HZAZyGWJk1e42NiEMKuJX3sJfcRxYUXiH0xbmY3BrQMbGo26TK', 'patient', NOW()),
(7, 'Priya Singh', 'priya.singh@example.com', '9876543221', '$2y$12$LT1HZAZyGWJk1e42NiEMKuJX3sJfcRxYUXiH0xbmY3BrQMbGo26TK', 'patient', NOW()),
(8, 'Amit Joshi', 'amit.joshi@example.com', '9876543222', '$2y$12$LT1HZAZyGWJk1e42NiEMKuJX3sJfcRxYUXiH0xbmY3BrQMbGo26TK', 'patient', NOW());

-- Insert Doctor Profiles
INSERT INTO `doctors` (`id`, `user_id`, `specialization`, `qualification`, `consultation_fee`, `available_days`, `start_time`, `end_time`, `slot_duration`, `status`, `bio`) VALUES
(1, 2, 'Cardiologist', 'MBBS, MD, DM (Cardiology)', 800.00, 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday', '09:00:00', '17:00:00', 30, 'active', 'Senior Interventional Cardiologist with over 14 years of clinical experience in preventive cardiology, hypertension, and heart disease management.'),
(2, 3, 'Dermatologist', 'MBBS, DVD, MD (Dermatology)', 600.00, 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday', '10:00:00', '18:00:00', 30, 'active', 'Expert clinical dermatologist specializing in skin allergies, acne therapeutics, cosmetic dermatology, and hair disorders with 10+ years experience.'),
(3, 4, 'Orthopedic Surgeon', 'MBBS, MS (Orthopedics)', 750.00, 'Monday,Tuesday,Wednesday,Thursday,Friday', '09:00:00', '16:00:00', 30, 'active', 'Consultant orthopedic and joint replacement surgeon focused on arthroscopy, sports injuries, fracture management, and arthritis care.'),
(4, 5, 'Pediatrician', 'MBBS, DCH, MD (Pediatrics)', 500.00, 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday', '10:00:00', '16:00:00', 30, 'active', 'Compassionate child specialist and neonatologist experienced in child immunizations, developmental pediatrics, and adolescent healthcare.');
