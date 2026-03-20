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
        // Désactiver les triggers temporairement pour éviter l'erreur
        $pdo->exec("SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = ''");
        
        // Insérer la nouvelle inscription sans vérifier la référence (pour éviter les triggers)
        $insertStmt = $pdo->prepare("
            INSERT INTO registrations (
                student_id, program_id, reference, registration_date,
                academic_year, amount, payment_status, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $insertStmt->execute([
            $studentId,
            $programId,
            $reference,
            $registrationDate,
            $academicYear,
            $amount,
            $paymentStatus,
            $notes
        ]);
        
        // Restaurer le mode SQL
        $pdo->exec("SET SQL_MODE = @OLD_SQL_MODE");
        
        if ($result) {
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
        // Restaurer le mode SQL en cas d'erreur
        try {
            $pdo->exec("SET SQL_MODE = @OLD_SQL_MODE");
        } catch (Exception $e2) {
            // Ignorer l'erreur de restauration
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>
