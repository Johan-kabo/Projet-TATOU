-- ========================================
-- BASE DE DONNÉES TAAJ CORP
-- ========================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS `taaj_corp` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `taaj_corp`;

-- ========================================
-- TABLE DES UTILISATEURS
-- ========================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','manager','teacher','student') NOT NULL DEFAULT 'admin',
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES PROGRAMMES
-- ========================================
CREATE TABLE `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `level` enum('Licence','Master','Doctorat','Certificat','Formation continue') NOT NULL,
  `duration` int(11) NOT NULL DEFAULT 3 COMMENT 'Durée en années',
  `capacity` int(11) NOT NULL DEFAULT 50 COMMENT 'Capacité maximale',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Frais de scolarité',
  `requirements` text DEFAULT NULL COMMENT 'Prérequis',
  `objectives` text DEFAULT NULL COMMENT 'Objectifs du programme',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_level` (`level`),
  KEY `idx_active` (`active`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_programs_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES ÉTUDIANTS
-- ========================================
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `place_of_birth` varchar(100) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('M','F','Autre') DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `level` enum('L1','L2','L3','M1','M2','D1','D2','D3') DEFAULT NULL,
  `status` enum('active','inactive','pending','graduated','suspended') NOT NULL DEFAULT 'pending',
  `registration_date` date DEFAULT NULL,
  `student_id_card` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `student_id_card` (`student_id_card`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_level` (`level`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_students_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES INSCRIPTIONS
-- ========================================
CREATE TABLE `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `reference` varchar(50) NOT NULL,
  `registration_date` date NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` enum('Semestre 1','Semestre 2','Année complète') NOT NULL DEFAULT 'Année complète',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('paid','pending','unpaid','partial','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','bank_transfer','mobile_money','credit_card','check') DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `status` enum('validated','pending','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `validation_date` datetime DEFAULT NULL,
  `validated_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_academic_year` (`academic_year`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_status` (`status`),
  KEY `idx_validated_by` (`validated_by`),
  CONSTRAINT `fk_registrations_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_registrations_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_registrations_validated_by` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES COURS
-- ========================================
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `credits` int(11) NOT NULL DEFAULT 3,
  `hours` int(11) NOT NULL DEFAULT 30,
  `program_id` int(11) NOT NULL,
  `level` enum('L1','L2','L3','M1','M2','D1','D2','D3') NOT NULL,
  `semester` enum('Semestre 1','Semestre 2') NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `max_students` int(11) NOT NULL DEFAULT 50,
  `current_students` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','completed') NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_level` (`level`),
  KEY `idx_teacher_id` (`teacher_id`),
  CONSTRAINT `fk_courses_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_courses_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES NOTES
-- ========================================
CREATE TABLE `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `grade` decimal(5,2) NOT NULL,
  `grade_letter` varchar(5) DEFAULT NULL,
  `coefficient` decimal(3,2) NOT NULL DEFAULT 1.00,
  `semester` enum('Semestre 1','Semestre 2') NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `graded_date` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grade` (`student_id`,`course_id`,`academic_year`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_academic_year` (`academic_year`),
  KEY `idx_graded_by` (`graded_by`),
  CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_graded_by` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES ATTENDANCES
-- ========================================
CREATE TABLE `attendances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'present',
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`course_id`,`date`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_date` (`date`),
  KEY `idx_status` (`status`),
  KEY `idx_marked_by` (`marked_by`),
  CONSTRAINT `fk_attendances_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendances_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendances_marked_by` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES PAIEMENTS
-- ========================================
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','mobile_money','credit_card','check','scholarship') NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` datetime NOT NULL,
  `status` enum('completed','pending','failed','refunded') NOT NULL DEFAULT 'completed',
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_registration_id` (`registration_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_status` (`status`),
  KEY `idx_processed_by` (`processed_by`),
  CONSTRAINT `fk_payments_registration` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES ANNALES ACADÉMIQUES
-- ========================================
CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `year` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','completed','upcoming') NOT NULL DEFAULT 'upcoming',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `year` (`year`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES PARAMÈTRES
-- ========================================
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE DES LOGS
-- ========================================
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- DONNÉES DE TEST
-- ========================================

-- Insertion des années académiques
INSERT INTO `academic_years` (`year`, `start_date`, `end_date`, `status`) VALUES
('2023-2024', '2023-10-01', '2024-07-31', 'completed'),
('2024-2025', '2024-10-01', '2025-07-31', 'active'),
('2025-2026', '2025-10-01', '2026-07-31', 'upcoming');

-- Insertion de l'administrateur par défaut
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `role`, `status`) VALUES
('admin', 'admin@taajcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Johan', 'Kabo', 'admin', 'active');

-- Insertion des programmes
INSERT INTO `programs` (`code`, `name`, `description`, `level`, `duration`, `capacity`, `price`, `active`) VALUES
('INF-LIC', 'Licence en Informatique', 'Formation complète en développement logiciel, réseaux et cybersécurité', 'Licence', 3, 60, 150000.00, 1),
('GES-LIC', 'Licence en Gestion', 'Programme complet en management, finance et marketing', 'Licence', 3, 80, 120000.00, 1),
('MED-LIC', 'Licence en Médecine', 'Formation médicale générale avec stages pratiques', 'Licence', 6, 40, 500000.00, 1),
('ECO-LIC', 'Licence en Économie', 'Analyse économique, politique monétaire et commerce international', 'Licence', 3, 50, 130000.00, 1),
('DRO-LIC', 'Licence en Droit', 'Droit privé, public, pénal et des affaires', 'Licence', 3, 60, 140000.00, 1),
('IA-MS', 'Master en Intelligence Artificielle', 'Machine learning, deep learning et applications IA', 'Master', 2, 25, 300000.00, 1);

-- Insertion des étudiants de test
INSERT INTO `students` (`first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `program_id`, `level`, `status`, `registration_date`, `student_id_card`) VALUES
('Janot', 'NKENG', 'nkengjanot@gmail.com', '+237 699123456', '2000-05-15', 2, 'L2', 'active', '2023-10-15', 'STU2023001'),
('Alex', 'TAMO', 'tamoalex@gmail.com', '+237 698234567', '2001-03-22', 3, 'L3', 'active', '2023-10-16', 'STU2023002'),
('Johan', 'Manuel', 'kanojohan@gmail.com', '+237 697345678', '2000-08-10', 1, 'L3', 'active', '2023-10-17', 'STU2023003'),
('Junior', 'TATOU', 'tatou.jr@gmail.com', '+237 696456789', '2002-01-25', 1, 'L2', 'active', '2023-10-18', 'STU2023004'),
('Marie', 'ONANA', 'onana.marie@gmail.com', '+237 695567890', '2001-12-03', 4, 'L1', 'pending', '2024-03-10', 'STU2024001'),
('Paul', 'MBARGA', 'paul.mbarga@gmail.com', '+237 694678901', '2000-07-18', 5, 'M1', 'active', '2023-10-20', 'STU2023005'),
('Claire', 'FOPA', 'fopa.claire@gmail.com', '+237 693789012', '2001-09-30', 2, 'L3', 'active', '2023-10-21', 'STU2023006'),
('Eric', 'BIYONG', 'eric.biyong@gmail.com', '+237 692890123', '2000-11-12', 3, 'L2', 'inactive', '2023-10-22', 'STU2023007'),
('Sandra', 'ATEBA', 'ateba.s@gmail.com', '+237 691901234', '2001-04-08', 1, 'M2', 'active', '2023-10-23', 'STU2023008'),
('Kevin', 'ESSOMBA', 'kessomba@gmail.com', '+237 690012345', '2002-02-14', 5, 'L1', 'pending', '2024-03-12', 'STU2024002'),
('Diane', 'NGONO', 'd.ngono@gmail.com', '+237 689123456', '2001-06-27', 4, 'L2', 'active', '2023-10-25', 'STU2023009'),
('Boris', 'ENOW', 'b.enow@gmail.com', '+237 688234567', '2000-10-05', 2, 'M1', 'active', '2023-10-26', 'STU2023010');

-- Insertion des inscriptions
INSERT INTO `registrations` (`student_id`, `program_id`, `reference`, `registration_date`, `academic_year`, `amount`, `payment_status`, `status`, `validation_date`) VALUES
(1, 2, 'REG2023001', '2023-10-15', '2023-2024', 120000.00, 'paid', 'validated', '2023-10-16'),
(2, 3, 'REG2023002', '2023-10-16', '2023-2024', 500000.00, 'paid', 'validated', '2023-10-17'),
(3, 1, 'REG2023003', '2023-10-17', '2023-2024', 150000.00, 'paid', 'validated', '2023-10-18'),
(4, 1, 'REG2023004', '2023-10-18', '2023-2024', 150000.00, 'paid', 'validated', '2023-10-19'),
(5, 4, 'REG2024001', '2024-03-10', '2024-2025', 130000.00, 'pending', 'pending', NULL),
(6, 5, 'REG2023005', '2023-10-20', '2023-2024', 140000.00, 'paid', 'validated', '2023-10-21'),
(7, 2, 'REG2023006', '2023-10-21', '2023-2024', 120000.00, 'paid', 'validated', '2023-10-22'),
(8, 3, 'REG2023007', '2023-10-22', '2023-2024', 500000.00, 'unpaid', 'pending', NULL),
(9, 1, 'REG2023008', '2023-10-23', '2023-2024', 150000.00, 'paid', 'validated', '2023-10-24'),
(10, 5, 'REG2024002', '2024-03-12', '2024-2025', 140000.00, 'pending', 'pending', NULL),
(11, 4, 'REG2023009', '2023-10-25', '2023-2024', 130000.00, 'paid', 'validated', '2023-10-26'),
(12, 2, 'REG2023010', '2023-10-26', '2023-2024', 120000.00, 'paid', 'validated', '2023-10-27');

-- Insertion des cours
INSERT INTO `courses` (`code`, `name`, `description`, `credits`, `hours`, `program_id`, `level`, `semester`, `max_students`) VALUES
('INF101', 'Algorithmique et Programmation', 'Introduction aux algorithmes et structures de données', 4, 45, 1, 'L1', 'Semestre 1', 60),
('INF102', 'Bases de Données', 'Conception et gestion de bases de données relationnelles', 4, 45, 1, 'L1', 'Semestre 1', 60),
('INF201', 'Développement Web', 'HTML, CSS, JavaScript et frameworks modernes', 4, 60, 1, 'L2', 'Semestre 1', 50),
('INF202', 'Réseaux Informatiques', 'Protocoles réseau et administration système', 4, 45, 1, 'L2', 'Semestre 2', 50),
('INF301', 'Sécurité Informatique', 'Cybersécurité et protection des systèmes', 4, 45, 1, 'L3', 'Semestre 1', 40),
('INF302', 'Intelligence Artificielle', 'Introduction au machine learning', 4, 45, 1, 'L3', 'Semestre 2', 40),
('GES101', 'Comptabilité Générale', 'Principes fondamentaux de la comptabilité', 4, 45, 2, 'L1', 'Semestre 1', 80),
('GES102', 'Marketing', 'Stratégies marketing et communication', 3, 30, 2, 'L1', 'Semestre 2', 80),
('MED101', 'Anatomie Humaine', 'Étude détaillée de l''anatomie humaine', 5, 60, 3, 'L1', 'Semestre 1', 40),
('MED102', 'Physiologie', 'Fonctionnement des systèmes biologiques', 5, 60, 3, 'L1', 'Semestre 2', 40);

-- Insertion des paramètres système
INSERT INTO `settings` (`key`, `value`, `description`, `category`, `type`) VALUES
('school_name', 'TAAJ Corp', 'Nom de l''établissement', 'general', 'string'),
('school_address', 'Yaoundé, Cameroun', 'Adresse de l''établissement', 'general', 'string'),
('school_phone', '+237 222 123 456', 'Téléphone de l''établissement', 'general', 'string'),
('school_email', 'contact@taajcorp.com', 'Email de contact', 'general', 'string'),
('academic_year', '2024-2025', 'Année académique en cours', 'academic', 'string'),
('registration_fee', '10000.00', 'Frais d''inscription', 'fees', 'number'),
('late_payment_penalty', '0.10', 'Pénalité pour paiement en retard (10%)', 'fees', 'number'),
('max_students_per_class', '50', 'Nombre maximum d''étudiants par classe', 'academic', 'number'),
('min_attendance_rate', '0.75', 'Taux de présence minimum (75%)', 'academic', 'number'),
('passing_grade', '10.00', 'Note minimale pour valider un cours', 'academic', 'number'),
('enable_online_registration', '1', 'Activer les inscriptions en ligne', 'features', 'boolean'),
('enable_payment_online', '1', 'Activer les paiements en ligne', 'features', 'boolean');

-- ========================================
-- VUES UTILES
-- ========================================

-- Vue des statistiques des étudiants
CREATE VIEW `student_stats` AS
SELECT 
  p.id as program_id,
  p.name as program_name,
  COUNT(s.id) as total_students,
  COUNT(CASE WHEN s.status = 'active' THEN 1 END) as active_students,
  COUNT(CASE WHEN s.status = 'pending' THEN 1 END) as pending_students,
  COUNT(CASE WHEN s.status = 'inactive' THEN 1 END) as inactive_students,
  COUNT(CASE WHEN s.status = 'graduated' THEN 1 END) as graduated_students
FROM programs p
LEFT JOIN students s ON p.id = s.program_id
GROUP BY p.id, p.name;

-- Vue des statistiques des inscriptions
CREATE VIEW `registration_stats` AS
SELECT 
  p.id as program_id,
  p.name as program_name,
  COUNT(r.id) as total_registrations,
  COUNT(CASE WHEN r.payment_status = 'paid' THEN 1 END) as paid_registrations,
  COUNT(CASE WHEN r.payment_status = 'pending' THEN 1 END) as pending_registrations,
  COUNT(CASE WHEN r.payment_status = 'unpaid' THEN 1 END) as unpaid_registrations,
  SUM(CASE WHEN r.payment_status = 'paid' THEN r.amount ELSE 0 END) as total_revenue
FROM programs p
LEFT JOIN registrations r ON p.id = r.program_id
GROUP BY p.id, p.name;

-- Vue des performances académiques
CREATE VIEW `academic_performance` AS
SELECT 
  s.id as student_id,
  CONCAT(s.first_name, ' ', s.last_name) as student_name,
  p.name as program_name,
  s.level,
  AVG(g.grade) as average_grade,
  COUNT(g.id) as total_grades,
  COUNT(CASE WHEN g.grade >= 10 THEN 1 END) as passed_courses,
  COUNT(CASE WHEN g.grade < 10 THEN 1 END) as failed_courses
FROM students s
JOIN programs p ON s.program_id = p.id
LEFT JOIN grades g ON s.id = g.student_id
WHERE s.status = 'active'
GROUP BY s.id, s.first_name, s.last_name, p.name, s.level;

-- ========================================
-- TRIGGERS
-- ========================================

-- Trigger pour mettre à jour le nombre d'étudiants actuels dans les cours
DELIMITER //
CREATE TRIGGER `update_course_students_after_registration`
AFTER INSERT ON `registrations`
FOR EACH ROW
BEGIN
  UPDATE courses 
  SET current_students = (
    SELECT COUNT(*) 
    FROM registrations r 
    JOIN students s ON r.student_id = s.id 
    WHERE r.program_id = (
      SELECT program_id FROM courses WHERE id = NEW.program_id LIMIT 1
    ) AND r.status = 'validated'
  )
  WHERE id = NEW.program_id;
END//
DELIMITER ;

-- Trigger pour logger les modifications importantes
DELIMITER //
CREATE TRIGGER `log_program_changes`
AFTER UPDATE ON `programs`
FOR EACH ROW
BEGIN
  IF OLD.name != NEW.name OR OLD.active != NEW.active THEN
    INSERT INTO logs (action, table_name, record_id, old_values, new_values)
    VALUES (
      'UPDATE',
      'programs',
      NEW.id,
      JSON_OBJECT('name', OLD.name, 'active', OLD.active),
      JSON_OBJECT('name', NEW.name, 'active', NEW.active)
    );
  END IF;
END//
DELIMITER ;

-- ========================================
-- INDEXATION OPTIMALE
-- ========================================

-- Index composites pour les performances
CREATE INDEX `idx_registrations_student_program` ON `registrations` (`student_id`, `program_id`);
CREATE INDEX `idx_grades_student_academic` ON `grades` (`student_id`, `academic_year`);
CREATE INDEX `idx_attendances_student_date` ON `attendances` (`student_id`, `date`);
CREATE INDEX `idx_payments_registration_date` ON `payments` (`registration_id`, `payment_date`);

-- ========================================
-- PROCÉDURES STOCKÉES
-- ========================================

DELIMITER //
CREATE PROCEDURE `GetStudentReport`(IN student_id_param INT)
BEGIN
  SELECT 
    s.*,
    p.name as program_name,
    p.level as program_level,
    (SELECT COUNT(*) FROM registrations r WHERE r.student_id = s.id AND r.status = 'validated') as registration_count,
    (SELECT AVG(g.grade) FROM grades g WHERE g.student_id = s.id) as average_grade,
    (SELECT COUNT(*) FROM attendances a WHERE a.student_id = s.id AND a.status = 'present') as attendance_count
  FROM students s
  JOIN programs p ON s.program_id = p.id
  WHERE s.id = student_id_param;
END//
DELIMITER ;

DELIMITER //
CREATE PROCEDURE `GenerateAcademicReport`(IN academic_year_param VARCHAR(20))
BEGIN
  SELECT 
    p.name as program_name,
    COUNT(s.id) as total_students,
    COUNT(CASE WHEN r.payment_status = 'paid' THEN 1 END) as paid_students,
    SUM(CASE WHEN r.payment_status = 'paid' THEN r.amount ELSE 0 END) as total_revenue,
    AVG(g.grade) as average_grade
  FROM programs p
  LEFT JOIN students s ON p.id = s.program_id
  LEFT JOIN registrations r ON s.id = r.student_id AND r.academic_year = academic_year_param
  LEFT JOIN grades g ON s.id = g.student_id AND g.academic_year = academic_year_param
  WHERE p.active = 1
  GROUP BY p.id, p.name
  ORDER BY total_revenue DESC;
END//
DELIMITER ;

-- ========================================
-- FIN DU SCRIPT
-- ========================================
