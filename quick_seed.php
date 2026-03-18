<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

include 'db/mysql_connection_gestion_inscription.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_students'])) {
    try {
        $pdo->beginTransaction();
        
        // Insérer quelques étudiants de test
        $testStudents = [
            [
                'first_name' => 'Test',
                'last_name' => 'Student 1',
                'email' => 'test1@taajcorp.com',
                'phone' => '+237 699123456',
                'date_of_birth' => '2000-01-15',
                'program_id' => 1,
                'level' => 'L2',
                'status' => 'active',
                'student_id_card' => 'STU2024001'
            ],
            [
                'first_name' => 'Test',
                'last_name' => 'Student 2',
                'email' => 'test2@taajcorp.com',
                'phone' => '+237 698234567',
                'date_of_birth' => '2001-03-22',
                'program_id' => 2,
                'level' => 'L3',
                'status' => 'active',
                'student_id_card' => 'STU2024002'
            ],
            [
                'first_name' => 'Test',
                'last_name' => 'Student 3',
                'email' => 'test3@taajcorp.com',
                'phone' => '+237 697345678',
                'date_of_birth' => '2000-08-10',
                'program_id' => 3,
                'level' => 'L1',
                'status' => 'pending',
                'student_id_card' => 'STU2024003'
            ]
        ];
        
        foreach ($testStudents as $student) {
            $insertStmt = $pdo->prepare("
                INSERT INTO students (
                    first_name, last_name, email, phone, 
                    date_of_birth, program_id, level, status, 
                    student_id_card, registration_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insertStmt->execute([
                $student['first_name'],
                $student['last_name'],
                $student['email'],
                $student['phone'],
                $student['date_of_birth'],
                $student['program_id'],
                $student['level'],
                $student['status'],
                $student['student_id_card']
            ]);
        }
        
        $pdo->commit();
        $message = "✅ 3 étudiants de test ajoutés avec succès !";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "❌ Erreur lors de l'ajout : " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_registrations'])) {
    try {
        $pdo->beginTransaction();
        
        // Insérer quelques inscriptions de test
        $testRegistrations = [
            [
                'student_id' => 1,
                'program_id' => 1,
                'reference' => 'REG2024001',
                'registration_date' => '2024-03-15',
                'academic_year' => '2024-2025',
                'semester' => 'Année complète',
                'amount' => 150000,
                'payment_method' => 'bank_transfer',
                'payment_status' => 'paid',
                'status' => 'validated',
                'validation_date' => '2024-03-16 09:00:00',
                'validated_by' => $_SESSION['user_id']
            ],
            [
                'student_id' => 2,
                'program_id' => 2,
                'reference' => 'REG2024002',
                'registration_date' => '2024-03-16',
                'academic_year' => '2024-2025',
                'semester' => 'Année complète',
                'amount' => 500000,
                'payment_method' => 'bank_transfer',
                'payment_status' => 'pending',
                'status' => 'pending',
                'validation_date' => null,
                'validated_by' => $_SESSION['user_id']
            ]
        ];
        
        foreach ($testRegistrations as $registration) {
            $insertStmt = $pdo->prepare("
                INSERT INTO registrations (
                    student_id, program_id, reference, registration_date,
                    academic_year, semester, amount, payment_method,
                    payment_status, status, validation_date, validated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $registration['student_id'],
                $registration['program_id'],
                $registration['reference'],
                $registration['registration_date'],
                $registration['academic_year'],
                $registration['semester'],
                $registration['amount'],
                $registration['payment_method'],
                $registration['payment_status'],
                $registration['status'],
                $registration['validation_date'],
                $registration['validated_by']
            ]);
        }
        
        $pdo->commit();
        $message = "✅ 2 inscriptions de test ajoutées avec succès !";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "❌ Erreur lors de l'ajout : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quick Seed - TAAJ Corp</title>
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
.info-text {
    font-size: 14px; color: #374151; line-height: 1.5;
}
.btn {
    background: #3b82f6; color: white; border: none; border-radius: 8px;
    padding: 12px 24px; font-size: 14px; font-weight: 600;
    cursor: pointer; width: 100%; transition: background 0.2s; margin: 10px 0;
}
.btn:hover { background: #2563eb; }
.btn-danger {
    background: #dc2626;
}
.btn-danger:hover { background: #b91c1c; }
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
        <h1 class="title">🌱 Quick Seed</h1>
        <p class="subtitle">Ajout rapide de données de test pour tester les formulaires</p>
    </div>
    
    <div class="info-box">
        <h2 class="info-title">📊 État actuel de la base de données</h2>
        <div class="info-text">
            <?php
            try {
                $studentsCount = $pdo->query("SELECT COUNT(*) as count FROM students")->fetch()['count'];
                $programsCount = $pdo->query("SELECT COUNT(*) as count FROM programs")->fetch()['count'];
                $registrationsCount = $pdo->query("SELECT COUNT(*) as count FROM registrations")->fetch()['count'];
                
                echo "<p><strong>Étudiants:</strong> $studentsCount</p>";
                echo "<p><strong>Programmes:</strong> $programsCount</p>";
                echo "<p><strong>Inscriptions:</strong> $registrationsCount</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <button type="submit" name="seed_students" class="btn">
            👨‍🎓 Ajouter 3 étudiants de test
        </button>
        
        <button type="submit" name="seed_registrations" class="btn">
            📝 Ajouter 2 inscriptions de test
        </button>
    </form>
    
    <a href="test_forms.php" class="btn" style="background: #6b7280;">
        🧪 Retour au test des formulaires
    </a>
    
    <a href="dashboard.php" class="btn" style="background: #6b7280;">
        📊 Retour au tableau de bord
    </a>
</div>
</body>
</html>
