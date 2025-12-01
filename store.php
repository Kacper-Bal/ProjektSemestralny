<?php
require_once('conn.php');
require_once('auth.php');

$preSelectedPlatformName = isset($_GET['platform']) ? strtolower(trim($_GET['platform'])) : null;
$preSelectedTagName = isset($_GET['tag']) ? strtolower(trim($_GET['tag'])) : null;

// NOWE: Pobieramy frazę wyszukiwania przekazaną z nagłówka
$preSelectedSearch = isset($_GET['search']) ? trim($_GET['search']) : '';

function getGameImage($gameName) {
    $name = strtolower($gameName);
    
    $filename = preg_replace('/[^a-z0-9\-]/', '_', $name);
   
    $possiblePath = "img/games/{$filename}_1.jpg";
    if (file_exists($possiblePath)) {
        return $possiblePath;
    }
    
    $simpleName = str_replace(' ', '_', $name);
    $simplePath = "img/games/{$simpleName}_1.jpg";
    if (file_exists($simplePath)) {
        return $simplePath;
    }

    return "img/others/loginBackground.jpg"; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_filter'])) {
    
    $searchText = $_POST['search'] ?? '';
    $maxPrice = $_POST['price'] ?? 1000;
    $tags = $_POST['tags'] ?? [];
    $platforms = $_POST['platforms'] ?? [];
    $publishers = $_POST['publishers'] ?? [];
    $developers = $_POST['developers'] ?? [];

    $sql = "SELECT g.id, g.name, g.price, 
            MAX(p.discount_percent) as discount_percent 
            FROM games g
            LEFT JOIN promotion_games pg ON g.id = pg.game_id
            LEFT JOIN promotions p ON pg.promotion_id = p.id 
            AND NOW() BETWEEN p.start_date AND p.end_date";

    $params = [];
    $types = "";
    $joins = [];
    $wheres = [];

    $wheres[] = "(g.price * (1 - (IFNULL(p.discount_percent, 0) / 100))) <= ?";
    $params[] = $maxPrice;
    $types .= "d";

    if (!empty($searchText)) {
        $wheres[] = "g.name LIKE ?";
        $params[] = "%" . $searchText . "%";
        $types .= "s";
    }

    if (!empty($platforms)) {
        $joins[] = "JOIN game_platforms gp ON g.id = gp.game_id";
        $inQuery = implode(',', array_fill(0, count($platforms), '?'));
        $wheres[] = "gp.platform_id IN ($inQuery)";
        foreach ($platforms as $pid) { $params[] = $pid; $types .= "i"; }
    }

    if (!empty($tags)) {
        $joins[] = "JOIN game_tags gt ON g.id = gt.game_id";
        $inQuery = implode(',', array_fill(0, count($tags), '?'));
        $wheres[] = "gt.tag_id IN ($inQuery)";
        foreach ($tags as $tid) { $params[] = $tid; $types .= "i"; }
    }

    if (!empty($publishers)) {
        $inQuery = implode(',', array_fill(0, count($publishers), '?'));
        $wheres[] = "g.publisher_id IN ($inQuery)";
        foreach ($publishers as $pubId) { $params[] = $pubId; $types .= "i"; }
    }

    if (!empty($developers)) {
        $inQuery = implode(',', array_fill(0, count($developers), '?'));
        $wheres[] = "g.developer_id IN ($inQuery)";
        foreach ($developers as $devId) { $params[] = $devId; $types .= "i"; }
    }

    if (!empty($joins)) { $sql .= " " . implode(" ", array_unique($joins)); }
    if (!empty($wheres)) { $sql .= " WHERE " . implode(" AND ", $wheres); }
    $sql .= " GROUP BY g.id ";
    $sql .= " ORDER BY g.name ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $gameId = (int)$row['id']; 
            $name = htmlspecialchars($row['name']);
            $price = (float)$row['price'];
            $discount = $row['discount_percent'];
            
            $imgSrc = getGameImage($row['name']);

            if ($discount) {
                $newPrice = $price - ($price * ($discount / 100));
                $priceHtml = '
                    <span class="discount-badge">-' . $discount . '%</span>
                    <div class="price-column">
                        <div class="old-price">' . number_format($price, 2) . ' zł</div>
                        <div class="discounted-price">' . number_format($newPrice, 2) . ' zł</div>
                    </div>';
            } else {
                $priceHtml = '<div class="original-price">' . number_format($price, 2) . ' zł</div>';
            }

            echo '
            <a href="game.php?game=' . urlencode($row['name']) . '" class="game-card">
                <img src="' . $imgSrc . '" alt="' . $name . '" class="game-card-img" loading="lazy">
                
                <div class="game-info-overlay">
                    <div class="game-title">' . $name . '</div>
                    <div class="game-price-container">
                        ' . $priceHtml . '
                    </div>
                </div>
            </a>';
        }
    } else {
        echo '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--color-text-secondary);">Nie znaleziono gier spełniających kryteria.</div>';
    }
    exit;
}

require_once('header.php');

$sqlTags = "SELECT * FROM tags ORDER BY name ASC";
$resultTags = $conn->query($sqlTags);
$sqlPlatforms = "SELECT * FROM platforms ORDER BY name ASC";
$resultPlatforms = $conn->query($sqlPlatforms);
$sqlPublishers = "SELECT * FROM publishers ORDER BY name ASC";
$resultPublishers = $conn->query($sqlPublishers);
$sqlDevelopers = "SELECT * FROM developers ORDER BY name ASC";
$resultDevelopers = $conn->query($sqlDevelopers);

$sqlMaxPrice = "SELECT MAX(price) as max_price FROM games";
$resultMaxPrice = $conn->query($sqlMaxPrice);
$maxPriceData = $resultMaxPrice->fetch_assoc();
$maxPriceLimit = ceil($maxPriceData['max_price'] ?? 300);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Sklep</title>
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="style/styleStore.css">
    <link rel="icon" type="image/png" href="img/others/logo_steam_icon.png">

</head>
<body>

<div class="store-container">
    <aside class="store-sidebar">
        <div class="filter-group">
            <h3>Cena maksymalna: <span id="priceValue"><?php echo $maxPriceLimit; ?></span> zł</h3>
            <input type="range" id="priceRange" min="0" max="<?php echo $maxPriceLimit; ?>" value="<?php echo $maxPriceLimit; ?>" class="slider">
        </div>

        <div class="filter-group">
            <h3>Platformy</h3>
            <div class="platform-grid">
                <?php while($row = $resultPlatforms->fetch_assoc()): 
                    $platformKey = strtolower($row['name']);
                    $isChecked = ($preSelectedPlatformName === $platformKey) ? 'checked' : '';
                ?>
                    <label class="platform-option" title="<?php echo htmlspecialchars($row['name']); ?>">
                        <input type="checkbox" class="filter-checkbox" name="platforms" value="<?php echo $row['id']; ?>" <?php echo $isChecked; ?>>
                        <div class="platform-icon-box" data-platform="<?php echo $platformKey; ?>"></div>
                    </label>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="filter-group">
            <h3>Tagi</h3>
            <?php while($row = $resultTags->fetch_assoc()): 
                $tagNameLower = strtolower($row['name']);
                $isChecked = ($preSelectedTagName === $tagNameLower) ? 'checked' : '';
            ?>
                <label class="checkbox-container">
                    <input type="checkbox" class="filter-checkbox" name="tags" value="<?php echo $row['id']; ?>" <?php echo $isChecked; ?>>
                    <span class="checkmark"></span>
                    <span class="filter-text"><?php echo htmlspecialchars($row['name']); ?></span>
                </label>
            <?php endwhile; ?>
        </div>

        <div class="filter-group">
            <details>
                <summary>Wydawcy</summary>
                <div class="details-content">
                    <?php while($row = $resultPublishers->fetch_assoc()): ?>
                        <label class="checkbox-container">
                            <input type="checkbox" class="filter-checkbox" name="publishers" value="<?php echo $row['id']; ?>">
                            <span class="checkmark"></span>
                            <span class="filter-text"><?php echo htmlspecialchars($row['name']); ?></span>
                        </label>
                    <?php endwhile; ?>
                </div>
            </details>
        </div>

        <div class="filter-group">
            <details>
                <summary>Deweloperzy</summary>
                <div class="details-content">
                    <?php while($row = $resultDevelopers->fetch_assoc()): ?>
                        <label class="checkbox-container">
                            <input type="checkbox" class="filter-checkbox" name="developers" value="<?php echo $row['id']; ?>">
                            <span class="checkmark"></span>
                            <span class="filter-text"><?php echo htmlspecialchars($row['name']); ?></span>
                        </label>
                    <?php endwhile; ?>
                </div>
            </details>
        </div>
    </aside>

    <main class="store-content">
        <div class="store-search-bar">
            <input type="text" id="searchInput" placeholder="Szukaj gry według nazwy..." value="<?php echo htmlspecialchars($preSelectedSearch); ?>">
        </div>

        <div id="gamesGrid" class="games-grid">
            </div>
    </main>
</div>

<?php require_once('footer.php'); ?>
<script src="js/store.js"></script>
</body>
</html>