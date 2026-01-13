<?php
// Load environment variables
$env_file = __DIR__ . '/../.env';
$env = parse_ini_file($env_file);

if (!$env) {
    die("Could not load .env file from: $env_file");
}

// Database configuration
$host = $env['DB_HOST'];
$dbname = $env['DB_NAME'];
$username = $env['DB_USER'];
$password = $env['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>