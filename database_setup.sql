-- ==========================================
-- Script de création de la base de données
-- Gestion Inscription - TAAJ Corp
-- ==========================================

-- Créer les tables

-- Table des étudiants
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    program VARCHAR(100) NOT NULL,
    level ENUM('L1', 'L2', 'L3', 'M1', 'M2') DEFAULT 'L1',
    status ENUM('Active', 'Blocked', 'Pending') DEFAULT 'Active',
    joined DATE NULL,
    avatar VARCHAR(255) DEFAULT 'default.jpg',
    phone VARCHAR(20),
    address TEXT,
    birth_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des programmes
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    duration INT DEFAULT 52,
    level ENUM('L1', 'L2', 'L3', 'M1', 'M2'),
    department VARCHAR(100),
    tuition_fees DECIMAL(10,2) DEFAULT 150000.00,
    max_students INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'teacher') DEFAULT 'admin',
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des inscriptions
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_id INT NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash', 'bank_transfer', 'mobile_money', 'credit_card') DEFAULT 'cash',
    payment_date DATE,
    registration_date DATE NULL,
    academic_year VARCHAR(9) DEFAULT '2025-2026',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

-- ==========================================
-- Insertion des données initiales
-- ==========================================

-- Programmes
INSERT INTO programs (name, code, description, duration, tuition_fees) VALUES 
('Gestion', 'GEST', 'Programme en Sciences de Gestion', 52, 150000.00),
('Informatique', 'INFO', 'Programme en Génie Informatique', 52, 180000.00),
('Médecine', 'MED', 'Programme en Sciences Médicales', 78, 250000.00),
('Droit', 'DR', 'Programme en Sciences Juridiques', 52, 160000.00);

-- Administrateur
INSERT INTO users (username, email, password, role, first_name, last_name) VALUES 
('admin', 'admin@taajcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Johan', 'Kabo');

-- Étudiants
INSERT INTO students (name, email, program, level, phone, address) VALUES 
('Janot NKENG', 'nkengjanot@gmail.com', 'Gestion', 'L2', '+237 698 123 456', 'Yaoundé, Cameroun'),
('Alex TAMO', 'tamoalex@gmail.com', 'Médecine', 'L3', '+237 697 234 567', 'Douala, Cameroun'),
('Johan Manuel', 'kanojohan@gmail.com', 'Informatique', 'L3', '+237 696 345 678', 'Yaoundé, Cameroun'),
('Junior TATOU', 'junior@gmail.com', 'Informatique', 'L2', '+237 695 456 789', 'Bafoussam, Cameroun');

-- Inscriptions
INSERT INTO registrations (student_id, program_id, code, amount, status, payment_method, payment_date, registration_date) VALUES 
(1, 1, 'REG-6oMj', 150000.00, 'paid', 'cash', '2026-03-17', '2026-03-17'),
(2, 3, 'REG-9ccn', 250000.00, 'paid', 'bank_transfer', '2026-03-17', '2026-03-17'),
(3, 2, 'REG-4xKp', 180000.00, 'paid', 'mobile_money', '2026-03-16', '2026-03-16'),
(4, 2, 'REG-2mNq', 180000.00, 'paid', 'cash', '2026-03-16', '2026-03-16');
