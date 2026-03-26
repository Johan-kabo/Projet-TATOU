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
    // Mode debug : si le paramètre debug=1 est présent, on bypass la vérification de session
    $debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';
    
    // Vérifier si l'utilisateur est connecté (sauf en mode debug)
    if (!$debugMode && (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Non autorisé - Session non trouvée',
            'debug_info' => [
                'session_data' => $_SESSION,
                'cookies' => $_COOKIE,
                'debug_mode' => $debugMode
            ]
        ]);
        exit;
    }

    // Récupérer la méthode et l'action
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            // Lire les étudiants
            if ($action === 'get') {
                $studentId = $_GET['id'] ?? null;
                
                if ($studentId) {
                    // Récupérer un étudiant spécifique
                    $stmt = $pdo->prepare("
                        SELECT 
                            s.id, s.first_name, s.last_name, s.email, s.phone,
                            s.date_of_birth, s.nationality, s.address, s.gender,
                            s.level, s.status, s.registration_date, s.student_id_card,
                            p.name as program_name, p.code as program_code
                        FROM students s
                        LEFT JOIN programs p ON s.program_id = p.id
                        WHERE s.id = ?
                    ");
                    $stmt->execute([$studentId]);
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($student) {
                        echo json_encode([
                            'success' => true,
                            'student' => $student
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Étudiant non trouvé'
                        ]);
                    }
                } else {
                    // Récupérer tous les étudiants
                    $stmt = $pdo->query("
                        SELECT 
                            s.id, s.first_name, s.last_name, s.email, s.phone,
                            s.date_of_birth, s.nationality, s.address, s.gender,
                            s.level, s.status, s.registration_date, s.student_id_card,
                            p.name as program_name, p.code as program_code
                        FROM students s
                        LEFT JOIN programs p ON s.program_id = p.id
                        ORDER BY s.id DESC
                    ");
                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $formattedStudents = array_map(function($student) {
                        return [
                            'id' => (int)$student['id'],
                            'name' => $student['first_name'] . ' ' . $student['last_name'],
                            'first_name' => $student['first_name'],
                            'last_name' => $student['last_name'],
                            'email' => $student['email'],
                            'phone' => $student['phone'] ?? '',
                            'program_id' => $student['program_id'] ?? null,
                            'program_name' => $student['program_name'] ?? 'Non assigné',
                            'level' => $student['level'] ?? '',
                            'status' => $student['status'],
                            'date' => $student['registration_date'] ? date('d/m/Y', strtotime($student['registration_date'])) : '',
                            'student_id_card' => $student['student_id_card'] ?? ''
                        ];
                    }, $students);
                    
                    echo json_encode([
                        'success' => true,
                        'students' => $formattedStudents,
                        'total' => count($formattedStudents)
                    ]);
                }
            }
            break;

        case 'POST':
            // Créer un étudiant
            $data = json_decode(file_get_contents('php://input'), true);
            
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            $email = $data['email'] ?? '';
            $phone = $data['phone'] ?? '';
            $programId = $data['program_id'] ?? null;
            $level = $data['level'] ?? '';
            $dateOfBirth = $data['date_of_birth'] ?? '';
            $nationality = $data['nationality'] ?? 'Camerounaise';
            $address = $data['address'] ?? '';
            $gender = $data['gender'] ?? '';
            $status = $data['status'] ?? 'pending';
            
            // Validation
            if (!$firstName || !$lastName || !$email) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Les champs nom, prénom et email sont obligatoires'
                ]);
                exit;
            }
            
            // Vérifier si l'email existe déjà
            $checkStmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cet email est déjà utilisé'
                ]);
                exit;
            }
            
            // Valider que le programme existe si program_id est fourni
            if (!empty($programId)) {
                $programCheckStmt = $pdo->prepare("SELECT id FROM programs WHERE id = ?");
                $programCheckStmt->execute([$programId]);
                if (!$programCheckStmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Le programme sélectionné n\'existe pas']);
                    exit;
                }
            }
            
            // Générer une carte d'étudiant unique
            $studentIdCard = 'STU' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Insérer le nouvel étudiant
            $insertStmt = $pdo->prepare("
                INSERT INTO students (
                    first_name, last_name, email, phone, 
                    date_of_birth, program_id, level, status,
                    nationality, address, gender, student_id_card, registration_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $insertStmt->execute([
                $firstName, $lastName, $email, $phone,
                $dateOfBirth, $programId, $level, $status,
                $nationality, $address, $gender, $studentIdCard
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
                        'status' => $status
                    ])
                ]);
                
                echo json_encode([
                    'success' => true,
                    'student_id' => $studentId,
                    'student_id_card' => $studentIdCard,
                    'message' => 'Étudiant ajouté avec succès'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de l\'ajout'
                ]);
            }
            break;

        case 'PUT':
            // Mettre à jour un étudiant
            $studentId = $_GET['id'] ?? null;
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$studentId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID d\'étudiant requis'
                ]);
                exit;
            }
            
            $firstName = $data['first_name'] ?? '';
            $lastName = $data['last_name'] ?? '';
            $email = $data['email'] ?? '';
            $phone = $data['phone'] ?? '';
            $programId = $data['program_id'] ?? null;
            $level = $data['level'] ?? '';
            $dateOfBirth = $data['date_of_birth'] ?? '';
            $nationality = $data['nationality'] ?? '';
            $address = $data['address'] ?? '';
            $gender = $data['gender'] ?? '';
            $status = $data['status'] ?? '';
            
            // Validation
            if (!$firstName || !$lastName || !$email) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Les champs nom, prénom et email sont obligatoires'
                ]);
                exit;
            }
            
            // Vérifier si l'email existe déjà (excepté pour cet étudiant)
            $checkStmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
            $checkStmt->execute([$email, $studentId]);
            if ($checkStmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cet email est déjà utilisé par un autre étudiant'
                ]);
                exit;
            }
            
            // Récupérer les anciennes valeurs pour le log
            $oldStmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
            $oldStmt->execute([$studentId]);
            $oldValues = $oldStmt->fetch(PDO::FETCH_ASSOC);
            
            // Valider que le programme existe si program_id est fourni
            if (!empty($programId)) {
                $programCheckStmt = $pdo->prepare("SELECT id FROM programs WHERE id = ?");
                $programCheckStmt->execute([$programId]);
                if (!$programCheckStmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Le programme sélectionné n\'existe pas']);
                    exit;
                }
            }
            
            // Mettre à jour l'étudiant
            $updateStmt = $pdo->prepare("
                UPDATE students SET 
                    first_name = ?, last_name = ?, email = ?, phone = ?,
                    date_of_birth = ?, program_id = ?, level = ?, status = ?,
                    nationality = ?, address = ?, gender = ?
                WHERE id = ?
            ");
            
            $result = $updateStmt->execute([
                $firstName, $lastName, $email, $phone,
                $dateOfBirth, $programId, $level, $status,
                $nationality, $address, $gender, $studentId
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
                    'students',
                    $studentId,
                    json_encode($oldValues),
                    json_encode([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'program_id' => $programId,
                        'level' => $level,
                        'status' => $status
                    ])
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Étudiant mis à jour avec succès'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour'
                ]);
            }
            break;

        case 'DELETE':
            // Supprimer un étudiant
            $studentId = $_GET['id'] ?? null;
            
            if (!$studentId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID d\'étudiant requis'
                ]);
                exit;
            }
            
            // Récupérer les anciennes valeurs pour le log
            $oldStmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
            $oldStmt->execute([$studentId]);
            $oldValues = $oldStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldValues) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Étudiant non trouvé'
                ]);
                exit;
            }
            
            // Vérifier s'il y a des inscriptions associées
            $regCheckStmt = $pdo->prepare("SELECT COUNT(*) as count FROM registrations WHERE student_id = ?");
            $regCheckStmt->execute([$studentId]);
            $regCount = $regCheckStmt->fetch()['count'];
            
            if ($regCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Impossible de supprimer cet étudiant car il a des inscriptions associées'
                ]);
                exit;
            }
            
            // Supprimer l'étudiant
            $deleteStmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $result = $deleteStmt->execute([$studentId]);
            
            if ($result) {
                // Journaliser l'action
                $logStmt = $pdo->prepare("
                    INSERT INTO logs (user_id, action, table_name, record_id, old_values) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $logStmt->execute([
                    $_SESSION['user_id'],
                    'DELETE',
                    'students',
                    $studentId,
                    json_encode($oldValues)
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Étudiant supprimé avec succès'
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
