<?php
// Script de test pour vérifier la connexion à la base de données gestion_inscription
include 'db/mysql_connection_gestion_inscription.php';

echo "<h1>Test de connexion à la base de données</h1>";
echo "<p><strong>Base de données:</strong> " . DB_NAME . "</p>";
echo "<p><strong>Hôte:</strong> " . DB_HOST . "</p>";
echo "<p><strong>Utilisateur:</strong> " . DB_USER . "</p>";

try {
    // Test de connexion
    echo "<h2>✅ Connexion réussie !</h2>";
    
    // Afficher les tables
    echo "<h3>Tables existantes:</h3>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Vérifier les tables principales
    $mainTables = ['users', 'students', 'programs', 'registrations'];
    echo "<h3>Vérification des tables principales:</h3>";
    
    foreach ($mainTables as $table) {
        if (in_array($table, $tables)) {
            $count = $pdo->query("SELECT COUNT(*) as count FROM $table")->fetch()['count'];
            echo "<p>✅ $table : $count enregistrements</p>";
        } else {
            echo "<p>❌ $table : Table non trouvée</p>";
        }
    }
    
    // Afficher quelques données si elles existent
    if (in_array('programs', $tables)) {
        $programs = $pdo->query("SELECT * FROM programs LIMIT 5")->fetchAll();
        if (!empty($programs)) {
            echo "<h3>Programmes trouvés:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Nom</th><th>Code</th><th>Niveau</th><th>Prix</th><th>Actif</th></tr>";
            foreach ($programs as $program) {
                echo "<tr>";
                echo "<td>{$program['id']}</td>";
                echo "<td>{$program['name']}</td>";
                echo "<td>{$program['code']}</td>";
                echo "<td>{$program['level']}</td>";
                echo "<td>{$program['price']} FCFA</td>";
                echo "<td>" . ($program['active'] ? 'Oui' : 'Non') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    if (in_array('students', $tables)) {
        $students = $pdo->query("SELECT * FROM students LIMIT 5")->fetchAll();
        if (!empty($students)) {
            echo "<h3>Étudiants trouvés:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Programme</th><th>Statut</th></tr>";
            foreach ($students as $student) {
                echo "<tr>";
                echo "<td>{$student['id']}</td>";
                echo "<td>{$student['first_name']} {$student['last_name']}</td>";
                echo "<td>{$student['email']}</td>";
                echo "<td>" . ($student['program_id'] ? $student['program_id'] : 'N/A') . "</td>";
                echo "<td>{$student['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    if (in_array('registrations', $tables)) {
        $registrations = $pdo->query("SELECT * FROM registrations LIMIT 5")->fetchAll();
        if (!empty($registrations)) {
            echo "<h3>Inscriptions trouvées:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Référence</th><th>Étudiant</th><th>Programme</th><th>Montant</th><th>Statut paiement</th></tr>";
            foreach ($registrations as $registration) {
                echo "<tr>";
                echo "<td>{$registration['reference']}</td>";
                echo "<td>{$registration['student_id']}</td>";
                echo "<td>{$registration['program_id']}</td>";
                echo "<td>{$registration['amount']} FCFA</td>";
                echo "<td>{$registration['payment_status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Test des requêtes des pages
    echo "<h3>Test des requêtes principales:</h3>";
    
    // Test stats.php
    $totalStudents = $pdo->query("SELECT COUNT(*) as total FROM students")->fetch()['total'];
    echo "<p>📊 Total étudiants (stats.php): $totalStudents</p>";
    
    // Test students.php
    $activeStudents = $pdo->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'")->fetch()['total'];
    echo "<p>👨‍🎓 Étudiants actifs (students.php): $activeStudents</p>";
    
    // Test programs.php
    $activePrograms = $pdo->query("SELECT COUNT(*) as total FROM programs WHERE active = 1")->fetch()['total'];
    echo "<p>📚 Programmes actifs (programs.php): $activePrograms</p>";
    
    // Test registrations.php
    $totalRegistrations = $pdo->query("SELECT COUNT(*) as total FROM registrations")->fetch()['total'];
    echo "<p>📝 Total inscriptions (registrations.php): $totalRegistrations</p>";
    
    echo "<h2>🎉 Tous les tests passés avec succès !</h2>";
    echo "<p>La plateforme est maintenant connectée à votre base de données gestion_inscription</p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Erreur de connexion</h2>";
    echo "<p>Erreur: " . $e->getMessage() . "</p>";
}
?>

<p><a href="dashboard.php">→ Accéder au tableau de bord</a></p>
<p><a href="students.php">→ Voir les étudiants</a></p>
<p><a href="programs.php">→ Voir les programmes</a></p>
<p><a href="registrations.php">→ Voir les inscriptions</a></p>
<p><a href="stats.php">→ Voir les statistiques</a></p>
