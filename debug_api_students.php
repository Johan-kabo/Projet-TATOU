<?php
// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

echo "=== DEBUG API STUDENTS ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Logged in: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'true' : 'false') : 'false') . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'N/A') . "\n";
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Action: " . ($_GET['action'] ?? 'none') . "\n";
echo "Debug mode: " . (isset($_GET['debug']) ? 'true' : 'false') . "\n";
echo "\n";

// Test de connexion à la base de données
try {
    include 'db/mysql_connection_gestion_inscription.php';
    echo "✅ Connexion DB réussie\n";
    
    // Test de requête simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $count = $stmt->fetch()['count'];
    echo "✅ Nombre d'étudiants: $count\n";
    
    // Test de requête complète
    $stmt = $pdo->query("
        SELECT 
            s.id, s.first_name, s.last_name, s.email, s.phone,
            s.date_of_birth, s.nationality, s.address, s.gender,
            s.level, s.status, s.registration_date, s.student_id_card,
            p.name as program_name, p.code as program_code
        FROM students s
        LEFT JOIN programs p ON s.program_id = p.id
        ORDER BY s.id DESC
        LIMIT 5
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Requête étudiants réussie, " . count($students) . " résultats\n";
    
    if (!empty($students)) {
        echo "Premier étudiant:\n";
        print_r($students[0]);
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>
