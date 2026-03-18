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
    $firstName = $data['first_name'] ?? '';
    $lastName = $data['last_name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $programId = $data['program_id'] ?? '';
    $level = $data['level'] ?? '';
    $dateOfBirth = $data['date_of_birth'] ?? '';
    $status = $data['status'] ?? 'pending';
    
    try {
        // Vérifier si l'email existe déjà
        $checkStmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
            exit;
        }
        
        // Générer une carte d'étudiant unique
        $studentIdCard = 'STU' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insérer le nouvel étudiant
        $insertStmt = $pdo->prepare("
            INSERT INTO students (
                first_name, last_name, email, phone, 
                date_of_birth, program_id, level, status, 
                student_id_card, registration_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $insertStmt->execute([
            $firstName,
            $lastName,
            $email,
            $phone,
            $dateOfBirth,
            $programId,
            $level,
            $status,
            $studentIdCard
        ]);
        
        if ($result) {
            $studentId = $pdo->lastInsertId();
            
            // Journaliser l'action
            $logStmt = $pdo->prepare("
                INSERT INTO logs (user_id, action, table_name, record_id, new_values) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $logStmt->execute([
                $_SESSION['user_id'],
                'CREATE',
                'students',
                $studentId,
                json_encode([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'program_id' => $programId,
                    'level' => $level,
                    'status' => $status,
                    'student_id_card' => $studentIdCard
                ])
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'student_id' => $studentId,
                'message' => 'Étudiant ajouté avec succès'
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
