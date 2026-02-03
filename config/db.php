<?php
$host = 'localhost';
$dbname = 'np03CS4A240218';
$user = 'np03CS4A240218';
$pass = 'DnyCJeTIT8';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}