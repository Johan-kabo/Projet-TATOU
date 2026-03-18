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
            'message' => 'Non autorisé - Session non trouvée'
        ]);
        exit;
    }

    // Récupérer la méthode et l'action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            // Lire les programmes
            if ($action === 'get') {
                $programId = $_GET['id'] ?? null;
                
                if ($programId) {
                    // Récupérer un programme spécifique
                    $stmt = $pdo->prepare("
                        SELECT 
                            p.id, p.name, p.code, p.description, p.level,
                            p.duration, p.capacity, p.price, p.active,
                            p.created_at, p.updated_at,
                            COUNT(s.id) as student_count
                        FROM programs p
                        LEFT JOIN students s ON p.id = s.program_id
                        WHERE p.id = ?
                        GROUP BY p.id
                    ");
                    $stmt->execute([$programId]);
                    $program = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($program) {
                        echo json_encode([
                            'success' => true,
                            'program' => $program
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Programme non trouvé'
                        ]);
                    }
                } else {
                    // Récupérer tous les programmes
                    $stmt = $pdo->query("
                        SELECT 
                            p.id, p.name, p.code, p.description, p.level,
                            p.duration, p.capacity, p.price, p.active,
                            p.created_at, p.updated_at,
                            COUNT(s.id) as student_count
                        FROM programs p
                        LEFT JOIN students s ON p.id = s.program_id
                        GROUP BY p.id
                        ORDER BY p.id DESC
                    ");
                    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $formattedPrograms = array_map(function($program) {
                        return [
                            'id' => (int)$program['id'],
                            'name' => $program['name'],
                            'code' => $program['code'],
                            'description' => $program['description'] ?? '',
                            'level' => $program['level'],
                            'duration' => (int)$program['duration'],
                            'capacity' => (int)$program['capacity'],
                            'price' => (int)$program['price'],
                            'students' => (int)$program['student_count'],
                            'active' => (bool)$program['active'],
                            'date' => $program['created_at'] ? date('d/m/Y', strtotime($program['created_at'])) : ''
                        ];
                    }, $programs);
                    
                    echo json_encode([
                        'success' => true,
                        'programs' => $formattedPrograms,
                        'total' => count($formattedPrograms)
                    ]);
                }
            }
            break;

        case 'POST':
            // Créer un programme
            $data = json_decode(file_get_contents('php://input'), true);
            
            $name = $data['name'] ?? '';
            $code = $data['code'] ?? '';
            $description = $data['description'] ?? '';
            $level = $data['level'] ?? '';
            $duration = $data['duration'] ?? 1;
            $capacity = $data['capacity'] ?? 50;
            $price = $data['price'] ?? 0;
            $active = $data['active'] ?? true;
            
            // Validation
            if (!$name || !$code || !$level) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Les champs nom, code et niveau sont obligatoires'
                ]);
                exit;
            }
            
            // Vérifier si le code existe déjà
            $checkStmt = $pdo->prepare("SELECT id FROM programs WHERE code = ?");
            $checkStmt->execute([$code]);
            if ($checkStmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ce code de programme est déjà utilisé'
                ]);
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
                $name, $code, $description, $level,
                $duration, $capacity, $price, $active
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
                        'active' => $active
                    ])
                ]);
                
                echo json_encode([
                    'success' => true,
                    'program_id' => $programId,
                    'message' => 'Programme ajouté avec succès'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de l\'ajout'
                ]);
            }
            break;

        case 'PUT':
            // Mettre à jour un programme
            $programId = $_GET['id'] ?? null;
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$programId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de programme requis'
                ]);
                exit;
            }
            
            $name = $data['name'] ?? '';
            $code = $data['code'] ?? '';
            $description = $data['description'] ?? '';
            $level = $data['level'] ?? '';
            $duration = $data['duration'] ?? 1;
            $capacity = $data['capacity'] ?? 50;
            $price = $data['price'] ?? 0;
            $active = $data['active'] ?? true;
            
            // Validation
            if (!$name || !$code || !$level) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Les champs nom, code et niveau sont obligatoires'
                ]);
                exit;
            }
            
            // Vérifier si le code existe déjà (excepté pour ce programme)
            $checkStmt = $pdo->prepare("SELECT id FROM programs WHERE code = ? AND id != ?");
            $checkStmt->execute([$code, $programId]);
            if ($checkStmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ce code de programme est déjà utilisé par un autre programme'
                ]);
                exit;
            }
            
            // Récupérer les anciennes valeurs pour le log
            $oldStmt = $pdo->prepare("SELECT * FROM programs WHERE id = ?");
            $oldStmt->execute([$programId]);
            $oldValues = $oldStmt->fetch(PDO::FETCH_ASSOC);
            
            // Mettre à jour le programme
            $updateStmt = $pdo->prepare("
                UPDATE programs SET 
                    name = ?, code = ?, description = ?, level = ?,
                    duration = ?, capacity = ?, price = ?, active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $updateStmt->execute([
                $name, $code, $description, $level,
                $duration, $capacity, $price, $active, $programId
            ]);
            
            if ($result) {
                // Journaliser l'action
                $logStmt = $pdo->prepare("
                    INSERT INTO logs (user_id, action, table_name, record_id, old_values, new_values) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $logStmt->execute([
                    $_SESSION['user_id'],
                    'UPDATE',
                    'programs',
                    $programId,
                    json_encode($oldValues),
                    json_encode([
                        'name' => $name,
                        'code' => $code,
                        'level' => $level,
                        'active' => $active
                    ])
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Programme mis à jour avec succès'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour'
                ]);
            }
            break;

        case 'DELETE':
            // Supprimer un programme
            $programId = $_GET['id'] ?? null;
            
            if (!$programId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de programme requis'
                ]);
                exit;
            }
            
            // Récupérer les anciennes valeurs pour le log
            $oldStmt = $pdo->prepare("SELECT * FROM programs WHERE id = ?");
            $oldStmt->execute([$programId]);
            $oldValues = $oldStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldValues) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Programme non trouvé'
                ]);
                exit;
            }
            
            // Vérifier s'il y a des étudiants associés
            $studentCheckStmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE program_id = ?");
            $studentCheckStmt->execute([$programId]);
            $studentCount = $studentCheckStmt->fetch()['count'];
            
            if ($studentCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce programme car il a des étudiants associés'
                ]);
                exit;
            }
            
            // Vérifier s'il y a des inscriptions associées
            $regCheckStmt = $pdo->prepare("SELECT COUNT(*) as count FROM registrations WHERE program_id = ?");
            $regCheckStmt->execute([$programId]);
            $regCount = $regCheckStmt->fetch()['count'];
            
            if ($regCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce programme car il a des inscriptions associées'
                ]);
                exit;
            }
            
            // Supprimer le programme
            $deleteStmt = $pdo->prepare("DELETE FROM programs WHERE id = ?");
            $result = $deleteStmt->execute([$programId]);
            
            if ($result) {
                // Journaliser l'action
                $logStmt = $pdo->prepare("
                    INSERT INTO logs (user_id, action, table_name, record_id, old_values) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $logStmt->execute([
                    $_SESSION['user_id'],
                    'DELETE',
                    'programs',
                    $programId,
                    json_encode($oldValues)
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Programme supprimé avec succès'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression'
                ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Méthode non autorisée'
            ]);
            break;
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'error_info' => $e->errorInfo
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur générale: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
