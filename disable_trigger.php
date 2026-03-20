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
    // Désactiver le trigger problématique
    $pdo->exec("DROP TRIGGER IF EXISTS update_course_students_after_registration");
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Trigger désactivé avec succès'
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
