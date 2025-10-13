<?php
require_once('auth.php'); 
?>

<header>
    <input id="menu-toggle" type="checkbox" />

    <div id="header-top">
        
        <label for="menu-toggle" id="hamburger-menu">
            <span></span>
            <span></span>
            <span></span>
        </label>
        
        <div id="header-left">
            <a href="main.php"><img class="logo" style="height: 8vh" src="img\others\logo_steam.svg"></a>
            <div id="links">
                <div class="header-element active"><a href="store.php">SKLEP</a></div>
                <div class="header-element"><a href="community.php">SPOŁECZNOŚĆ</a></div>
                <div class="header-element"><a href="info.php">INFORMACJE</a></div>
                <div class="header-element"><a href="helpdesk.php">POMOC TECHNICZNA</a></div>
            </div>
        </div>
        
        <div class="login-panel">
            <?php if ($currentUser): ?>
                <div class="login-element"><a href="profile.php"><?php echo htmlspecialchars($currentUser['username']); ?></a></div><div class="login-element"><a href="logout.php">Wyloguj się</a></div>
            <?php else: ?>
               <div class="login-element"> <a href="login.php">Zaloguj się</a></div><div class="login-element"><a href="register.php">Zarejestruj się</a></div>
            <?php endif; ?>
        </div>

    </div>

    <?php
    $currentPage = basename($_SERVER['PHP_SELF']);
    $pagesToHideOn = ['login.php', 'register.php', 'addgames.php', 'addpubdev.php', 'gamedit.php'];
    if (!in_array($currentPage, $pagesToHideOn)):
    ?>
    <div id="header-bottom">
        <form id="searchForm" action="search.php" method="GET">
            <input type="text" name="game_name" placeholder="Wyszukaj gre">
            <input type="submit" value="Szukaj">
        </form>
    </div>
    <?php endif; ?>

</header>