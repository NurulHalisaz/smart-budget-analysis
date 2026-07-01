<?php
// config/database.php

$host = 'localhost';
$db_name = 'smart_budget_analysis';
$username = 'root'; // default for local environments
$password = ''; // default for local environments

try {
    // We use PDO for secure database connections and prepared statements
    $conn = new PDO("mysql:host={$host};dbname={$db_name}", $username, $password);
    
    // Set PDO error mode to exception to catch database errors gracefully
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array for easier data handling
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $exception) {
    // If connection fails, display error and stop execution
    echo "Koneksi database gagal: " . $exception->getMessage();
    exit();
}
?>
