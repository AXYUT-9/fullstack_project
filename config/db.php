<?php
// config/db.php
$host = 'localhost';
$dbname = 'movie_db';
$user = 'root';
$pass = '';  // change if needed on school server

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}