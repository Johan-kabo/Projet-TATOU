<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

include 'db/mysql_connection_gestion_inscription.php';

$message = '';
$apiResponse = '';
$apiError = '';

// Test direct de l'API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_api'])) {
    try {
        // Activer l'affichage des erreurs
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Capturer la sortie de l'API
        ob_start();
        
        // Simuler l'appel à l'API
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [];
        
        // Inclure le fichier de l'API
        include 'api_crud_students.php';
        
        $apiResponse = ob_get_clean();
        
    } catch (Exception $e) {
        $apiError = $e->getMessage();
        $apiResponse = 'Exception: ' . $e->getMessage();
    } catch (Error $e) {
        $apiError = $e->getMessage();
        $apiResponse = 'Error: ' . $e->getMessage();
    }
}

// Test de la base de données directement
$dbTest = '';
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $count = $stmt->fetch()['count'];
    $dbTest = "✅ Connexion BD OK - $count étudiants";
} catch (PDOException $e) {
    $dbTest = "❌ Erreur BD: " . $e->getMessage();
}

// Test avec cURL pour simuler un appel AJAX
$curlTest = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_curl'])) {
    try {
        $ch = curl_init('http://localhost/Projet%20TATOU/api_crud_students.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);
        
        if ($curlError) {
            $curlTest = "❌ Erreur cURL: $curlError";
        } else {
            $curlTest = "✅ cURL OK - HTTP $httpCode - Réponse: " . substr($response, 0, 200);
        }
        
    } catch (Exception $e) {
        $curlTest = "❌ Exception cURL: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test API Direct - TAAJ Corp</title>
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
.code-block {
    background: #1e293b; color: #e2e8f0; padding: 15px; border-radius: 8px;
    font-family: 'Courier New', monospace; font-size: 12px; margin: 10px 0;
    white-space: pre-wrap; overflow-x: auto;
    max-height: 300px; overflow-y: auto;
}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="title">🔍 Test API Direct</h1>
        <p>Diagnostic complet de l'API students</p>
    </div>

    <div class="grid">
        <!-- Session Info -->
        <div class="card">
            <h2 class="card-title">🔐 Session</h2>
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
        </div>

        <!-- Database Test -->
        <div class="card">
            <h2 class="card-title">🗄️ Base de Données</h2>
            <div class="test-item">
                <span class="test-label">Status:</span>
                <span class="test-result <?php echo strpos($dbTest, '✅') !== false ? 'success' : 'error'; ?>">
                    <?php echo $dbTest; ?>
                </span>
            </div>
        </div>

        <!-- API File Test -->
        <div class="card">
            <h2 class="card-title">📄 Fichier API</h2>
            <div class="test-item">
                <span class="test-label">Fichier:</span>
                <span class="test-result"><?php echo file_exists('api_crud_students.php') ? '✅ Existe' : '❌ Manquant'; ?></span>
            </div>
            <div class="test-item">
                <span class="test-label">Taille:</span>
                <span class="test-result"><?php echo filesize('api_crud_students.php') ?? 'N/A'; ?> octets</span>
            </div>
        </div>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">🧪 Tests de l'API</h2>
        <p>Tests directs pour identifier le problème</p>
        
        <form method="POST">
            <button type="submit" name="test_api" class="btn btn-success">
                🔍 Tester l'API directement
            </button>
            
            <button type="submit" name="test_curl" class="btn">
                🌐 Tester avec cURL (AJAX)
            </button>
        </form>
        
        <?php if ($apiResponse || $apiError): ?>
            <div style="margin-top: 20px;">
                <h3 style="margin-bottom: 10px; color: #1a202c;">📋 Réponse de l'API:</h3>
                <div class="code-block">
<?php 
if ($apiError) {
    echo "ERREUR: " . $apiError . "\n\n";
}
echo $apiResponse; 
?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($curlTest): ?>
            <div style="margin-top: 20px;">
                <h3 style="margin-bottom: 10px; color: #1a202c;">📡 Test cURL:</h3>
                <div class="code-block"><?php echo $curlTest; ?></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">🔧 Actions Rapides</h2>
        <button class="btn" onclick="window.open('debug_validation.php', '_blank')">
            🔍 Debug Validation Formulaire
        </button>
        
        <button class="btn" onclick="window.open('students.php', '_blank')">
            👨‍🎓 Page Étudiants
        </button>
        
        <button class="btn" onclick="window.open('simple_test.php', '_blank')">
            🧪 Test Simple BD
        </button>
        
        <button class="btn btn-danger" onclick="window.open('api_crud_students.php', '_blank')">
            📄 Voir l'API directement
        </button>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">📋 Diagnostic</h2>
        <div style="line-height: 1.6;">
            <h3 style="margin: 15px 0 10px 0; color: #dc2626;">🐛 Problème identifié:</h3>
            <p>L'API retourne une réponse vide, ce qui indique probablement:</p>
            <ul style="margin-left: 20px;">
                <li>Erreur PHP fatale non affichée</li>
                <li>Problème de connexion à la base de données</li>
                <li>Erreur dans le code de l'API</li>
                <li>Session non transmise correctement</li>
            </ul>
            
            <h3 style="margin: 15px 0 10px 0; color: #10b981;">✅ Solutions:</h3>
            <ul style="margin-left: 20px;">
                <li>Tester l'API directement pour voir l'erreur</li>
                <li>Vérifier les logs d'erreurs PHP</li>
                <li>Activer l'affichage des erreurs</li>
                <li>Vérifier la connexion à la base de données</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
