<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

// Vérifier si c'est un admin
if ($_SESSION['role'] !== 'admin') {
    die("Accès non autorisé");
}

// Inclure la connexion DB
include 'db/mysql_connection_gestion_inscription.php';

$message = '';

// Traitement de la réinitialisation et insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    try {
        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Vider toutes les tables principales
        $pdo->exec("DELETE FROM grades");
        $pdo->exec("DELETE FROM attendances");
        $pdo->exec("DELETE FROM payments");
        $pdo->exec("DELETE FROM registrations");
        $pdo->exec("DELETE FROM students");
        $pdo->exec("DELETE FROM logs");
        
        // Réinitialiser les auto-incréments
        $pdo->exec("ALTER TABLE grades AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE attendances AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE payments AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE registrations AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE students AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE logs AUTO_INCREMENT = 1");
        
        // Insérer les données de test
        
        // 1. Insérer les programmes
        $programs = [
            ['INF-LIC', 'Licence en Informatique', 'Formation complète en développement logiciel, réseaux et cybersécurité', 'Licence', 3, 60, 150000.00, 'Aucun prérequis', 'Maîtriser les fondamentaux de la programmation', 1],
            ['GES-LIC', 'Licence en Gestion', 'Programme complet en management, finance et marketing', 'Licence', 3, 80, 120000.00, 'Baccalauréat', 'Acquérir les compétences en gestion d\'entreprise', 1],
            ['MED-LIC', 'Licence en Médecine', 'Formation médicale générale avec stages pratiques', 'Licence', 6, 40, 500000.00, 'Excellence en sciences', 'Devenir un professionnel de la santé', 1],
            ['ECO-LIC', 'Licence en Économie', 'Analyse économique, politique monétaire et commerce international', 'Licence', 3, 50, 130000.00, 'Baccalauréat ES', 'Comprendre les mécanismes économiques', 1],
            ['DRO-LIC', 'Licence en Droit', 'Droit privé, public, pénal et des affaires', 'Licence', 3, 60, 140000.00, 'Baccalauréat en droit', 'Maîtriser les différents domaines du droit', 1],
            ['IA-MS', 'Master en Intelligence Artificielle', 'Machine learning, deep learning et applications IA', 'Master', 2, 25, 300000.00, 'Licence en informatique', 'Devenir expert en IA et machine learning', 1]
        ];
        
        foreach ($programs as $prog) {
            $insertStmt = $pdo->prepare("
                INSERT INTO programs (
                    code, name, description, level, duration, capacity, price, 
                    requirements, objectives, active, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute($prog);
        }
        
        // 2. Insérer les étudiants
        $students = [
            ['Janot', 'NKENG', 'nkengjanot@gmail.com', '+237 699123456', '2000-05-15', 2, 'L2', 'active', 'STU2023001'],
            ['Alex', 'TAMO', 'tamoalex@gmail.com', '+237 698234567', '2001-03-22', 3, 'L3', 'active', 'STU2023002'],
            ['Johan', 'Manuel', 'kanojohan@gmail.com', '+237 697345678', '2000-08-10', 1, 'L3', 'active', 'STU2023003'],
            ['Junior', 'TATOU', 'tatou.jr@gmail.com', '+237 696456789', '2002-01-25', 1, 'L2', 'active', 'STU2023004'],
            ['Marie', 'ONANA', 'onana.marie@gmail.com', '+237 695567890', '2001-12-03', 4, 'L1', 'pending', 'STU2024001'],
            ['Paul', 'MBARGA', 'paul.mbarga@gmail.com', '+237 694678901', '2000-07-18', 5, 'M1', 'active', 'STU2023005'],
            ['Claire', 'FOPA', 'fopa.claire@gmail.com', '+237 693789012', '2001-09-30', 2, 'L3', 'active', 'STU2023006'],
            ['Eric', 'BIYONG', 'eric.biyong@gmail.com', '+237 692890123', '2000-11-12', 3, 'L2', 'inactive', 'STU2023007'],
            ['Sandra', 'ATEBA', 'ateba.s@gmail.com', '+237 691901234', '2001-04-08', 1, 'M2', 'active', 'STU2023008'],
            ['Kevin', 'ESSOMBA', 'kessomba@gmail.com', '+237 690012345', '2002-02-14', 5, 'L1', 'pending', 'STU2024002'],
            ['Diane', 'NGONO', 'd.ngono@gmail.com', '+237 689123456', '2001-06-27', 4, 'L2', 'active', 'STU2023009'],
            ['Boris', 'ENOW', 'b.enow@gmail.com', '+237 688234567', '2000-10-05', 2, 'M1', 'active', 'STU2023010']
        ];
        
        foreach ($students as $student) {
            $insertStmt = $pdo->prepare("
                INSERT INTO students (
                    first_name, last_name, email, phone, date_of_birth, 
                    program_id, level, status, registration_date, student_id_card
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute($student);
        }
        
        // 3. Insérer les inscriptions
        $registrations = [
            [1, 2, 'REG2023001', '2023-10-15', '2023-2024', 'Année complète', 120000.00, 'bank_transfer', 'paid', 'validated', '2023-10-16 09:00:00', 1],
            [2, 3, 'REG2023002', '2023-10-16', '2023-2024', 'Année complète', 500000.00, 'bank_transfer', 'paid', 'validated', '2023-10-17 10:00:00', 1],
            [3, 1, 'REG2023003', '2023-10-17', '2023-2024', 'Année complète', 150000.00, 'bank_transfer', 'paid', 'validated', '2023-10-18 11:00:00', 1],
            [4, 1, 'REG2023004', '2023-10-18', '2023-2024', 'Année complète', 150000.00, 'bank_transfer', 'paid', 'validated', '2023-10-19 14:00:00', 1],
            [5, 4, 'REG2024001', '2024-03-10', '2024-2025', 'Année complète', 130000.00, 'pending', 'pending', 'pending', NULL, 1],
            [6, 5, 'REG2023005', '2023-10-20', '2023-2024', 'Année complète', 140000.00, 'bank_transfer', 'paid', 'validated', '2023-10-21 09:00:00', 1],
            [7, 2, 'REG2023006', '2023-10-21', '2023-2024', 'Année complète', 120000.00, 'bank_transfer', 'paid', 'validated', '2023-10-22 10:00:00', 1],
            [8, 3, 'REG2023007', '2023-10-22', '2023-2024', 'Année complète', 500000.00, 'unpaid', 'pending', 'pending', NULL, 1],
            [9, 1, 'REG2023008', '2023-10-23', '2023-2024', 'Année complète', 150000.00, 'bank_transfer', 'paid', 'validated', '2023-10-24 15:00:00', 1],
            [10, 5, 'REG2024002', '2024-03-12', '2024-2025', 'Année complète', 140000.00, 'pending', 'pending', 'pending', NULL, 1],
            [11, 4, 'REG2023009', '2023-10-25', '2023-2024', 'Année complète', 130000.00, 'bank_transfer', 'paid', 'validated', '2023-10-26 11:00:00', 1],
            [12, 2, 'REG2023010', '2023-10-26', '2023-2024', 'Année complète', 120000.00, 'bank_transfer', 'paid', 'validated', '2023-10-27 14:00:00', 1]
        ];
        
        foreach ($registrations as $reg) {
            $insertStmt = $pdo->prepare("
                INSERT INTO registrations (
                    student_id, program_id, reference, registration_date, academic_year,
                    semester, amount, payment_method, payment_status, payment_date,
                    status, validation_date, validated_by, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute($reg);
        }
        
        // 4. Insérer quelques cours
        $courses = [
            ['INF101', 'Algorithmique et Programmation', 'Introduction aux algorithmes et structures de données', 4, 45, 1, 'L1', 'Semestre 1', 60, 0, 'active'],
            ['INF102', 'Bases de Données', 'Conception et gestion de bases de données relationnelles', 4, 45, 1, 'L1', 'Semestre 1', 60, 0, 'active'],
            ['INF201', 'Développement Web', 'HTML, CSS, JavaScript et frameworks modernes', 4, 60, 1, 'L2', 'Semestre 1', 50, 0, 'active'],
            ['INF202', 'Réseaux Informatiques', 'Protocoles réseau et administration système', 4, 45, 1, 'L2', 'Semestre 2', 50, 0, 'active'],
            ['INF301', 'Sécurité Informatique', 'Cybersécurité et protection des systèmes', 4, 45, 1, 'L3', 'Semestre 1', 40, 0, 'active'],
            ['INF302', 'Intelligence Artificielle', 'Introduction au machine learning', 4, 45, 1, 'L3', 'Semestre 2', 40, 0, 'active'],
            ['GES101', 'Comptabilité Générale', 'Principes fondamentaux de la comptabilité', 4, 45, 2, 'L1', 'Semestre 1', 80, 0, 'active'],
            ['GES102', 'Marketing', 'Stratégies marketing et communication', 3, 30, 2, 'L1', 'Semestre 2', 80, 0, 'active']
        ];
        
        foreach ($courses as $course) {
            $insertStmt = $pdo->prepare("
                INSERT INTO courses (
                    code, name, description, credits, hours, program_id, 
                    level, semester, max_students, current_students, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute($course);
        }
        
        // Valider la transaction
        $pdo->commit();
        
        // Journaliser l'action
        $logStmt = $pdo->prepare("
            INSERT INTO logs (user_id, action, table_name, new_values) 
            VALUES (?, ?, ?, ?)
        ");
        $logStmt->execute([
            $_SESSION['user_id'],
            'RESET_AND_SEED',
            'multiple',
            json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'programs_inserted' => count($programs),
                'students_inserted' => count($students),
                'registrations_inserted' => count($registrations),
                'courses_inserted' => count($courses)
            ])
        ]);
        
        $message = "✅ Données réinitialisées et peuplées avec succès !";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "❌ Erreur lors de la réinitialisation : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réinitialisation et Peuplement - TAAJ Corp</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    padding: 20px;
}
.container {
    background: white; border-radius: 16px; padding: 40px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    max-width: 600px; width: 100%;
}
.header {
    text-align: center; margin-bottom: 30px;
}
.title {
    font-size: 24px; font-weight: 700; color: #1a202c; margin-bottom: 10px;
}
.subtitle {
    font-size: 14px; color: #64748b; line-height: 1.5;
}
.info-box {
    background: #f0f9ff; border: 1px solid #e0e7ff; border-radius: 8px;
    padding: 20px; margin: 20px 0;
}
.info-title {
    font-size: 16px; font-weight: 600; color: #1e40af; margin-bottom: 10px;
}
.info-list {
    list-style: none; padding: 0;
}
.info-list li {
    padding: 8px 0; border-bottom: 1px solid #e5e7eb;
    font-size: 14px; color: #374151;
}
.info-list li:last-child {
    border-bottom: none;
}
.info-list strong {
    color: #1f2937; font-weight: 600;
}
.warning-box {
    background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px;
    padding: 20px; margin: 20px 0;
}
.warning-title {
    font-size: 16px; font-weight: 600; color: #d97706; margin-bottom: 10px;
}
.warning-text {
    font-size: 14px; color: #92400e; line-height: 1.5;
}
.success-box {
    background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 8px;
    padding: 20px; margin: 20px 0;
}
.success-title {
    font-size: 16px; font-weight: 600; color: #059669; margin-bottom: 10px;
}
.success-text {
    font-size: 14px; color: #047857; line-height: 1.5;
}
.btn-reset {
    background: #dc2626; color: white; border: none; border-radius: 8px;
    padding: 12px 24px; font-size: 14px; font-weight: 600;
    cursor: pointer; width: 100%; transition: background 0.2s;
}
.btn-reset:hover { background: #b91c1c; }
.btn-back {
    background: #6b7280; color: white; border: none; border-radius: 8px;
    padding: 12px 24px; font-size: 14px; font-weight: 600;
    cursor: pointer; width: 100%; margin-top: 10px; transition: background 0.2s;
}
.btn-back:hover { background: #4b5563; }
.message {
    padding: 12px 16px; border-radius: 8px; margin: 15px 0;
    font-size: 14px; font-weight: 500;
}
.success { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
.error { background: #fee2e2; color: #991b1b; border: 1px solid #feca2a; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="title">🔄 Réinitialisation et Peuplement</h1>
        <p class="subtitle">Cette action va réinitialiser toutes les données et insérer des données de test complètes</p>
    </div>
    
    <div class="info-box">
        <h2 class="info-title">📊 Données qui seront insérées :</h2>
        <ul class="info-list">
            <li><strong>Programmes:</strong> 6 programmes académiques (Informatique, Gestion, Médecine, Économie, Droit, IA)</li>
            <li><strong>Étudiants:</strong> 12 étudiants avec informations complètes</li>
            <li><strong>Inscriptions:</strong> 12 inscriptions avec différents statuts de paiement</li>
            <li><strong>Cours:</strong> 10 cours répartis par programme</li>
            <li><strong>Admin:</strong> Utilisateur admin@taajcorp.com / password</li>
        </ul>
    </div>
    
    <div class="warning-box">
        <h2 class="warning-title">⚠️ ATTENTION</h2>
        <p class="warning-text">
            <strong>Cette action est irréversible !</strong><br>
            • Toutes les données existantes seront supprimées<br>
            • Les auto-incréments seront réinitialisés<br>
            • L'action sera journalisée<br>
            • Seul un administrateur peut effectuer cette action
        </p>
    </div>
    
    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" onsubmit="return confirm('Êtes-vous ABSOLUMENT certain de vouloir réinitialiser TOUTES les données et insérer les données de test ?\\n\\nCette action va supprimer toutes les données existantes !\\n\\nCliquez sur OK pour continuer ou ANNULER pour arrêter.');">
        <button type="submit" name="confirm_reset" value="1" class="btn-reset">
            🔄 Réinitialiser et Peupler les données
        </button>
    </form>
    
    <a href="dashboard.php" class="btn-back">
        ← Retour au tableau de bord
    </a>
</div>
</body>
</html>
