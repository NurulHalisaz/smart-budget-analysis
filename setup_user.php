<?php
// setup_user.php
// Run this file once in your browser to create the default user.
require_once 'config/database.php';

$name = 'Admin Smart Budget';
$email = 'admin@example.com';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "User 'admin@example.com' sudah ada di database.";
    } else {
        // Insert user
        $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        $insert_stmt->bindParam(':name', $name);
        $insert_stmt->bindParam(':email', $email);
        $insert_stmt->bindParam(':password', $hashed_password);
        
        if ($insert_stmt->execute()) {
            echo "User berhasil dibuat!<br>";
            echo "Email: admin@example.com<br>";
            echo "Password: admin123<br>";
            echo "<a href='login.php'>Ke Halaman Login</a>";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    echo "<br>Pastikan tabel 'users' sudah dibuat dari file database.sql";
}
?>
