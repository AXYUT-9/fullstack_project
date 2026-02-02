<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

// Get search term
$q = '%' . trim($_POST['q'] ?? '') . '%';

$stmt = $pdo->prepare("
    SELECT * FROM movies 
    WHERE title LIKE ? OR genre LIKE ?
    ORDER BY year DESC, title ASC
");
$stmt->execute([$q, $q]);
$movies = $stmt->fetchAll();

if (empty($movies)) {
    echo '<p class="no-results">No movies found matching your search.</p>';
    exit;
}
?>

<div class="movie-grid">
    <?php foreach ($movies as $m): ?>
    <div class="movie-card">
        <img src="<?= get_poster_url($m['poster']) ?>" alt="<?= e($m['title']) ?>">
        <div class="movie-info">
            <h3><?= e($m['title']) ?></h3>
            <div class="movie-meta">
                <span><?= e($m['year']) ?></span>
                <span>★ <?= e($m['rating']) ?></span>
            </div>
            <div class="movie-actions">
                <a href="view.php?id=<?= $m['id'] ?>" class="btn-view">View</a>
                <a href="edit.php?id=<?= $m['id'] ?>" class="btn-edit">Edit</a>
                <a href="delete.php?id=<?= $m['id'] ?>" class="btn-delete" onclick="return confirm('Delete?')">Delete</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>