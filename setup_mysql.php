<?php
require_once __DIR__ . '/db/mysql_connection.php';
require_once __DIR__ . '/db/mysql_init.php';

// Mettre à jour tous les fichiers pour utiliser MySQL
function updateFilesForMySQL() {
    $files = [
        'includes/functions.php',
        'includes/header.php'
    ];
    
    foreach ($files as $file) {
        $filePath = __DIR__ . '/' . $file;
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $content = str_replace("require_once __DIR__ . '/../db/init.php';", "require_once __DIR__ . '/../db/mysql_init.php';", $content);
            $content = str_replace("require_once __DIR__ . '/../db/connection.php';", "require_once __DIR__ . '/../db/mysql_connection.php';", $content);
            file_put_contents($filePath, $content);
        }
    }
}

// Exécuter la mise à jour
updateFilesForMySQL();

echo "Base de données MySQL initialisée avec succès!\n";
echo "Tables créées: students, programs, registrations, users\n";
echo "Données insérées: programmes, utilisateurs, étudiants, inscriptions\n";
echo "\nIdentifiants de connexion:\n";
echo "Hôte: localhost\n";
echo "Base: gestion_inscription\n";
echo "Utilisateur: root\n";
echo "Mot de passe: (vide)\n";
echo "\nAdmin par défaut:\n";
echo "Email: admin@taajcorp.com\n";
echo "Mot de passe: password\n";
?>
