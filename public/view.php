<?php
require '../includes/functions.php';
require '../config/db.php';

if (!isset($_GET['id'])) die("No ID");

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch();

if (!$m) die("Not found");
?>

<?php include '../includes/header.php'; ?>

<h2><?= e($m['title']) ?> (<?= e($m['year']) ?>)</h2>

<?php if ($m['poster']): ?>
    <img src="../uploads/<?= e($m['poster']) ?>" alt="Poster" style="max-width:300px;">
<?php else: ?>
    <p>No poster available.</p>
<?php endif; ?>

<p><strong>Genre:</strong> <?= e($m['genre']) ?></p>
<p><strong>Rating:</strong> <?= e($m['rating']) ?>/10</p>
<p><strong>Description:</strong> <?= nl2br(e($m['description'])) ?></p>

<a href="index.php">← Back to list</a>

<?php include '../includes/footer.php'; ?>