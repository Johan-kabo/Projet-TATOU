<?php
require_once __DIR__ . '/../db/mysql_connection.php';

// Start session for flash messages and simple state.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format a number as FCFA currency.
 */
function formatMoney(int $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

/**
 * Returns the active class for navigation links.
 */
function navActive(string $page, string $activePage): string
{
    return $page === $activePage ? 'active' : '';
}

/**
 * Store a flash message to display after redirect.
 */
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Retrieve and clear the current flash message.
 */
function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

// Validation
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Génération de codes uniques
function generateRegistrationCode() {
    return 'REG-' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
}

// Sécurité
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDb();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?>
