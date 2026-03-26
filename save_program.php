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
        // Vérifier si c'est une modification ou une création
        $isUpdate = isset($data['id']) && $data['id'] > 0;
        $isDelete = isset($data['delete']) && $data['delete'] === true;
        
        if ($isDelete) {
            // Suppression d'un programme
            $programId = $data['id'];
            
            // Vérifier s'il y a des étudiants associés à ce programme
            $studentsCheckStmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE program_id = ?");
            $studentsCheckStmt->execute([$programId]);
            $studentCount = $studentsCheckStmt->fetch()['count'];
            
            if ($studentCount > 0) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false, 
                    'message' => "Impossible de supprimer ce programme : {$studentCount} étudiant(s) y sont inscrit(s). Veuillez d'abord réinscrire ou supprimer les étudiants."
                ]);
                exit;
            }
            
            $deleteStmt = $pdo->prepare("DELETE FROM programs WHERE id = ?");
            $result = $deleteStmt->execute([$programId]);
            
            if ($result) {
                error_log("Programme supprimé: ID {$programId} par utilisateur " . $_SESSION['user_id'] ?? 'unknown');
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Programme supprimé avec succès']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
            }
            exit;
        }
        
        if ($isUpdate) {
            // Modification d'un programme
            $programId = $data['id'];
            
            $updateStmt = $pdo->prepare("
                UPDATE programs SET 
                    name = ?, description = ?, level = ?, duration = ?, 
                    capacity = ?, price = ?, active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $updateStmt->execute([
                $name,
                $description,
                $level,
                (int)$duration,
                (int)$capacity,
                (float)$price,
                (int)$active,
                $programId
            ]);
            
            if ($result) {
                error_log("Programme modifié: {$name} (ID: {$programId}) par utilisateur " . $_SESSION['user_id'] ?? 'unknown');
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Programme mis à jour avec succès']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
            }
            exit;
        }
        
        // Création d'un nouveau programme
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
                capacity, price, active, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $insertStmt->execute([
            $name,
            $code,
            $description,
            $level,
            (int)$duration,
            (int)$capacity,
            (float)$price,
            (int)$active
        ]);
        
        if ($result) {
            $programId = $pdo->lastInsertId();
            
            // Journaliser l'action dans les logs d'erreur PHP
            error_log("Nouveau programme créé: {$name} (ID: {$programId}) par utilisateur " . $_SESSION['user_id'] ?? 'unknown');
            
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
