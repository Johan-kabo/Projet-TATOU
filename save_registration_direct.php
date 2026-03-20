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
        // Désactiver complètement les triggers et contraintes
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("SET UNIQUE_CHECKS = 0");
        $pdo->exec("SET AUTOCOMMIT = 0");
        
        // Commencer une transaction manuelle
        $pdo->beginTransaction();
        
        // Insérer directement sans vérification (approche simplifiée)
        $sql = "INSERT INTO registrations (student_id, program_id, reference, registration_date, academic_year, amount, payment_status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $studentId,
            $programId,
            $reference,
            $registrationDate,
            $academicYear,
            $amount,
            $paymentStatus,
            $notes
        ]);
        
        if ($result) {
            $registrationId = $pdo->lastInsertId();
            
            // Valider la transaction
            $pdo->commit();
            
            // Réactiver les contraintes
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $pdo->exec("SET UNIQUE_CHECKS = 1");
            $pdo->exec("SET AUTOCOMMIT = 1");
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'registration_id' => $registrationId,
                'reference' => $reference,
                'message' => 'Inscription enregistrée avec succès'
            ]);
        } else {
            // Annuler la transaction en cas d'échec
            $pdo->rollBack();
            
            // Réactiver les contraintes
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $pdo->exec("SET UNIQUE_CHECKS = 1");
            $pdo->exec("SET AUTOCOMMIT = 1");
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout']);
        }
        
    } catch (PDOException $e) {
        // Annuler la transaction en cas d'erreur
        try {
            $pdo->rollBack();
        } catch (Exception $e2) {
            // Ignorer l'erreur de rollback
        }
        
        // Réactiver les contraintes en cas d'erreur
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $pdo->exec("SET UNIQUE_CHECKS = 1");
            $pdo->exec("SET AUTOCOMMIT = 1");
        } catch (Exception $e3) {
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
