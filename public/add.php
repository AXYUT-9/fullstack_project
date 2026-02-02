<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $year = (int)($_POST['year'] ?? 0);
        $genre = trim($_POST['genre'] ?? '');
        $rating = (float)($_POST['rating'] ?? 0);
        $desc = trim($_POST['description'] ?? '');
        $poster = null;

        if (empty($title)) $errors[] = "Title is required.";
        if ($year < 1880 || $year > date('Y') + 5) $errors[] = "Invalid year.";
        if (empty($genre)) $errors[] = "Genre is required.";

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
            $stmt = $pdo->prepare("
                INSERT INTO movies (title, year, genre, rating, description, poster)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $year, $genre, $rating, $desc, $poster]);

            flash("Movie added successfully.");
            header("Location: index.php");
            exit;
        }
    }
}

generate_csrf_token();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="form-page">
    <div class="form-card">
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
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="year">Year</label>
                <input type="number" id="year" name="year" min="1880" max="<?= date('Y') + 5 ?>" required>
            </div>

            <div class="form-group">
                <label for="genre">Genre</label>
                <input type="text" id="genre" name="genre" required placeholder="e.g. Action, Drama">
            </div>

            <div class="form-group">
                <label for="rating">Rating (0-10)</label>
                <input type="number" step="0.1" id="rating" name="rating" min="0" max="10" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"></textarea>
            </div>

            <div class="form-group">
                <label for="poster">Poster (JPG/PNG, max 2MB)</label>
                <input type="file" id="poster" name="poster" accept="image/jpeg,image/png">
            </div>

            <button type="submit" class="btn-submit">Add Movie</button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>