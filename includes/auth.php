<?php
// includes/auth.php

// Start the session to manage user login state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Authentication Middleware
 * This function checks if a user is logged in.
 * If not, it attempts to log them in via a 'remember_me' cookie.
 * If still not logged in, it redirects them to the login page.
 */
function require_login() {
    global $conn; // Access the database connection

    // Check if the 'user_id' session variable exists
    if (!isset($_SESSION['user_id'])) {
        
        // Check for "Remember Me" cookie if session doesn't exist
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            
            // Look up the token in the database
            if (isset($conn)) {
                $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE remember_token = :token");
                $stmt->bindParam(':token', $token);
                $stmt->execute();
                
                if ($user = $stmt->fetch()) {
                    // Token is valid, log the user in
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    return; // Successfully authenticated via cookie
                }
            }
        }

        // If no session and no valid cookie, redirect to login page
        header("Location: login.php");
        exit();
    }
}

/**
 * Check if the user is already logged in (useful for login/register pages)
 * Redirects to index.php if they are.
 */
function redirect_if_logged_in() {
    if (isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}
?>
