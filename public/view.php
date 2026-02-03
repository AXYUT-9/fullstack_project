<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);

// Fetch movie info
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch();

if (!$m) die('Movie not found');

// Fetch Genres
$stmt = $pdo->prepare("
    SELECT g.name 
    FROM genres g 
    JOIN movie_genres mg ON g.id = mg.genre_id 
    WHERE mg.movie_id = ?
    ORDER BY g.name
");
$stmt->execute([$id]);
$genres = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch Cast
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.photo, mc.role 
    FROM `cast` c 
    JOIN movie_cast mc ON c.id = mc.cast_id 
    WHERE mc.movie_id = ?
    ORDER BY mc.role, c.name
");
$stmt->execute([$id]);
$cast = $stmt->fetchAll();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="view-movie">
    <div class="movie-detail-card">
        <div class="movie-header">
            <div>
                <h1><?= e($m['title']) ?> <span class="year">(<?= e($m['year']) ?>)</span></h1>
                <div class="meta-tags">
                    <?php if ($genres): ?>
                        <span class="genre-tag"><?= implode('</span> <span class="genre-tag">', array_map('e', $genres)) ?></span>
                    <?php else: ?>
                        <span class="text-muted">No genres listed</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="rating-badge">
                <?= e($m['rating']) ?><span style="font-size:0.5em">/10</span>
            </div>
        </div>

        <div class="movie-content">
            <div class="poster-container">
                <img src="<?= get_poster_url($m['poster']) ?>" alt="<?= e($m['title']) ?>" class="main-poster">
            </div>
            
            <div class="info-container">
                <h3>Description</h3>
                <p class="description"><?= nl2br(e($m['description'])) ?></p>
                
                <h3>Cast & Crew</h3>
                <?php if ($cast): ?>
                    <div class="cast-list">
                        <?php foreach ($cast as $c): ?>
                            <div class="cast-item">
                                <?php if ($c['photo']): ?>
                                    <img src="../uploads/<?= e($c['photo']) ?>" alt="<?= e($c['name']) ?>">
                                <?php else: ?>
                                    <div class="cast-placeholder"><?= substr(e($c['name']), 0, 1) ?></div>
                                <?php endif; ?>
                                <div>
                                    <strong><a href="cast.php?cast_id=<?= $c['id'] ?>"><?= e($c['name']) ?></a></strong>
                                    <div class="role-name"><?= e($c['role']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No cast info available.</p>
                <?php endif; ?>
                
                <div class="actions" style="margin-top:30px;">
                    <a href="edit.php?id=<?= $m['id'] ?>" class="btn-edit">Edit Movie</a>
                    <a href="index.php" class="btn-back">Back to List</a>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Main Container */
.view-movie {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* Card Design */
.movie-detail-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.3s ease;
}

/* Header Section */
.movie-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eaeaea;
}

.movie-header h1 {
    margin: 0;
    font-size: 2.5rem;
    color: #1a202c;
    font-weight: 800;
    line-height: 1.2;
}

.year {
    color: #718096;
    font-weight: 400;
    font-size: 0.8em;
}

/* Meta Tags (Genres) */
.meta-tags {
    margin-top: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.genre-tag {
    background: #edf2f7;
    color: #4a5568;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: background 0.2s, color 0.2s;
}

.genre-tag:hover {
    background: #cbd5e0;
    color: #2d3748;
}

/* Rating Badge */
.rating-badge {
    background: #fbbf24;
    color: #78350f;
    font-weight: 800;
    font-size: 1.8rem;
    padding: 12px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(251, 191, 36, 0.3);
    display: flex;
    align-items: baseline;
    gap: 4px;
}

/* Content Layout */
.movie-content {
    display: flex;
    gap: 50px;
    padding: 40px;
}

/* Poster */
.poster-container {
    flex-shrink: 0;
}

.main-poster {
    width: 320px;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    transition: transform 0.3s ease;
}

.main-poster:hover {
    transform: scale(1.02);
}

/* Info Section */
.info-container {
    flex: 1;
}

.info-container h3 {
    font-size: 1.4rem;
    color: #2d3748;
    margin-bottom: 15px;
    border-left: 4px solid #4fd1c5;
    padding-left: 12px;
}

.description {
    line-height: 1.8;
    color: #4a5568;
    font-size: 1.05rem;
    margin-bottom: 40px;
}

/* Cast Grid */
.cast-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}

.cast-item {
    display: flex;
    align-items: center;
    gap: 15px;
    background: #f7fafc;
    padding: 12px;
    border-radius: 10px;
    transition: background 0.2s, transform 0.2s;
    border: 1px solid #edf2f7;
}

.cast-item:hover {
    background: #edf2f7;
    transform: translateY(-2px);
    border-color: #e2e8f0;
}

.cast-item img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.cast-placeholder {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    font-size: 1.2rem;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.cast-item div {
    display: flex;
    flex-direction: column;
}

.cast-item a {
    color: #2b6cb0;
    text-decoration: none;
    font-weight: 600;
}

.cast-item a:hover {
    text-decoration: underline;
}

.role-name {
    font-size: 0.85rem;
    color: #718096;
}

/* Actions */
.actions {
    margin-top: 50px;
    display: flex;
    gap: 15px;
    border-top: 1px solid #edf2f7;
    padding-top: 30px;
}

.btn-edit, .btn-back {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    text-align: center;
}

.btn-edit {
    background: #4299e1;
    color: white;
    box-shadow: 0 4px 6px rgba(66, 153, 225, 0.3);
}

.btn-edit:hover {
    background: #3182ce;
    transform: translateY(-1px);
}

.btn-back {
    background: #e2e8f0;
    color: #4a5568;
}

.btn-back:hover {
    background: #cbd5e0;
    color: #2d3748;
}

/* Responsive */
@media (max-width: 850px) {
    .movie-content {
        flex-direction: column;
        align-items: center;
    }
    
    .main-poster {
        width: 100%;
        max-width: 350px;
    }

    .movie-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }

    .meta-tags {
        justify-content: center;
    }

    .info-container {
        width: 100%;
    }

    .actions {
        justify-content: center;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>