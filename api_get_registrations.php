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

    // Récupérer toutes les inscriptions avec les informations des étudiants et programmes
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.reference,
            r.registration_date,
            r.academic_year,
            r.semester,
            r.amount,
            r.payment_method,
            r.payment_status,
            r.status,
            r.validation_date,
            r.notes,
            s.first_name,
            s.last_name,
            s.email as student_email,
            p.name as program_name,
            p.code as program_code
        FROM registrations r
        JOIN students s ON r.student_id = s.id
        JOIN programs p ON r.program_id = p.id
        ORDER BY r.id DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Erreur dans la requête SQL inscriptions");
    }
    
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données pour le frontend
    $formattedRegistrations = array_map(function($reg) {
        return [
            'id' => (int)$reg['id'],
            'name' => $reg['first_name'] . ' ' . $reg['last_name'],
            'email' => $reg['student_email'],
            'ref' => $reg['reference'],
            'program' => $reg['program_name'],
            'amount' => number_format($reg['amount'], 0, '.', ' ') . ' FCFA',
            'status' => $reg['payment_status'],
            'date' => $reg['registration_date'] ? date('d/m/Y', strtotime($reg['registration_date'])) : '',
            'validation_date' => $reg['validation_date'] ? date('d/m/Y H:i', strtotime($reg['validation_date'])) : 'Non validée'
        ];
    }, $registrations);
    
    echo json_encode([
        'success' => true,
        'registrations' => $formattedRegistrations,
        'total' => count($formattedRegistrations),
        'debug_info' => [
            'raw_count' => count($registrations),
            'formatted_count' => count($formattedRegistrations),
            'query_success' => true
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données inscriptions: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'error_info' => $e->errorInfo
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur générale inscriptions: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
