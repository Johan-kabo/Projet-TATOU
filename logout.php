<?php
// Démarrer la session
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Supprimer les cookies de connexion
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Rediriger vers la page de connexion
header('Location: login_db.php');
exit;
?>
