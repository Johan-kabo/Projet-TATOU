<?php
// Version ultra-simplifiée qui fonctionne
header('Content-Type: application/json');

try {
    // Inclure la connexion
    include 'db/mysql_connection_gestion_inscription.php';
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Récupérer les étudiants
        $stmt = $pdo->query("SELECT id, first_name, last_name, email, phone, level, status, registration_date FROM students ORDER BY id DESC");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater
        $formatted = [];
        foreach ($students as $s) {
            $formatted[] = [
                'id' => (int)$s['id'],
                'name' => $s['first_name'] . ' ' . $s['last_name'],
                'first_name' => $s['first_name'],
                'last_name' => $s['last_name'],
                'email' => $s['email'],
                'phone' => $s['phone'] ?? '',
                'level' => $s['level'] ?? '',
                'status' => $s['status'] ?? 'pending',
                'date' => $s['registration_date'] ? date('d/m/Y', strtotime($s['registration_date'])) : '',
                'student_id_card' => 'STU' . str_pad($s['id'], 4, '0', STR_PAD_LEFT)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'students' => $formatted,
            'total' => count($formatted)
        ]);
        
    } elseif ($method === 'POST') {
        // Ajouter un étudiant
        $data = json_decode(file_get_contents('php://input'), true);
        
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $programId = $data['program_id'] ?? null;
        $level = $data['level'] ?? '';
        $dateOfBirth = $data['date_of_birth'] ?? '';
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
        
        // Insérer le nouvel étudiant
        $insertStmt = $pdo->prepare("
            INSERT INTO students (
                first_name, last_name, email, phone, program_id, level, 
                date_of_birth, status, registration_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $insertStmt->execute([
            $firstName, $lastName, $email, $phone, $programId, $level,
            $dateOfBirth, $status
        ]);
        
        if ($result) {
            $newStudentId = $pdo->lastInsertId();
            $studentIdCard = 'STU' . str_pad($newStudentId, 4, '0', STR_PAD_LEFT);
            
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
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Méthode non autorisée'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'students' => [],
        'total' => 0
    ]);
}
?>
