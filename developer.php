<?php
session_start();
require_once 'conn.php';
require_once 'auth.php';

$developerName = $_GET['name'] ?? null;
$pageTitle = 'Nie znaleziono dewelopera';
$developer = null;
$headerColor = '#141E2A'; 

if (!$developerName) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, name, logo_color FROM developers WHERE name = ?");
$stmt->bind_param("s", $developerName);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $developer = $result->fetch_assoc();
    $pageTitle = htmlspecialchars($developer['name']);
    if (!empty($developer['logo_color'])) {
        $headerColor = htmlspecialchars($developer['logo_color']);
    }
}

$gamesResult = null;
$gamePlatforms = [];
$gameTags = [];

if ($developer) {
    $developerId = $developer['id'];

    $stmt = $conn->prepare("SELECT g.id, g.name, g.date, pub.name AS publisher_name
               FROM games g 
               LEFT JOIN publishers pub ON g.publisher_id = pub.id
               WHERE g.developer_id = ? 
               ORDER BY g.name ASC");
    $stmt->bind_param("i", $developerId);
    $stmt->execute();
    $gamesResult = $stmt->get_result();

    if ($gamesResult && $gamesResult->num_rows > 0) {
        $gameIds = [];
        $gamesData = []; 
        while($row = $gamesResult->fetch_assoc()) {
            $gamesData[] = $row;
            $gameIds[] = $row['id'];
        }

        if (!empty($gameIds)) {
            $count = count($gameIds);
            $placeholders = implode(',', array_fill(0, $count, '?'));
            $types = str_repeat('i', $count);

            $platformsQuery = "SELECT gp.game_id, p.name FROM game_platforms gp JOIN platforms p ON gp.platform_id = p.id WHERE gp.game_id IN ($placeholders)";
            $stmt = $conn->prepare($platformsQuery);
            $stmt->bind_param($types, ...$gameIds);
            $stmt->execute();
            $platformsResult = $stmt->get_result();

            if ($platformsResult) {
                while ($row = $platformsResult->fetch_assoc()) {
                    $gamePlatforms[$row['game_id']][] = $row['name'];
                }
            }

            $tagsQuery = "SELECT gt.game_id, t.name FROM game_tags gt JOIN tags t ON gt.tag_id = t.id WHERE gt.game_id IN ($placeholders)";
            $stmt = $conn->prepare($tagsQuery);
            $stmt->bind_param($types, ...$gameIds);
            $stmt->execute();
            $tagsResult = $stmt->get_result();

            if ($tagsResult) {
                while ($row = $tagsResult->fetch_assoc()) {
                    $gameTags[$row['game_id']][] = $row['name'];
                }
            }
        }
    }
}

$developerImage = 'img\avatars\default_avatar.png';
if ($developer) {
    $safeDeveloperName = preg_replace('/[^a-z0-9_]/', '_', strtolower($developer['name']));
    
    $extensions = ['jpg', 'jpeg', 'png', 'png']; 
        foreach ($extensions as $ext) {
        $testPath = "img/developer/{$safeDeveloperName}.{$ext}";
        if (file_exists($testPath)) {
            $developerImage = $testPath;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="style/styleProfile.css">
    <link rel="icon" type="image/png" href="img/others/logo_steam_icon.png">

</head>
<body>
    <?php include('header.php'); ?>

    <?php if ($developer): ?>
        <div class="profile-header-banner" style="--header-bg: <?php echo $headerColor; ?>; background-color: <?php echo $headerColor; ?>;">
            <div class="profile-header-content">
                <img 
                    src="<?php echo htmlspecialchars($developerImage); ?>" 
                    alt="Logo <?php echo htmlspecialchars($developer['name']); ?>"
                    class="profile-avatar"
                >
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($developer['name']); ?></h1>
                </div>
            </div>
        </div>
        
        <div id="profile-container-main">
            
            <div class="sort-slider profile-tab-slider">
                <div class="sort-options">
                    <button class="sort-option active" data-tab="games">GRY (<?php echo isset($gamesData) ? count($gamesData) : 0; ?>)</button>
                </div>
            </div>

            <div class="profile-tab-content active" id="profile-games-content">
                <?php if (!empty($gamesData)): ?>
                    <div class="profile-games-list"> 
                        <?php foreach ($gamesData as $game): ?>
                            <?php
                                $safeGameName = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($game['name']));
                                $imagePath = "img/games/{$safeGameName}_1.jpg";
                                if (!file_exists($imagePath)) {
                                    $files = glob("img/games/{$safeGameName}_*.{jpg,png,jpeg}", GLOB_BRACE);
                                    $imagePath = !empty($files) ? $files[0] : 'img/others/placeholder.jpg';
                                }
                                $gameUrl = 'game.php?game=' . urlencode($game['name']);
                            ?>
                            <div class="profile-game-tile">
                                <a href="<?php echo $gameUrl; ?>" class="game-tile-image">
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($game['name']); ?>">
                                </a>
                                
                                <div class="game-tile-info">
                                    <h3 class="game-tile-title">
                                        <a href="<?php echo $gameUrl; ?>" class="profile-content-link">
                                            <?php echo htmlspecialchars($game['name']); ?>
                                        </a>
                                    </h3>
                                    <hr class="game-tile-divider">
                                    
                                    <div class="game-tile-details">
                                        <p><strong>DATA WYDANIA:</strong> <?php echo htmlspecialchars($game['date']); ?></p>
                                        <p>
                                            <strong>WYDAWCA:</strong> 
                                            <a href="publisher.php?name=<?php echo urlencode($game['publisher_name']); ?>" class="profile-content-link">
                                                <?php echo htmlspecialchars($game['publisher_name']); ?>
                                            </a>
                                        </p>
                                        <div class="game-tile-footer">
                                            <div class="game-tile-platforms">
                                                <?php if (!empty($gamePlatforms[$game['id']])): ?>
                                                    <?php foreach ($gamePlatforms[$game['id']] as $platformName): ?>
                                                        <span class="platform-item" data-platform-name="<?php echo htmlspecialchars($platformName); ?>" title="<?php echo htmlspecialchars($platformName); ?>"></span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <span class="footer-divider">|</span>
                                            <div class="game-tile-tags">
                                                <?php if (!empty($gameTags[$game['id']])): ?>
                                                    <?php echo implode(', ', array_map('htmlspecialchars', $gameTags[$game['id']])); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="profile-tab-placeholder">Ten deweloper nie stworzył jeszcze żadnych gier.</p>
                <?php endif; ?>
            </div>

            <hr class="divider">
        </div>

    <?php else: ?>
        <div id="profile-container-main">
            <h1>Nie znaleziono dewelopera</h1>
            <p>Deweloper o nazwie "<?php echo htmlspecialchars($publisherName); ?>" nie został znaleziony.</p>
        </div>
    <?php endif; ?>

    <?php include('footer.php'); ?>
    <script src="js/user.js" defer></script> 
    <script src="js/slider.js" defer></script>
</body>
</html>