<?php
require_once('auth.php');
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Testowy panel</title>
    <link rel="stylesheet" href="style/styleCommon.css">
</head>
<body>
    <?php include('header.php'); ?>


    <h1 style="margin-top: 100px">Testowy panel aplikacji Steam</h1>

    <?php if ($currentUser): ?>
        <p>Zalogowany jako: <?php echo htmlspecialchars($currentUser['username']); ?> (<?php echo $currentUser['role'] ? 'Admin' : 'Użytkownik'; ?>)</p>
        <a href="logout.php">Wyloguj się</a><br><br>
    <?php else: ?>
        <a href="register.php">Rejestracja</a><br>
        <a href="login.php">Logowanie</a><br><br>
    <?php endif; ?>

    <h2>Dodawanie developerów/wydawców</h2>
    <a href="addgame.php">Dodaj grę</a><br>
    <a href="game.php?game=Dark+Souls">Wyświetl gre</a>

    <?php include('footer.php'); ?>
</body>
</html>