<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_login();

$stmt = $pdo->query("SELECT * FROM movies ORDER BY year DESC, title ASC");
$movies = $stmt->fetchAll();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main>
    <h1>Movies</h1>

    <!-- Live Search -->
    <div class="search-container">
        <input type="text" id="live-search" placeholder="Live search by title or genre...">
    </div>

    <!-- Advanced Filter Form -->
    <form id="filter-form" class="filter-form">
        <div class="filter-row">
            <div class="form-group">
                <label>Year from</label>
                <input type="number" name="year_from" min="1900">
            </div>
            <div class="form-group">
                <label>Year to</label>
                <input type="number" name="year_to" min="1900">
            </div>
            <div class="form-group">
                <label>Genre contains</label>
                <input type="text" name="genre">
            </div>
            <div class="form-group">
                <label>Rating ≥</label>
                <input type="number" step="0.1" name="rating_min" min="0" max="10">
            </div>
            <button type="submit" class="btn-submit">Filter</button>
            <button type="button" id="clear-filter" class="btn-submit" style="background:#6c757d;">Clear</button>
        </div>
    </form>

    <div id="movie-results">
        <?php if (empty($movies)): ?>
            <p class="no-data">No movies found.</p>
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
                        <div class="movie-actions">
                            <a href="view.php?id=<?= $m['id'] ?>" class="btn-view">View</a>
                            <a href="edit.php?id=<?= $m['id'] ?>" class="btn-edit">Edit</a>
                            <a href="delete.php?id=<?= $m['id'] ?>" class="btn-delete" onclick="return confirm('Delete?')">Delete</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const liveInput = document.getElementById('live-search');
    const filterForm = document.getElementById('filter-form');
    const results = document.getElementById('movie-results');
    const clearBtn = document.getElementById('clear-filter');

    let timeout;

    // Live search
    liveInput.addEventListener('input', () => {
        clearTimeout(timeout);
        const q = liveInput.value.trim();
        if (q.length === 0) return location.reload();

        timeout = setTimeout(() => fetchResults({q}), 400);
    });

    // Advanced filter
    filterForm.addEventListener('submit', e => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(filterForm));
        fetchResults(data);
    });

    // Clear
    clearBtn.addEventListener('click', () => {
        filterForm.reset();
        liveInput.value = '';
        location.reload();
    });

    function fetchResults(params) {
        const query = new URLSearchParams(params).toString();
        fetch(`search.php?${query}`)
            .then(res => res.text())
            .then(html => results.innerHTML = html)
            .catch(err => results.innerHTML = `<p style="color:red;">Error: ${err}</p>`);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>