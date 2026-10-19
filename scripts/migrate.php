<?php
// scripts/migrate.php
// Simple migration script to create the PAPF database schema using PDO.
// Run with: php scripts/migrate.php

require __DIR__ . '/../config/koneksi.php';

$schemaFile = __DIR__ . '/../database/schema.sql';
if (!file_exists($schemaFile)) {
    exit('Schema file not found: ' . $schemaFile . PHP_EOL);
}

$sql = file_get_contents($schemaFile);
if ($sql === false) {
    exit('Failed to read schema file.' . PHP_EOL);
}

try {
    // Execute the whole script in a single transaction for safety.
    $pdo->beginTransaction();
    $pdo->exec($sql);
    $pdo->commit();
    echo "Database schema imported successfully.\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    exit('Error importing schema: ' . $e->getMessage() . PHP_EOL);
}
?>
