<?php
// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

// Inclure la connexion DB
include 'db/mysql_connection_gestion_inscription.php';

// Définir l'en-tête JSON AVANT toute sortie
header('Content-Type: application/json');

try {
    // Mode debug : bypass TOUTES les vérifications pour tester
    $debugMode = true; // Forcer le mode debug pour diagnostiquer
    
    // Logs de debug
    error_log("API Students - Debug mode: " . ($debugMode ? 'true' : 'false'));
    error_log("API Students - Session ID: " . session_id());
    error_log("API Students - Logged in: " . (isset($_SESSION['logged_in']) ? ($_SESSION['logged_in'] ? 'true' : 'false') : 'false'));
    
    // Bypass la vérification de session en mode debug
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
    
    error_log("API Students - Method: $method, Action: $action");

    switch ($method) {
        case 'GET':
            // Lire les étudiants
            try {
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
                        error_log("API Students - Executing query for all students");
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
                        
                        error_log("API Students - Found " . count($students) . " students");
                        
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
                                'status' => $student['status'] ?? 'pending',
                                'date' => $student['registration_date'] ? date('d/m/Y', strtotime($student['registration_date'])) : '',
                                'student_id_card' => $student['student_id_card'] ?? ''
                            ];
                        }, $students);
                        
                        error_log("API Students - Formatted " . count($formattedStudents) . " students");
                        
                        echo json_encode([
                            'success' => true,
                            'students' => $formattedStudents,
                            'total' => count($formattedStudents),
                            'debug_info' => [
                                'query_executed' => true,
                                'raw_count' => count($students),
                                'formatted_count' => count($formattedStudents),
                                'session_id' => session_id()
                            ]
                        ]);
                    }
                }
            } catch (PDOException $e) {
                error_log("API Students - PDO Error: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur base de données: ' . $e->getMessage(),
                    'debug_info' => [
                        'error_code' => $e->getCode(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]
                ]);
            }
            break;

        case 'POST':
            // Créer un étudiant
            try {
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
                
                // Générer une carte d'étudiant unique
                $studentIdCard = 'STU' . date('Y') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Insérer le nouvel étudiant
                $insertStmt = $pdo->prepare("
                    INSERT INTO students (
                        first_name, last_name, email, phone, program_id, level, 
                        date_of_birth, nationality, address, gender, status, 
                        student_id_card, registration_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $result = $insertStmt->execute([
                    $firstName, $lastName, $email, $phone, $programId, $level,
                    $dateOfBirth, $nationality, $address, $gender, $status,
                    $studentIdCard
                ]);
                
                if ($result) {
                    $newStudentId = $pdo->lastInsertId();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Étudiant ajouté avec succès',
                        'student_id' => $newStudentId,
                        'student_id_card' => $studentIdCard
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Erreur lors de l\'ajout de l\'étudiant'
                    ]);
                }
                
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur base de données: ' . $e->getMessage()
                ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Méthode non autorisée',
                'debug_info' => [
                    'method_received' => $method,
                    'available_methods' => ['GET', 'POST']
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("API Students - General Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur générale: ' . $e->getMessage(),
        'debug_info' => [
            'error_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
