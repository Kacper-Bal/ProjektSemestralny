<?php
require_once 'conn.php';
require_once 'auth.php';

$gameName = $_GET["game"] ?? null;

if ($gameName == null) {
    header('location: index.php');
    exit;
}

$queryGame = "SELECT games.id, games.name, description, publishers.name as publisher, developers.name AS developer, price FROM games LEFT JOIN developers ON games.developer_id=developers.id LEFT JOIN publishers ON games.publisher_id=publishers.id WHERE games.name='$gameName'";
$resultGame = $conn->query($queryGame);
$gameData = $resultGame->fetch_assoc();

if (!$gameData) {
    header('location: index.php');
    exit;
}

$gameId = $gameData['id'];

$queryTag = "SELECT tags.name FROM tags LEFT JOIN game_tags ON tags.id=game_tags.tag_id WHERE game_tags.game_id='$gameId'";
$resultTag = $conn->query($queryTag);

$queryPlat = "SELECT platforms.name FROM platforms LEFT JOIN game_platforms ON platforms.id=game_platforms.platform_id WHERE game_platforms.game_id='$gameId'";
$resultPlat = $conn->query($queryPlat);

$reviewMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if ($currentUser) {
        $userId = $currentUser['user_id'];
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        if ($rating < 1 || $rating > 5) {
            $reviewMessage = "Ocena musi być wybrana (1-5 gwiazdek).";
        } elseif (empty($comment)) {
            $reviewMessage = "Komentarz nie może być pusty.";
        } else {
            $checkQuery = "SELECT id FROM reviews WHERE user_id = '$userId' AND game_id = '$gameId'";
            $checkResult = $conn->query($checkQuery);

            if ($checkResult && $checkResult->num_rows > 0) {
                $updateQuery = "UPDATE reviews SET rating = '$rating', comment = '$comment', created_at = NOW() WHERE user_id = '$userId' AND game_id = '$gameId'";
                if ($conn->query($updateQuery)) {
                    $reviewMessage = "Twoja opinia została zaktualizowana!";
                } else {
                    $reviewMessage = "Błąd podczas aktualizacji opinii: " . $conn->error;
                }
            } else {
                $insertQuery = "INSERT INTO reviews (user_id, game_id, rating, comment) VALUES ('$userId', '$gameId', '$rating', '$comment')";
                if ($conn->query($insertQuery)) {
                    $reviewMessage = "Dziękujemy za dodanie opinii!";
                } else {
                    $reviewMessage = "Błąd podczas dodawania opinii: " . $conn->error;
                }
            }
        }
    } else {
        $reviewMessage = "Musisz być zalogowany, aby dodać opinię.";
    }
}

$queryReviews = "SELECT r.rating, r.comment, r.created_at, u.username 
                 FROM reviews r 
                 LEFT JOIN users u ON r.user_id = u.id 
                 WHERE r.game_id = '$gameId' 
                 ORDER BY r.created_at DESC";
$resultReviews = $conn->query($queryReviews);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($gameData["name"]) ?></title>
    <link rel="stylesheet" href="style/styleGame.css">
</head>
<body>

    <div class="container">
        <header class="game-header">
            <h1><?= htmlspecialchars($gameData["name"]) ?></h1>
        </header>

        <section class="game-content">
            <p class="game-description"><?= htmlspecialchars($gameData["description"]) ?></p>

            <div class="screenshots-container">
                <?php 
                $safeGameName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $gameData["name"]);
                for ($i = 1; $i <= 4; $i++): ?>
                    <img class="screenshot-item" src="/steam/img/games/<?= htmlspecialchars($safeGameName) ?>_<?= $i ?>.jpg" alt="Screenshot <?= $i ?>">
                <?php endfor; ?>
            </div>

            <div class="game-details">
                <div class="detail-item">
                    <h3>Wydawca</h3>
                    <p><?= htmlspecialchars($gameData["publisher"]) ?></p>
                    <img class="logo-image" src="/steam/img/publisher/<?= preg_replace('/[^a-zA-Z0-9_-]/', '_', $gameData["publisher"]) ?>.jpg" alt="Logo <?= htmlspecialchars($gameData["publisher"]) ?>">
                </div>
                <div class="detail-item">
                    <h3>Producent</h3>
                    <p><?= htmlspecialchars($gameData["developer"]) ?></p>
                    <img class="logo-image" src="/steam/img/developer/<?= preg_replace('/[^a-zA-Z0-9_-]/', '_', $gameData["developer"]) ?>.jpg" alt="Logo <?= htmlspecialchars($gameData["developer"]) ?>">
                </div>
            </div>

            <div class="game-meta">
                <div class="tags-container">
                    <?php while ($row = $resultTag->fetch_assoc()): ?>
                        <span class="tag-item"><?= htmlspecialchars($row["name"]) ?></span>
                    <?php endwhile; ?>
                </div>
                <div class="platforms-container">
                    <?php while ($row = $resultPlat->fetch_assoc()): ?>
                        <span class="platform-item" 
                              data-platform-name="<?= htmlspecialchars($row['name']) ?>" 
                              title="<?= htmlspecialchars($row['name']) ?>">
                            </span>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="game-price">
                Cena: <?= htmlspecialchars($gameData["price"]) ?> PLN
            </div>
        </section>
        <section class="review-section">
            <h2>Opinie</h2>

            <?php if ($currentUser): ?>
                <div class="review-form-container">
                    <h3>Dodaj swoją opinię:</h3>
                    <form method="post" class="review-form">
                        <div class="form-group rating-group">
                            <label>Ocena:</label>
                            <div class="rating-stars">
                                <input type="radio" id="star5" name="rating" value="5" /><label for="star5" title="5 gwiazdek">★</label>
                                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 gwiazdki">★</label>
                                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 gwiazdki">★</label>
                                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 gwiazdki">★</label>
                                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 gwiazdka">★</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="comment">Komentarz:</label>
                            <textarea name="comment" id="comment" rows="4"></textarea>
                        </div>
                        <button type="submit" name="submit_review">Wyślij opinię</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($reviewMessage): ?>
                <p class="review-message"><?= htmlspecialchars($reviewMessage); ?></p>
            <?php endif; ?>

            <div class="reviews-list">
                <?php if ($resultReviews && $resultReviews->num_rows > 0): ?>
                    <ul>
                        <?php while($row = $resultReviews->fetch_assoc()): ?>
                            <li class="review-item">
                                <div class="review-header">
                                    <span class="review-author"><?= htmlspecialchars($row['username']); ?></span>
                                    <span class="review-date"><?= date('d.m.Y H:i', strtotime($row['created_at'])); ?></span>
                                </div>
                                <div class="review-rating">
                                    <span class="stars-display"><?= str_repeat('★', $row['rating']); ?><?= str_repeat('☆', 5 - $row['rating']); ?></span>
                                </div>
                                <p class="review-comment">
                                    <?= nl2br(htmlspecialchars($row['comment'])); ?>
                                </p>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>Brak opinii dla tej gry. Bądź pierwszy!</p>
                <?php endif; ?>
            </div>
        </section>

    </div>
    
    <script src="skrypty.js"></script>
</body>
</html>