<?php
session_start(); 
require_once 'conn.php'; 
require_once 'auth.php'; 

function get_average_color($filepath) {
    $default_color = '#141E2A'; 
    if (!file_exists($filepath)) return $default_color;
    try {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $image = null;
        if ($extension === 'png') $image = @imagecreatefrompng($filepath);
        elseif ($extension === 'jpg' || $extension === 'jpeg') $image = @imagecreatefromjpeg($filepath);
        else return $default_color;
        if (!$image) return $default_color;
        $thumb = imagecreatetruecolor(1, 1);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, 1, 1, imagesx($image), imagesy($image));
        $rgb = imagecolorat($thumb, 0, 0);
        $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
        imagedestroy($image); imagedestroy($thumb);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    } catch (Exception $e) {
        return $default_color;
    }
}

$profileUsername = $_GET['user'] ?? null;
$pageTitle = 'Nie znaleziono użytkownika';
$profileUser = null;
$error = $_SESSION['profile_error'] ?? null;
$success = $_SESSION['profile_success'] ?? null;
unset($_SESSION['profile_error'], $_SESSION['profile_success']);

if (!$profileUsername) {
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, username, avatar_filename, profile_color, role FROM users WHERE username = ?");
$stmt->bind_param("s", $profileUsername);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $profileUser = $result->fetch_assoc();
    $pageTitle = htmlspecialchars($profileUser['username']);
}

$isCurrentUserProfile = ($currentUser && $profileUser && $currentUser['user_id'] == $profileUser['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isCurrentUserProfile) {
    
    $newUsername = trim($_POST['new_username'] ?? $profileUser['username']);
    $newAvatar = $_FILES['avatar'] ?? null;
    $userId = $profileUser['id'];
    
    $updateClauses = [];
    $updateParams = [];
    $paramTypes = '';
    
    $redirectUrl = 'user.php?user=' . urlencode($profileUser['username']);

    try {
        if ($newUsername !== $profileUser['username']) {
            if (empty($newUsername)) {
                throw new Exception("Nazwa użytkownika nie może być pusta.");
            }
            $checkUserQuery = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $checkUserQuery->bind_param('si', $newUsername, $userId);
            $checkUserQuery->execute();
            $checkResult = $checkUserQuery->get_result();
            
            if ($checkResult->num_rows > 0) {
                throw new Exception("Ta nazwa użytkownika jest już zajęta.");
            }
            
            $updateClauses[] = "username = ?";
            $updateParams[] = $newUsername;
            $paramTypes .= 's';
            $redirectUrl = 'user.php?user=' . urlencode($newUsername);
        }

        if ($newAvatar && $newAvatar['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($newAvatar['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'png', 'jpeg'];
            
            if (!in_array($ext, $allowedExt)) {
                throw new Exception("Dozwolone typy awatarów: JPG, PNG, JPEG.");
            }
            
            $newFileName = "user_{$userId}_" . time() . ".{$ext}";
            $filePath = "img/avatars/{$newFileName}";

            if (!move_uploaded_file($newAvatar['tmp_name'], $filePath)) {
                throw new Exception("Nie udało się zapisać nowego awatara.");
            }

            $newColor = get_average_color($filePath);

            $updateClauses[] = "avatar_filename = ?";
            $updateParams[] = $newFileName;
            $paramTypes .= 's';
            
            $updateClauses[] = "profile_color = ?";
            $updateParams[] = $newColor;
            $paramTypes .= 's';

            if ($profileUser['avatar_filename'] && $profileUser['avatar_filename'] !== 'default_avatar.png') {
                $oldAvatarPath = "img/avatars/" . $profileUser['avatar_filename'];
                if (file_exists($oldAvatarPath)) {
                    @unlink($oldAvatarPath);
                }
            }
        }

        if (!empty($updateClauses)) {
            $sql = "UPDATE users SET " . implode(', ', $updateClauses) . " WHERE id = ?";
            $updateParams[] = $userId;
            $paramTypes .= 'i';
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($paramTypes, ...$updateParams);
            
            if (!$stmt->execute()) {
                throw new Exception("Błąd bazy danych: " . $stmt->error);
            }
            
            $_SESSION['profile_success'] = "Profil został pomyślnie zaktualizowany!";
        } else {
            $_SESSION['profile_success'] = "Nie wprowadzono żadnych zmian.";
        }

    } catch (Exception $e) {
        $_SESSION['profile_error'] = $e->getMessage();
    }
    if (isset($_POST['recharge_amount'])) {
        $amount = (float)$_POST['recharge_amount'];
        $allowed_amounts = [20, 50, 100, 200, 500, 1000];

        if (in_array($amount, $allowed_amounts)) {
            try {
                $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->bind_param('di', $amount, $userId);
                
                if ($stmt->execute()) {
                    $_SESSION['profile_success'] = "Konto pomyślnie doładowano o " . number_format($amount, 2) . " PLN!";
                    $_SESSION['currentUser']['balance'] = ($currentUser['balance'] ?? 0) + $amount;
                } else {
                    throw new Exception("Błąd bazy danych podczas doładowywania: " . $stmt->error);
                }
            } catch (Exception $e) {
                $_SESSION['profile_error'] = $e->getMessage();
            }
        } else {
            $_SESSION['profile_error'] = "Wybrano nieprawidłową kwotę doładowania.";
        }

        $redirectUrl = 'user.php?user=' . urlencode($profileUser['username']);
    }
    header("Location: $redirectUrl");
    exit;
}

$headerColor = htmlspecialchars($profileUser['profile_color'] ?? '#141E2A');

$gamesResult = null;
$reviewsResult = null;
$gamePlatforms = []; 
$gameTags = []; 

if ($profileUser) {
    $userId = $profileUser['id'];

    $stmt = $conn->prepare("SELECT g.id, g.name, g.date, dev.name AS developer_name, pub.name AS publisher_name
               FROM user_games ug 
               JOIN games g ON ug.game_id = g.id 
               LEFT JOIN developers dev ON g.developer_id = dev.id
               LEFT JOIN publishers pub ON g.publisher_id = pub.id
               WHERE ug.user_id = ? 
               ORDER BY g.name ASC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $gamesResult = $stmt->get_result();

    if ($gamesResult && $gamesResult->num_rows > 0) {
        $gameIds = [];
        $gamesResult->data_seek(0); 
        while($game = $gamesResult->fetch_assoc()) {
            $gameIds[] = $game['id'];
        }
        $gamesResult->data_seek(0);

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

    $reviewsQuery = "SELECT r.id, r.rating, r.comment, r.created_at, g.name AS game_name 
                     FROM reviews r 
                     JOIN games g ON r.game_id = g.id 
                     WHERE r.user_id = $userId 
                     ORDER BY r.created_at DESC";
    $reviewsResult = $conn->query($reviewsQuery);
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
</head>
<body>
    <?php include('header.php'); ?>

    <?php if ($profileUser): ?>
        <div class="profile-header-banner" style="--header-bg: <?php echo $headerColor; ?>; background-color: <?php echo $headerColor; ?>;">
            <div class="profile-header-content">
                <img 
                    src="img/avatars/<?php echo htmlspecialchars($profileUser['avatar_filename'] ?? 'default_avatar.png'); ?>" 
                    alt="Awatar <?php echo htmlspecialchars($profileUser['username']); ?>"
                    class="profile-avatar"
                >
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($profileUser['username']); ?></h1>
                </div>
            </div>
        </div>
        
        <div id="profile-container-main">
            
            <?php if ($isCurrentUserProfile): ?>
                
                <?php if ($error): ?>
                    <p class="profile-message error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <?php if ($success): ?>
                    <p class="profile-message success"><?php echo htmlspecialchars($success); ?></p>
                <?php endif; ?>

                <a href="#" class="btnEDIT" id="show-edit-form-btn" style="width: 200px;">Edytuj profil</a>

                <form method="POST" enctype="multipart/form-data" id="edit-profile-form" style="display: none;">
                    
                    <h3>Zmień awatar</h3>
                    <label for="avatar_input" class="uploader-container" id="avatar_preview" 
                           style="width: 150px; height: 150px; background-image: url('img/avatars/<?php echo htmlspecialchars($profileUser['avatar_filename'] ?? 'default_avatar.png'); ?>');">
                        <span class="uploader-label">Kliknij, by zmienić</span>
                        <input type="file" name="avatar" class="uploader-input" id="avatar_input" data-preview-target="avatar_preview" accept="image/png,image/jpeg">
                    </label>

                    <h3>Zmień nazwę użytkownika</h3>
                    <input type="text" name="new_username" class="profile-form-input" value="<?php echo htmlspecialchars($profileUser['username']); ?>">
                    
                    <div class="form-button-container">
                        <button type="button" class="btnForm cancel-btn-red" id="cancel-edit-btn">Anuluj</button>
                        <button type="submit" class="btnForm">Zapisz zmiany</button>
                    </div>
                </form>
                <hr class="divider">
                <div class="profile-balance-box">
                    <span>Balans konta:</span>
                    <strong><?php echo number_format($currentUser['balance'], 2); ?> PLN</strong>
                </div>
                <div class="profile-recharge-section">
                    <h3>Doładuj konto</h3>
                    <form method="POST" id="recharge-form">
                        <input type="hidden" name="recharge_amount" id="recharge-amount-input">
                        
                        <div class="recharge-options-grid">
                            <button type="button" class="btnForm recharge-btn" data-amount="20">20 zł</button>
                            <button type="button" class="btnForm recharge-btn" data-amount="50">50 zł</button>
                            <button type="button" class="btnForm recharge-btn" data-amount="100">100 zł</button>
                            <button type="button" class="btnForm recharge-btn" data-amount="200">200 zł</button>
                            <button type="button" class="btnForm recharge-btn" data-amount="500">500 zł</button>
                            <button type="button" class="btnForm recharge-btn" data-amount="1000">1000 zł</button>
                        </div>
                    </form>
                </div>
                <hr class="divider">
            <?php endif; ?>
            <div class="sort-slider profile-tab-slider">
                <div class="sort-options">
                    <button class="sort-option active" data-tab="games">GRY (<?php echo $gamesResult ? $gamesResult->num_rows : 0; ?>)</button>
                    <button class="sort-option" data-tab="reviews">SPOŁECZNOŚĆ (<?php echo $reviewsResult ? $reviewsResult->num_rows : 0; ?>)</button>
                </div>
                </div>

                <div class="profile-tab-content active" id="profile-games-content">
                    <?php if ($gamesResult && $gamesResult->num_rows > 0): ?>
                        <div class="profile-games-list"> 
                            <?php while ($game = $gamesResult->fetch_assoc()): ?>
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
                                                <strong>DEWELOPER:</strong> 
                                                <a href="developer.php?name=<?php echo urlencode($game['developer_name']); ?>" class="profile-content-link">
                                                    <?php echo htmlspecialchars($game['developer_name']); ?>
                                                </a>
                                            </p>
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
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="profile-tab-placeholder">Ten użytkownik nie posiada (jeszcze) żadnych gier.</p>
                    <?php endif; ?>
                </div>

                <div class="profile-tab-content" id="profile-reviews-content">
                    <?php if ($reviewsResult && $reviewsResult->num_rows > 0): ?>
                        <div class="profile-reviews-list"> <?php while ($review = $reviewsResult->fetch_assoc()): ?>
                                <?php
                                    $safeGameName = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($review['game_name']));
                                    $imagePath = "img/games/{$safeGameName}_1.jpg";
                                    if (!file_exists($imagePath)) {
                                        $files = glob("img/games/{$safeGameName}_*.{jpg,png,jpeg}", GLOB_BRACE);
                                        $imagePath = !empty($files) ? $files[0] : 'img/others/placeholder.jpg';
                                    }
                                    $gameUrl = 'game.php?game=' . urlencode($review['game_name']);
                                ?>
                                <div class="profile-game-tile profile-review-tile">
                                    <a href="<?php echo $gameUrl; ?>" class="game-tile-image">
                                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($review['game_name']); ?>">
                                    </a>
                                    
                                    <div class="game-tile-info">
                                        <h3 class="game-tile-title">
                                            <a href="<?php echo $gameUrl; ?>" class="profile-content-link">
                                                <?php echo htmlspecialchars($review['game_name']); ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="review-tile-meta">
                                            <span class="stars-display"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></span>
                                            <small class="review-date"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></small>
                                        </div>

                                        <p class="review-tile-comment">
                                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="profile-tab-placeholder">Ten użytkownik nie napisał jeszcze żadnych recenzji.</p>
                    <?php endif; ?>
                </div>
                <hr class="divider">
            </div>

    <?php else: ?>
        <div id="profile-container-main">
            <h1>Nie znaleziono użytkownika</h1>
            <p>Użytkownik o nazwie "<?php echo htmlspecialchars($profileUsername); ?>" nie został znaleziony.</p>
        </div>
    <?php endif; ?>

    <div id="recharge-modal-backdrop" class="modal-backdrop">
        <div id="recharge-modal-window" class="modal-window">
            <h3>Potwierdzenie doładowania</h3>
            <p id="recharge-modal-text">Czy na pewno chcesz doładować konto?</p>
            
            <div class="form-button-container modal-footer">
                <button type="button" class="btnForm cancel-btn-red" id="modal-cancel-btn">Anuluj</button>
                <button type="button" class="btnForm" id="modal-confirm-btn">Doładuj</button>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <script src="js/user.js" defer></script>
    <script src="js/slider.js" defer></script>
    <script src="js/imageUploader.js" defer></script>
</body>
</html>