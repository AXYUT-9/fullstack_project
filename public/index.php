<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_login();

// Get all movies initially
$stmt = $pdo->query("SELECT * FROM movies ORDER BY year DESC, title ASC");
$movies = $stmt->fetchAll();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main>
    <div class="page-header">
        <h1>All Movies</h1>
    </div>

    <!-- Live Search -->
    <div class="search-container">
        <input 
            type="search" 
            id="live-search" 
            placeholder="Search by title or genre..." 
            autocomplete="off"
        >
    </div>

    <!-- Results area -->
    <div id="movie-results">
        <?php if (empty($movies)): ?>
            <p class="no-data">No movies found in the database yet.</p>
        <?php else: ?>
            <div class="movie-grid">
                <?php foreach ($movies as $movie): ?>
                <div class="movie-card">
                    <img 
                        src="<?= get_poster_url($movie['poster']) ?>" 
                        alt="<?= e($movie['title']) ?>" 
                        loading="lazy"
                    >
                    <div class="movie-info">
                        <h3 class="movie-title"><?= e($movie['title']) ?></h3>
                        <div class="movie-meta">
                            <span><?= e($movie['year']) ?></span>
                            <span class="rating">★ <?= number_format($movie['rating'], 1) ?></span>
                        </div>
                        <div class="movie-actions">
                            <a href="view.php?id=<?= $movie['id'] ?>" class="btn-view">View</a>
                            <a href="edit.php?id=<?= $movie['id'] ?>" class="btn-edit">Edit</a>
                            <a href="delete.php?id=<?= $movie['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this movie?')">Delete</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
// Final reliable live search
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('live-search');
    const resultsContainer = document.getElementById('movie-results');

    if (!searchInput || !resultsContainer) {
        console.error('Live search elements not found');
        return;
    }

    let timeoutId = null;

    searchInput.addEventListener('input', () => {
        clearTimeout(timeoutId);

        const query = searchInput.value.trim();

        // Show all when search is cleared
        if (query.length === 0) {
            location.reload();
            return;
        }

        timeoutId = setTimeout(() => {
            fetch('search.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'q=' + encodeURIComponent(query)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                resultsContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Live search failed:', error);
                resultsContainer.innerHTML = `
                    <p style="color:#ff6b6b; text-align:center; padding:3rem 0;">
                        Search failed: ${error.message}
                    </p>
                `;
            });
        }, 400); // 400ms debounce
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>