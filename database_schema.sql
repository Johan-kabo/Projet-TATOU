-- ========================================
-- BASE DE DONNÉES : TAAJ - GESTION INSCRIPTION
-- SERVEUR : localhost
-- BASE : gestion_inscription
-- ========================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS `gestion_inscription` 
DEFAULT CHARACTER SET utf8mb4 
DEFAULT COLLATE utf8mb4_unicode_ci;

USE `gestion_inscription`;

-- ========================================
-- TABLE DES UTILISATEURS (AUTHENTIFICATION)
-- ========================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `role` enum('admin','manager','user') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES ÉTUDIANTS
-- ========================================
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','graduated') DEFAULT 'active',
  `registration_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_registration_date` (`registration_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES PROGRAMMES/FORMATIONS
-- ========================================
CREATE TABLE IF NOT EXISTS `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration_months` int(11) DEFAULT NULL,
  `level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `category` varchar(50) DEFAULT NULL,
  `price` decimal(10,0) DEFAULT NULL,
  `max_students` int(11) DEFAULT NULL,
  `current_students` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES INSCRIPTIONS
-- ========================================
CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `reference` varchar(50) NOT NULL,
  `registration_date` date NOT NULL,
  `academic_year` varchar(20) DEFAULT '2024-2025',
  `amount` decimal(10,0) DEFAULT NULL,
  `payment_status` enum('paid','pending','unpaid') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_registration_date` (`registration_date`),
  KEY `idx_academic_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES PAIEMENTS
-- ========================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `amount` decimal(10,0) NOT NULL,
  `payment_method` enum('cash','card','bank_transfer','mobile_money','check') DEFAULT 'cash',
  `payment_date` date NOT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `status` enum('completed','pending','failed','refunded') DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_registration_id` (`registration_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES COURS/SESSIONS
-- ========================================
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `max_students` int(11) DEFAULT NULL,
  `current_students` int(11) DEFAULT 0,
  `credits` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_instructor` (`instructor`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES PRÉSENCES
-- ========================================
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`course_id`,`date`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES NOTES/ÉVALUATIONS
-- ========================================
CREATE TABLE IF NOT EXISTS `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `exam_type` varchar(50) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT '2024-2025',
  `notes` text DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_academic_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES DOCUMENTS
-- ========================================
CREATE TABLE IF NOT EXISTS `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `registration_id` int(11) DEFAULT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_registration_id` (`registration_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES NOTIFICATIONS
-- ========================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES PARAMÈTRES/SYSTEM SETTINGS
-- ========================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` enum('string','number','boolean','json') DEFAULT 'string',
  `category` varchar(50) DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- CONTRAINTES DE CLÉS ÉTRANGÈRES
-- ========================================

-- Relations pour la table registrations
ALTER TABLE `registrations` 
  ADD CONSTRAINT `fk_registrations_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registrations_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Relations pour la table payments
ALTER TABLE `payments` 
  ADD CONSTRAINT `fk_payments_registration` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Relations pour la table courses
ALTER TABLE `courses` 
  ADD CONSTRAINT `fk_courses_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Relations pour la table attendance
ALTER TABLE `attendance` 
  ADD CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_attendance_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_attendance_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Relations pour la table grades
ALTER TABLE `grades` 
  ADD CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grades_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_grades_graded_by` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Relations pour la table documents
ALTER TABLE `documents` 
  ADD CONSTRAINT `fk_documents_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_documents_registration` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_documents_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Relations pour la table notifications
ALTER TABLE `notifications` 
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notifications_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ========================================
-- TRIGGERS
-- ========================================

-- Trigger pour mettre à jour le nombre d'étudiants dans les programmes
DELIMITER //
CREATE TRIGGER `update_program_students_after_registration`
AFTER INSERT ON `registrations`
FOR EACH ROW
BEGIN
    UPDATE programs 
    SET current_students = (
        SELECT COUNT(*) 
        FROM registrations 
        WHERE program_id = NEW.program_id AND status = 'active'
    )
    WHERE id = NEW.program_id;
END//
DELIMITER ;

-- Trigger pour mettre à jour le nombre d'étudiants dans les cours
DELIMITER //
CREATE TRIGGER `update_course_students_after_registration`
AFTER INSERT ON `registrations`
FOR EACH ROW
BEGIN
    DECLARE course_count INT DEFAULT 0;
    
    -- Compter les étudiants pour ce programme
    SELECT COUNT(*) INTO course_count 
    FROM registrations 
    WHERE program_id = NEW.program_id;
    
    -- Mettre à jour le cours avec le comptage
    UPDATE courses 
    SET current_students = course_count
    WHERE id = NEW.program_id;
END//
DELIMITER ;

-- ========================================
-- DONNÉES EXEMPLE (OPTIONNEL)
-- ========================================

-- Insertion d'un utilisateur administrateur
INSERT INTO `users` (`username`, `email`, `password_hash`, `first_name`, `last_name`, `role`) VALUES
('admin', 'admin@taaj.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'TAAJ', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Insertion de programmes exemples
INSERT INTO `programs` (`name`, `code`, `description`, `duration_months`, `level`, `category`, `price`, `max_students`) VALUES
('Développement Web Full Stack', 'DWFS-001', 'Formation complète en développement web moderne', 12, 'intermediate', 'Développement', 150000, 25),
('Marketing Digital', 'MD-002', 'Stratégies et techniques de marketing digital', 6, 'beginner', 'Marketing', 80000, 30),
('Design UX/UI', 'DUI-003', 'Conception d\'interfaces utilisateur optimales', 8, 'intermediate', 'Design', 120000, 20)
ON DUPLICATE KEY UPDATE name = name;

-- Insertion de paramètres système
INSERT INTO `settings` (`key`, `value`, `description`, `type`, `category`) VALUES
('school_name', 'TAAJ Corp', 'Nom de l\'école', 'string', 'general'),
('academic_year', '2024-2025', 'Année académique en cours', 'string', 'academic'),
('registration_fee', '5000', 'Frais d\'inscription par défaut', 'number', 'financial'),
('currency', 'FCFA', 'Devise utilisée', 'string', 'financial'),
('max_file_size', '5242880', 'Taille maximale des fichiers (octets)', 'number', 'uploads')
ON DUPLICATE KEY UPDATE key = key;

-- ========================================
-- VUES UTILES
-- ========================================

-- Vue pour les statistiques des inscriptions
CREATE OR REPLACE VIEW `registration_stats` AS
SELECT 
    p.name as program_name,
    COUNT(r.id) as total_registrations,
    COUNT(CASE WHEN r.payment_status = 'paid' THEN 1 END) as paid_registrations,
    COUNT(CASE WHEN r.payment_status = 'pending' THEN 1 END) as pending_registrations,
    COUNT(CASE WHEN r.payment_status = 'unpaid' THEN 1 END) as unpaid_registrations,
    SUM(CASE WHEN r.payment_status = 'paid' THEN r.amount ELSE 0 END) as total_revenue,
    AVG(r.amount) as avg_amount
FROM programs p
LEFT JOIN registrations r ON p.id = r.program_id
WHERE p.status = 'active'
GROUP BY p.id, p.name;

-- Vue pour les détails des étudiants avec leurs inscriptions
CREATE OR REPLACE VIEW `student_registrations` AS
SELECT 
    s.id as student_id,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.email,
    r.id as registration_id,
    r.reference,
    r.registration_date,
    r.payment_status,
    r.amount,
    p.name as program_name,
    p.code as program_code,
    r.academic_year
FROM students s
LEFT JOIN registrations r ON s.id = r.student_id
LEFT JOIN programs p ON r.program_id = p.id
WHERE s.status = 'active';

-- ========================================
-- INDEX OPTIMISÉS
-- ========================================

-- Index composites pour les performances
CREATE INDEX `idx_registrations_student_program` ON `registrations` (`student_id`, `program_id`);
CREATE INDEX `idx_registrations_status_date` ON `registrations` (`payment_status`, `registration_date`);
CREATE INDEX `idx_attendance_student_course_date` ON `attendance` (`student_id`, `course_id`, `date`);
CREATE INDEX `idx_grades_student_course_year` ON `grades` (`student_id`, `course_id`, `academic_year`);
CREATE INDEX `idx_payments_registration_date` ON `payments` (`registration_id`, `payment_date`);

-- ========================================
-- FIN DU SCRIPT
-- ========================================
