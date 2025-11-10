<?php
session_start();
require_once 'conn.php';
require_once 'auth.php';

if (!$currentUser || $currentUser['role'] != 1) {
    header('Location: index.php');
    exit;
}

$role = $_GET['role'] ?? null;
if (!in_array($role, ['developer', 'publisher'])) {
    header('Location: addgame.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $logo = $_FILES['logo'] ?? null;

    if ($name === '') {
        $error = 'Nazwa jest wymagana.';
    } 
    elseif (!$logo || $logo['error'] !== UPLOAD_ERR_OK) {
        $error = 'Logo jest wymagane.';
    } else {

        $ext = strtolower(pathinfo($logo['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'png'];

        if (!in_array($ext, $allowedExt)) {
            $error = 'Dozwolone typy obrazów: JPG, PNG.';
        } else {
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($name));
            $table = $role . 's';
            $filePath = "img/{$role}/{$safeName}.{$ext}";

            if (!move_uploaded_file($logo['tmp_name'], $filePath)) {
                $error = 'Nie udało się zapisać pliku.';
            } else {
                $nameEsc = $conn->real_escape_string($name);
                $query = "INSERT INTO `$table` (`name`) VALUES ('$nameEsc')";
                if ($conn->query($query)) {
                    $newId = $conn->insert_id;
                    $_SESSION["new_{$role}_id"] = $newId;
                    $_SESSION["new_{$role}_name"] = $name;

                    header('Location: addgame.php');
                    exit;
                } else {
                    $error = 'Błąd bazy danych: ' . $conn->error;
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title><?php echo "Dodaj ".htmlspecialchars(ucfirst($role)) ."a"; ?></title>
    <link rel="stylesheet" href="style/styleGame.css">
    <link rel="stylesheet" href="style/styleCommon.css">
</head>
<body>
<?php include('header.php'); ?>

<div id="container">
<h1><?php echo "Dodaj nowego ".htmlspecialchars(ucfirst($role)) ."a"; ?></h1>

<?php if ($error) echo "<p style='color:red;'>" . htmlspecialchars($error) . "</p>"; ?>

<form method="POST" enctype="multipart/form-data">
    <h2 style="padding-top: 20px;">Nazwa: <input type="text" name="name" style="font-size: 0.8em; width: 60%; background-color: #2c313a; border: 1px solid #434953; color: white; padding: 5px;" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required></h2>

    <h2 style="padding-top: 20px;">Logo:</h2>
    <label for="logo_input" class="uploader-container logo-uploader" id="logo_preview_container">
        <span class="uploader-label visible">Wybierz plik</span>
        <input type="file" name="logo" class="uploader-input" id="logo_input" data-preview-target="logo_preview_container" accept="image/png,image/jpeg" required>
    </label>


    <div style="padding-top: 20px;" class="form-button-container">
            <a href="addgame.php?action=cancel" class="cart-button cancel-btn-red">Anuluj</a>
            <button type="submit" class="cart-button">Dodaj Grę</button>
        </div>
</form>

    </div>  

<?php include('footer.php'); ?>
<script src="skrypty.js"></script>
</body>
</html>