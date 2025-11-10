<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
try {
    $path = __DIR__ . '/database/database.sqlite';
    echo "Testing SQLite at: $path\n";
    $pdo = new PDO('sqlite:' . $path);
    echo "CONNECT_OK\n";
    $stmt = $pdo->query("SELECT sqlite_version();");
    $v = $stmt->fetchColumn();
    echo "SQLite version: $v\n";
} catch (PDOException $e) {
    echo "CONNECT_ERR: " . $e->getMessage() . "\n";
}
