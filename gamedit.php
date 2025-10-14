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

$gameNameEsc = $conn->real_escape_string($gameName);
$gameResult = $conn->query("SELECT * FROM games WHERE name = '$gameNameEsc'");


if ($gameResult->num_rows === 0) {
    header('Location: index.php');
    exit;
}
$game = $gameResult->fetch_assoc();
$game_id = (int)$game['id'];

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_review'])) {
        $review_id = (int)($_POST['review_id'] ?? 0);
        if ($review_id) {
            $conn->query("DELETE FROM reviews WHERE id = $review_id AND game_id = $game_id");
            if ($conn->affected_rows > 0) {
                $success = 'Komentarz został pomyślnie usunięty.';
            }
        }
    }
    elseif (isset($_POST['save_game'])) {
        $newName = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $date = $_POST['date'] ?? '';
        $developer_id = (int)($_POST['developer'] ?? 0);
        $publisher_id = (int)($_POST['publisher'] ?? 0);
        $price = $_POST['price'] ?? '';
        $selectedPlatforms = $_POST['platforms'] ?? [];
        $selectedTags = $_POST['tags'] ?? [];
        $screenshots = $_FILES['screenshots'] ?? [];

        if (empty($newName) || empty($description) || empty($date) || !$developer_id || !$publisher_id || $price === '') {
            $error = "Wszystkie pola są wymagane.";
        } elseif (!is_numeric($price) || $price < 0) {
            $error = 'Cena musi być liczbą większą lub równą 0.';
        } else {
            $conn->begin_transaction();
            try {
                $newNameEsc = $conn->real_escape_string($newName);
                $descriptionEsc = $conn->real_escape_string($description);

                $updateQuery = "UPDATE games SET name = '$newNameEsc', description = '$descriptionEsc', date = '$date', developer_id = $developer_id, publisher_id = $publisher_id, price = '$price' WHERE id = $game_id";
                $conn->query($updateQuery);

                $conn->query("DELETE FROM game_platforms WHERE game_id = $game_id");
                foreach ($selectedPlatforms as $platformId) {
                    $pId = (int)$platformId;
                    $conn->query("INSERT INTO game_platforms (game_id, platform_id) VALUES ($game_id, $pId)");
                }

                $conn->query("DELETE FROM game_tags WHERE game_id = $game_id");
                foreach ($selectedTags as $tagId) {
                    $tId = (int)$tagId;
                    $conn->query("INSERT INTO game_tags (game_id, tag_id) VALUES ($game_id, $tId)");
                }

                $safeGameName = preg_replace('/[^a-z0-9_-]/', '_', strtolower($newName));
                for ($i = 0; $i < 4; $i++) {
                    if (isset($screenshots['error'][$i]) && $screenshots['error'][$i] === UPLOAD_ERR_OK) {
                        $oldFiles = glob("img/games/{$safeGameName}_" . ($i + 1) . ".*");
                        foreach ($oldFiles as $oldFile) if (file_exists($oldFile)) unlink($oldFile);
                        $ext = strtolower(pathinfo($screenshots['name'][$i], PATHINFO_EXTENSION));
                        $fileName = "{$safeGameName}_" . ($i + 1) . ".{$ext}";
                        move_uploaded_file($screenshots['tmp_name'][$i], "img/games/{$fileName}");
                    }
                }
                
                $conn->commit();
                header('Location: gamedit.php?game=' . urlencode($newName) . '&status=success');
                exit;
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $error = "Błąd bazy danych: " . $exception->getMessage();
            }
        }
    }
}

if(isset($_GET['status']) && $_GET['status'] === 'success') {
    $success = 'Zmiany zostały pomyślnie zapisane.';
}

$devResult = $conn->query("SELECT id, name FROM developers ORDER BY name ASC");
$pubResult = $conn->query("SELECT id, name FROM publishers ORDER BY name ASC");
$pltResult = $conn->query("SELECT id, name FROM platforms ORDER BY name ASC");
$tagResult = $conn->query("SELECT id, name FROM tags ORDER BY name ASC");
$reviewsResult = $conn->query("SELECT reviews.id, comment, created_at, rating, users.username FROM reviews LEFT JOIN users ON reviews.user_id = users.id WHERE game_id = $game_id ORDER BY created_at DESC");

$currentPltsResult = $conn->query("SELECT platform_id FROM game_platforms WHERE game_id = $game_id");
$currentPlatforms = [];
while ($row = $currentPltsResult->fetch_assoc()) $currentPlatforms[] = $row['platform_id'];

$currentTagsResult = $conn->query("SELECT tag_id FROM game_tags WHERE game_id = $game_id");
$currentTags = [];
while ($row = $currentTagsResult->fetch_assoc()) $currentTags[] = $row['tag_id'];

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
    <link rel="stylesheet" href="style/styleCommon.css">
</head>
<body>
<?php include 'header.php'; ?>

<div id="container">

    <form method="POST" enctype="multipart/form-data" style="display: contents;">
        <input type="hidden" name="save_game" value="1">

        <h1>
            Edytuj grę: 
            <input type="text" name="name" value="<?= htmlspecialchars($game['name']) ?>" required style="font-size: 1em; width: 60%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 5px;">
        </h1>
        
        <a href="game.php?game=<?= urlencode($game['name']) ?>" class="back-link">
            Wróć do podglądu gry
        </a>

        <?php if ($error): ?>
            <p style="color: red; width: 100%;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color: lightgreen; width: 100%;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <div class="images">
            <p style="width: 100%; margin-bottom: 10px;">Kliknij na obrazek, aby go podmienić</p>

            <?php
            $i = 0;
            $safeGameName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($game['name']));
            $imagePath = null;
            $files = glob("img/games/{$safeGameName}_" . ($i + 1) . ".*");
            if (!empty($files)) $imagePath = $files[0];
            $style = $imagePath ? "style='background-image: url(\"{$imagePath}\");'" : '';
            ?>
            <label for="screenshot_input_<?= $i ?>" class="main-image-container uploader-container" id="preview_<?= $i ?>" <?= $style ?>>
                <span class="uploader-label <?= !$imagePath ? 'visible' : '' ?>">Kliknij, aby wybrać główny screenshot</span>
                <input type="file" name="screenshots[]" class="uploader-input" id="screenshot_input_<?= $i ?>" data-preview-target="preview_<?= $i ?>" accept="image/png,image/jpeg">
            </label>

            <div class="thumbnail-container">
                <?php
                for($i = 1; $i < 4; $i++):
                    $imagePath = null;
                    $files = glob("img/games/{$safeGameName}_" . ($i + 1) . ".*");
                    if (!empty($files)) $imagePath = $files[0];
                    $style = $imagePath ? "style='background-image: url(\"{$imagePath}\");'" : '';
                ?>
                    <label for="screenshot_input_<?= $i ?>" class="thumbnail uploader-container" id="preview_<?= $i ?>" <?= $style ?>>
                        <span class="uploader-label <?= !$imagePath ? 'visible' : '' ?>">Wybierz plik</span>
                        <input type="file" name="screenshots[]" class="uploader-input" id="screenshot_input_<?= $i ?>" data-preview-target="preview_<?= $i ?>" accept="image/png,image/jpeg">
                    </label>
                <?php endfor; ?>
            </div>

            <span class="detail-title" style="margin: 20px 0 10px 0; display: block;">TAGI:</span>
            <div class="tags-container">
                <?php while($row = $tagResult->fetch_assoc()):
                    $isSelected = in_array($row['id'], $currentTags); ?>
                    <span class="tag-item <?= $isSelected ? 'selected' : '' ?>" data-tag-id="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></span>
                    <input type="checkbox" name="tags[]" value="<?= $row['id'] ?>" <?= $isSelected ? 'checked' : '' ?> style="display:none;">
                <?php endwhile; ?>
            </div>
        </div>

        <div class="content">
            <span class="detail-title">OPIS GRY:</span>
            <textarea name="description" required class="edit-textarea" style="height: 120px; margin-bottom: 15px;"><?= htmlspecialchars($game['description']) ?></textarea>

            <span class="detail-title">DATA WYDANIA:</span>
            <input type="date" name="date" value="<?= htmlspecialchars($game['date']) ?>" required style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px; color-scheme: dark; margin-bottom: 15px;">

            <div class="details-container">
                <div class="detail-item">
                    <span class="detail-title">DEWELOPER:</span>
                    <select name="developer" id="developer_select" required style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px; margin-bottom: 8px;">
                        <?php while($row = $devResult->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" data-logo="<?= htmlspecialchars(find_logo_path($row['name'], 'developer') ?? '') ?>" <?= ($game['developer_id'] == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; $devResult->data_seek(0); ?>
                    </select>
                    <div id="developer_logo_preview" class="logo-container" style="background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                    <a href="addpubdev.php?role=developer" class="cart-button" style="display: block; text-align: center; margin-top: 8px;">Dodaj nowego</a>
                </div>
                <div class="detail-item">
                    <span class="detail-title">WYDAWCA:</span>
                    <select name="publisher" id="publisher_select" required style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px; margin-bottom: 8px;">
                        <?php while($row = $pubResult->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" data-logo="<?= htmlspecialchars(find_logo_path($row['name'], 'publisher') ?? '') ?>" <?= ($game['publisher_id'] == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; $pubResult->data_seek(0); ?>
                    </select>
                    <div id="publisher_logo_preview" class="logo-container" style="background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                    <a href="addpubdev.php?role=publisher" class="cart-button" style="display: block; text-align: center; margin-top: 8px;">Dodaj nowego</a>
                </div>
            </div>

            <span class="detail-title" style="margin-top: 15px;">PLATFORMY:</span>
            <div class="platforms-container" style="margin-bottom: 15px;">
                <?php while($row = $pltResult->fetch_assoc()):
                    $isSelected = in_array($row['id'], $currentPlatforms); ?>
                    <div class="platform-item <?= $isSelected ? 'selected' : '' ?>"
                         data-platform-id="<?= $row['id'] ?>"
                         data-platform-name="<?= htmlspecialchars($row['name']) ?>"
                         title="<?= htmlspecialchars($row['name']) ?>"
                         style="cursor: pointer;">
                    </div>
                    <input type="checkbox" name="platforms[]" value="<?= $row['id'] ?>" <?= $isSelected ? 'checked' : '' ?> style="display:none;">
                <?php endwhile; ?>
            </div>
            
            <span class="detail-title">CENA (PLN):</span>
            <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($game['price']) ?>" required style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px;">
        </div>

        <button type="submit" class="cart-button" style="width: 30%; margin-top: 20px; margin-bottom: 20px; margin-left: auto; margin-right: auto;">Zapisz Zmiany</button>
    </form>
    
    <hr class="divider">

    <div class="reviews-section">
        <h2>Zarządzaj komentarzami</h2>
         <div class="reviews-list">
            <?php if ($reviewsResult && $reviewsResult->num_rows > 0): ?>
                <?php while($review = $reviewsResult->fetch_assoc()): ?>
                    <div class="review-item" id="review-<?= $review['id'] ?>">
                        <div class="review-user-info">
                            <div class="avatar-placeholder"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="white" d="M6 22h13a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2zm6-17.001c1.647 0 3 1.351 3 3C15 9.647 13.647 11 12 11S9 9.647 9 7.999c0-1.649 1.353-3 3-3M6 17.25c0-2.219 2.705-4.5 6-4.5s6 2.281 6 4.5V18H6z"/></svg></div>
                            <span class="username"><?= htmlspecialchars($review['username']); ?></span>
                        </div>
                        <div class="review-main-content">
                                <div class="review-top">
                                    <span class="stars-display"><?= str_repeat('★', $review['rating']); ?><?= str_repeat('☆', 5 - $review['rating']); ?></span>
                                    <span class="review-date"><?= date('d.m.Y', strtotime($review['created_at'])) ?></span>
                                </div>
                                <div class="review-body">
                                    <p class="review-comment-content"><?= nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                        </div>
                        <form method="POST" onsubmit="return confirm('Czy na pewno chcesz usunąć ten komentarz?');" style="margin-left: 15px;">
                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                            <button type="submit" name="delete_review" class="cart-button" style="background-color: #c94c4c;">Usuń</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Brak komentarzy dla tej gry.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.uploader-input').forEach(input => {
        input.addEventListener('change', function() {
            const previewBox = document.getElementById(this.dataset.previewTarget);
            const label = previewBox.querySelector('.uploader-label');
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    previewBox.style.backgroundImage = `url('${e.target.result}')`;
                    if (label) label.classList.remove('visible');
                };
                reader.readAsDataURL(file);
            }
        });
    });

    function setupLogoPreview(selectId, previewId) {
        const select = document.getElementById(selectId);
        const preview = document.getElementById(previewId);
        function updateLogo() {
            const logo = select.options[select.selectedIndex]?.dataset.logo || '';
            preview.style.backgroundImage = logo ? `url('${logo}')` : 'none';
        }
        select.addEventListener('change', updateLogo);
        updateLogo();
    }
    setupLogoPreview('developer_select', 'developer_logo_preview');
    setupLogoPreview('publisher_select', 'publisher_logo_preview');

    const platformSvgs = {
        'windows': `<svg viewBox="0 0 56.693 56.693" xmlns="http://www.w3.org/2000/svg"><g><path d="M3.765,46.362l19.836,2.873V30.257H3.765V46.362z M3.765,27.546h19.836V8.566L3.765,11.439V27.546z M26.312,49.628 l26.616,3.855V30.257H26.312V49.628z M26.312,8.172v19.374h26.616V4.319L26.312,8.172z"/></g></svg>`,
        'playstation': `<svg fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M15.858 11.451c-.313.395-1.079.676-1.079.676l-5.696 2.046v-1.509l4.192-1.493c.476-.17.549-.412.162-.538-.386-.127-1.085-.09-1.56.08l-2.794.984v-1.566l.161-.054s.807-.286 1.942-.412c1.135-.125 2.525.017 3.616.43 1.23.39 1.368.962 1.056 1.356ZM9.625 8.883v-3.86c0-.453-.083-.87-.508-.988-.326-.105-.528.198-.528.65v9.664l-2.606-.827V2c1.108.206 2.722.692 3.59.985 2.207.757 2.955 1.7 2.955 3.825 0 2.071-1.278 2.856-2.903 2.072Zm-8.424 3.625C-.061 12.15-.271 11.41.304 10.984c.532-.394 1.436-.69 1.436-.69l3.737-1.33v1.515l-2.69.963c-.474.17-.547.411-.161.538.386.126 1.085.09 1.56-.08l1.29-.469v1.356l-.257.043a8.454 8.454 0 0 1-4.018-.323Z"/></svg>`,
        'xbox': `<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M369.9 318.2c44.3 54.3 64.7 98.8 54.4 118.7-7.9 15.1-56.7 44.6-92.6 55.9-29.6 9.3-68.4 13.3-100.4 10.2-38.2-3.7-76.9-17.4-110.1-39C93.3 445.8 87 438.3 87 423.4c0-29.9 32.9-82.3 89.2-142.1 32-33.9 76.5-73.7 81.4-72.6 9.4 2.1 84.3 75.1 112.3 109.5zM188.6 143.8c-29.7-26.9-58.1-53.9-86.4-63.4-15.2-5.1-16.3-4.8-28.7 8.1-29.2 30.4-53.5 79.7-60.3 122.4-5.4 34.2-6.1 43.8-4.2 60.5 5.6 50.5 17.3 85.4 40.5 120.9 9.5 14.6 12.1 17.3 9.3 9.9-4.2-11-.3-37.5 9.5-64 14.3-39 53.9-112.9 120.3-194.4zm311.6 63.5C483.3 127.3 432.7 77 425.6 77c-7.3 0-24.2 6.5-36 13.9-23.3 14.5-41 31.4-64.3 52.8C367.7 197 427.5 283.1 448.2 346c6.8 20.7 9.7 41.1 7.4 52.3-1.7 8.5-1.7 8.5 1.4 4.6 6.1-7.7 19.9-31.3 25.4-43.5 7.4-16.2 15-40.2 18.6-58.7 4.3-22.5 3.9-70.8-.8-93.4zM141.3 43C189 40.5 251 77.5 255.6 78.4c.7.1 10.4-4.2 21.6-9.7 63.9-31.1 94-25.8 107.4-25.2-63.9-39.3-152.7-50-233.9-11.7-23.4 11.1-24 11.9-9.4 11.2z"/></svg>`,
        'nintendo': `<svg fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M9.34 8.005c0-4.38.01-7.972.023-7.982C9.373.01 10.036 0 10.831 0c1.153 0 1.51.01 1.743.05 1.73.298 3.045 1.6 3.373 3.326.046.242.053.809.053 4.61 0 4.06.005 4.537-.123 4.976-.022.076-.048.15-.08.242a4.136 4.136 0 0 1-3.426 2.767c-.317.033-2.889.046-2.978.013-.05-.02-.053-.752-.053-7.979Zm4.675.269a1.621 1.621 0 0 0-1.113-1.034 1.609 1.609 0 0 0-1.938 1.073 1.9 1.9 0 0 0-.014.935 1.632 1.632 0 0 0 1.952 1.107c.51-.136.908-.504 1.11-1.028.11-.285.113-.742.003-1.053ZM3.71 3.317c-.208.04-.526.199-.695.348-.348.301-.52.729-.494 1.232.013.262.03.332.136.544.155.321.39.556.712.715.222.11.278.123.567.133.261.01.354 0 .53-.06.719-.242 1.153-.94 1.03-1.656-.142-.852-.95-1.422-1.786-1.256Z"/><path d="M3.425.053a4.136 4.136 0 0 0-3.28 3.015C0 3.628-.01 3.956.005 8.3c.01 3.99.014 4.082.08 4.39.368 1.66 1.548 2.844 3.224 3.235.22.05.497.06 2.29.07 1.856.012 2.048.009 2.097-.04.05-.05.053-.69.053-7.94 0-5.374-.01-7.906-.033-7.952-.033-.06-.09-.063-2.03-.06-1.578.004-2.052.014-2.26.05Zm3 14.665-1.35-.016c-1.242-.013-1.375-.02-1.623-.083a2.81 2.81 0 0 1-2.08-2.167c-.074-.335-.074-8.579-.004-8.907a2.845 2.845 0 0 1 1.716-2.05c.438-.176.64-.196 2.058-.2l1.282-.003v13.426Z"/></svg>`
    };
    const platformElements = document.querySelectorAll('.platform-item');
    platformElements.forEach(element => {
        const platformName = element.dataset.platformName.toLowerCase().trim();
        if (platformSvgs[platformName]) {
            element.innerHTML = platformSvgs[platformName];
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

    document.querySelector('.tags-container').addEventListener('click', function(e){
        if (e.target.classList.contains('tag-item')) {
            const span = e.target;
            const checkbox = this.querySelector(`input[value="${span.dataset.tagId}"]`);
            span.classList.toggle('selected');
            checkbox.checked = !checkbox.checked;
        }
    });
});
</script>
<script src="skrypty.js"></script>
</body>
</html>