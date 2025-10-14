<?php
session_start();

if (($_GET['action'] ?? '') === 'cancel') {
    if (isset($_SESSION['addgame_form_data'])) {
        unset($_SESSION['addgame_form_data']);
    }
    header('Location: index.php');
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'conn.php';
require_once 'auth.php';

if (!$currentUser || $currentUser['role'] != 1) {
    header('Location: index.php');
    exit;
}

$formData = $_SESSION['addgame_form_data'] ?? [];

if (isset($_SESSION["new_developer_id"])) {
    $formData['developer'] = $_SESSION["new_developer_id"];
    unset($_SESSION["new_developer_id"], $_SESSION["new_developer_name"]);
}
if (isset($_SESSION["new_publisher_id"])) {
    $formData['publisher'] = $_SESSION["new_publisher_id"];
    unset($_SESSION["new_publisher_id"], $_SESSION["new_publisher_name"]);
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_state_and_redirect'])) {
        $_SESSION['addgame_form_data'] = $_POST;
        $redirect_url = $_POST['redirect_url'];
        header("Location: $redirect_url");
        exit;
    }

    if (isset($_POST['add_game'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $date = $_POST['date'] ?? '';
        $developer_id = (int)($_POST['developer'] ?? 0);
        $publisher_id = (int)($_POST['publisher'] ?? 0);
        $price = $_POST['price'] ?? '';
        $selectedPlatforms = $_POST['platforms'] ?? [];
        $selectedTags = $_POST['tags'] ?? [];
        $screenshots = $_FILES['screenshots'] ?? [];
        
        $_SESSION['addgame_form_data'] = $_POST;


        if (empty($name) || empty($description) || empty($date) || !$developer_id || !$publisher_id || $price === '' || empty($selectedTags) || empty($selectedPlatforms)) {
            $error = "Wszystkie pola są wymagane.";
        } elseif (!is_numeric($price) || $price < 0) {
            $error = 'Cena musi być liczbą większą lub równą 0.';
        } else {
            $uploaded_files = 0;
            for ($i = 0; $i < 4; $i++) {
                if (isset($screenshots['error'][$i]) && $screenshots['error'][$i] === UPLOAD_ERR_OK) {
                    $uploaded_files++;
                }
            }
            if ($uploaded_files < 4) {
                $error = 'Wymagane są 4 screenshoty.';
            } else {
                $conn->begin_transaction();
                try {
                    $nameEsc = $conn->real_escape_string($name);
                    $descriptionEsc = $conn->real_escape_string($description);

                    $insertQuery = "INSERT INTO games (name, description, date, developer_id, publisher_id, price) VALUES ('$nameEsc', '$descriptionEsc', '$date', $developer_id, $publisher_id, '$price')";
                    $conn->query($insertQuery);
                    $newGameId = $conn->insert_id;

                    foreach ($selectedPlatforms as $platformId) {
                        $pId = (int)$platformId;
                        $conn->query("INSERT INTO game_platforms (game_id, platform_id) VALUES ($newGameId, $pId)");
                    }

                    foreach ($selectedTags as $tagId) {
                        $tId = (int)$tagId;
                        $conn->query("INSERT INTO game_tags (game_id, tag_id) VALUES ($newGameId, $tId)");
                    }

                    $safeGameName = preg_replace('/[^a-z0-9_-]/', '_', strtolower($name));
                    for ($i = 0; $i < 4; $i++) {
                        if (isset($screenshots['error'][$i]) && $screenshots['error'][$i] === UPLOAD_ERR_OK) {
                            $ext = strtolower(pathinfo($screenshots['name'][$i], PATHINFO_EXTENSION));
                            $fileName = "{$safeGameName}_" . ($i + 1) . ".{$ext}";
                            move_uploaded_file($screenshots['tmp_name'][$i], "img/games/{$fileName}");
                        }
                    }
                    
                    $conn->commit();
                    
                    if (isset($_SESSION['addgame_form_data'])) {
                        unset($_SESSION['addgame_form_data']);
                    }
                    
                    header('Location: game.php?game=' . urlencode($name) . '&status=success_added');
                    exit;
                } catch (mysqli_sql_exception $exception) {
                    $conn->rollback();
                    $error = "Błąd bazy danych: " . $exception->getMessage();
                }
            }
        }
        $formData = $_POST;
    }
}

$devResult = $conn->query("SELECT id, name FROM developers ORDER BY name ASC");
$pubResult = $conn->query("SELECT id, name FROM publishers ORDER BY name ASC");
$pltResult = $conn->query("SELECT id, name FROM platforms ORDER BY name ASC");
$tagResult = $conn->query("SELECT id, name FROM tags ORDER BY name ASC");

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
    <title>Dodaj nową grę</title>
    <link rel="stylesheet" href="style/styleGame.css">
    <link rel="stylesheet" href="style/styleCommon.css">
</head>
<body>
<?php include 'header.php'; ?>

<div id="container">

    <form method="POST" enctype="multipart/form-data" style="display: contents;" id="addGameForm">
        <input type="hidden" name="add_game" value="1">

        <h1>
            Dodaj nową grę: 
            <input type="text" class="h1-input" name="name" value="<?= htmlspecialchars($formData['name'] ?? '') ?>" style="font-size: 1em; width: 60%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 5px;">
        </h1>
        
        <?php if ($success): ?>
            <p style="color: lightgreen; width: 100%;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <div class="images">
            <p style="width: 100%; margin-bottom: 10px;">Dodaj 4 screenshoty (wszystkie wymagane)</p>

            <label for="screenshot_input_0" class="main-image-container uploader-container" id="preview_0">
                <span class="uploader-label visible">Kliknij, aby wybrać główny screenshot</span>
                <input type="file" name="screenshots[]" class="uploader-input" id="screenshot_input_0" data-preview-target="preview_0" accept="image/png,image/jpeg">
            </label>

            <div class="thumbnail-container">
                <?php for($i = 1; $i < 4; $i++): ?>
                    <label for="screenshot_input_<?= $i ?>" class="thumbnail uploader-container" id="preview_<?= $i ?>">
                        <span class="uploader-label visible">Wybierz plik</span>
                        <input type="file" name="screenshots[]" class="uploader-input" id="screenshot_input_<?= $i ?>" data-preview-target="preview_<?= $i ?>" accept="image/png,image/jpeg">
                    </label>
                <?php endfor; ?>
            </div>

            <span class="detail-title" style="margin: 20px 0 10px 0; display: block;">TAGI:</span>
            <div class="tags-container">
                <?php while($row = $tagResult->fetch_assoc()):
                    $isSelected = in_array($row['id'], $formData['tags'] ?? []); ?>
                    <span class="tag-item <?= $isSelected ? 'selected' : '' ?>" data-tag-id="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></span>
                    <input type="checkbox" name="tags[]" value="<?= $row['id'] ?>" <?= $isSelected ? 'checked' : '' ?> style="display:none;">
                <?php endwhile; ?>
            </div>
        </div>

        <div class="content">
            <span class="detail-title">OPIS GRY:</span>
            <textarea name="description" class="edit-textarea" style="height: 120px; margin-bottom: 15px;"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>

            <span class="detail-title">DATA WYDANIA:</span>
            <input type="date" name="date" value="<?= htmlspecialchars($formData['date'] ?? '') ?>" style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px; color-scheme: dark; margin-bottom: 15px;">

            <div class="details-container">
                <div class="detail-item">
                    <span class="detail-title">DEWELOPER:</span>
                    <select name="developer" id="developer_select" style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px; margin-bottom: 8px;">
                        <option value="">-- Wybierz dewelopera --</option>
                        <?php while($row = $devResult->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" data-logo="<?= htmlspecialchars(find_logo_path($row['name'], 'developer') ?? '') ?>" <?= (($formData['developer'] ?? 0) == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; $devResult->data_seek(0); ?>
                    </select>
                    <div id="developer_logo_preview" class="logo-container" style="background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                    <a href="addpubdev.php?role=developer" class="cart-button add-new-button" style="display: block; text-align: center; margin-top: 8px;">Dodaj nowego</a>
                </div>
                <div class="detail-item">
                    <span class="detail-title">WYDAWCA:</span>
                    <select name="publisher" id="publisher_select" style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px; margin-bottom: 8px;">
                        <option value="">-- Wybierz wydawcę --</option>
                        <?php while($row = $pubResult->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" data-logo="<?= htmlspecialchars(find_logo_path($row['name'], 'publisher') ?? '') ?>" <?= (($formData['publisher'] ?? 0) == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; $pubResult->data_seek(0); ?>
                    </select>
                    <div id="publisher_logo_preview" class="logo-container" style="background-size: contain; background-repeat: no-repeat; background-position: center;"></div>
                     <a href="addpubdev.php?role=publisher" class="cart-button add-new-button" style="display: block; text-align: center; margin-top: 8px;">Dodaj nowego</a>
                </div>
            </div>

            <span class="detail-title" style="margin-top: 15px;">PLATFORMY:</span>
            <div class="platforms-container" style="margin-bottom: 15px;">
                <?php while($row = $pltResult->fetch_assoc()):
                    $isSelected = in_array($row['id'], $formData['platforms'] ?? []); ?>
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
            <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($formData['price'] ?? '0.00') ?>" style="width: 100%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 10px; border-radius: 3px;">
        </div>
        
        <?php if ($error): ?>
            <p style="color: red; width: 100%; text-align: center; margin-bottom: 10px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <div class="form-button-container">
            <a href="addgame.php?action=cancel" class="cart-button cancel-btn-red">Anuluj</a>
            <button type="submit" class="cart-button">Dodaj Grę</button>
        </div>
    </form>
    
</div>

<?php include 'footer.php'; ?>

<script src="skrypty.js"></script>
</body>
</html>