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

        if (empty($newName) || empty($description) || empty($date) || !$developer_id || !$publisher_id || $price === '' || empty($selectedTags) || empty($selectedPlatforms)) {
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

                $safeOldGameName = preg_replace('/[^a-z0-9_-]/', '_', strtolower($game['name']));
                $safeNewGameName = preg_replace('/[^a-z0-9_-]/', '_', strtolower($newName));

                for ($i = 0; $i < 4; $i++) {    
                    if (isset($screenshots['error'][$i]) && $screenshots['error'][$i] === UPLOAD_ERR_OK) {
                        $oldFilesPattern = "img/games/{$safeOldGameName}_" . ($i + 1) . ".*";
                        $oldFiles = glob($oldFilesPattern);
                        foreach ($oldFiles as $oldFile) {
                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }

                        $ext = strtolower(pathinfo($screenshots['name'][$i], PATHINFO_EXTENSION));
                        $newFileName = "{$safeNewGameName}_" . ($i + 1) . ".{$ext}";
                        move_uploaded_file($screenshots['tmp_name'][$i], "img/games/{$newFileName}");
                    } elseif ($safeOldGameName !== $safeNewGameName) {
                        $oldFilesPattern = "img/games/{$safeOldGameName}_" . ($i + 1) . ".*";
                        $oldFiles = glob($oldFilesPattern);
                        if (!empty($oldFiles)) {
                             $oldFile = $oldFiles[0];
                             $ext = strtolower(pathinfo($oldFile, PATHINFO_EXTENSION));
                             $newFileName = "{$safeNewGameName}_" . ($i + 1) . ".{$ext}";
                             if (file_exists($oldFile)) {
                                 rename($oldFile, "img/games/{$newFileName}");
                             }
                        }
                    }
                }


                $conn->commit();
                header('Location: game.php?game=' . urlencode($newName));
                exit;
            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $error = "Błąd bazy danych: " . $exception->getMessage();
            }
        }
    }
}

$devResult = $conn->query("SELECT id, name FROM developers ORDER BY name ASC");
$pubResult = $conn->query("SELECT id, name FROM publishers ORDER BY name ASC");
$pltResult = $conn->query("SELECT id, name FROM platforms ORDER BY name ASC");
$tagResult = $conn->query("SELECT id, name FROM tags ORDER BY name ASC");
$reviewsResult = $conn->query("SELECT reviews.id, comment, created_at, rating, users.username, users.avatar_filename FROM reviews LEFT JOIN users ON reviews.user_id = users.id WHERE game_id = $game_id ORDER BY created_at DESC");


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
            <input type="text" name="name" value="<?= htmlspecialchars($game['name']) ?>" style="font-size: 1em; width: 60%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 5px;">
        </h1>

        <?php if ($success): ?>
            <p style="color: lightgreen; width: 100%;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <div class="images">
            <p style="width: 100%; margin-bottom: 10px;">Kliknij na obrazek, aby go podmienić</p>

            <?php
            $i = 0;
            $safeGameNameForDisplay = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($game['name']));
            $imagePath = null;
            $files = glob("img/games/{$safeGameNameForDisplay}_" . ($i + 1) . ".*");
            if (!empty($files)) $imagePath = $files[0];
            $style = $imagePath ? "style='background-image: url(\"{$imagePath}?" . filemtime($imagePath) . "\");'" : ''; // Dodano filemtime
            ?>
            <label for="screenshot_input_<?= $i ?>" class="main-image-container uploader-container" id="preview_<?= $i ?>" <?= $style ?>>
                <span class="uploader-label <?= !$imagePath ? 'visible' : '' ?>">Kliknij, aby wybrać główny screenshot</span>
                <input type="file" name="screenshots[]" class="uploader-input" id="screenshot_input_<?= $i ?>" data-preview-target="preview_<?= $i ?>" accept="image/png,image/jpeg">
            </label>

            <div class="thumbnail-container">
                <?php
                for($i = 1; $i < 4; $i++):
                    $imagePath = null;
                    $files = glob("img/games/{$safeGameNameForDisplay}_" . ($i + 1) . ".*");
                    if (!empty($files)) $imagePath = $files[0];
                    $style = $imagePath ? "style='background-image: url(\"{$imagePath}?" . filemtime($imagePath) . "\");'" : ''; // Dodano filemtime
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
            <textarea name="description" class="edit-textarea" style="height: 120px; margin-bottom: 15px;"><?= htmlspecialchars($game['description']) ?></textarea>

            <span class="detail-title">DATA WYDANIA:</span>
            <input type="date" name="date" value="<?= htmlspecialchars($game['date']) ?>" style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px; color-scheme: dark; margin-bottom: 15px;">

            <div class="details-container">
                <div class="detail-item">
                    <span class="detail-title">DEWELOPER:</span>
                    <select name="developer" id="developer_select" style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px; margin-bottom: 8px;">
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
                    <select name="publisher" id="publisher_select" style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px; margin-bottom: 8px;">
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
            <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($game['price']) ?>" style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px;">
        </div>

        <?php if ($error): ?>
            <p style="color: red; width: 100%; text-align: center; margin-bottom: 10px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <div class="form-button-container">
            <a href="game.php?game=<?= urlencode($game['name']) ?>" class="cart-button cancel-btn-red">Anuluj</a>
            <button type="submit" class="cart-button">Zapisz Zmiany</button>
        </div>
    </form>

    <hr class="divider">

    <div class="reviews-section">
        <h2>Zarządzaj komentarzami</h2>
         <div class="reviews-list">
            <?php if ($reviewsResult && $reviewsResult->num_rows > 0): ?>
                <?php while($review = $reviewsResult->fetch_assoc()): ?>
                    <div class="review-item" id="review-<?= $review['id'] ?>">
                        <div class="review-user-info">
                            <img src="img/avatars/<?= htmlspecialchars($review['avatar_filename'] ?? 'default_avatar.png') ?>" alt="Awatar <?= htmlspecialchars($review['username']) ?>" style="width: 50px; height: 50px; border-radius: 5px; object-fit: cover;">
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
<script src="js/formHelpers.js" defer></script>
<script src="js/slider.js" defer></script>
</body>
</html>