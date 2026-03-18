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
            // Lire les inscriptions
            if ($action === 'get') {
                $registrationId = $_GET['id'] ?? null;
                
                if ($registrationId) {
                    // Récupérer une inscription spécifique
                    $stmt = $pdo->prepare("
                        SELECT 
                            r.id, r.reference, r.registration_date, r.academic_year,
                            r.semester, r.amount, r.payment_method, r.payment_status,
                            r.status, r.validation_date, r.notes, r.validated_by,
                            s.first_name, s.last_name, s.email as student_email,
                            p.name as program_name, p.code as program_code
                        FROM registrations r
                        JOIN students s ON r.student_id = s.id
                        JOIN programs p ON r.program_id = p.id
                        WHERE r.id = ?
                    ");
                    $stmt->execute([$registrationId]);
                    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($registration) {
                        echo json_encode([
                            'success' => true,
                            'registration' => $registration
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Inscription non trouvée'
                        ]);
                    }
                } else {
                    // Récupérer toutes les inscriptions
                    $stmt = $pdo->query("
                        SELECT 
                            r.id, r.reference, r.registration_date, r.academic_year,
                            r.semester, r.amount, r.payment_method, r.payment_status,
                            r.status, r.validation_date, r.notes, r.validated_by,
                            s.first_name, s.last_name, s.email as student_email,
                            p.name as program_name, p.code as program_code
                        FROM registrations r
                        JOIN students s ON r.student_id = s.id
                        JOIN programs p ON r.program_id = p.id
                        ORDER BY r.id DESC
                    ");
                    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $formattedRegistrations = array_map(function($reg) {
                        return [
                            'id' => (int)$reg['id'],
                            'name' => $reg['first_name'] . ' ' . $reg['last_name'],
                            'first_name' => $reg['first_name'],
                            'last_name' => $reg['last_name'],
                            'student_email' => $reg['student_email'],
                            'ref' => $reg['reference'],
                            'program_name' => $reg['program_name'],
                            'amount' => (int)$reg['amount'],
                            'payment_status' => $reg['payment_status'],
                            'status' => $reg['status'],
                            'date' => $reg['registration_date'] ? date('d/m/Y', strtotime($reg['registration_date'])) : '',
                            'validation_date' => $reg['validation_date'] ? date('d/m/Y H:i', strtotime($reg['validation_date'])) : null
                        ];
                    }, $registrations);
                    
                    echo json_encode([
                        'success' => true,
                        'registrations' => $formattedRegistrations,
                        'total' => count($formattedRegistrations)
                    ]);
                }
            }
            break;

        case 'POST':
            // Créer une inscription
            $data = json_decode(file_get_contents('php://input'), true);
            
            $studentId = $data['student_id'] ?? null;
            $programId = $data['program_id'] ?? null;
            $reference = $data['reference'] ?? 'REG' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $registrationDate = $data['registration_date'] ?? date('Y-m-d');
            $academicYear = $data['academic_year'] ?? '2024-2025';
            $semester = $data['semester'] ?? 'Année complète';
            $amount = $data['amount'] ?? 0;
            $paymentMethod = $data['payment_method'] ?? 'bank_transfer';
            $paymentStatus = $data['payment_status'] ?? 'pending';
            $status = $data['status'] ?? 'pending';
            $notes = $data['notes'] ?? '';
            
            // Validation
            if (!$studentId || !$programId || !$amount) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Les champs étudiant, programme et montant sont obligatoires'
                ]);
                exit;
            }
            
            // Vérifier si l'étudiant et le programme existent
            $studentCheck = $pdo->prepare("SELECT id FROM students WHERE id = ?");
            $studentCheck->execute([$studentId]);
            if (!$studentCheck->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Étudiant non trouvé'
                ]);
                exit;
            }
            
            $programCheck = $pdo->prepare("SELECT id FROM programs WHERE id = ?");
            $programCheck->execute([$programId]);
            if (!$programCheck->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Programme non trouvé'
                ]);
                exit;
            }
            
            // Vérifier si l'étudiant est déjà inscrit à ce programme pour cette année
            $existingRegCheck = $pdo->prepare("
                SELECT id FROM registrations 
                WHERE student_id = ? AND program_id = ? AND academic_year = ?
            ");
            $existingRegCheck->execute([$studentId, $programId, $academicYear]);
            if ($existingRegCheck->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cet étudiant est déjà inscrit à ce programme pour cette année académique'
                ]);
                exit;
            }
            
            // Insérer la nouvelle inscription
            $insertStmt = $pdo->prepare("
                INSERT INTO registrations (
                    student_id, program_id, reference, registration_date,
                    academic_year, semester, amount, payment_method,
                    payment_status, status, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $insertStmt->execute([
                $studentId, $programId, $reference, $registrationDate,
                $academicYear, $semester, $amount, $paymentMethod,
                $paymentStatus, $status, $notes
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
                
                echo json_encode([
                    'success' => true,
                    'registration_id' => $registrationId,
                    'reference' => $reference,
                    'message' => 'Inscription créée avec succès'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la création'
                ]);
            }
            break;

        case 'PUT':
            // Mettre à jour une inscription
            $registrationId = $_GET['id'] ?? null;
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$registrationId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID d\'inscription requis'
                ]);
                exit;
            }
            
            $amount = $data['amount'] ?? 0;
            $paymentMethod = $data['payment_method'] ?? 'bank_transfer';
            $paymentStatus = $data['payment_status'] ?? 'pending';
            $status = $data['status'] ?? 'pending';
            $notes = $data['notes'] ?? '';
            $validationDate = null;
            $validatedBy = null;
            
            // Si le statut passe à validated, ajouter la date de validation
            if ($status === 'validated') {
                $validationDate = date('Y-m-d H:i:s');
                $validatedBy = $_SESSION['user_id'];
            }
            
            // Validation
            if (!$amount) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Le montant est obligatoire'
                ]);
                exit;
            }
            
            // Récupérer les anciennes valeurs pour le log
            $oldStmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ?");
            $oldStmt->execute([$registrationId]);
            $oldValues = $oldStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldValues) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Inscription non trouvée'
                ]);
                exit;
            }
            
            // Mettre à jour l'inscription
            $updateStmt = $pdo->prepare("
                UPDATE registrations SET 
                    amount = ?, payment_method = ?, payment_status = ?,
                    status = ?, notes = ?, validation_date = ?, 
                    validated_by = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $updateStmt->execute([
                $amount, $paymentMethod, $paymentStatus,
                $status, $notes, $validationDate, $validatedBy, $registrationId
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
                    'registrations',
                    $registrationId,
                    json_encode($oldValues),
                    json_encode([
                        'amount' => $amount,
                        'payment_status' => $paymentStatus,
                        'status' => $status,
                        'validation_date' => $validationDate
                    ])
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Inscription mise à jour avec succès'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour'
                ]);
            }
            break;

        case 'DELETE':
            // Supprimer une inscription
            $registrationId = $_GET['id'] ?? null;
            
            if (!$registrationId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID d\'inscription requis'
                ]);
                exit;
            }
            
            // Récupérer les anciennes valeurs pour le log
            $oldStmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ?");
            $oldStmt->execute([$registrationId]);
            $oldValues = $oldStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldValues) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Inscription non trouvée'
                ]);
                exit;
            }
            
            // Supprimer l'inscription
            $deleteStmt = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
            $result = $deleteStmt->execute([$registrationId]);
            
            if ($result) {
                // Journaliser l'action
                $logStmt = $pdo->prepare("
                    INSERT INTO logs (user_id, action, table_name, record_id, old_values) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $logStmt->execute([
                    $_SESSION['user_id'],
                    'DELETE',
                    'registrations',
                    $registrationId,
                    json_encode($oldValues)
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Inscription supprimée avec succès'
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
