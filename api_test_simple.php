<?php
// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

// Définir l'en-tête JSON
header('Content-Type: application/json');

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode([
            'success' => false, 
            'message' => 'Non autorisé - Session non trouvée',
            'debug_info' => [
                'session_data' => $_SESSION,
                'cookies' => $_COOKIE
            ]
        ]);
        exit;
    }

    // Récupérer la méthode
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Retourner des données de test pour l'instant
            echo json_encode([
                'success' => true,
                'students' => [
                    [
                        'id' => 1,
                        'name' => 'Test Student 1',
                        'first_name' => 'Test',
                        'last_name' => 'Student 1',
                        'email' => 'test1@example.com',
                        'phone' => '+237 123456789',
                        'program_name' => 'Informatique',
                        'level' => 'L1',
                        'status' => 'active',
                        'date' => '15/03/2024',
                        'student_id_card' => 'STU2024001'
                    ],
                    [
                        'id' => 2,
                        'name' => 'Test Student 2',
                        'first_name' => 'Test',
                        'last_name' => 'Student 2',
                        'email' => 'test2@example.com',
                        'phone' => '+237 987654321',
                        'program_name' => 'Gestion',
                        'level' => 'L2',
                        'status' => 'pending',
                        'date' => '16/03/2024',
                        'student_id_card' => 'STU2024002'
                    ]
                ],
                'total' => 2,
                'debug_info' => [
                    'message' => 'API de test - données statiques',
                    'session_valid' => true,
                    'method' => $method
                ]
            ]);
            break;

        case 'POST':
            // Simuler l'ajout d'un étudiant
            $data = json_decode(file_get_contents('php://input'), true);
            
            echo json_encode([
                'success' => true,
                'student_id' => rand(1000, 9999),
                'student_id_card' => 'STU' . date('Y') . rand(1000, 9999),
                'message' => 'Étudiant ajouté avec succès (mode test)',
                'debug_info' => [
                    'received_data' => $data,
                    'message' => 'API de test - simulation d\'ajout'
                ]
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Méthode non autorisée',
                'method' => $method
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur générale: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
