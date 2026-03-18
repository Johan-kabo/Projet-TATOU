<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

include 'db/mysql_connection_gestion_inscription.php';

$message = '';
$testResults = [];

// Test direct sans cURL
try {
    // Test étudiants
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $studentsCount = $stmt->fetch()['count'];
    $testResults['students_db'] = ['status' => true, 'count' => $studentsCount];
    
    // Test programmes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM programs");
    $programsCount = $stmt->fetch()['count'];
    $testResults['programs_db'] = ['status' => true, 'count' => $programsCount];
    
    // Test inscriptions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM registrations");
    $registrationsCount = $stmt->fetch()['count'];
    $testResults['registrations_db'] = ['status' => true, 'count' => $registrationsCount];
    
} catch (PDOException $e) {
    $testResults['db_error'] = ['status' => false, 'message' => $e->getMessage()];
}

// Test POST simple
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    try {
        $testData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test' . time() . '@example.com',
            'phone' => '+237 123456789',
            'program_id' => 1,
            'level' => 'L1',
            'date_of_birth' => '2000-01-01',
            'status' => 'active'
        ];
        
        // Insertion directe en base
        $insertStmt = $pdo->prepare("
            INSERT INTO students (
                first_name, last_name, email, phone, 
                date_of_birth, program_id, level, status,
                student_id_card, registration_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $studentIdCard = 'STU' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $result = $insertStmt->execute([
            $testData['first_name'],
            $testData['last_name'],
            $testData['email'],
            $testData['phone'],
            $testData['date_of_birth'],
            $testData['program_id'],
            $testData['level'],
            $testData['status'],
            $studentIdCard
        ]);
        
        if ($result) {
            $studentId = $pdo->lastInsertId();
            $message = "✅ Étudiant ajouté avec succès ! ID: $studentId, Carte: $studentIdCard";
        } else {
            $message = "❌ Erreur lors de l'ajout";
        }
        
    } catch (PDOException $e) {
        $message = "❌ Erreur base de données: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test Simple - TAAJ Corp</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #f8fafc; padding: 20px;
}
.container {
    max-width: 1200px; margin: 0 auto;
}
.header {
    background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.title {
    font-size: 24px; font-weight: 700; color: #1a202c; margin-bottom: 10px;
}
.grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;
}
.card {
    background: white; border-radius: 12px; padding: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.card-title {
    font-size: 18px; font-weight: 600; color: #1a202c; margin-bottom: 15px;
    padding-bottom: 10px; border-bottom: 2px solid #f59e0b;
}
.test-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0; border-bottom: 1px solid #e2e8f0;
}
.test-item:last-child {
    border-bottom: none;
}
.test-label {
    font-weight: 500; color: #64748b;
}
.test-result {
    font-weight: 600;
}
.success {
    color: #059669;
}
.error {
    color: #dc2626;
}
.warning {
    color: #d97706;
}
.info {
    color: #2563eb;
}
.btn {
    background: #3b82f6; color: white; border: none; border-radius: 8px;
    padding: 10px 20px; font-weight: 600; cursor: pointer;
    margin: 10px 5px 0 0; transition: background 0.2s;
}
.btn:hover { background: #2563eb; }
.btn-success {
    background: #10b981;
}
.btn-success:hover { background: #059669; }
.btn-danger {
    background: #dc2626;
}
.btn-danger:hover { background: #b91c1c; }
.message {
    padding: 12px 16px; border-radius: 8px; margin: 15px 0;
    font-size: 14px; font-weight: 500;
}
.success-message { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
.error-message { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="title">🧪 Test Simple de la Base de Données</h1>
        <p>Vérification directe de la connexion et des données</p>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <!-- Debug Session -->
        <div class="card">
            <h2 class="card-title">🔍 Session</h2>
            <div class="test-item">
                <span class="test-label">Session ID:</span>
                <span class="test-result"><?php echo session_id(); ?></span>
            </div>
            <div class="test-item">
                <span class="test-label">Logged In:</span>
                <span class="test-result <?php echo $_SESSION['logged_in'] ? 'success' : 'error'; ?>">
                    <?php echo $_SESSION['logged_in'] ? '✅ Oui' : '❌ Non'; ?>
                </span>
            </div>
            <div class="test-item">
                <span class="test-label">User ID:</span>
                <span class="test-result"><?php echo $_SESSION['user_id'] ?? 'N/A'; ?></span>
            </div>
            <div class="test-item">
                <span class="test-label">User Email:</span>
                <span class="test-result"><?php echo $_SESSION['email'] ?? 'N/A'; ?></span>
            </div>
        </div>

        <!-- Test Base de données -->
        <div class="card">
            <h2 class="card-title">🗄️ Base de Données</h2>
            <?php if (isset($testResults['db_error'])): ?>
                <div class="test-item">
                    <span class="test-label">Status:</span>
                    <span class="test-result error">❌ Erreur</span>
                </div>
                <div class="test-item">
                    <span class="test-label">Message:</span>
                    <span class="test-result"><?php echo htmlspecialchars($testResults['db_error']['message']); ?></span>
                </div>
            <?php else: ?>
                <div class="test-item">
                    <span class="test-label">Connexion:</span>
                    <span class="test-result success">✅ OK</span>
                </div>
                <div class="test-item">
                    <span class="test-label">Étudiants:</span>
                    <span class="test-result"><?php echo $testResults['students_db']['count'] ?? 0; ?></span>
                </div>
                <div class="test-item">
                    <span class="test-label">Programmes:</span>
                    <span class="test-result"><?php echo $testResults['programs_db']['count'] ?? 0; ?></span>
                </div>
                <div class="test-item">
                    <span class="test-label">Inscriptions:</span>
                    <span class="test-result"><?php echo $testResults['registrations_db']['count'] ?? 0; ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Test Ajout -->
        <div class="card">
            <h2 class="card-title">➕ Test d'Ajout</h2>
            <p style="margin-bottom: 15px; color: #64748b;">Test d'ajout d'un étudiant directement en base</p>
            
            <form method="POST">
                <button type="submit" name="add_student" class="btn btn-success">
                    🧪 Ajouter un étudiant test
                </button>
            </form>
            
            <div style="margin-top: 15px; font-size: 12px; color: #64748b;">
                <p><strong>Données test:</strong></p>
                <ul style="margin-left: 20px; margin-top: 5px;">
                    <li>Nom: Test User</li>
                    <li>Email: testXXX@example.com</li>
                    <li>Programme: ID 1</li>
                    <li>Niveau: L1</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">🚀 Actions Rapides</h2>
        <p>Testez les formulaires avec debug</p>
        
        <button class="btn" onclick="window.open('debug_students_form.php', '_blank')">
            🐛 Debug Formulaire Étudiants
        </button>
        
        <button class="btn" onclick="window.open('students.php', '_blank')">
            👨‍🎓 Page Étudiants
        </button>
        
        <button class="btn" onclick="window.open('programs.php', '_blank')">
            📚 Page Programmes
        </button>
        
        <button class="btn" onclick="window.open('registrations.php', '_blank')">
            📝 Page Inscriptions
        </button>
        
        <button class="btn btn-success" onclick="window.open('quick_seed.php', '_blank')">
            🌱 Ajouter données de test
        </button>
        
        <button class="btn btn-danger" onclick="window.open('reset_and_seed.php', '_blank')">
            🔄 Réinitialiser données
        </button>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">📋 Problèmes Identifiés</h2>
        <div style="line-height: 1.6;">
            <h3 style="margin: 15px 0 10px 0; color: #dc2626;">🐛 Causes possibles:</h3>
            <ul style="margin-left: 20px;">
                <li><strong>Session non transmise:</strong> Les APIs ne reçoivent pas la session</li>
                <li><strong>URL incorrecte:</strong> Les fetch() utilisent des mauvaises URLs</li>
                <li><strong>CORS:</strong> Problèmes de cross-origin</li>
                <li><strong>JavaScript:</strong> Erreurs dans le code JS</li>
            </ul>
            
            <h3 style="margin: 15px 0 10px 0; color: #10b981;">✅ Solutions:</h3>
            <ul style="margin-left: 20px;">
                <li><strong>Utiliser ce test simple</strong> pour vérifier la base de données</li>
                <li><strong>Debug formulaire</strong> pour voir les erreurs JavaScript</li>
                <li><strong>Console navigateur</strong> (F12) pour voir les erreurs réseau</li>
                <li><strong>Vérifier les URLs</strong> dans les fetch()</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
