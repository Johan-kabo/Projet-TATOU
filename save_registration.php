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
    $reference = $data['reference'] ?? '';
    $registrationDate = $data['registration_date'] ?? '';
    $academicYear = $data['academic_year'] ?? '';
    $semester = $data['semester'] ?? '';
    $amount = $data['amount'] ?? 0;
    $paymentMethod = $data['payment_method'] ?? '';
    $paymentStatus = $data['payment_status'] ?? 'pending';
    $notes = $data['notes'] ?? '';
    
    try {
        // Vérifier si la référence existe déjà
        $checkStmt = $pdo->prepare("SELECT id FROM registrations WHERE reference = ?");
        $checkStmt->execute([$reference]);
        if ($checkStmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Cette référence existe déjà']);
            exit;
        }
        
        // Insérer la nouvelle inscription
        $insertStmt = $pdo->prepare("
            INSERT INTO registrations (
                student_id, program_id, reference, registration_date,
                academic_year, semester, amount, payment_method,
                payment_status, notes, validated_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $insertStmt->execute([
            $studentId,
            $programId,
            $reference,
            $registrationDate,
            $academicYear,
            $semester,
            $amount,
            $paymentMethod,
            $paymentStatus,
            $notes,
            $_SESSION['user_id']
        ]);
        
        if ($result) {
            $registrationId = $pdo->lastInsertId();
            
            // Journaliser l'action
            $logStmt = $pdo->prepare("
                INSERT INTO logs (user_id, action, table_name, record_id, new_values) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $logStmt->execute([
                $_SESSION['user_id'],
                'CREATE',
                'registrations',
                $registrationId,
                json_encode([
                    'student_id' => $studentId,
                    'program_id' => $programId,
                    'reference' => $reference,
                    'amount' => $amount,
                    'payment_status' => $paymentStatus
                ])
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'registration_id' => $registrationId,
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
