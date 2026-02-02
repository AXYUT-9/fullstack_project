<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

echo "<h2>One-time Setup (run only once)</h2><pre>";

// Create tables
$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS movies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        year INT NOT NULL,
        genre VARCHAR(150) NOT NULL,
        rating DECIMAL(3,1) DEFAULT 5.0,
        description TEXT,
        poster VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

echo "Tables created or already exist.\n";

// Add admin if missing
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, password) VALUES ('admin', ?)")->execute([$hash]);
    echo "Admin user created (admin / admin123)\n";
} else {
    echo "Admin already exists.\n";
}

echo "</pre><p>Setup done. Go to <a href='login.php'>login</a>. Delete this file after use.</p>";
?>