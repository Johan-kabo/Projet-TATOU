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

// Traitement de la réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    try {
        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Vider les tables principales
        $pdo->exec("DELETE FROM grades");
        $pdo->exec("DELETE FROM attendances");
        $pdo->exec("DELETE FROM payments");
        $pdo->exec("DELETE FROM registrations");
        $pdo->exec("DELETE FROM students");
        
        // Conserver uniquement l'admin et les programmes
        $pdo->exec("DELETE FROM students WHERE id > 1");
        
        // Réinitialiser les auto-incréments
        $pdo->exec("ALTER TABLE grades AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE attendances AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE payments AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE registrations AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE students AUTO_INCREMENT = 1");
        
        // Valider la transaction
        $pdo->commit();
        
        $message = "✅ Données réinitialisées avec succès !";
        
        // Ajouter un log de l'action
        $logStmt = $pdo->prepare("INSERT INTO logs (user_id, action, table_name, new_values) VALUES (?, ?, ?, ?)");
        $logStmt->execute([
            $_SESSION['user_id'],
            'RESET_DATA',
            'multiple',
            json_encode(['timestamp' => date('Y-m-d H:i:s')])
        ]);
        
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
<title>Réinitialisation des données - TAAJ Corp</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
}
.container {
    background: white; border-radius: 16px; padding: 40px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    max-width: 500px; width: 100%;
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
.warning-box {
    background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px;
    padding: 16px; margin: 20px 0;
}
.warning-title {
    font-size: 16px; font-weight: 600; color: #92400e; margin-bottom: 8px;
}
.warning-text {
    font-size: 14px; color: #78350f; line-height: 1.5;
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
.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.error { background: #fee2e2; color: #991b1b; border: 1px solid #feca2a; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="title">⚠️ Réinitialisation des données</h1>
        <p class="subtitle">Cette action va supprimer toutes les données des étudiants, inscriptions, notes et paiements</p>
    </div>
    
    <div class="warning-box">
        <div class="warning-title">🚨 ATTENTION - ACTION IRRÉVERSIBLE</div>
        <div class="warning-text">
            • Tous les étudiants seront supprimés (sauf l'admin)<br>
            • Toutes les inscriptions seront supprimées<br>
            • Toutes les notes et présences seront supprimées<br>
            • Tous les paiements seront supprimés<br>
            • Les programmes et paramètres seront conservés<br>
            • Cette action sera journalisée
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" onsubmit="return confirm('Êtes-vous ABSOLUMENT certain de vouloir réinitialiser toutes les données ? Cette action ne peut pas être annulée !');">
        <button type="submit" name="confirm_reset" value="1" class="btn-reset">
            🗑️ Réinitialiser toutes les données
        </button>
    </form>
    
    <a href="dashboard.php" class="btn-back">
        ← Retour au tableau de bord
    </a>
</div>
</body>
</html>
