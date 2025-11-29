<?php
require_once('auth.php');

$currentPage = basename($_SERVER['PHP_SELF']);

function isNavLinkActive($pageName, $currentPage) {
    $specificNavPages = ['community.php', 'information.php', 'helpdesk.php', 'addgame.php', 'promotion.php'];

    if (in_array($currentPage, $specificNavPages)) {
        return $pageName === $currentPage;
    } else {
        return $pageName === 'store.php';
    }
}
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
            <a href="index.php"><img class="logo" style="height: 8vh" src="img/others/logo_steam.svg"></a>
            <div id="links">
                <div class="header-element <?= isNavLinkActive('store.php', $currentPage) ? 'active' : '' ?>"><a href="store.php">SKLEP</a></div>
                <div class="header-element <?= isNavLinkActive('community.php', $currentPage) ? 'active' : '' ?>"><a href="community.php">SPOŁECZNOŚĆ</a></div>
                <div class="header-element <?= isNavLinkActive('information.php', $currentPage) ? 'active' : '' ?>"><a href="information.php">INFORMACJE</a></div>
                <div class="header-element <?= isNavLinkActive('helpdesk.php', $currentPage) ? 'active' : '' ?>"><a href="helpdesk.php">POMOC TECHNICZNA</a></div>
            
                <?php if (isset($currentUser['role']) && $currentUser['role'] == 1): ?>
        
                    <div class="header-element <?= isNavLinkActive('addgame.php', $currentPage) ? 'active' : '' ?>">
                        <a href="addgame.php" style="display: flex; align-items: center;">
                            <svg style="width: 32px; height: 32px;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 14H12M12 14H14M12 14V16M12 14V12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M2 6.94975C2 6.06722 2 5.62595 2.06935 5.25839C2.37464 3.64031 3.64031 2.37464 5.25839 2.06935C5.62595 2 6.06722 2 6.94975 2C7.33642 2 7.52976 2 7.71557 2.01738C8.51665 2.09229 9.27652 2.40704 9.89594 2.92051C10.0396 3.03961 10.1763 3.17633 10.4497 3.44975L11 4C11.8158 4.81578 12.2237 5.22367 12.7121 5.49543C12.9804 5.64471 13.2651 5.7626 13.5604 5.84678C14.0979 6 14.6747 6 15.8284 6H16.2021C18.8345 6 20.1506 6 21.0062 6.76946C21.0849 6.84024 21.1598 6.91514 21.2305 6.99383C22 7.84935 22 9.16554 22 11.7979V14C22 17.7712 22 19.6569 20.8284 20.8284C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.8284C2 19.6569 2 17.7712 2 14V6.94975Z" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="header-element <?= isNavLinkActive('promotion.php', $currentPage) ? 'active' : '' ?>">
                        <a href="promotion.php" style="display: flex; align-items: center;">
                            <svg style="width: 32px; height: 32px;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 3.91992H6C3.79086 3.91992 2 5.71078 2 7.91992V17.9199C2 20.1291 3.79086 21.9199 6 21.9199H18C20.2091 21.9199 22 20.1291 22 17.9199V7.91992C22 5.71078 20.2091 3.91992 18 3.91992Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 17.9199L17 7.91992" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8 11.9199C9.10457 11.9199 10 11.0245 10 9.91992C10 8.81535 9.10457 7.91992 8 7.91992C6.89543 7.91992 6 8.81535 6 9.91992C6 11.0245 6.89543 11.9199 8 11.9199Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M16 17.9199C17.1046 17.9199 18 17.0245 18 15.9199C18 14.8154 17.1046 13.9199 16 13.9199C14.8954 13.9199 14 14.8154 14 15.9199C14 17.0245 14.8954 17.9199 16 17.9199Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </div>

                <?php endif; ?>
            </div>
        </div>
        <div id="header-right">
    
    <?php if (!$currentUser): ?>
        <a href="login.php" class="auth-btn">Log In</a>
        <a href="register.php" class="auth-btn">Sign Up</a>
    
        <?php else: ?>
        <a href="user.php?user=<?= urlencode($currentUser['username']) ?>" class="auth-btn profile-group">
            <span class="text-part">
                <?= htmlspecialchars($currentUser['username']) ?>
            </span>
            <div class="img-part">
                <?php 
                    $avatarFile = $currentUser['avatar_filename'] ?? 'default_avatar.png';
                    $avatarPath = "img/avatars/" . $avatarFile;
                ?>
                <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Avatar">
            </div>
        </a>

        <a href="logout.php" class="auth-btn logout-btn">
            Sign Out
        </a>

    <?php endif; ?>
</div>
        

    </div>

    <?php
    $pagesToHideSearchOn = ['login.php', 'promotion.php', 'register.php', 'addgame.php', 'addpubdev.php', 'store.php', 'gamedit.php'];
    if (!in_array($currentPage, $pagesToHideSearchOn)):
    ?>
    <div id="header-bottom">
        <form id="searchForm" action="search.php" method="GET">
            <input type="text" name="query" placeholder="Wyszukaj...">
            <input type="submit" value="Szukaj">
        </form>
    </div>
    <?php endif; ?>

</header>