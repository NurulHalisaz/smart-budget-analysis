<?php
// logout.php

require_once 'config/database.php';

// Start session to have access to $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user was logged in and had a remember token, we should clear it from the database
if (isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    try {
        $stmt = $conn->prepare("UPDATE users SET remember_token = NULL WHERE id = :id");
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
    } catch (PDOException $e) {
        // Ignore DB errors on logout, just proceed to clear session
    }
}

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear the "Remember Me" cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, "/"); // Expire an hour ago
}

// Redirect back to the login page
header("Location: login.php");
exit();
?>
