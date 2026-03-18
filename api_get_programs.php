<?php
// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

// Inclure la connexion DB
include 'db/mysql_connection_gestion_inscription.php';

// Définir l'en-tête JSON
header('Content-Type: application/json');

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode([
            'success' => false, 
            'message' => 'Non autorisé - Session non trouvée',
            'session_data' => $_SESSION
        ]);
        exit;
    }

    // Récupérer tous les programmes avec le nombre d'étudiants
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.name,
            p.code,
            p.description,
            p.level,
            p.duration,
            p.capacity,
            p.price,
            p.active,
            p.created_at,
            COUNT(s.id) as student_count
        FROM programs p
        LEFT JOIN students s ON p.id = s.program_id
        GROUP BY p.id
        ORDER BY p.id DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Erreur dans la requête SQL programmes");
    }
    
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour le frontend
    $formattedPrograms = array_map(function($program) {
        return [
            'id' => (int)$program['id'],
            'name' => $program['name'],
            'code' => $program['code'],
            'description' => $program['description'] ?? '',
            'level' => $program['level'],
            'duration' => $program['duration'] . ' ans',
            'capacity' => (int)$program['capacity'],
            'students' => (int)$program['student_count'],
            'price' => number_format($program['price'], 0, '.', ' ') . ' FCFA',
            'active' => (bool)$program['active'],
            'date' => $program['created_at'] ? date('d/m/Y', strtotime($program['created_at'])) : ''
        ];
    }, $programs);
    
    echo json_encode([
        'success' => true,
        'programs' => $formattedPrograms,
        'total' => count($formattedPrograms),
        'debug_info' => [
            'raw_count' => count($programs),
            'formatted_count' => count($formattedPrograms),
            'query_success' => true
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données programmes: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'error_info' => $e->errorInfo
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur générale programmes: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
