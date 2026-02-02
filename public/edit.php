<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid movie ID");

$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$id]);
$movie = $stmt->fetch();

if (!$movie) die("Movie not found");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf'] ?? '')) {
        $errors[] = "Invalid CSRF token. Please refresh the page.";
    } else {
        $title       = trim($_POST['title'] ?? '');
        $year        = (int)($_POST['year'] ?? 0);
        $genre       = trim($_POST['genre'] ?? '');
        $rating      = (float)($_POST['rating'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $poster      = $movie['poster'];

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

        if (empty($errors) && $title && $year >= 1900) {
            $stmt = $pdo->prepare("
                UPDATE movies 
                SET title = ?, year = ?, genre = ?, rating = ?, description = ?, poster = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $year, $genre, $rating, $description, $poster, $id]);

            flash("Movie updated successfully");
            header("Location: index.php");
            exit;
        }
    }
}

generate_csrf_token();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<h2>Edit Movie</h2>

<?php if ($errors): ?>
    <div style="color:#721c24; background:#f8d7da; padding:12px; border:1px solid #f5c6cb; border-radius:4px; margin-bottom:15px;">
        <?php foreach ($errors as $err) echo "<div>$err</div>"; ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_token']) ?>">

    <div style="margin-bottom:15px;">
        <label>Title<br>
            <input name="title" value="<?= e($movie['title']) ?>" required style="width:100%; max-width:500px; padding:8px;">
        </label>
    </div>

    <div style="margin-bottom:15px;">
        <label>Year<br>
            <input type="number" name="year" value="<?= e($movie['year']) ?>" min="1900" required>
        </label>
    </div>

    <div style="margin-bottom:15px;">
        <label>Genre<br>
            <input name="genre" value="<?= e($movie['genre']) ?>" required>
        </label>
    </div>

    <div style="margin-bottom:15px;">
        <label>Rating (0–10)<br>
            <input type="number" step="0.1" name="rating" value="<?= e($movie['rating']) ?>" min="0" max="10" required>
        </label>
    </div>

    <div style="margin-bottom:15px;">
        <label>Description<br>
            <textarea name="description" rows="4" style="width:100%; max-width:500px;"><?= e($movie['description']) ?></textarea>
        </label>
    </div>

    <div style="margin-bottom:15px;">
        <strong>Current poster:</strong><br>
        <?php if ($movie['poster']): ?>
            <img src="../uploads/<?= e($movie['poster']) ?>" style="max-width:180px; margin:10px 0;" alt="Poster">
        <?php else: ?>
            <p><i>No poster</i></p>
        <?php endif; ?>
    </div>

    <div style="margin-bottom:20px;">
        <label>New poster (optional – JPG/PNG, max 2MB)<br>
            <input type="file" name="poster" accept="image/jpeg,image/png">
        </label>
    </div>

    <button type="submit" style="padding:10px 24px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer;">
        Update Movie
    </button>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>