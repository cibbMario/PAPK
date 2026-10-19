<?php
require_once __DIR__ . '/../config/koneksi.php';

$adminPass = password_hash('admin123', PASSWORD_BCRYPT);
$userPass  = password_hash('user123', PASSWORD_BCRYPT);

$stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, failed_attempts = 0, locked_until = NULL WHERE username = :uname");

$stmt->execute([':hash' => $adminPass, ':uname' => 'admin']);
$stmt->execute([':hash' => $userPass, ':uname' => 'johndoe']);

echo "Password hash updated successfully!\n";
