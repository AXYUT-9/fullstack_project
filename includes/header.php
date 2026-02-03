<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Database</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
    <a href="index.php" class="logo">MovieDB</a>
    <nav>
        <a href="index.php">Home</a>
        <a href="genres.php">Genres</a>
        <a href="cast.php">Cast</a>
        <?php if (is_logged_in()): ?>
            <a href="add.php">Add Movie</a>
            <span>Logged in as <?= e($_SESSION['username'] ?? 'admin') ?></span>
            <a href="logout.php" class="logout">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>

<?= flash() ?>