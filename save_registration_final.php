<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Inclure la connexion DB
include 'db/mysql_connection_gestion_inscription.php';

// Récupérer les données POST
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
    $studentId = $data['student_id'] ?? '';
    $programId = $data['program_id'] ?? '';
    $registrationDate = $data['registration_date'] ?? '';
    $academicYear = $data['academic_year'] ?? '2024-2025';
    $amount = $data['amount'] ?? 0;
    $paymentStatus = $data['payment_status'] ?? 'pending';
    $notes = $data['notes'] ?? '';
    
    // Générer une référence unique
    $reference = 'REG' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    try {
        // Solution ultime : construire la requête SQL manuellement
        $studentId = (int)$studentId;
        $programId = (int)$programId;
        $amount = (float)$amount;
        $reference = addslashes($reference);
        $registrationDate = addslashes($registrationDate);
        $academicYear = addslashes($academicYear);
        $paymentStatus = addslashes($paymentStatus);
        $notes = addslashes($notes);
        
        // Requête SQL directe
        $sql = "INSERT INTO registrations (student_id, program_id, reference, registration_date, academic_year, amount, payment_status, notes, created_at) 
                VALUES ($studentId, $programId, '$reference', '$registrationDate', '$academicYear', $amount, '$paymentStatus', '$notes', NOW())";
        
        // Exécuter directement avec exec
        $result = $pdo->exec($sql);
        
        if ($result !== false) {
            $registrationId = $pdo->lastInsertId();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'registration_id' => $registrationId,
                'reference' => $reference,
                'message' => 'Inscription enregistrée avec succès'
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout']);
        }
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>
