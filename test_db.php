<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=gestion inscription;charset=utf8mb4', 'root', '');
    echo 'Connexion MySQL réussie';
} catch (PDOException $e) {
    echo 'Erreur de connexion: ' . $e->getMessage();
}
?>