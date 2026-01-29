<?php
// db.php - configure your database connection here
$DB_HOST = "127.0.0.1";
$DB_PORT = "3306";
$DB_NAME = "it_asset_db";
$DB_USER = "root";
$DB_PASS = "szthj@1688";

$DSN = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";

try {
    $pdo = new PDO($DSN, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "<h1>Database connection failed</h1><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit;
}
