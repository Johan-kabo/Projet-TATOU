<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

include 'db/mysql_connection_gestion_inscription.php';

// Ajouter les fonctions manquantes pour le renderTable et updateStatistics
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mise à jour Table Étudiants - TAAJ Corp</title>
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
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="title">🔄 Mise à jour Complète de la Synchronisation</h1>
        <p>Cette page met à jour le tableau des étudiants pour inclure les boutons de modification et suppression</p>
    </div>

    <div class="grid">
        <div class="card">
            <h2 class="card-title">📊 État Actuel</h2>
            <?php
            try {
                $studentsCount = $pdo->query("SELECT COUNT(*) as count FROM students")->fetch()['count'];
                echo "<p><strong>Total étudiants:</strong> $studentsCount</p>";
                
                $programsCount = $pdo->query("SELECT COUNT(*) as count FROM programs")->fetch()['count'];
                echo "<p><strong>Total programmes:</strong> $programsCount</p>";
                
                $registrationsCount = $pdo->query("SELECT COUNT(*) as count FROM registrations")->fetch()['count'];
                echo "<p><strong>Total inscriptions:</strong> $registrationsCount</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>

        <div class="card">
            <h2 class="card-title">🎯 Actions Disponibles</h2>
            <p><strong>CRUD Complet Activé:</strong></p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>✅ Créer des étudiants</li>
                <li>✅ Lire les étudiants (liste)</li>
                <li>✅ Mettre à jour les étudiants</li>
                <li>✅ Supprimer les étudiants</li>
                <li>✅ Synchronisation en temps réel</li>
            </ul>
        </div>

        <div class="card">
            <h2 class="card-title">🔗 APIs CRUD</h2>
            <p><strong>Endpoints créés:</strong></p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>📄 <code>api_crud_students.php</code></li>
                <li>📚 <code>api_crud_programs.php</code></li>
                <li>📝 <code>api_crud_registrations.php</code></li>
            </ul>
        </div>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">🚀 Accès Rapide</h2>
        <p>Testez toutes les fonctionnalités CRUD</p>
        
        <button class="btn btn-success" onclick="window.open('students.php', '_blank')">
            👨‍🎓 Étudiants (CRUD complet)
        </button>
        
        <button class="btn btn-success" onclick="window.open('programs.php', '_blank')">
            📚 Programmes (CRUD complet)
        </button>
        
        <button class="btn btn-success" onclick="window.open('registrations.php', '_blank')">
            📝 Inscriptions (CRUD complet)
        </button>
        
        <button class="btn" onclick="window.open('test_forms.php', '_blank')">
            🧪 Tester les formulaires
        </button>
        
        <button class="btn" onclick="window.open('quick_seed.php', '_blank')">
            🌱 Ajouter des données de test
        </button>
        
        <button class="btn btn-danger" onclick="window.open('reset_and_seed.php', '_blank')">
            🔄 Réinitialiser les données
        </button>
        
        <button class="btn" onclick="window.open('dashboard.php', '_blank')">
            📊 Tableau de bord
        </button>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">📋 Instructions d'utilisation</h2>
        <div style="line-height: 1.6;">
            <h3 style="margin: 15px 0 10px 0; color: #3b82f6;">👨‍🎓 Pour les étudiants:</h3>
            <ol style="margin-left: 20px;">
                <li><strong>Ajouter:</strong> Cliquez sur "Nouvel étudiant" → Remplissez le formulaire → "Enregistrer"</li>
                <li><strong>Modifier:</strong> Cliquez sur l'icône ✏️ d'un étudiant → Modifiez les champs → "Mettre à jour"</li>
                <li><strong>Supprimer:</strong> Cliquez sur l'icône 🗑️ d'un étudiant → Confirmez la suppression</li>
                <li><strong>Synchronisation:</strong> Les changements apparaissent immédiatement dans la liste</li>
            </ol>
            
            <h3 style="margin: 15px 0 10px 0; color: #3b82f6;">📚 Pour les programmes:</h3>
            <ol style="margin-left: 20px;">
                <li><strong>Ajouter:</strong> Cliquez sur "Nouveau programme" → Remplissez → "Enregistrer"</li>
                <li><strong>Modifier:</strong> Cliquez sur "Modifier" → Mettez à jour → "Enregistrer"</li>
                <li><strong>Supprimer:</strong> Cliquez sur "Supprimer" → Confirmez</li>
            </ol>
            
            <h3 style="margin: 15px 0 10px 0; color: #3b82f6;">📝 Pour les inscriptions:</h3>
            <ol style="margin-left: 20px;">
                <li><strong>Ajouter:</strong> Cliquez sur "Nouvelle inscription" → Sélectionnez → "Enregistrer"</li>
                <li><strong>Modifier:</strong> Cliquez sur "Modifier" → Mettez à jour → "Enregistrer"</li>
                <li><strong>Supprimer:</strong> Cliquez sur "Supprimer" → Confirmez</li>
            </ol>
        </div>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">✨ Fonctionnalités Spéciales</h2>
        <div style="line-height: 1.6;">
            <ul style="margin-left: 20px;">
                <li>🔄 <strong>Synchronisation en temps réel:</strong> Toutes les modifications sont instantanément réfléchies dans l'interface</li>
                <li>📊 <strong>Statistiques dynamiques:</strong> Les KPIs se mettent à jour automatiquement</li>
                <li>🔔 <strong>Notifications:</strong> Messages de confirmation pour chaque action</li>
                <li>🛡️ <strong>Sécurité:</strong> Validation des données et protection contre les doublons</li>
                <li>📝 <strong>Journalisation:</strong> Toutes les actions sont enregistrées dans les logs</li>
                <li>📄 <strong>Exports PDF/CSV:</strong> Génération de rapports professionnels</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
