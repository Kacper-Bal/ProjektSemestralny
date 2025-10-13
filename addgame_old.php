<?php
session_start();
require_once 'conn.php';
require_once 'auth.php';

if (!$currentUser || $currentUser['role'] != 1) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'save_state') {
    $_SESSION['addgame_form_data'] = $_POST;
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Form state saved.']);
    exit;
}

$formData = $_SESSION['addgame_form_data'] ?? [];
if (isset($_SESSION['addgame_form_data'])) {
    unset($_SESSION['addgame_form_data']);
}

if (isset($_SESSION["new_developer_id"])) {
    $formData['developer'] = $_SESSION["new_developer_id"];
    unset($_SESSION["new_developer_id"], $_SESSION["new_developer_name"]);
}
if (isset($_SESSION["new_publisher_id"])) {
    $formData['publisher'] = $_SESSION["new_publisher_id"];
    unset($_SESSION["new_publisher_id"], $_SESSION["new_publisher_name"]);
}

function find_logo_path($name, $role)
{
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($name));
    foreach (['png', 'jpg'] as $ext) {
        $filePath = __DIR__ . "/img/$role/$safeName.$ext";
        if (file_exists($filePath)) {
            return "img/$role/$safeName.$ext";
        }
    }
    return null;
}

$devResult = $conn->query("SELECT id, name FROM developers ORDER BY name ASC");
$pubResult = $conn->query("SELECT id, name FROM publishers ORDER BY name ASC");
$tagResult = $conn->query("SELECT id, name FROM tags ORDER BY name ASC");
$pltResult = $conn->query("SELECT id, name FROM platforms ORDER BY name ASC");

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') !== 'save_state') {
    $_SESSION['addgame_form_data'] = $_POST;
    $formData = $_POST;

    $name = trim($formData['name'] ?? '');
    $description = trim($formData['description'] ?? '');
    $developer_id = $formData['developer'] ?? 0;
    $publisher_id = $formData['publisher'] ?? 0;
    $date = $formData['date'] ?? '';
    $price = $formData['price'] ?? '';
    $selectedTags = $formData['tags'] ?? [];
    $selectedPlatforms = $formData['platforms'] ?? [];
    $screenshots = $_FILES['screenshots'] ?? [];

    if (!$name || !$description || !$developer_id || !$publisher_id || !$date || $price === '' || empty($selectedTags) || empty($selectedPlatforms)) {
        $error = 'Wszystkie pola formularza muszą być wypełnione.';
    } elseif (!is_numeric($price) || $price < 0) {
        $error = 'Cena musi być liczbą większą lub równą 0.';
    } else {
        $allowedTypes = ['image/jpeg', 'image/png'];
        for ($i = 0; $i < 4; $i++) {
            if (!isset($screenshots['error'][$i]) || $screenshots['error'][$i] !== UPLOAD_ERR_OK || !in_array(mime_content_type($screenshots['tmp_name'][$i]), $allowedTypes)) {
                $error = 'Wszystkie 4 screenshoty muszą być poprawnie przesłanymi plikami w formacie JPG lub PNG.';
                break;
            }
        }
    }

    if (!$error) {
        $safeGameName = preg_replace('/[^a-z0-9_-]/', '_', strtolower($name));

        for ($i = 0; $i < 4; $i++) {
            $ext = pathinfo($screenshots['name'][$i], PATHINFO_EXTENSION);
            $fileName = "{$safeGameName}_" . ($i + 1) . ".{$ext}";
            move_uploaded_file($screenshots['tmp_name'][$i], "img/games/$fileName");
        }

        $query = "INSERT INTO games (name, description, developer_id, publisher_id, date, price) 
                  VALUES ('$name', '$description', $developer_id, $publisher_id, '$date', $price)";
        if ($conn->query($query)) {
            $newGameId = $conn->insert_id;

            foreach ($selectedTags as $tagId) {
                $tagId = (int)$tagId;
                $conn->query("INSERT INTO game_tags (game_id, tag_id) VALUES ($newGameId, $tagId)");
            }

            foreach ($selectedPlatforms as $platformId) {
                $platformId = (int)$platformId;
                $conn->query("INSERT INTO game_platforms (game_id, platform_id) VALUES ($newGameId, $platformId)");
            }

            unset($_SESSION['addgame_form_data']);
            header('Location: index.php');
            exit;
        } else {
            $error = 'Błąd bazy danych: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj nową grę</title>
    <link rel="stylesheet" href="style/styleGame.css">
</head>
<body>

    <div class="container">
        <header class="form-header">
            <h1>Dodaj nową grę</h1>
        </header>

        <?php if ($error): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="game-form">

            <div class="form-group">
                <label for="name">Nazwa gry</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Opis</label>
                <textarea id="description" name="description" rows="5" required><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group dev-pub-container">
                <div class="selector-container">
                    <label for="developer_select">Producent</label>
                    <select name="developer" id="developer_select" required>
                        <option value="">-- wybierz --</option>
                        <?php while ($row = $devResult->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" data-logo="<?= htmlspecialchars(find_logo_path($row['name'], 'developer') ?? '') ?>" <?= (($formData['developer'] ?? 0) == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; $devResult->data_seek(0); ?>
                    </select>
                    <a href="addpubdev.php?role=developer" class="add-new-link">Dodaj nowego</a>
                </div>
                <div id="developer_logo_preview" class="logo-preview-box"></div>
            </div>

            <div class="form-group dev-pub-container">
                <div class="selector-container">
                    <label for="publisher_select">Wydawca</label>
                    <select name="publisher" id="publisher_select" required>
                        <option value="">-- wybierz --</option>
                        <?php while ($row = $pubResult->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" data-logo="<?= htmlspecialchars(find_logo_path($row['name'], 'publisher') ?? '') ?>" <?= (($formData['publisher'] ?? 0) == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; $pubResult->data_seek(0); ?>
                    </select>
                    <a href="addpubdev.php?role=publisher" class="add-new-link">Dodaj nowego</a>
                </div>
                <div id="publisher_logo_preview" class="logo-preview-box"></div>
            </div>

            <div class="form-group">
                <label>Tagi</label>
                <div class="tags-container">
                    <?php while ($row = $tagResult->fetch_assoc()):
                        $isSelected = in_array($row['id'], $formData['tags'] ?? []); ?>
                        <span class="tag-item <?= $isSelected ? 'selected' : '' ?>" data-tag-id="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></span>
                        <input type="checkbox" name="tags[]" value="<?= $row['id'] ?>" <?= $isSelected ? 'checked' : '' ?> style="display:none;">
                    <?php endwhile; $tagResult->data_seek(0); ?>
                </div>
            </div>

            <div class="form-group">
                <label>Platformy</label>
                <div class="platforms-container">
                    <?php while ($row = $pltResult->fetch_assoc()):
                        $isSelected = in_array($row['id'], $formData['platforms'] ?? []); ?>
                        <span class="platform-item <?= $isSelected ? 'selected' : '' ?>" 
                              data-platform-id="<?= $row['id'] ?>" 
                              data-platform-name="<?= htmlspecialchars($row['name']) ?>"
                              title="<?= htmlspecialchars($row['name']) ?>">
                            </span>
                        <input type="checkbox" name="platforms[]" value="<?= $row['id'] ?>" <?= $isSelected ? 'checked' : '' ?> style="display:none;">
                    <?php endwhile; $pltResult->data_seek(0); ?>
                </div>
            </div>

            <div class="form-group">
                <label>Screenshoty (4 wymagane)</label>
                <div class="screenshots-container">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div class="screenshot-upload-box" id="preview_<?= $i ?>">
                            <label for="screenshot_input_<?= $i ?>">Kliknij, aby dodać</label>
                            <input type="file" name="screenshots[]" class="screenshot-input" id="screenshot_input_<?= $i ?>" data-preview-target="preview_<?= $i ?>" accept="image/png,image/jpeg" required>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="date">Data wydania</label>
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($formData['date'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="price">Cena</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?= htmlspecialchars($formData['price'] ?? '0.00') ?>" required>
            </div>

            <button type="submit" class="submit-button">Dodaj grę</button>
        </form>
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

    document.querySelector('.tags-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('tag-item')) {
            const span = e.target;
            const checkbox = this.querySelector(`input[value="${span.dataset.tagId}"]`);
            span.classList.toggle('selected');
            checkbox.checked = !checkbox.checked;
        }
    });

    document.querySelector('.platforms-container').addEventListener('click', function(e) {
        const span = e.target.closest('.platform-item');
        if (span) {
            const checkbox = this.querySelector(`input[value="${span.dataset.platformId}"]`);
            span.classList.toggle('selected');
            checkbox.checked = !checkbox.checked;
        }
    });

    document.querySelectorAll('.screenshot-input').forEach(input => {
        input.addEventListener('change', function() {
            const preview = document.getElementById(this.dataset.previewTarget);
            const label = preview.querySelector('label');
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.style.backgroundImage = `url('${e.target.result}')`;
                    if (label) label.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.backgroundImage = '';
                if (label) label.style.display = 'flex';
            }
        });
    });

    document.querySelectorAll('a.add-new-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.querySelector('form.game-form');
            const formData = new FormData(form);
            const dest = this.href;
            fetch('addgames.php?action=save_state', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(() => { window.location.href = dest; })
                .catch(() => { window.location.href = dest; });
        });
    });
});
</script>
<script src="skrypty.js"></script>
</body>
</html>