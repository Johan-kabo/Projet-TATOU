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
<title>Debug Synchronisation - TAAJ Corp</title>
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
.stat-item {
    display: flex; justify-content: space-between; padding: 8px 0;
    border-bottom: 1px solid #e2e8f0;
}
.stat-item:last-child {
    border-bottom: none;
}
.stat-label {
    font-weight: 500; color: #64748b;
}
.stat-value {
    font-weight: 600; color: #1a202c;
}
.error {
    color: #dc2626; font-weight: 600;
}
.success {
    color: #059669; font-weight: 600;
}
.warning {
    color: #d97706; font-weight: 600;
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
        <h1 class="title">🔍 Debug de Synchronisation</h1>
        <p>Vérification de l'état actuel de la base de données</p>
    </div>

    <div class="grid">
        <!-- Étudiants -->
        <div class="card">
            <h2 class="card-title">👨‍🎓 Étudiants</h2>
            <?php
            try {
                $totalStudents = $pdo->query("SELECT COUNT(*) as count FROM students")->fetch()['count'];
                $activeStudents = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'")->fetch()['count'];
                $pendingStudents = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'pending'")->fetch()['count'];
                $inactiveStudents = $pdo->query("SELECT COUNT(*) as count FROM students WHERE status = 'inactive'")->fetch()['count'];
                
                echo "<div class='stat-item'>
                    <span class='stat-label'>Total étudiants:</span>
                    <span class='stat-value success'>$totalStudents</span>
                </div>";
                echo "<div class='stat-item'>
                    <span class='stat-label'>Actifs:</span>
                    <span class='stat-value'>$activeStudents</span>
                </div>";
                echo "<div class='stat-item'>
                    <span class='stat-label'>En attente:</span>
                    <span class='stat-value warning'>$pendingStudents</span>
                </div>";
                echo "<div class='stat-item'>
                    <span class='stat-label'>Inactifs:</span>
                    <span class='stat-value error'>$inactiveStudents</span>
                </div>";
                
                // Afficher les 5 premiers étudiants
                $students = $pdo->query("SELECT id, first_name, last_name, email, program_id, level, status FROM students ORDER BY id DESC LIMIT 5")->fetchAll();
                if (!empty($students)) {
                    echo "<h3 style='margin-top: 15px; font-size: 14px;'>Derniers étudiants:</h3>";
                    foreach ($students as $student) {
                        echo "<div style='font-size: 12px; padding: 5px; background: #f8fafc; margin: 5px 0; border-radius: 4px;'>
                            <strong>#{$student['id']}</strong> {$student['first_name']} {$student['last_name']} - {$student['email']} - {$student['status']}
                        </div>";
                    }
                }
            } catch (PDOException $e) {
                echo "<div class='error'>Erreur: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>

        <!-- Programmes -->
        <div class="card">
            <h2 class="card-title">📚 Programmes</h2>
            <?php
            try {
                $totalPrograms = $pdo->query("SELECT COUNT(*) as count FROM programs")->fetch()['count'];
                $activePrograms = $pdo->query("SELECT COUNT(*) as count FROM programs WHERE active = 1")->fetch()['count'];
                
                echo "<div class='stat-item'>
                    <span class='stat-label'>Total programmes:</span>
                    <span class='stat-value success'>$totalPrograms</span>
                </div>";
                echo "<div class='stat-item'>
                    <span class='stat-label'>Actifs:</span>
                    <span class='stat-value'>$activePrograms</span>
                </div>";
                
                $programs = $pdo->query("SELECT id, name, code, level, active FROM programs ORDER BY id DESC LIMIT 5")->fetchAll();
                if (!empty($programs)) {
                    echo "<h3 style='margin-top: 15px; font-size: 14px;'>Derniers programmes:</h3>";
                    foreach ($programs as $program) {
                        $status = $program['active'] ? 'Actif' : 'Inactif';
                        $statusClass = $program['active'] ? 'success' : 'error';
                        echo "<div style='font-size: 12px; padding: 5px; background: #f8fafc; margin: 5px 0; border-radius: 4px;'>
                            <strong>#{$program['id']}</strong> {$program['name']} ({$program['code']}) - <span class='$statusClass'>$status</span>
                        </div>";
                    }
                }
            } catch (PDOException $e) {
                echo "<div class='error'>Erreur: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>

        <!-- Inscriptions -->
        <div class="card">
            <h2 class="card-title">📝 Inscriptions</h2>
            <?php
            try {
                $totalRegistrations = $pdo->query("SELECT COUNT(*) as count FROM registrations")->fetch()['count'];
                $paidRegistrations = $pdo->query("SELECT COUNT(*) as count FROM registrations WHERE payment_status = 'paid'")->fetch()['count'];
                $pendingRegistrations = $pdo->query("SELECT COUNT(*) as count FROM registrations WHERE payment_status = 'pending'")->fetch()['count'];
                
                echo "<div class='stat-item'>
                    <span class='stat-label'>Total inscriptions:</span>
                    <span class='stat-value success'>$totalRegistrations</span>
                </div>";
                echo "<div class='stat-item'>
                    <span class='stat-label'>Payées:</span>
                    <span class='stat-value'>$paidRegistrations</span>
                </div>";
                echo "<div class='stat-item'>
                    <span class='stat-label'>En attente:</span>
                    <span class='stat-value warning'>$pendingRegistrations</span>
                </div>";
                
                $registrations = $pdo->query("SELECT r.id, r.reference, s.first_name, s.last_name, p.name as program_name, r.payment_status, r.amount FROM registrations r JOIN students s ON r.student_id = s.id JOIN programs p ON r.program_id = p.id ORDER BY r.id DESC LIMIT 5")->fetchAll();
                if (!empty($registrations)) {
                    echo "<h3 style='margin-top: 15px; font-size: 14px;'>Dernières inscriptions:</h3>";
                    foreach ($registrations as $reg) {
                        $statusClass = $reg['payment_status'] == 'paid' ? 'success' : ($reg['payment_status'] == 'pending' ? 'warning' : 'error');
                        echo "<div style='font-size: 12px; padding: 5px; background: #f8fafc; margin: 5px 0; border-radius: 4px;'>
                            <strong>#{$reg['id']}</strong> {$reg['reference']} - {$reg['first_name']} {$reg['last_name']} - {$reg['program_name']} - <span class='$statusClass'>{$reg['payment_status']}</span> - {$reg['amount']} FCFA
                        </div>";
                    }
                }
            } catch (PDOException $e) {
                echo "<div class='error'>Erreur: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>

        <!-- Cours -->
        <div class="card">
            <h2 class="card-title">📖 Cours</h2>
            <?php
            try {
                $totalCourses = $pdo->query("SELECT COUNT(*) as count FROM courses")->fetch()['count'];
                $activeCourses = $pdo->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")->fetch()['count'];
                
                echo "<div class='stat-item'>
                    <span class='stat-label'>Total cours:</span>
                    <span class='stat-value success'>$totalCourses</span>
                </div>";
                echo "<div class='stat-item'>
                    <span class='stat-label'>Actifs:</span>
                    <span class='stat-value'>$activeCourses</span>
                </div>";
                
                $courses = $pdo->query("SELECT id, code, name, program_id, level FROM courses ORDER BY id DESC LIMIT 5")->fetchAll();
                if (!empty($courses)) {
                    echo "<h3 style='margin-top: 15px; font-size: 14px;'>Derniers cours:</h3>";
                    foreach ($courses as $course) {
                        echo "<div style='font-size: 12px; padding: 5px; background: #f8fafc; margin: 5px 0; border-radius: 4px;'>
                            <strong>#{$course['id']}</strong> {$course['code']} - {$course['name']} - Niv: {$course['level']}
                        </div>";
                    }
                }
            } catch (PDOException $e) {
                echo "<div class='error'>Erreur: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>

    <div class="header" style="margin-top: 30px;">
        <h2 class="title">🔧 Actions de synchronisation</h2>
        <p>Utilisez ces boutons pour corriger les problèmes de synchronisation</p>
        
        <button class="btn" onclick="window.location.href='reset_and_seed.php'">
            🔄 Réinitialiser et peupler les données
        </button>
        
        <button class="btn btn-danger" onclick="window.location.href='reset_data.php'">
            🗑️ Vider toutes les données
        </button>
        
        <button class="btn" onclick="window.location.href='dashboard.php'">
            📊 Retour au tableau de bord
        </button>
        
        <button class="btn" onclick="window.location.href='students.php'">
            👨‍🎓 Voir les étudiants
        </button>
        
        <button class="btn" onclick="window.location.href='programs.php'">
            📚 Voir les programmes
        </button>
        
        <button class="btn" onclick="window.location.href='registrations.php'">
            📝 Voir les inscriptions
        </button>
    </div>
</div>
</body>
</html>
