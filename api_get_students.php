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

    // Récupérer tous les étudiants avec leurs programmes
    $stmt = $pdo->query("
        SELECT 
            s.id,
            s.first_name,
            s.last_name,
            s.email,
            s.phone,
            s.date_of_birth,
            s.nationality,
            s.address,
            s.gender,
            s.level,
            s.status,
            s.registration_date,
            s.student_id_card,
            p.name as program_name,
            p.code as program_code
        FROM students s
        LEFT JOIN programs p ON s.program_id = p.id
        ORDER BY s.id DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Erreur dans la requête SQL");
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour le frontend
    $formattedStudents = array_map(function($student) {
        return [
            'id' => (int)$student['id'],
            'name' => $student['first_name'] . ' ' . $student['last_name'],
            'email' => $student['email'],
            'phone' => $student['phone'] ?? '',
            'prog' => $student['program_name'] ?? 'Non assigné',
            'level' => $student['level'] ?? '',
            'status' => $student['status'],
            'date' => $student['registration_date'] ? date('d/m/Y', strtotime($student['registration_date'])) : '',
            'student_id_card' => $student['student_id_card'] ?? ''
        ];
    }, $students);
    
    echo json_encode([
        'success' => true,
        'students' => $formattedStudents,
        'total' => count($formattedStudents),
        'debug_info' => [
            'raw_count' => count($students),
            'formatted_count' => count($formattedStudents),
            'query_success' => true
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'error_info' => $e->errorInfo
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur générale: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
