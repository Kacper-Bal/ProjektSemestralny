<?php
session_start();
require_once 'conn.php';
require_once 'auth.php';

// Sprawdzenie czy użytkownik jest adminem
if (!$currentUser || $currentUser['role'] != 1) {
    header('Location: index.php');
    exit;
}

// Sprawdzenie parametru role
$role = $_GET['role'] ?? null;
if (!in_array($role, ['developer', 'publisher'])) {
    header('Location: addgames.php');
    exit;
}

$error = '';

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $logo = $_FILES['logo'] ?? null;

    // Walidacja nazwy
    if ($name === '') {
        $error = 'Nazwa jest wymagana.';
    } 
    // Walidacja pliku
    elseif (!$logo || $logo['error'] !== UPLOAD_ERR_OK) {
        $error = 'Logo jest wymagane.';
    } else {

        // Zapis pliku
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
                // Wstawienie do bazy danych
                $nameEsc = $conn->real_escape_string($name);
                $query = "INSERT INTO `$table` (`name`) VALUES ('$nameEsc')";
                if ($conn->query($query)) {
                    $newId = $conn->insert_id;
                    $_SESSION["new_{$role}_id"] = $newId;
                    $_SESSION["new_{$role}_name"] = $name;

                    // Powrót do addgames.php
                    header('Location: addgames.php');
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
    <title>Dodaj <?php echo htmlspecialchars(ucfirst($role)); ?></title>
    <style>
        .logo-box {
            width: 120px; 
            height: 120px; 
            border: 2px dashed #ccc;
            background-size: contain; 
            background-repeat: no-repeat; 
            background-position: center;
            display: inline-block; 
            vertical-align: middle; 
            margin-left: 10px;
        }
    </style>
</head>
<body>
<h1>Dodaj <?php echo htmlspecialchars(ucfirst($role)); ?></h1>

<?php if ($error) echo "<p style='color:red;'>" . htmlspecialchars($error) . "</p>"; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Nazwa<br>
        <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
    </label>
    <br><br>

    <label>Logo<br>
        <input type="file" name="logo" id="logo_input" accept="image/png,image/jpeg" required>
    </label>
    <div id="logo_preview" class="logo-box"></div>
    <br>

    <button type="submit">Dodaj</button>
    <a href="addgames.php">Anuluj</a>
</form>

<script>
const input = document.getElementById('logo_input');
const preview = document.getElementById('logo_preview');

input && input.addEventListener('change', function() {
    const file = this.files[0];
    if (!file || !file.type.startsWith('image/')) {
        preview.style.backgroundImage = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        preview.style.backgroundImage = `url('${e.target.result}')`;
    };
    reader.readAsDataURL(file);
});
</script>
</body>
</html>