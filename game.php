<?php
require_once 'conn.php';
require_once 'auth.php';

$gameName = $_GET["game"] ?? null;

if ($gameName == null) {
    header('location: index.php');
    exit;
}

$queryGame = "SELECT games.id, games.name as name, description, publishers.name as publisher, developers.name AS developer, price, date FROM games LEFT JOIN developers ON games.developer_id=developers.id LEFT JOIN publishers ON games.publisher_id=publishers.id WHERE games.name='$gameName'";
$resultGame = $conn->query($queryGame);
$gameData = $resultGame->fetch_assoc();

if (!$gameData) {
    header('location: index.php');
    exit;
}

$gameId = $gameData['id'];
$reviewMessage = null;
$userId = $currentUser['user_id'] ?? null;

if (isset($_GET['add_to_cart'])) {
    if ($currentUser) {
        $gameIdToAdd = (int)$_GET['add_to_cart'];
        $insertQuery = "INSERT IGNORE INTO cart (user_id, game_id) VALUES ('$userId', '$gameIdToAdd')";
        $conn->query($insertQuery);
        header('Location: game.php?game=' . urlencode($gameName));
        exit;
    } else {
        header('Location: login.php');
        exit;
    }
}

if (isset($_GET['vote'], $_GET['review_id']) && $currentUser) {
    $reviewId = (int)$_GET['review_id'];
    $voteType = $_GET['vote'] === 'up' ? 1 : -1;

    $checkVoteQuery = "SELECT vote_type FROM review_votes WHERE user_id = $userId AND review_id = $reviewId";
    $voteResult = $conn->query($checkVoteQuery);
    $existingVote = $voteResult->fetch_assoc();

    if ($existingVote) {
        if ((int)$existingVote['vote_type'] === $voteType) {
            // Użytkownik cofa swój głos
            $conn->query("DELETE FROM review_votes WHERE user_id = $userId AND review_id = $reviewId");
            $conn->query("UPDATE reviews SET votes = votes - $voteType WHERE id = $reviewId");
        } else {
            // Użytkownik zmienia swój głos
            $conn->query("UPDATE review_votes SET vote_type = $voteType WHERE user_id = $userId AND review_id = $reviewId");
            $conn->query("UPDATE reviews SET votes = votes + (2 * $voteType) WHERE id = $reviewId");
        }
    } else {
        // Nowy głos
        $conn->query("INSERT INTO review_votes (user_id, review_id, vote_type) VALUES ($userId, $reviewId, $voteType)");
        $conn->query("UPDATE reviews SET votes = votes + $voteType WHERE id = $reviewId");
    }

    header('Location: game.php?game=' . urlencode($gameName) . '#review-' . $reviewId);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser) {
    if (isset($_POST['submit_review'])) {
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
            $insertQuery = "INSERT INTO reviews (user_id, game_id, rating, comment) VALUES ('$userId', '$gameId', '$rating', '$comment')";
            if ($conn->query($insertQuery)) {
                header('Location: game.php?game=' . urlencode($gameName) . '&status=review_added#reviews');
                exit;
            } else {
                $reviewMessage = "Błąd: " . $conn->error;
            }
        } else {
            $reviewMessage = "Ocena i komentarz są wymagane.";
        }
    }

    if (isset($_POST['edit_review'])) {
        $reviewId = (int)$_POST['review_id'];
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
            $checkOwnerQuery = "SELECT id FROM reviews WHERE id = $reviewId AND user_id = '$userId'";
            $ownerResult = $conn->query($checkOwnerQuery);
            if ($ownerResult && $ownerResult->num_rows > 0) {
                $updateQuery = "UPDATE reviews SET rating = '$rating', comment = '$comment' WHERE id = $reviewId";
                if ($conn->query($updateQuery)) {
                    header('Location: game.php?game=' . urlencode($gameName) . '#review-' . $reviewId);
                    exit;
                } else {
                     $reviewMessage = "Błąd podczas aktualizacji: " . $conn->error;
                }
            }
        }
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'review_added') {
    $reviewMessage = "Recenzja została dodana!";
}

$safeGameName = preg_replace('/[^a-z0-9_-]/', '_', strtolower($gameName));

function find_logo_path($name, $role) {
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($name));
    foreach (['png','jpg'] as $ext) {
        $filePath = "img/$role/$safeName.$ext";
        if (file_exists($filePath)) {
            return $filePath;
        }
    }
    return 'img/others/placeholder.jpg';
}

$developer_logo = find_logo_path($gameData['developer'], 'developer');
$publisher_logo = find_logo_path($gameData['publisher'], 'publisher');

$queryTag = "SELECT tags.name FROM tags LEFT JOIN game_tags ON tags.id=game_tags.tag_id WHERE game_tags.game_id='$gameId'";
$resultTag = $conn->query($queryTag);

$queryPlat = "SELECT platforms.name FROM platforms LEFT JOIN game_platforms ON platforms.id=game_platforms.platform_id WHERE game_platforms.game_id='$gameId'";
$resultPlat = $conn->query($queryPlat);

$queryReviews = "SELECT r.id, r.rating, r.comment, r.created_at, r.votes, u.username
                 FROM reviews r
                 LEFT JOIN users u ON r.user_id = u.id
                 WHERE r.game_id = '$gameId'
                 ORDER BY r.created_at DESC";
$resultReviews = $conn->query($queryReviews);

$userVotes = [];
if ($currentUser) {
    $votesQuery = "SELECT review_id, vote_type FROM review_votes WHERE user_id = $userId";
    $votesResult = $conn->query($votesQuery);
    while ($row = $votesResult->fetch_assoc()) {
        $userVotes[$row['review_id']] = (int)$row['vote_type'];
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($gameData["name"]) ?></title>
    <link rel="stylesheet" href="style/styleGame.css">
    <link rel="stylesheet" href="style/styleCommon.css">
</head>
<body>
<?php include 'header.php'; ?>

    <div id="container">
        <h1>
            <?= htmlspecialchars($gameData["name"]) ?>
            <?php if ($currentUser && $currentUser['role'] == 1): ?>
                <a href="gamedit.php?game=<?= urlencode($gameData["name"]) ?>" title="Edytuj grę">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1024" height="1024" viewBox="0 0 1024 1024"><path fill="currentColor" d="M880 836H144c-17.7 0-32 14.3-32 32v36c0 4.4 3.6 8 8 8h784c4.4 0 8-3.6 8-8v-36c0-17.7-14.3-32-32-32m-622.3-84c2 0 4-.2 6-.5L431.9 722c2-.4 3.9-1.3 5.3-2.8l423.9-423.9a9.96 9.96 0 0 0 0-14.1L694.9 114.9c-1.9-1.9-4.4-2.9-7.1-2.9s-5.2 1-7.1 2.9L256.8 538.8c-1.5 1.5-2.4 3.3-2.8 5.3l-29.5 168.2a33.5 33.5 0 0 0 9.4 29.8c6.6 6.4 14.9 9.9 23.8 9.9"/></svg>
                </a>
            <?php endif; ?>
        </h1>
        <div class="images">
             <div class="main-image-container">
                 <div class="slide fade">
                    <img src="img/games/<?= $safeGameName ?>_1.jpg" alt="Screenshot 1">
                </div>
                <div class="slide fade">
                    <img src="img/games/<?= $safeGameName ?>_2.jpg" alt="Screenshot 2">
                </div>
                <div class="slide fade">
                    <img src="img/games/<?= $safeGameName ?>_3.jpg" alt="Screenshot 3">
                </div>
                <div class="slide fade">
                    <img src="img/games/<?= $safeGameName ?>_4.jpg" alt="Screenshot 4">
                </div>

                <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                <a class="next" onclick="plusSlides(1)">&#10095;</a>
            </div>
             <div class="thumbnail-container">
                <img class="thumbnail" src="img/games/<?= $safeGameName ?>_1.jpg" onclick="currentSlide(1)" alt="Thumbnail 1">
                <img class="thumbnail" src="img/games/<?= $safeGameName ?>_2.jpg" onclick="currentSlide(2)" alt="Thumbnail 2">
                <img class="thumbnail" src="img/games/<?= $safeGameName ?>_3.jpg" onclick="currentSlide(3)" alt="Thumbnail 3">
                <img class="thumbnail" src="img/games/<?= $safeGameName ?>_4.jpg" onclick="currentSlide(4)" alt="Thumbnail 4">
            </div>
        </div>
        <div class="content">
            <p class="description"><?= htmlspecialchars($gameData["description"]) ?></p>
            <hr>
            <p class="release-date"><strong>DATA WYDANIA:</strong> <?= htmlspecialchars($gameData["date"]) ?></p>

            <div class="details-container">
                <div class="detail-item">
                    <span class="detail-title">DEWELOPER:</span>
                    <a href="developer.php?name=<?= urlencode($gameData['developer']) ?>">
                        <div class="logo-container">
                            <img src="<?= $developer_logo ?>" alt="Logo <?= htmlspecialchars($gameData['developer']) ?>">
                        </div>
                        <span class="company-name"><?= htmlspecialchars($gameData['developer']) ?></span>
                    </a>
                </div>
                <div class="detail-item">
                     <span class="detail-title">WYDAWCA:</span>
                     <a href="publisher.php?name=<?= urlencode($gameData['publisher']) ?>">
                        <div class="logo-container">
                            <img src="<?= $publisher_logo ?>" alt="Logo <?= htmlspecialchars($gameData['publisher']) ?>">
                        </div>
                        <span class="company-name"><?= htmlspecialchars($gameData['publisher']) ?></span>
                    </a>
                </div>
            </div>

            <div class="platforms-container">
                 <?php while ($row = $resultPlat->fetch_assoc()): ?>
                    <span class="platform-item" data-platform-name="<?= htmlspecialchars($row['name']) ?>" title="<?= htmlspecialchars($row['name']) ?>"></span>
                <?php endwhile; ?>
            </div>

            <div class="tags-container">
                <?php while ($row = $resultTag->fetch_assoc()): ?>
                    <a href="tag.php?name=<?= urlencode($row["name"]) ?>" class="tag-item"><?= htmlspecialchars($row["name"]) ?></a>
                <?php endwhile; ?>
            </div>
        </div>

        <hr class="divider">
        
        <div class="buy-container">
            <div class="buy-box">
                <h2>Kup <?= htmlspecialchars($gameData["name"]) ?></h2>
                <div class="purchase-box">
                    <div class="price"><?= htmlspecialchars($gameData['price']) ?> PLN</div>
                    <div class="add-to-cart">
                        <a href="game.php?game=<?= urlencode($gameName) ?>&add_to_cart=<?= $gameId ?>" class="cart-button">Do koszyka</a>
                    </div>
                </div>
            </div>
        </div>

        <hr class="divider">

        <div id="reviews" class="reviews-section">
            <h2>Recenzje użytkowników</h2>

            <?php if ($currentUser): ?>
            <div class="review-form-container">
                <form method="post" action="game.php?game=<?= urlencode($gameName) ?>" class="review-form">
                    <div class="review-user-info">
                        <div class="avatar-placeholder"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="white" d="M6 22h13a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2zm6-17.001c1.647 0 3 1.351 3 3C15 9.647 13.647 11 12 11S9 9.647 9 7.999c0-1.649 1.353-3 3-3M6 17.25c0-2.219 2.705-4.5 6-4.5s6 2.281 6 4.5V18H6z"/></svg></div>
                        <span class="username"><?= htmlspecialchars($currentUser['username']) ?></span>
                    </div>
                    <div class="review-main-content">
                        <div class="review-form-top">
                             <div class="review-stars rating-input">
                                <input type="radio" id="star5-new" name="rating" value="5" /><label for="star5-new" title="5 gwiazdek">★</label>
                                <input type="radio" id="star4-new" name="rating" value="4" /><label for="star4-new" title="4 gwiazdki">★</label>
                                <input type="radio" id="star3-new" name="rating" value="3" /><label for="star3-new" title="3 gwiazdki">★</label>
                                <input type="radio" id="star2-new" name="rating" value="2" /><label for="star2-new" title="2 gwiazdki">★</label>
                                <input type="radio" id="star1-new" name="rating" value="1" /><label for="star1-new" title="1 gwiazdka">★</label>
                            </div>
                        </div>
                        <div class="review-comment-field">
                            <textarea name="comment" placeholder="Napisz swoją recenzję..."></textarea>
                            <div class="form-footer">
                                <?php if ($reviewMessage): ?><span class="review-message"><?= htmlspecialchars($reviewMessage); ?></span><?php endif; ?>
                                <button type="submit" name="submit_review">Opublikuj</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="reviews-list">
                <?php if ($resultReviews && $resultReviews->num_rows > 0): ?>
                    <?php while($row = $resultReviews->fetch_assoc()): ?>
                        <?php
                            $userVote = $userVotes[$row['id']] ?? 0;
                            $upvoteClass = ($userVote === 1) ? 'voted' : '';
                            $downvoteClass = ($userVote === -1) ? 'voted' : '';
                        ?>
                        <div class="review-item" id="review-<?= $row['id'] ?>">
                            <div class="review-user-info">
                                <a href="user.php?user=<?= urlencode($row['username']) ?>" class="user-profile-link">
                                    <div class="avatar-placeholder"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="white" d="M6 22h13a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2zm6-17.001c1.647 0 3 1.351 3 3C15 9.647 13.647 11 12 11S9 9.647 9 7.999c0-1.649 1.353-3 3-3M6 17.25c0-2.219 2.705-4.5 6-4.5s6 2.281 6 4.5V18H6z"/></svg></div>
                                    <span class="username"><?= htmlspecialchars($row['username']); ?></span>
                                </a>
                            </div>
                            <div class="review-main-content">
                                <div class="review-top">
                                    <span class="stars-display"><?= str_repeat('★', $row['rating']); ?><?= str_repeat('☆', 5 - $row['rating']); ?></span>
                                    <div class="review-top-right">
                                        <?php if ($currentUser && $currentUser['username'] === $row['username']): ?>
                                            <button class="edit-review-btn" onclick="toggleEditForm(<?= $row['id'] ?>)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1024" height="1024" viewBox="0 0 1024 1024"><path fill="currentColor" d="M880 836H144c-17.7 0-32 14.3-32 32v36c0 4.4 3.6 8 8 8h784c4.4 0 8-3.6 8-8v-36c0-17.7-14.3-32-32-32m-622.3-84c2 0 4-.2 6-.5L431.9 722c2-.4 3.9-1.3 5.3-2.8l423.9-423.9a9.96 9.96 0 0 0 0-14.1L694.9 114.9c-1.9-1.9-4.4-2.9-7.1-2.9s-5.2 1-7.1 2.9L256.8 538.8c-1.5 1.5-2.4 3.3-2.8 5.3l-29.5 168.2a33.5 33.5 0 0 0 9.4 29.8c6.6 6.4 14.9 9.9 23.8 9.9"/></svg>
                                            </button>
                                        <?php endif; ?>
                                        <span class="review-date"><?= date('d.m.Y', strtotime($row['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="review-body" id="review-body-<?= $row['id'] ?>">
                                    <p class="review-comment-content"><?= nl2br(htmlspecialchars($row['comment'])); ?></p>
                                </div>
                                <div class="review-edit-form" id="review-edit-form-<?= $row['id'] ?>" style="display: none;">
                                    <form method="post" action="game.php?game=<?= urlencode($gameName) ?>">
                                        <input type="hidden" name="review_id" value="<?= $row['id'] ?>">
                                        <div class="review-stars rating-input">
                                            <input type="radio" id="star5-<?= $row['id'] ?>" name="rating" value="5" <?= $row['rating'] == 5 ? 'checked' : '' ?> /><label for="star5-<?= $row['id'] ?>">★</label>
                                            <input type="radio" id="star4-<?= $row['id'] ?>" name="rating" value="4" <?= $row['rating'] == 4 ? 'checked' : '' ?> /><label for="star4-<?= $row['id'] ?>">★</label>
                                            <input type="radio" id="star3-<?= $row['id'] ?>" name="rating" value="3" <?= $row['rating'] == 3 ? 'checked' : '' ?> /><label for="star3-<?= $row['id'] ?>">★</label>
                                            <input type="radio" id="star2-<?= $row['id'] ?>" name="rating" value="2" <?= $row['rating'] == 2 ? 'checked' : '' ?> /><label for="star2-<?= $row['id'] ?>">★</label>
                                            <input type="radio" id="star1-<?= $row['id'] ?>" name="rating" value="1" <?= $row['rating'] == 1 ? 'checked' : '' ?> /><label for="star1-<?= $row['id'] ?>">★</label>
                                        </div>
                                        <textarea name="comment" class="edit-textarea"><?= htmlspecialchars($row['comment']) ?></textarea>
                                        <div class="form-footer">
                                            <button type="button" onclick="toggleEditForm(<?= $row['id'] ?>)" class="cancel-btn">Anuluj</button>
                                            <button type="submit" name="edit_review">Zapisz</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="review-voting">
                                <a href="game.php?game=<?= urlencode($gameName) ?>&vote=up&review_id=<?= $row['id'] ?>" class="vote-arrow upvote <?= $upvoteClass ?>">▲</a>
                                <span class="vote-count"><?= $row['votes'] ?></span>
                                <a href="game.php?game=<?= urlencode($gameName) ?>&vote=down&review_id=<?= $row['id'] ?>" class="vote-arrow downvote <?= $downvoteClass ?>">▼</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <br><p>Brak opinii dla tej gry. Bądź pierwszy!</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

<?php include 'footer.php'; ?>

<script src="skrypty.js"></script>
</body>
</html>