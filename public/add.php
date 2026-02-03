<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_login();

// Fetch Genres and Cast for form
$allGenres = $pdo->query("SELECT * FROM genres ORDER BY name")->fetchAll();
$allCast = $pdo->query("SELECT * FROM cast ORDER BY name")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $year = (int)($_POST['year'] ?? 0);
        $rating = (float)($_POST['rating'] ?? 0);
        $desc = trim($_POST['description'] ?? '');
        $selectedGenres = $_POST['genres'] ?? []; // Array of IDs
        $selectedCast = $_POST['cast'] ?? [];     // Array of IDs
        
        $poster = null;
        if($rating < 0){
            $errors[] = "Rating cannot be negative";
        }
        if (empty($title)) $errors[] = "Title is required.";
        if ($year < 1880 || $year > date('Y') + 5) $errors[] = "Invalid year.";
        if (empty($selectedGenres)) $errors[] = "At least one genre is required.";

        if (!empty($_FILES['poster']['name'])) {
            $f = $_FILES['poster'];
            $allowed = ['image/jpeg', 'image/png'];
            $max = 2 * 1024 * 1024;

            if (!in_array($f['type'], $allowed)) $errors[] = "Only JPG/PNG allowed.";
            elseif ($f['size'] > $max) $errors[] = "File too large.";
            else {
                $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                $poster = 'poster_' . time() . '.' . $ext;
                $target = __DIR__ . '/../uploads/' . $poster;
                if (!move_uploaded_file($f['tmp_name'], $target)) $errors[] = "Failed to save poster.";
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // 1. Insert Movie
                $stmt = $pdo->prepare("
                    INSERT INTO movies (title, year, rating, description, poster)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $year, $rating, $desc, $poster]);
                $movieId = $pdo->lastInsertId();

                // 2. Insert Genres
                $stmtGenre = $pdo->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                foreach ($selectedGenres as $gId) {
                    $stmtGenre->execute([$movieId, $gId]);
                }

                // 3. Insert Cast
                if (!empty($selectedCast)) {
                    $stmtCast = $pdo->prepare("INSERT INTO movie_cast (movie_id, cast_id, role) VALUES (?, ?, ?)");
                    foreach ($selectedCast as $cId) {
                        // Default to 'Cast Member' if no specific role input
                        $stmtCast->execute([$movieId, $cId, 'Cast Member']);
                    }
                }

                $pdo->commit();
                flash("Movie added successfully.");
                header("Location: index.php");
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

generate_csrf_token();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="form-page">
    <div class="form-card" style="max-width: 800px;">
        <h1>Add New Movie</h1>

        <?php if ($errors): ?>
            <div class="error-box">
                <ul>
                    <?php foreach ($errors as $err) echo "<li>" . e($err) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required value="<?= e($_POST['title'] ?? '') ?>">
            </div>

            <div class="form-group two-col">
                <div>
                    <label for="year">Year</label>
                    <input type="number" id="year" name="year" min="1880" max="<?= date('Y') + 5 ?>" required value="<?= e($_POST['year'] ?? '') ?>">
                </div>
                <div>
                    <label for="rating">Rating (0-10)</label>
                    <input type="number" step="0.1" id="rating" name="rating" min="0" max="10" required value="<?= e($_POST['rating'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Genres*</label>
                <div class="checkbox-grid">
                    <?php foreach ($allGenres as $g): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" <?= in_array($g['id'], $_POST['genres'] ?? []) ? 'checked' : '' ?>>
                            <?= e($g['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Cast (Select actors)</label>
                <div class="checkbox-grid scrollable-grid">
                    <?php foreach ($allCast as $c): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="cast[]" value="<?= $c['id'] ?>" <?= in_array($c['id'], $_POST['cast'] ?? []) ? 'checked' : '' ?>>
                            <?= e($c['name']) ?>
                        </label>
                    <?php endforeach; ?>
                    <?php if(empty($allCast)): ?>
                        <p style="color:#666; font-size:0.9em;">No cast members found. <a href="cast.php">Add some first</a>.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"><?= e($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="poster">Poster (JPG/PNG, max 2MB)</label>
                <input type="file" id="poster" name="poster" accept="image/jpeg,image/png">
            </div>

            <button type="submit" class="btn-submit">Add Movie</button>
        </form>
    </div>
</main>

<style>
.two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 10px;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
}
.scrollable-grid {
    max-height: 200px;
    overflow-y: auto;
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 0.95em;
}
.checkbox-label input {
    width: auto;
    margin: 0;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>