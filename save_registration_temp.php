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
        // Créer une table temporaire pour contourner les triggers (sans colonne TEXT)
        $pdo->exec("CREATE TEMPORARY TABLE IF NOT EXISTS temp_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT,
            program_id INT,
            reference VARCHAR(50),
            registration_date DATE,
            academic_year VARCHAR(20),
            amount DECIMAL(10,0),
            payment_status ENUM('paid', 'pending', 'unpaid'),
            notes VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=MEMORY");
        
        // Insérer dans la table temporaire
        $sql = "INSERT INTO temp_registrations (student_id, program_id, reference, registration_date, academic_year, amount, payment_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
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
            
            // Copier les données vers la table principale avec une requête SQL brute
            $copySql = "INSERT INTO registrations (student_id, program_id, reference, registration_date, academic_year, amount, payment_status, notes, created_at) 
                        SELECT student_id, program_id, reference, registration_date, academic_year, amount, payment_status, notes, created_at 
                        FROM temp_registrations WHERE id = ?";
            
            $copyStmt = $pdo->prepare($copySql);
            $copyResult = $copyStmt->execute([$registrationId]);
            
            if ($copyResult) {
                $finalId = $pdo->lastInsertId();
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'registration_id' => $finalId,
                    'reference' => $reference,
                    'message' => 'Inscription enregistrée avec succès'
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la copie vers la table principale']);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout dans la table temporaire']);
        }
        
        // Nettoyer la table temporaire
        $pdo->exec("DROP TEMPORARY TABLE IF EXISTS temp_registrations");
        
    } catch (PDOException $e) {
        // Nettoyer en cas d'erreur
        try {
            $pdo->exec("DROP TEMPORARY TABLE IF EXISTS temp_registrations");
        } catch (Exception $e2) {
            // Ignorer l'erreur de nettoyage
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>
