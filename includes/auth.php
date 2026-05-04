<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isModerator() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'moderator';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /polititrack/login.php");
        exit;
    }
}

function requireModerator() {
    if (!isModerator()) {
        header("Location: /polititrack/index.php");
        exit;
    }
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function generateCsrfToken() {
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die("CSRF token validation failed.");
    }
}
?>
