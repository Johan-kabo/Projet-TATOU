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
    $name = $data['name'] ?? '';
    $code = $data['code'] ?? '';
    $description = $data['description'] ?? '';
    $level = $data['level'] ?? '';
    $duration = $data['duration'] ?? 3;
    $capacity = $data['capacity'] ?? 50;
    $price = $data['price'] ?? 0;
    $requirements = $data['requirements'] ?? '';
    $objectives = $data['objectives'] ?? '';
    $active = $data['active'] ?? true;
    
    try {
        // Vérifier si le code existe déjà
        $checkStmt = $pdo->prepare("SELECT id FROM programs WHERE code = ?");
        $checkStmt->execute([$code]);
        if ($checkStmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ce code de programme existe déjà']);
            exit;
        }
        
        // Insérer le nouveau programme
        $insertStmt = $pdo->prepare("
            INSERT INTO programs (
                name, code, description, level, duration, 
                capacity, price, requirements, objectives, 
                active, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $insertStmt->execute([
            $name,
            $code,
            $description,
            $level,
            $duration,
            $capacity,
            $price,
            $requirements,
            $objectives,
            $active,
            $_SESSION['user_id']
        ]);
        
        if ($result) {
            $programId = $pdo->lastInsertId();
            
            // Journaliser l'action
            $logStmt = $pdo->prepare("
                INSERT INTO logs (user_id, action, table_name, record_id, new_values) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $logStmt->execute([
                $_SESSION['user_id'],
                'CREATE',
                'programs',
                $programId,
                json_encode([
                    'name' => $name,
                    'code' => $code,
                    'level' => $level,
                    'duration' => $duration,
                    'capacity' => $capacity,
                    'price' => $price,
                    'active' => $active
                ])
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'program_id' => $programId,
                'message' => 'Programme ajouté avec succès'
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
