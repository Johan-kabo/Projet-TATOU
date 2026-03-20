<?php
// Version minimaliste pour tester - SEULEMENT JSON
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    session_start();
    
    include 'db/mysql_connection_gestion_inscription.php';
    
    // Test simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM students");
    $count = $stmt->fetch()['count'];
    
    // Retourner SEULEMENT du JSON - pas de texte avant
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'students' => [
            ['id' => 1, 'name' => 'Test 1', 'email' => 'test1@example.com'],
            ['id' => 2, 'name' => 'Test 2', 'email' => 'test2@example.com']
        ],
        'total' => 2,
        'debug_info' => [
            'db_count' => $count,
            'session_id' => session_id()
        ]
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'error_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
