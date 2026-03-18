<?php
// Démarrer la session
session_start();

// Inclure la connexion DB
include 'db/mysql_connection_gestion_inscription.php';

// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Test de connexion à la base
    $test = $pdo->query("SELECT 1")->fetch();
    
    // Test API students
    echo json_encode([
        'success' => true,
        'message' => 'API de debug fonctionnelle',
        'db_connection' => $test ? 'OK' : 'ERROR',
        'session_data' => [
            'logged_in' => $_SESSION['logged_in'] ?? false,
            'user_id' => $_SESSION['user_id'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ],
        'students_count' => $pdo->query("SELECT COUNT(*) as count FROM students")->fetch()['count'],
        'programs_count' => $pdo->query("SELECT COUNT(*) as count FROM programs")->fetch()['count'],
        'registrations_count' => $pdo->query("SELECT COUNT(*) as count FROM registrations")->fetch()['count']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
