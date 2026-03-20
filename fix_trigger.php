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

try {
    // Supprimer l'ancien trigger
    $pdo->exec("DROP TRIGGER IF EXISTS update_course_students_after_registration");
    
    // Créer un trigger correct qui utilise une variable
    $triggerSql = "
    CREATE TRIGGER update_course_students_after_registration
    AFTER INSERT ON registrations
    FOR EACH ROW
    BEGIN
        DECLARE course_count INT DEFAULT 0;
        
        -- Compter les étudiants pour ce programme
        SELECT COUNT(*) INTO course_count 
        FROM registrations 
        WHERE program_id = NEW.program_id;
        
        -- Mettre à jour le cours avec le comptage
        UPDATE courses 
        SET current_students = course_count
        WHERE id = NEW.program_id;
    END";
    
    $pdo->exec($triggerSql);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Trigger corrigé avec succès'
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
