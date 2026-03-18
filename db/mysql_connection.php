<?php
// Configuration de la base de données MySQL pour TAAJ Corp
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion inscription');
define('DB_USER', 'root');
define('DB_PASS', '');

// Variable globale pour la connexion PDO
global $pdo;

// Connexion à la base de données MySQL
function getDb() {
    global $pdo;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Initialiser la connexion globale
$pdo = getDb();
?>
