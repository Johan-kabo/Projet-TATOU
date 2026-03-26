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
    $registrationId = $data['registration_id'] ?? '';
    $newStatus = $data['status'] ?? '';
    
    // Valider les données
    if (!in_array($newStatus, ['paid', 'pending', 'unpaid'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Statut invalide']);
        exit;
    }
    
    try {
        // Mettre à jour le statut de l'inscription
        $sql = "UPDATE registrations SET payment_status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$newStatus, $registrationId]);
        
        if ($result) {
            // Récupérer les détails de l'inscription mise à jour
            $sql = "SELECT r.*, s.first_name, s.last_name, p.name as program_name 
                    FROM registrations r 
                    LEFT JOIN students s ON r.student_id = s.id 
                    LEFT JOIN programs p ON r.program_id = p.id 
                    WHERE r.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$registrationId]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Journaliser l'action
            $action = $newStatus === 'paid' ? 'validée' : 'rejetée';
            error_log("Inscription {$registration['reference']} {$action} par utilisateur " . $_SESSION['user_id'] ?? 'unknown');
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'message' => 'Statut mis à jour avec succès',
                'registration' => $registration
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
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
