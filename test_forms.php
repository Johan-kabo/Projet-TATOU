<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login_db.php');
    exit;
}

include 'db/mysql_connection_gestion_inscription.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test des Formulaires - TAAJ Corp</title>
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
.btn-danger {
    background: #dc2626;
}
.btn-danger:hover { background: #b91c1c; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 class="title">🧪 Test des Formulaires et API</h1>
        <p>Vérification du fonctionnement complet des formulaires d'ajout</p>
    </div>

    <div class="grid">
        <!-- Test API Étudiants -->
        <div class="card">
            <h2 class="card-title">👨‍🎓 API Étudiants</h2>
            <?php
            try {
                $students = $pdo->query("SELECT COUNT(*) as count FROM students")->fetch()['count'];
                echo "<div class='test-item'>
                    <span class='test-label'>Total étudiants en base:</span>
                    <span class='test-result info'>$students</span>
                </div>";
                
                $activeStudents = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'")->fetch()['count'];
                echo "<div class='test-item'>
                    <span class='test-label'>Étudiants actifs:</span>
                    <span class='test-result success'>$activeStudents</span>
                </div>";
                
                // Test API students
                $apiResponse = file_get_contents('http://localhost/Projet%20TATOU/api_get_students.php');
                $apiData = json_decode($apiResponse, true);
                
                if ($apiData && $apiData['success']) {
                    echo "<div class='test-item'>
                        <span class='test-label'>API students:</span>
                        <span class='test-result success'>✅ Fonctionne</span>
                    </div>";
                    echo "<div class='test-item'>
                        <span class='test-label'>Données retournées:</span>
                        <span class='test-result info'>" . count($apiData['students']) . " enregistrements</span>
                    </div>";
                } else {
                    echo "<div class='test-item'>
                        <span class='test-label'>API students:</span>
                        <span class='test-result error'>❌ Erreur</span>
                    </div>";
                }
            } catch (Exception $e) {
                echo "<div class='test-item'>
                    <span class='test-label'>Test API:</span>
                    <span class='test-result error'>❌ " . $e->getMessage() . "</span>
                </div>";
            }
            ?>
        </div>

        <!-- Test API Programmes -->
        <div class="card">
            <h2 class="card-title">📚 API Programmes</h2>
            <?php
            try {
                $programs = $pdo->query("SELECT COUNT(*) as count FROM programs")->fetch()['count'];
                echo "<div class='test-item'>
                    <span class='test-label'>Total programmes en base:</span>
                    <span class='test-result info'>$programs</span>
                </div>";
                
                $activePrograms = $pdo->query("SELECT COUNT(*) as count FROM programs WHERE active = 1")->fetch()['count'];
                echo "<div class='test-item'>
                    <span class='test-label'>Programmes actifs:</span>
                    <span class='test-result success'>$activePrograms</span>
                </div>";
                
                // Test API programs
                $apiResponse = file_get_contents('http://localhost/Projet%20TATOU/api_get_programs.php');
                $apiData = json_decode($apiResponse, true);
                
                if ($apiData && $apiData['success']) {
                    echo "<div class='test-item'>
                        <span class='test-label'>API programs:</span>
                        <span class='test-result success'>✅ Fonctionne</span>
                    </div>";
                    echo "<div class='test-item'>
                        <span class='test-label'>Données retournées:</span>
                        <span class='test-result info'>" . count($apiData['programs']) . " enregistrements</span>
                    </div>";
                } else {
                    echo "<div class='test-item'>
                        <span class='test-label'>API programs:</span>
                        <span class='test-result error'>❌ Erreur</span>
                    </div>";
                }
            } catch (Exception $e) {
                echo "<div class='test-item'>
                    <span class='test-label'>Test API:</span>
                    <span class='test-result error'>❌ " . $e->getMessage() . "</span>
                </div>";
            }
            ?>
        </div>

        <!-- Test API Inscriptions -->
        <div class="card">
            <h2 class="card-title">📝 API Inscriptions</h2>
            <?php
            try {
                $registrations = $pdo->query("SELECT COUNT(*) as count FROM registrations")->fetch()['count'];
                echo "<div class='test-item'>
                    <span class='test-label'>Total inscriptions en base:</span>
                    <span class='test-result info'>$registrations</span>
                </div>";
                
                $paidRegistrations = $pdo->query("SELECT COUNT(*) as count FROM registrations WHERE payment_status = 'paid'")->fetch()['count'];
                echo "<div class='test-item'>
                    <span class='test-label'>Inscriptions payées:</span>
                    <span class='test-result success'>$paidRegistrations</span>
                </div>";
                
                // Test API registrations
                $apiResponse = file_get_contents('http://localhost/Projet%20TATOU/api_get_registrations.php');
                $apiData = json_decode($apiResponse, true);
                
                if ($apiData && $apiData['success']) {
                    echo "<div class='test-item'>
                        <span class='test-label'>API registrations:</span>
                        <span class='test-result success'>✅ Fonctionne</span>
                    </div>";
                    echo "<div class='test-item'>
                        <span class='test-label'>Données retournées:</span>
                        <span class='test-result info'>" . count($apiData['registrations']) . " enregistrements</span>
                    </div>";
                } else {
                    echo "<div class='test-item'>
                        <span class='test-label'>API registrations:</span>
                        <span class='test-result error'>❌ Erreur</span>
                    </div>";
                }
            } catch (Exception $e) {
                echo "<div class='test-item'>
                    <span class='test-label'>Test API:</span>
                    <span class='test-result error'>❌ " . $e->getMessage() . "</span>
                    </div>";
            }
            ?>
        </div>

        <!-- Test d'ajout -->
        <div class="card">
            <h2 class="card-title">🧪 Test d'Ajout</h2>
            <p style="margin-bottom: 15px; color: #64748b;">Testez l'ajout d'un étudiant, programme et inscription directement depuis les pages</p>
            
            <div class="test-item">
                <span class="test-label">Étudiant Test:</span>
                <a href="students.php" class="btn">Tester l'ajout d'étudiant</a>
            </div>
            
            <div class="test-item">
                <span class='test-label'>Programme Test:</span>
                <a href="programs.php" class="btn">Tester l'ajout de programme</a>
            </div>
            
            <div class="test-item">
                <span class='test-label'>Inscription Test:</span>
                <a href="registrations.php" class="btn">Tester l'ajout d'inscription</a>
            </div>
            
            <div class="test-item">
                <span class='test-label'>Version Corrigée:</span>
                <a href="registrations_fixed.php" class="btn">Version corrigée</a>
            </div>
        </div>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">🔧 Actions de Test</h2>
        <p>Utilisez ces boutons pour tester les formulaires</p>
        
        <button class="btn" onclick="window.open('students.php', '_blank')">
            👨‍🎓 Ouvrir la page Étudiants
        </button>
        
        <button class="btn" onclick="window.open('programs.php', '_blank')">
            📚 Ouvrir la page Programmes
        </button>
        
        <button class="btn" onclick="window.open('registrations.php', '_blank')">
            📝 Ouvrir la page Inscriptions
        </button>
        
        <button class="btn btn-danger" onclick="window.location.href='reset_and_seed.php'">
            🔄 Réinitialiser les données de test
        </button>
        
        <button class="btn" onclick="window.location.href='dashboard.php'">
            📊 Retour au tableau de bord
        </button>
    </div>
</div>
</body>
</html>
