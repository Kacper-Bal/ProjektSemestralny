<?php
session_start();
require_once 'conn.php';
require_once 'auth.php';

if (!$currentUser || $currentUser['role'] != 1) {
    header('Location: index.php');
    exit;
}

$gameName = $_GET['game'] ?? null;
if (!$gameName) {
    header('Location: index.php');
    exit;
}

$gameResult = $conn->query("SELECT * FROM games WHERE name = '$gameName'");
if ($gameResult->num_rows === 0) {
    header('Location: index.php');
    exit;
}
$game = $gameResult->fetch_assoc();
$game_id = (int)$game['id'];

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $review_id = (int)($_POST['review_id'] ?? 0);
    if ($review_id) {
        $conn->query("DELETE FROM reviews WHERE id = $review_id AND game_id = $game_id");
        $success = 'Komentarz został pomyślnie usunięty.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_game'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $developer_id = (int)($_POST['developer'] ?? 0);
    $publisher_id = (int)($_POST['publisher'] ?? 0);
    $date = $_POST['date'] ?? '';
    $price = $_POST['price'] ?? '';
    $selectedTags = $_POST['tags'] ?? [];
    $selectedPlatforms = $_POST['platforms'] ?? [];
    $screenshots = $_FILES['screenshots'] ?? [];

    if (!$name || !$description || !$developer_id || !$publisher_id || !$date || $price === '') {
        $error = 'Wszystkie pola muszą być wypełnione.';
    } elseif (!is_numeric($price) || $price < 0) {
        $error = 'Cena musi być liczbą większą lub równą 0.';
    }

    if (!$error) {
        $updateQuery = "UPDATE games SET 
            name = '$name',
            description = '$description',
            developer_id = $developer_id,
            publisher_id = $publisher_id,
            date = '$date',
            price = $price
            WHERE id = $game_id";
        
        if ($conn->query($updateQuery)) {
            $conn->query("DELETE FROM game_tags WHERE game_id = $game_id");
            foreach ($selectedTags as $tagId) {
                $conn->query("INSERT INTO game_tags (game_id, tag_id) VALUES ($game_id, " . (int)$tagId . ")");
            }

            $conn->query("DELETE FROM game_platforms WHERE game_id = $game_id");
            foreach ($selectedPlatforms as $platformId) {
                $conn->query("INSERT INTO game_platforms (game_id, platform_id) VALUES ($game_id, " . (int)$platformId . ")");
            }

            $safeGameName = preg_replace('/[^a-z0-9_-]/', '_', strtolower($name));
            for ($i = 0; $i < 4; $i++) {
                if (isset($screenshots['error'][$i]) && $screenshots['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($screenshots['name'][$i], PATHINFO_EXTENSION);
                    $fileName = "{$safeGameName}_".($i+1).".$ext";
                    move_uploaded_file($screenshots['tmp_name'][$i], "img/games/$fileName");
                }
            }

            header('Location: game.php?game=' . urlencode($name));
            exit;

        } else {
            $error = 'Błąd bazy danych: ' . $conn->error;
        }
    }
}

$devResult = $conn->query("SELECT id, name FROM developers ORDER BY name ASC");
$pubResult = $conn->query("SELECT id, name FROM publishers ORDER BY name ASC");
$tagResult = $conn->query("SELECT id, name FROM tags ORDER BY name ASC");
$pltResult = $conn->query("SELECT id, name FROM platforms ORDER BY name ASC");
$reviewsResult = $conn->query("SELECT reviews.id, comment, created_at, rating, users.username FROM reviews LEFT JOIN users ON reviews.user_id = users.id WHERE game_id = $game_id ORDER BY created_at DESC");

$currentTagsResult = $conn->query("SELECT tag_id FROM game_tags WHERE game_id = $game_id");
$currentTags = [];
while ($row = $currentTagsResult->fetch_assoc()) {
    $currentTags[] = $row['tag_id'];
}

$currentPltsResult = $conn->query("SELECT platform_id FROM game_platforms WHERE game_id = $game_id");
$currentPlatforms = [];
while ($row = $currentPltsResult->fetch_assoc()) {
    $currentPlatforms[] = $row['platform_id'];
}

function find_logo_path($name, $role) {
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($name));
    foreach (['png','jpg'] as $ext) {
        $filePath = __DIR__ . "/img/$role/$safeName.$ext";
        if (file_exists($filePath)) return "img/$role/$safeName.$ext";
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Edytuj grę: <?= htmlspecialchars($game['name']) ?></title>
    <link rel="stylesheet" href="style/styleGame.css">
</head>
<body>

<div class="container">
    <header class="form-header">
        <h1>Edytuj grę: <?= htmlspecialchars($game['name']) ?></h1>
        <a href="game.php?game=<?= urlencode($game['name']) ?>" class="back-link">Wróć do podglądu gry</a>
    </header>

    <?php if ($error) echo "<p class='error-message'>".htmlspecialchars($error)."</p>"; ?>
    <?php if ($success) echo "<p class='success-message'>".htmlspecialchars($success)."</p>"; ?>

    <form method="POST" enctype="multipart/form-data" class="game-form">
        <input type="hidden" name="save_game" value="1">

        <div class="form-group">
            <label for="name">Nazwa gry</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($game['name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Opis</label>
            <textarea id="description" name="description" rows="5" required><?= htmlspecialchars($game['description']) ?></textarea>
        </div>
        
        <div class="form-group dev-pub-container">
            <div class="selector-container">
                <label for="developer_select">Producent</label>
                <select name="developer" id="developer_select" required>
                    <?php while($row = $devResult->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" data-logo="<?= htmlspecialchars(find_logo_path($row['name'], 'developer') ?? '') ?>" <?= ($game['developer_id'] == $row['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endwhile; $devResult->data_seek(0); ?>
                </select>
            </div>
            <div id="developer_logo_preview" class="logo-preview-box"></div>
        </div>

        <div class="form-group dev-pub-container">
            <div class="selector-container">
                <label for="publisher_select">Wydawca</label>
                <select name="publisher" id="publisher_select" required>
                    <?php while($row = $pubResult->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" data-logo="<?= htmlspecialchars(find_logo_path($row['name'], 'publisher') ?? '') ?>" <?= ($game['publisher_id'] == $row['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['name']) ?>
                        </option>
                    <?php endwhile; $pubResult->data_seek(0); ?>
                </select>
            </div>
            <div id="publisher_logo_preview" class="logo-preview-box"></div>
        </div>

        <div class="form-group">
            <label>Tagi</label>
            <div class="tags-container">
                <?php while($row = $tagResult->fetch_assoc()):
                    $isSelected = in_array($row['id'], $currentTags); ?>
                    <span class="tag-item <?= $isSelected ? 'selected' : '' ?>" data-tag-id="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></span>
                    <input type="checkbox" name="tags[]" value="<?= $row['id'] ?>" <?= $isSelected ? 'checked' : '' ?> style="display:none;">
                <?php endwhile; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Platformy</label>
            <div class="platforms-container">
                <?php while($row = $pltResult->fetch_assoc()):
                    $isSelected = in_array($row['id'], $currentPlatforms); ?>
                    <span class="platform-item <?= $isSelected ? 'selected' : '' ?>" 
                          data-platform-id="<?= $row['id'] ?>"
                          data-platform-name="<?= htmlspecialchars($row['name']) ?>"
                          title="<?= htmlspecialchars($row['name']) ?>">
                        </span>
                    <input type="checkbox" name="platforms[]" value="<?= $row['id'] ?>" <?= $isSelected ? 'checked' : '' ?> style="display:none;">
                <?php endwhile; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label>Screenshoty (zostaw puste, aby zachować stare)</label>
            <div class="screenshots-container">
                <?php 
                $safeGameName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($game['name']));
                for($i=0; $i<4; $i++):
                    $imagePath = null;
                    $style = '';
                    foreach(['jpg', 'png', 'jpeg'] as $ext) {
                        $potentialPath = "img/games/{$safeGameName}_" . ($i + 1) . ".{$ext}";
                        if (file_exists($potentialPath)) {
                            $imagePath = $potentialPath;
                            break;
                        }
                    }
                    if ($imagePath) {
                        $style = "style='background-image: url(\"{$imagePath}\");'";
                    }
                ?>
                    <div class="screenshot-upload-box" id="preview_<?= $i ?>" <?= $style ?>>
                        <label for="screenshot_input_<?= $i ?>" <?= $imagePath ? "style='display:none;'" : '' ?>>Kliknij, aby podmienić</label>
                        <input type="file" name="screenshots[]" class="screenshot-input" id="screenshot_input_<?= $i ?>" data-preview-target="preview_<?= $i ?>" accept="image/png,image/jpeg">
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="date">Data wydania</label>
            <input type="date" id="date" name="date" value="<?= htmlspecialchars($game['date']) ?>" required>
        </div>

        <div class="form-group">
            <label for="price">Cena</label>
            <input type="number" id="price" name="price" step="0.01" min="0" value="<?= htmlspecialchars($game['price']) ?>" required>
        </div>

        <button type="submit" class="submit-button">Zapisz zmiany</button>
    </form>
</div>
<div class="container">
    <section class="management-section">
        <h2>Zarządzaj komentarzami</h2>
        <div class="reviews-list">
            <?php if ($reviewsResult && $reviewsResult->num_rows > 0): ?>
                <?php while($review = $reviewsResult->fetch_assoc()): ?>
                    <div class="review-item-admin">
                        <div class="review-details">
                            <p>
                                <strong>Użytkownik:</strong> <?= htmlspecialchars($review['username']) ?><br>
                                <strong>Ocena:</strong> <span class="stars-display"><?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?></span><br>
                                <strong>Data:</strong> <?= date('d.m.Y H:i', strtotime($review['created_at'])) ?>
                            </p>
                            <p class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Czy na pewno chcesz usunąć ten komentarz?');" class="delete-form">
                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                            <button type="submit" name="delete_review" class="delete-button">Usuń</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Brak komentarzy dla tej gry.</p>
            <?php endif; ?>
        </div>
    </section>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupLogoPreview(selectId, previewId) {
        const select = document.getElementById(selectId);
        const preview = document.getElementById(previewId);
        function updateLogo() {
            const logo = select.options[select.selectedIndex]?.dataset.logo || '';
            preview.style.backgroundImage = logo ? `url('${logo}')` : '';
        }
        select.addEventListener('change', updateLogo);
        updateLogo();
    }
    setupLogoPreview('developer_select', 'developer_logo_preview');
    setupLogoPreview('publisher_select', 'publisher_logo_preview');

    document.querySelector('.tags-container').addEventListener('click', function(e){
        if (e.target.classList.contains('tag-item')) {
            const span = e.target;
            const checkbox = this.querySelector(`input[value="${span.dataset.tagId}"]`);
            span.classList.toggle('selected');
            checkbox.checked = !checkbox.checked;
        }
    });

    document.querySelector('.platforms-container').addEventListener('click', function(e){
        const span = e.target.closest('.platform-item');
        if(span){
            const checkbox = this.querySelector(`input[value="${span.dataset.platformId}"]`);
            span.classList.toggle('selected');
            checkbox.checked = !checkbox.checked;
        }
    });

    document.querySelectorAll('.screenshot-input').forEach(input => {
        input.addEventListener('change', function(){
            const preview = document.getElementById(this.dataset.previewTarget);
            const label = preview.querySelector('label');
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.style.backgroundImage = `url('${e.target.result}')`;
                    if(label) label.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    });
});
</script>
<script src="skrypty.js"></script>
</body>
</html>