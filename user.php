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

$usernameEsc = $conn->real_escape_string($profileUsername);
$query = "SELECT id, username, avatar_filename, profile_color, role 
          FROM users 
          WHERE username = '$usernameEsc'";
$result = $conn->query($query);

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

    $gamesQuery = "SELECT g.id, g.name, g.date, 
                          dev.name AS developer_name, 
                          pub.name AS publisher_name
                   FROM user_games ug 
                   JOIN games g ON ug.game_id = g.id 
                   LEFT JOIN developers dev ON g.developer_id = dev.id
                   LEFT JOIN publishers pub ON g.publisher_id = pub.id
                   WHERE ug.user_id = $userId 
                   ORDER BY g.name ASC";
    $gamesResult = $conn->query($gamesQuery);

    if ($gamesResult && $gamesResult->num_rows > 0) {
        $gameIds = [];
        $gamesResult->data_seek(0); 
        while($game = $gamesResult->fetch_assoc()) {
            $gameIds[] = $game['id'];
        }
        $gamesResult->data_seek(0);

        if (!empty($gameIds)) {
            $idString = implode(',', $gameIds);

            $platformsQuery = "SELECT gp.game_id, p.name 
                               FROM game_platforms gp 
                               JOIN platforms p ON gp.platform_id = p.id 
                               WHERE gp.game_id IN ($idString)";
            $platformsResult = $conn->query($platformsQuery);
            if ($platformsResult) {
                while ($row = $platformsResult->fetch_assoc()) {
                    $gamePlatforms[$row['game_id']][] = $row['name'];
                }
            }

            $tagsQuery = "SELECT gt.game_id, t.name 
                          FROM game_tags gt 
                          JOIN tags t ON gt.tag_id = t.id 
                          WHERE gt.game_id IN ($idString)";
            $tagsResult = $conn->query($tagsQuery);
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
        <div class="profile-header-banner" style="background-color: <?php echo $headerColor; ?>;">
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
    
    <script>
    // === FUNKCJE POMOCNICZE (zawsze dostępne) ===

    function updateSortIndicator(activeButton, slider) {
        if (!activeButton || !slider) return;
        setTimeout(() => {
            const buttonRect = activeButton.getBoundingClientRect();
            const sliderRect = slider.getBoundingClientRect();
            if (buttonRect.width > 0 && sliderRect.width > 0) {
                slider.style.setProperty('--slider-highlight-left', `${buttonRect.left - sliderRect.left}px`);
                slider.style.setProperty('--slider-highlight-width', `${buttonRect.width}px`);
            }
        }, 0);
    }

    function renderPlatformIcons() {
        const platformSvgs = {
            'windows': `<svg viewBox="0 0 56.693 56.693" xmlns="http://www.w3.org/2000/svg"><g><path d="M3.765,46.362l19.836,2.873V30.257H3.765V46.362z M3.765,27.546h19.836V8.566L3.765,11.439V27.546z M26.312,49.628 l26.616,3.855V30.257H26.312V49.628z M26.312,8.172v19.374h26.616V4.319L26.312,8.172z"/></g></svg>`,
            'playstation': `<svg fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M15.858 11.451c-.313.395-1.079.676-1.079.676l-5.696 2.046v-1.509l4.192-1.493c.476-.17.549-.412.162-.538-.386-.127-1.085-.09-1.56.08l-2.794.984v-1.566l.161-.054s.807-.286 1.942-.412c1.135-.125 2.525.017 3.616.43 1.23.39 1.368.962 1.056 1.356ZM9.625 8.883v-3.86c0-.453-.083-.87-.508-.988-.326-.105-.528.198-.528.65v9.664l-2.606-.827V2c1.108.206 2.722.692 3.59.985 2.207.757 2.955 1.7 2.955 3.825 0 2.071-1.278 2.856-2.903 2.072Zm-8.424 3.625C-.061 12.15-.271 11.41.304 10.984c.532-.394 1.436-.69 1.436-.69l3.737-1.33v1.515l-2.69.963c-.474.17-.547.411-.161.538.386.126 1.085.09 1.56-.08l1.29-.469v1.356l-.257.043a8.454 8.454 0 0 1-4.018-.323Z"/></svg>`,
            'xbox': `<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M369.9 318.2c44.3 54.3 64.7 98.8 54.4 118.7-7.9 15.1-56.7 44.6-92.6 55.9-29.6 9.3-68.4 13.3-100.4 10.2-38.2-3.7-76.9-17.4-110.1-39C93.3 445.8 87 438.3 87 423.4c0-29.9 32.9-82.3 89.2-142.1 32-33.9 76.5-73.7 81.4-72.6 9.4 2.1 84.3 75.1 112.3 109.5zM188.6 143.8c-29.7-26.9-58.1-53.9-86.4-63.4-15.2-5.1-16.3-4.8-28.7 8.1-29.2 30.4-53.5 79.7-60.3 122.4-5.4 34.2-6.1 43.8-4.2 60.5 5.6 50.5 17.3 85.4 40.5 120.9 9.5 14.6 12.1 17.3 9.3 9.9-4.2-11-.3-37.5 9.5-64 14.3-39 53.9-112.9 120.3-194.4zm311.6 63.5C483.3 127.3 432.7 77 425.6 77c-7.3 0-24.2 6.5-36 13.9-23.3 14.5-41 31.4-64.3 52.8C367.7 197 427.5 283.1 448.2 346c6.8 20.7 9.7 41.1 7.4 52.3-1.7 8.5-1.7 8.5 1.4 4.6 6.1-7.7 19.9-31.3 25.4-43.5 7.4-16.2 15-40.2 18.6-58.7 4.3-22.5 3.9-70.8-.8-93.4zM141.3 43C189 40.5 251 77.5 255.6 78.4c.7.1 10.4-4.2 21.6-9.7 63.9-31.1 94-25.8 107.4-25.2-63.9-39.3-152.7-50-233.9-11.7-23.4 11.1-24 11.9-9.4 11.2z"/></svg>`,
            'nintendo': `<svg fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M9.34 8.005c0-4.38.01-7.972.023-7.982C9.373.01 10.036 0 10.831 0c1.153 0 1.51.01 1.743.05 1.73.298 3.045 1.6 3.373 3.326.046.242.053.809.053 4.61 0 4.06.005 4.537-.123 4.976-.022.076-.048.15-.08.242a4.136 4.136 0 0 1-3.426 2.767c-.317.033-2.889.046-2.978.013-.05-.02-.053-.752-.053-7.979Zm4.675.269a1.621 1.621 0 0 0-1.113-1.034 1.609 1.609 0 0 0-1.938 1.073 1.9 1.9 0 0 0-.014.935 1.632 1.632 0 0 0 1.952 1.107c.51-.136.908-.504 1.11-1.028.11-.285.113-.742.003-1.053ZM3.71 3.317c-.208.04-.526.199-.695.348-.348.301-.52.729-.494 1.232.013.262.03.332.136.544.155.321.39.556.712.715.222.11.278.123.567.133.261.01.354 0 .53-.06.719-.242 1.153-.94 1.03-1.656-.142-.852-.95-1.422-1.786-1.256Z"/><path d="M3.425.053a4.136 4.136 0 0 0-3.28 3.015C0 3.628-.01 3.956.005 8.3c.01 3.99.014 4.082.08 4.39.368 1.66 1.548 2.844 3.224 3.235.22.05.497.06 2.29.07 1.856.012 2.048.009 2.097-.04.05-.05.053-.69.053-7.94 0-5.374-.01-7.906-.033-7.952-.033-.06-.09-.063-2.03-.06-1.578.004-2.052.014-2.26.05Zm3 14.665-1.35-.016c-1.242-.013-1.375-.02-1.623-.083a2.81 2.81 0 0 1-2.08-2.167c-.074-.335-.074-8.579-.004-8.907a2.845 2.845 0 0 1 1.716-2.05c.438-.176.64-.196 2.058-.2l1.282-.003v13.426Z"/></svg>`
        };
        document.querySelectorAll('.platform-item').forEach(element => {
            const platformName = element.dataset.platformName.toLowerCase().trim();
            if (platformSvgs[platformName]) {
                element.innerHTML = platformSvgs[platformName];
            }
        });
    }

    // === GŁÓWNY LISTENER ===
    document.addEventListener('DOMContentLoaded', function() {
        
        /* --- LOGIKA DLA FORMULARZA EDYCJI PROFILU --- */
        const showBtn = document.getElementById('show-edit-form-btn');
        const cancelBtn = document.getElementById('cancel-edit-btn');
        const editForm = document.getElementById('edit-profile-form');

        if (showBtn && editForm) {
            showBtn.addEventListener('click', function(e) {
                e.preventDefault();
                editForm.style.display = 'block';
                showBtn.style.display = 'none';
            });
        }
        
        if (cancelBtn && editForm && showBtn) {
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                editForm.style.display = 'none';
                showBtn.style.display = 'inline-flex'; // Poprawka z 'inline-block'
            });
        }
        
        // Skrypt do podglądu awatara
        document.querySelectorAll('.uploader-input').forEach(input => {
            input.addEventListener('change', function() {
                const previewBox = document.getElementById(this.dataset.previewTarget);
                if (!previewBox) return;
                
                const label = previewBox.querySelector('.uploader-label');
                const file = this.files[0];
                
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        previewBox.style.backgroundImage = `url('${e.target.result}')`;
                        if (label) label.classList.add('visible'); // Pokazuje label przy najechaniu
                    };
                    reader.readAsDataURL(file);
                }
            });
        });

        // Usuwanie komunikatów o błędzie/sukcesie po 5 sekundach
        const message = document.querySelector('.profile-message');
        if (message) {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            }, 5000);
        }
        /* --- LOGIKA DLA PRZYCISKÓW DOŁADOWANIA (z modalem) --- */
        const rechargeForm = document.getElementById('recharge-form');
        const rechargeInput = document.getElementById('recharge-amount-input');
        const rechargeButtons = document.querySelectorAll('.recharge-btn');

        // Elementy nowego modala
        const modalBackdrop = document.getElementById('recharge-modal-backdrop');
        const modalText = document.getElementById('recharge-modal-text');
        const modalCancelBtn = document.getElementById('modal-cancel-btn');
        const modalConfirmBtn = document.getElementById('modal-confirm-btn');
        
        let currentRechargeAmount = 0; // Zmienna do przechowania kwoty

        if (rechargeForm && rechargeInput && rechargeButtons && modalBackdrop) {
            
            // 1. Co się dzieje po kliknięciu przycisku "20 zł" itd.
            rechargeButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); 
                    
                    const amount = this.dataset.amount;
                    currentRechargeAmount = amount; // Zapisz kwotę
                    
                    // Ustaw tekst w modalu
                    modalText.textContent = 'Czy na pewno chcesz doładować konto o ' + amount + ' zł?';
                    
                    // Pokaż modal
                    modalBackdrop.classList.add('modal-open');
                });
            });

            // Funkcja do zamykania modala
            function closeModal() {
                modalBackdrop.classList.remove('modal-open');
                currentRechargeAmount = 0; // Resetuj kwotę
            }

            // 2. Co się dzieje po kliknięciu "Anuluj" w modalu
            modalCancelBtn.addEventListener('click', closeModal);

            // 3. Zamykanie modala po kliknięciu w tło
            modalBackdrop.addEventListener('click', function(e) {
                if (e.target === modalBackdrop) { // Upewnij się, że kliknięto tło, a nie okienko
                    closeModal();
                }
            });

            // 4. Co się dzieje po kliknięciu "Doładuj" (Potwierdź) w modalu
            modalConfirmBtn.addEventListener('click', function() {
                if (currentRechargeAmount > 0) {
                    rechargeInput.value = currentRechargeAmount; // Ustaw ukryte pole
                    rechargeForm.submit(); // Wyślij formularz
                    closeModal(); // Zamknij modal
                }
            });
        }
        
        /* --- LOGIKA DLA ZAKŁADEK PROFILU (GRY/SPOŁECZNOŚĆ) --- */
        const tabSlider = document.querySelector('.profile-tab-slider');
        const tabOptions = document.querySelectorAll('.profile-tab-slider .sort-option');
        const tabContents = document.querySelectorAll('.profile-tab-content');

        if (tabSlider) {
            // Inicjalizacja slidera przy ładowaniu strony
            const initialActiveTab = tabSlider.querySelector('.sort-option.active');
            if (initialActiveTab) {
                updateSortIndicator(initialActiveTab, tabSlider);
            }

            tabOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const tabId = this.dataset.tab;

                    // 1. Zaktualizuj suwak
                    tabOptions.forEach(opt => opt.classList.remove('active'));
                    this.classList.add('active');
                    updateSortIndicator(this, tabSlider);

                    // 2. Pokaż/ukryj zawartość
                    tabContents.forEach(content => {
                        if (content.id === `profile-${tabId}-content`) {
                            content.classList.add('active');
                        } else {
                            content.classList.remove('active');
                        }
                    });
                });
            });

            // Aktualizuj wskaźnik slidera przy zmianie rozmiaru okna
            window.addEventListener('resize', () => {
                const activeTab = tabSlider.querySelector('.sort-option.active');
                if(activeTab) {
                    updateSortIndicator(activeTab, tabSlider);
                }
            });
        }

        // === WYWOŁAJ RENDEROWANIE IKON PO ZAŁADOWANIU DOM ===
        renderPlatformIcons();
    });
</script>
</body>
</html>