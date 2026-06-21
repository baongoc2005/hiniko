<?php
$host = 'localhost';
$dbname = 'internship_manager';
$username = 'root';
$password = '';

$dsnCandidates = [
    "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4",
    "mysql:host=$host;port=3307;dbname=$dbname;charset=utf8mb4",
    "mysql:host=127.0.0.1;port=3306;dbname=$dbname;charset=utf8mb4",
    "mysql:host=127.0.0.1;port=3307;dbname=$dbname;charset=utf8mb4"
];

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

$lastError = null;
foreach ($dsnCandidates as $dsn) {
    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        break;
    } catch (PDOException $e) {
        $lastError = $e;
    }
}

if (!isset($pdo)) {
    die("Database connection failed: " . $lastError->getMessage());
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
