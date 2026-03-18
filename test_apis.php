<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

include 'db/mysql_connection_gestion_inscription.php';

$message = '';
$testResults = [];

// Test API Students avec session
try {
    // Utiliser cURL pour inclure les cookies de session
    $ch = curl_init('http://localhost/Projet%20TATOU/api_crud_students.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    
    $testResults['students_api'] = [
        'status' => $data['success'] ?? false,
        'message' => $data['message'] ?? 'No message',
        'count' => isset($data['students']) ? count($data['students']) : 0,
        'curl_error' => curl_error($ch),
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE)
    ];
    
    curl_close($ch);
} catch (Exception $e) {
    $testResults['students_api'] = [
        'status' => false,
        'message' => $e->getMessage(),
        'count' => 0,
        'curl_error' => 'Exception: ' . $e->getMessage()
    ];
}

// Test API Programs avec session
try {
    $ch = curl_init('http://localhost/Projet%20TATOU/api_crud_programs.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    
    $testResults['programs_api'] = [
        'status' => $data['success'] ?? false,
        'message' => $data['message'] ?? 'No message',
        'count' => isset($data['programs']) ? count($data['programs']) : 0,
        'curl_error' => curl_error($ch),
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE)
    ];
    
    curl_close($ch);
} catch (Exception $e) {
    $testResults['programs_api'] = [
        'status' => false,
        'message' => $e->getMessage(),
        'count' => 0,
        'curl_error' => 'Exception: ' . $e->getMessage()
    ];
}

// Test API Registrations avec session
try {
    $ch = curl_init('http://localhost/Projet%20TATOU/api_crud_registrations.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    
    $testResults['registrations_api'] = [
        'status' => $data['success'] ?? false,
        'message' => $data['message'] ?? 'No message',
        'count' => isset($data['registrations']) ? count($data['registrations']) : 0,
        'curl_error' => curl_error($ch),
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE)
    ];
    
    curl_close($ch);
} catch (Exception $e) {
    $testResults['registrations_api'] = [
        'status' => false,
        'message' => $e->getMessage(),
        'count' => 0,
        'curl_error' => 'Exception: ' . $e->getMessage()
    ];
}

// Test POST API Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_student'])) {
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
    
    $ch = curl_init('http://localhost/Projet%20TATOU/api_crud_students.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    
    if ($data['success'] ?? false) {
        $message = "✅ Test POST API réussi! Étudiant ID: " . ($data['student_id'] ?? 'N/A');
    } else {
        $message = "❌ Test POST API échoué: " . ($data['message'] ?? 'Unknown error');
    }
    
    curl_close($ch);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test APIs - TAAJ Corp</title>
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
.message {
    padding: 12px 16px; border-radius: 8px; margin: 15px 0;
    font-size: 14px; font-weight: 500;
}
.success-message { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
.error-message { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.code-block {
    background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px;
    font-family: 'Courier New', monospace; font-size: 12px; margin: 10px 0;
    overflow-x: auto;
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="title">🧪 Test des APIs CRUD</h1>
        <p>Vérification du fonctionnement des APIs pour les formulaires</p>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <!-- Debug Session -->
        <div class="card">
            <h2 class="card-title">🔍 Debug Session</h2>
            <div class="test-item">
                <span class="test-label">Session ID:</span>
                <span class="test-result"><?php echo session_id(); ?></span>
            </div>
            <div class="test-item">
                <span class="test-label">Session Name:</span>
                <span class="test-result"><?php echo session_name(); ?></span>
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

        <!-- Test API Students -->
        <div class="card">
            <h2 class="card-title">👨‍🎓 API Students</h2>
            <div class="test-item">
                <span class="test-label">Status:</span>
                <span class="test-result <?php echo $testResults['students_api']['status'] ? 'success' : 'error'; ?>">
                    <?php echo $testResults['students_api']['status'] ? '✅ Fonctionne' : '❌ Erreur'; ?>
                </span>
            </div>
            <div class="test-item">
                <span class="test-label">HTTP Code:</span>
                <span class="test-result"><?php echo $testResults['students_api']['http_code'] ?? 'N/A'; ?></span>
            </div>
            <div class="test-item">
                <span class="test-label">Message:</span>
                <span class="test-result"><?php echo htmlspecialchars($testResults['students_api']['message']); ?></span>
            </div>
            <div class="test-item">
                <span class="test-label">Étudiants trouvés:</span>
                <span class="test-result"><?php echo $testResults['students_api']['count']; ?></span>
            </div>
            <?php if (!empty($testResults['students_api']['curl_error'])): ?>
            <div class="test-item">
                <span class="test-label">cURL Error:</span>
                <span class="test-result error"><?php echo htmlspecialchars($testResults['students_api']['curl_error']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Test API Programs -->
        <div class="card">
            <h2 class="card-title">📚 API Programs</h2>
            <div class="test-item">
                <span class="test-label">Status:</span>
                <span class="test-result <?php echo $testResults['programs_api']['status'] ? 'success' : 'error'; ?>">
                    <?php echo $testResults['programs_api']['status'] ? '✅ Fonctionne' : '❌ Erreur'; ?>
                </span>
            </div>
            <div class="test-item">
                <span class="test-label">HTTP Code:</span>
                <span class="test-result"><?php echo $testResults['programs_api']['http_code'] ?? 'N/A'; ?></span>
            </div>
            <div class="test-item">
                <span class="test-label">Message:</span>
                <span class="test-result"><?php echo htmlspecialchars($testResults['programs_api']['message']); ?></span>
            </div>
            <div class="test-item">
                <span class="test-label">Programmes trouvés:</span>
                <span class="test-result"><?php echo $testResults['programs_api']['count']; ?></span>
            </div>
            <?php if (!empty($testResults['programs_api']['curl_error'])): ?>
            <div class="test-item">
                <span class="test-label">cURL Error:</span>
                <span class="test-result error"><?php echo htmlspecialchars($testResults['programs_api']['curl_error']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Test API Registrations -->
        <div class="card">
            <h2 class="card-title">📝 API Registrations</h2>
            <div class="test-item">
                <span class="test-label">Status:</span>
                <span class="test-result <?php echo $testResults['registrations_api']['status'] ? 'success' : 'error'; ?>">
                    <?php echo $testResults['registrations_api']['status'] ? '✅ Fonctionne' : '❌ Erreur'; ?>
                </span>
            </div>
            <div class="test-item">
                <span class="test-label">HTTP Code:</span>
                <span class="test-result"><?php echo $testResults['registrations_api']['http_code'] ?? 'N/A'; ?></span>
            </div>
            <div class="test-item">
                <span class="test-label">Message:</span>
                <span class="test-result"><?php echo htmlspecialchars($testResults['registrations_api']['message']); ?></span>
            </div>
            <div class="test-item">
                <span class="test-label">Inscriptions trouvées:</span>
                <span class="test-result"><?php echo $testResults['registrations_api']['count']; ?></span>
            </div>
            <?php if (!empty($testResults['registrations_api']['curl_error'])): ?>
            <div class="test-item">
                <span class="test-label">cURL Error:</span>
                <span class="test-result error"><?php echo htmlspecialchars($testResults['registrations_api']['curl_error']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">🔧 Test POST API</h2>
        <p>Testez l'ajout d'un étudiant via l'API</p>
        
        <form method="POST">
            <button type="submit" name="test_student" class="btn btn-success">
                🧪 Tester l'ajout d'un étudiant
            </button>
        </form>
        
        <div class="code-block">
// Exemple de données envoyées:
{
  "first_name": "Test",
  "last_name": "User",
  "email": "test123@example.com",
  "phone": "+237 123456789",
  "program_id": 1,
  "level": "L1",
  "date_of_birth": "2000-01-01",
  "status": "active"
}
        </div>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">🔍 Débogage JavaScript</h2>
        <p>Ouvrez la console du navigateur (F12) et testez les formulaires pour voir les erreurs</p>
        
        <div class="code-block">
// Vérifiez ces points dans la console:
1. Erreurs réseau (Network tab)
2. Erreurs JavaScript (Console tab)
3. Réponses des APIs (Network tab > Response)
4. Headers et payloads envoyés
        </div>
        
        <button class="btn" onclick="window.open('students.php', '_blank')">
            👨‍🎓 Ouvrir la page étudiants
        </button>
        
        <button class="btn" onclick="window.open('programs.php', '_blank')">
            📚 Ouvrir la page programmes
        </button>
        
        <button class="btn" onclick="window.open('registrations.php', '_blank')">
            📝 Ouvrir la page inscriptions
        </button>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">📋 Checklist de Dépannage</h2>
        <div style="line-height: 1.8;">
            <h3 style="margin: 15px 0 10px 0; color: #3b82f6;">🔍 Vérifications à faire:</h3>
            <ol style="margin-left: 20px;">
                <li><strong>Session active:</strong> Êtes-vous bien connecté ?</li>
                <li><strong>URL correcte:</strong> L'URL dans le navigateur doit être correcte</li>
                <li><strong>Console navigateur:</strong> Ouvrez F12 → Console pour voir les erreurs</li>
                <li><strong>Network tab:</strong> Vérifiez les requêtes AJAX envoyées</li>
                <li><strong>API response:</strong> Les APIs retournent-elles des erreurs ?</li>
            </ol>
            
            <h3 style="margin: 15px 0 10px 0; color: #dc2626;">🐛 Problèmes courants:</h3>
            <ul style="margin-left: 20px;">
                <li>CORS errors (Cross-Origin)</li>
                <li>Session expirée</li>
                <li>URL incorrecte dans les fetch()</li>
                <li>Formulaire non soumis correctement</li>
                <li>JavaScript bloqué par le navigateur</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
