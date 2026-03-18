<?php
// ========================================
// CONNEXION À LA BASE DE DONNÉES gestion_inscription
// Serveur: localhost
// Base: gestion_inscription
// Utilisateur: root
// Mot de passe: vide
// ========================================

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_inscription');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Options de connexion PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// Chaîne de connexion DSN
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

try {
    // Création de la connexion PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Activer le mode d'erreur
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Définir le jeu de caractères
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
} catch (PDOException $e) {
    // En cas d'erreur de connexion
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonction pour exécuter des requêtes sécurisées
function executeQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        die("Erreur lors de l'exécution de la requête : " . $e->getMessage());
    }
}

// Fonction pour récupérer une seule ligne
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

// Fonction pour récupérer plusieurs lignes
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

// Fonction pour insérer des données
function insert($table, $data) {
    global $pdo;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        die("Erreur lors de l'insertion : " . $e->getMessage());
    }
}

// Fonction pour mettre à jour des données
function update($table, $data, $where, $whereParams = []) {
    global $pdo;
    
    $setClause = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $setClause[] = "$column = ?";
        $values[] = $value;
    }
    
    $setClause = implode(', ', $setClause);
    $values = array_merge($values, $whereParams);
    
    $sql = "UPDATE $table SET $setClause WHERE $where";
    
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    } catch (PDOException $e) {
        die("Erreur lors de la mise à jour : " . $e->getMessage());
    }
}

// Fonction pour supprimer des données
function delete($table, $where, $params = []) {
    global $pdo;
    
    $sql = "DELETE FROM $table WHERE $where";
    
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
}

// Debug mode (désactiver en production)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
?>
