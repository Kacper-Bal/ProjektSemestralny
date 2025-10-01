<?php
require_once('auth.php');
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Testowy panel</title>
</head>
<body>
    <h1>Testowy panel aplikacji Steam</h1>

    <?php if ($currentUser): ?>
        <p>Zalogowany jako: <?php echo htmlspecialchars($currentUser['username']); ?> (<?php echo $currentUser['role'] ? 'Admin' : 'Użytkownik'; ?>)</p>
        <a href="logout.php">Wyloguj się</a><br><br>
    <?php else: ?>
        <a href="register.php">Rejestracja</a><br>
        <a href="login.php">Logowanie</a><br><br>
    <?php endif; ?>

    <h2>Dodawanie developerów/wydawców</h2>
    <a href="addgames.php">Dodaj grę</a><br>
    <a href="game.php?game=Dark+Souls">Wyświetl gre</a>
</body>
</html>