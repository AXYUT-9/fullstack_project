<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_login();

// Fetch all cast members with movie counts
$stmt = $pdo->query("
    SELECT c.id, c.name, c.biography, c.photo, COUNT(mc.movie_id) as movie_count
    FROM cast c
    LEFT JOIN movie_cast mc ON c.id = mc.cast_id
    GROUP BY c.id, c.name, c.biography, c.photo
    ORDER BY c.name ASC
");
$castMembers = $stmt->fetchAll();

// Handle Add Cast
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cast'])) {
    if (validate_csrf_token($_POST['csrf'] ?? '')) {
        $name = trim($_POST['name'] ?? '');
        $bio = trim($_POST['biography'] ?? '');
        $photo = null;
        
        if ($name) {
            // Handle Photo Upload
            if (!empty($_FILES['photo']['name'])) {
                $f = $_FILES['photo'];
                $allowed = ['image/jpeg', 'image/png'];
                if (in_array($f['type'], $allowed) && $f['size'] <= 2*1024*1024) {
                    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
                    $photo = 'cast_' . time() . '.' . $ext;
                    move_uploaded_file($f['tmp_name'], __DIR__ . '/../uploads/' . $photo);
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO cast (name, biography, photo) VALUES (?, ?, ?)");
            $stmt->execute([$name, $bio, $photo]);
            flash("Cast member added!");
            header("Location: cast.php");
            exit;
        } else {
            flash("Name is required.");
        }
    }
}

generate_csrf_token();

// Handle cast filter
$selectedCast = isset($_GET['cast_id']) ? (int)$_GET['cast_id'] : null;
$movies = [];
$castInfo = null;

if ($selectedCast) {
    // Get cast member info
    $stmt = $pdo->prepare("SELECT * FROM cast WHERE id = ?");
    $stmt->execute([$selectedCast]);
    $castInfo = $stmt->fetch();
    
    // Get movies for this cast member
    $stmt = $pdo->prepare("
        SELECT m.*, mc.role, GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') as genres
        FROM movies m
        INNER JOIN movie_cast mc ON m.id = mc.movie_id
        LEFT JOIN movie_genres mg ON m.id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.id
        WHERE mc.cast_id = ?
        GROUP BY m.id, mc.role
        ORDER BY m.year DESC
    ");
    $stmt->execute([$selectedCast]);
    $movies = $stmt->fetchAll();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1>Cast & Crew</h1>
        <?php if (!$selectedCast): ?>
            <button onclick="document.getElementById('addCastModal').style.display='block'" class="btn-submit">
                + Add New Cast
            </button>
        <?php endif; ?>
    </div>

    <!-- Add Cast Modal -->
    <div id="addCastModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
        <div style="background:white; width:90%; max-width:500px; margin:50px auto; padding:20px; border-radius:8px; position:relative;">
            <span onclick="document.getElementById('addCastModal').style.display='none'" style="float:right; cursor:pointer; font-size:20px;">&times;</span>
            <h2>Add New Cast Member</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="add_cast" value="1">
                
                <div style="margin-bottom:15px;">
                    <label>Name*</label>
                    <input type="text" name="name" required style="width:100%; padding:8px;">
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>Biography</label>
                    <textarea name="biography" rows="3" style="width:100%; padding:8px;"></textarea>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label>Photo</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png">
                </div>
                
                <button type="submit" class="btn-submit" style="width:100%;">Save Cast Member</button>
            </form>
        </div>
    </div>
    
    <?php if ($selectedCast && $castInfo): ?>
        <div class="cast-detail">
            <a href="cast.php" class="btn-submit" style="margin-bottom: 20px; display: inline-block;">← Back to All Cast</a>
            
            <div class="cast-profile">
                <?php if ($castInfo['photo']): ?>
                    <img src="../uploads/<?= e($castInfo['photo']) ?>" alt="<?= e($castInfo['name']) ?>" class="cast-photo">
                <?php else: ?>
                    <div class="cast-photo-placeholder">
                        <span><?= substr(e($castInfo['name']), 0, 1) ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="cast-info">
                    <h2><?= e($castInfo['name']) ?></h2>
                    <?php if ($castInfo['biography']): ?>
                        <p class="biography"><?= e($castInfo['biography']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <h3 style="margin-top: 40px;">Movies</h3>
            <?php if (empty($movies)): ?>
                <p class="no-data">No movies found for this cast member.</p>
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
                            <p class="cast-role">Role: <?= e($m['role']) ?></p>
                            <?php if ($m['genres']): ?>
                                <p class="movie-genre"><?= e($m['genres']) ?></p>
                            <?php endif; ?>
                            <div class="movie-actions">
                                <a href="view.php?id=<?= $m['id'] ?>" class="btn-view">View</a>
                                <a href="edit.php?id=<?= $m['id'] ?>" class="btn-edit">Edit</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="cast-grid">
            <?php foreach ($castMembers as $cast): ?>
                <div class="cast-card">
                    <a href="?cast_id=<?= $cast['id'] ?>" class="cast-link">
                        <?php if ($cast['photo']): ?>
                            <img src="../uploads/<?= e($cast['photo']) ?>" alt="<?= e($cast['name']) ?>" class="cast-photo-small">
                        <?php else: ?>
                            <div class="cast-photo-placeholder-small">
                                <span><?= substr(e($cast['name']), 0, 1) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <h3><?= e($cast['name']) ?></h3>
                        <span class="movie-count"><?= $cast['movie_count'] ?> movies</span>
                        
                        <?php if ($cast['biography']): ?>
                            <p class="biography-preview"><?= e(substr($cast['biography'], 0, 100)) ?><?= strlen($cast['biography']) > 100 ? '...' : '' ?></p>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<style>
.cast-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 25px;
    margin: 30px 0;
}

.cast-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    overflow: hidden;
}

.cast-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.cast-link {
    display: block;
    padding: 20px;
    text-align: center;
    text-decoration: none;
    color: #333;
}

.cast-photo-small {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 15px;
    display: block;
}

.cast-photo-placeholder-small {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3em;
    color: white;
    font-weight: bold;
}

.cast-link h3 {
    margin: 0 0 10px 0;
    font-size: 1.3em;
}

.biography-preview {
    font-size: 0.9em;
    color: #666;
    margin-top: 10px;
    line-height: 1.4;
}

.cast-detail {
    margin: 20px 0;
}

.cast-profile {
    display: flex;
    gap: 30px;
    align-items: flex-start;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.cast-photo {
    width: 200px;
    height: 200px;
    border-radius: 12px;
    object-fit: cover;
}

.cast-photo-placeholder {
    width: 200px;
    height: 200px;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 5em;
    color: white;
    font-weight: bold;
}

.cast-info {
    flex: 1;
}

.cast-info h2 {
    margin: 0 0 15px 0;
    font-size: 2em;
    color: #333;
}

.biography {
    line-height: 1.6;
    color: #666;
    font-size: 1.1em;
}

.cast-role {
    font-size: 0.9em;
    color: #007bff;
    margin: 5px 0;
    font-weight: 600;
}

.movie-genre {
    font-size: 0.85em;
    color: #666;
    margin: 5px 0;
}

@media (max-width: 768px) {
    .cast-profile {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .cast-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>