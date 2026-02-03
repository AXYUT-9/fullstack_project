<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid movie ID");

// Fetch movie details
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$id]);
$movie = $stmt->fetch();

if (!$movie) die("Movie not found");

// Fetch all options
$allGenres = $pdo->query("SELECT * FROM genres ORDER BY name")->fetchAll();
$allCast = $pdo->query("SELECT * FROM cast ORDER BY name")->fetchAll();

// Fetch currently selected genres
$stmtGenres = $pdo->prepare("SELECT genre_id FROM movie_genres WHERE movie_id = ?");
$stmtGenres->execute([$id]);
$currentGenres = $stmtGenres->fetchAll(PDO::FETCH_COLUMN);

// Fetch currently selected cast
$stmtCast = $pdo->prepare("SELECT cast_id FROM movie_cast WHERE movie_id = ?");
$stmtCast->execute([$id]);
$currentCast = $stmtCast->fetchAll(PDO::FETCH_COLUMN);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf'] ?? '')) {
        $errors[] = "Invalid CSRF token. Please refresh the page.";
    } else {
        $title       = trim($_POST['title'] ?? '');
        $year        = (int)($_POST['year'] ?? 0);
        $rating      = (float)($_POST['rating'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $selectedGenres = $_POST['genres'] ?? [];
        $selectedCast = $_POST['cast'] ?? [];
        
        $poster      = $movie['poster'];

        if (empty($title)) $errors[] = "Title is required";
        if (empty($selectedGenres)) $errors[] = "At least one genre is required";

        // New poster upload
        if (!empty($_FILES['poster']['name']) && $_FILES['poster']['error'] === 0) {
            $allowed = ['image/jpeg', 'image/png'];
            $maxsize = 2 * 1024 * 1024;

            if (in_array($_FILES['poster']['type'], $allowed) && $_FILES['poster']['size'] <= $maxsize) {
                $ext = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
                $newname = 'poster_' . time() . '_' . uniqid() . '.' . $ext;
                $target = __DIR__ . '/../uploads/' . $newname;

                if (move_uploaded_file($_FILES['poster']['tmp_name'], $target)) {
                    if ($poster && file_exists(__DIR__ . '/../uploads/' . $poster)) {
                        @unlink(__DIR__ . '/../uploads/' . $poster);
                    }
                    $poster = $newname;
                } else {
                    $errors[] = "Could not save uploaded poster";
                }
            } else {
                $errors[] = "Invalid file (only JPG/PNG, max 2MB)";
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // 1. Update Movie
                $stmt = $pdo->prepare("
                    UPDATE movies 
                    SET title = ?, year = ?, rating = ?, description = ?, poster = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $year, $rating, $description, $poster, $id]);

                // 2. Update Genres (Delete then Insert)
                $pdo->prepare("DELETE FROM movie_genres WHERE movie_id = ?")->execute([$id]);
                $stmtGenre = $pdo->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                foreach ($selectedGenres as $gId) {
                    $stmtGenre->execute([$id, $gId]);
                }

                // 3. Update Cast (Delete then Insert)
                $pdo->prepare("DELETE FROM movie_cast WHERE movie_id = ?")->execute([$id]);
                if (!empty($selectedCast)) {
                    $stmtCast = $pdo->prepare("INSERT INTO movie_cast (movie_id, cast_id, role) VALUES (?, ?, ?)");
                    foreach ($selectedCast as $cId) {
                        $stmtCast->execute([$id, $cId, 'Cast Member']);
                    }
                }

                $pdo->commit();
                flash("Movie updated successfully");
                header("Location: index.php");
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }
}

generate_csrf_token();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="form-page">
    <div class="form-card" style="max-width: 800px;">
        <h2>Edit Movie</h2>

        <?php if ($errors): ?>
            <div class="error-box">
                <?php foreach ($errors as $err) echo "<div>".e($err)."</div>"; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label>Title</label>
                <input name="title" value="<?= e($movie['title']) ?>" required>
            </div>

            <div class="form-group two-col">
                <div>
                    <label>Year</label>
                    <input type="number" name="year" value="<?= e($movie['year']) ?>" min="1900" required>
                </div>
                <div>
                    <label>Rating (0–10)</label>
                    <input type="number" step="0.1" name="rating" value="<?= e($movie['rating']) ?>" min="0" max="10" required>
                </div>
            </div>

            <div class="form-group">
                <label>Genres*</label>
                <div class="checkbox-grid">
                    <?php foreach ($allGenres as $g): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>" 
                                <?= in_array($g['id'], $currentGenres) ? 'checked' : '' ?>>
                            <?= e($g['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Cast</label>
                <div class="checkbox-grid scrollable-grid">
                    <?php foreach ($allCast as $c): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="cast[]" value="<?= $c['id'] ?>"
                                <?= in_array($c['id'], $currentCast) ? 'checked' : '' ?>>
                            <?= e($c['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"><?= e($movie['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Current Poster</label><br>
                <?php if ($movie['poster']): ?>
                    <img src="../uploads/<?= e($movie['poster']) ?>" style="max-width:150px; border-radius:8px;" alt="Poster">
                <?php else: ?>
                    <p><i>No poster</i></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>New Poster (optional)</label>
                <input type="file" name="poster" accept="image/jpeg,image/png">
            </div>

            <button type="submit" class="btn-submit">Update Movie</button>
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
.error-box {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>