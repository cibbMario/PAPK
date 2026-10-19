<?php
// config/koneksi.php
// Database connection using PDO
// Update the variables with your own DB credentials
$host = 'localhost';
$db   = 'papk_db';
$user = 'db_user';
$pass = 'db_password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // In production you might want to log this instead of displaying
    exit('Database connection failed: ' . $e->getMessage());
}
?>
