<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

$where = [];
$params = [];

// 1. Text Search (title)
// Note: We search genre by name via a subquery if needed, or primarily just title.
// For simplicity and performance, 'q' often searches multiple fields. 
if (!empty($_GET['q'])) {
    $q = trim($_GET['q']);
    // Search title OR search if the movie has a genre with that name
    $where[] = "(
        m.title LIKE ? 
        OR EXISTS (
            SELECT 1 FROM movie_genres mg 
            JOIN genres g ON mg.genre_id = g.id 
            WHERE mg.movie_id = m.id AND g.name LIKE ?
        )
    )";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

// 2. Year Range
if (!empty($_GET['year_from'])) {
    $where[] = "m.year >= ?";
    $params[] = (int)$_GET['year_from'];
}

if (!empty($_GET['year_to'])) {
    $where[] = "m.year <= ?";
    $params[] = (int)$_GET['year_to'];
}

// 3. Specific Genre Filter (exact ID or name)
// Assuming the input might be text from a query parameter
if (!empty($_GET['genre'])) {
    // If it's a specific genre name search
    $where[] = "EXISTS (
        SELECT 1 FROM movie_genres mg 
        JOIN genres g ON mg.genre_id = g.id 
        WHERE mg.movie_id = m.id AND g.name LIKE ?
    )";
    $params[] = '%' . trim($_GET['genre']) . '%';
}

// 4. Rating
if (!empty($_GET['rating_min'])) {
    $where[] = "m.rating >= ?";
    $params[] = (float)$_GET['rating_min'];
}

// Base SQL
$sql = "SELECT m.*, 
        (SELECT GROUP_CONCAT(g.name SEPARATOR ', ') FROM movie_genres mg JOIN genres g ON mg.genre_id = g.id WHERE mg.movie_id = m.id) as genres
        FROM movies m";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY m.year DESC, m.title ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll();

if (empty($movies)) {
    // Don't kill the page (exit), just render the empty state later so footer still shows if included
    // But since this is likely loaded via AJAX or include, we keep behavior consistent.
    // If this file is included by index.php, we just let $movies be empty.
    // If specific AJAX request:
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo '<p class="no-data">No movies match your search.</p>';
        exit;
    }
}
?>

<!-- Only output HTML if not empty, or handle in index.php -->
<?php if (!empty($movies)): ?>
<div class="movie-grid">
    <?php foreach ($movies as $m): ?>
    <div class="movie-card">
        <a href="view.php?id=<?= $m['id'] ?>" style="text-decoration:none; color:inherit; display:block;">
            <img src="<?= get_poster_url($m['poster']) ?>" alt="<?= e($m['title']) ?>">
            <div class="movie-info">
                <h3 class="movie-title"><?= e($m['title']) ?></h3>
                <div class="movie-meta">
                    <span><?= e($m['year']) ?></span>
                    <span>★ <?= e($m['rating']) ?></span>
                </div>
                <?php if (!empty($m['genres'])): ?>
                    <div style="font-size:0.85em; color:#666; margin-top:5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= e($m['genres']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </a>
        <div class="movie-actions">
            <!-- Buttons kept outside the main link to avoid nested a tags -->
            <a href="view.php?id=<?= $m['id'] ?>" class="btn-view">View</a>
            <a href="edit.php?id=<?= $m['id'] ?>" class="btn-edit">Edit</a>
            <a href="delete.php?id=<?= $m['id'] ?>" class="btn-delete" onclick="return confirm('Delete?')">Delete</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php elseif (isset($_GET['q'])): ?>
    <p class="no-data">No movies found matching your criteria.</p>
<?php endif; ?>