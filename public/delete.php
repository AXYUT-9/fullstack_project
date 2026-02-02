<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid movie ID");

$stmt = $pdo->prepare("SELECT title, poster FROM movies WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) die("Movie not found");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf'] ?? '')) {
        flash("Security check failed. Please try again.");
    } else {
        // Delete poster file if exists
        if ($row['poster'] && file_exists(__DIR__ . '/../uploads/' . $row['poster'])) {
            @unlink(__DIR__ . '/../uploads/' . $row['poster']);
        }

        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->execute([$id]);

        flash("Movie \"{$row['title']}\" has been deleted.");
        header("Location: index.php");
        exit;
    }
}

generate_csrf_token();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<h2>Delete Movie</h2>

<p style="color:#dc3545; font-weight:bold;">
    Are you sure you want to permanently delete<br>
    <strong><?= e($row['title']) ?></strong> ?
</p>

<form method="post">
    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_token']) ?>">
    <button type="submit" style="padding:10px 28px; background:#dc3545; color:white; border:none; border-radius:4px; cursor:pointer; font-size:16px;">
        Yes, Delete
    </button>
    <a href="index.php" style="margin-left:25px; font-size:16px;">Cancel</a>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>