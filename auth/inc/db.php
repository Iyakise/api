<?php

// $host = 'localhost';
// $db_name = 'xpertbrige';
// $username = 'root';
// $password = '';

// try {
//     $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// } catch (PDOException $e) {
//     die("Database connection failed: " . $e->getMessage());
// }


// Database connection settings
define('DB_DSN', 'mysql:host=localhost;dbname=api;charset=utf8mb4'); // Replace 'xpertbrige' with your database name
define('DB_USERNAME', 'root'); // Replace 'root' with your database username
define('DB_PASSWORD', ''); // Replace with your database password with the one on the live server

?>
