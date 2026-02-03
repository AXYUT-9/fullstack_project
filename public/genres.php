<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_login();

// Fetch all genres with movie counts
$stmt = $pdo->query("
    SELECT g.id, g.name, COUNT(mg.movie_id) as movie_count
    FROM genres g
    LEFT JOIN movie_genres mg ON g.id = mg.genre_id
    GROUP BY g.id, g.name
    ORDER BY g.name ASC
");
$genres = $stmt->fetchAll();

// Handle genre filter
$selectedGenre = isset($_GET['genre_id']) ? (int)$_GET['genre_id'] : null;
$movies = [];

if ($selectedGenre) {
    $stmt = $pdo->prepare("
        SELECT m.*, GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
        FROM movies m
        INNER JOIN movie_genres mg ON m.id = mg.movie_id
        INNER JOIN genres g ON mg.genre_id = g.id
        WHERE mg.genre_id = ?
        GROUP BY m.id
        ORDER BY m.title ASC
    ");
    $stmt->execute([$selectedGenre]);
    $movies = $stmt->fetchAll();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main>
    <h1>Genres</h1>
    
    <div class="genres-grid">
        <?php foreach ($genres as $genre): ?>
            <div class="genre-card">
                <a href="?genre_id=<?= $genre['id'] ?>" class="genre-link <?= $selectedGenre == $genre['id'] ? 'active' : '' ?>">
                    <h3><?= e($genre['name']) ?></h3>
                    <span class="movie-count"><?= $genre['movie_count'] ?> movies</span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($selectedGenre): ?>
        <h2 style="margin-top: 40px;">Movies in this Genre</h2>
        <a href="genres.php" class="btn-submit" style="margin-bottom: 20px; display: inline-block;">Show All Genres</a>
        
        <?php if (empty($movies)): ?>
            <p class="no-data">No movies found in this genre.</p>
        <?php else: ?>
            <div class="movie-grid">
                <?php foreach ($movies as $m): ?>
                <div class="movie-card">
                    <img src="<?= get_poster_url($m['poster']) ?>" alt="<?= e($m['title']) ?>">
                    <div class="movie-info">
                        <h3 class="movie-title"><?= e($m['title']) ?></h3>
                        <div class="movie-meta">
                            <span><?= e($m['year']) ?></span>
                            <span>★ <?= e($m['rating']) ?></span>
                        </div>
                        <p class="movie-genre"><?= e($m['genres']) ?></p>
                        <div class="movie-actions">
                            <a href="view.php?id=<?= $m['id'] ?>" class="btn-view">View</a>
                            <a href="edit.php?id=<?= $m['id'] ?>" class="btn-edit">Edit</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<style>
.genres-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.genre-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.genre-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.genre-link {
    display: block;
    padding: 30px 20px;
    text-align: center;
    text-decoration: none;
    color: #333;
}

.genre-link.active {
    background: #007bff;
    color: white;
    border-radius: 8px;
}

.genre-link h3 {
    margin: 0 0 10px 0;
    font-size: 1.4em;
}

.movie-count {
    display: inline-block;
    padding: 5px 12px;
    background: #f0f0f0;
    border-radius: 20px;
    font-size: 0.9em;
    color: #666;
}

.genre-link.active .movie-count {
    background: rgba(255,255,255,0.2);
    color: white;
}

.movie-genre {
    font-size: 0.9em;
    color: #666;
    margin: 10px 0;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>