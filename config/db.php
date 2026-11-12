<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/Mora_Bodima');
    session_start();
}

$base_url = '/Mora_Bodima';

$host = 'localhost';
$dbname = 'boarding_rating_db';
$username = 'superuser';
$password = '1234';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>  