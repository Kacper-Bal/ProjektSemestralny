<?php
require_once 'conn.php';
require_once 'auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$gameName = $_GET["game"] ?? null;

if ($gameName == null) {
    header('location: index.php');
    exit;
}

$queryGame = "SELECT
    g.id, g.name as name, g.description, pub.name as publisher, dev.name AS developer, g.price, g.date,p.discount_percent FROM games g
LEFT JOIN developers dev ON g.developer_id=dev.id
LEFT JOIN publishers pub ON g.publisher_id=pub.id
LEFT JOIN promotions p ON g.id = p.game_id AND NOW() BETWEEN p.start_date AND p.end_date
WHERE g.name='$gameName'";
$resultGame = $conn->query($queryGame);
$gameData = $resultGame->fetch_assoc();


$originalPrice = (float)$gameData['price'];
$discountPercent = isset($gameData['discount_percent']) ? (int)$gameData['discount_percent'] : null;
$finalPrice = $originalPrice;
$isOnSale = false;

if ($discountPercent !== null && $discountPercent > 0) {
    $discountAmount = ($originalPrice * $discountPercent) / 100;
    $finalPrice = round($originalPrice - $discountAmount, 2);
    $isOnSale = true;
}

if (!$gameData) {
    header('location: index.php');
    exit;
}

$gameId = $gameData['id'];
$userId = $currentUser['user_id'] ?? null;

if (isset($_GET['action']) && $_GET['action'] == 'vote' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'not_logged_in']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $reviewId = isset($input['reviewId']) ? (int)$input['reviewId'] : 0;
    $voteTypeParam = isset($input['voteType']) ? $input['voteType'] : null;

    if ($reviewId <= 0 || !in_array($voteTypeParam, ['up', 'down'])) {
        echo json_encode(['success' => false, 'error' => 'invalid_input']);
        exit;
    }

    $voteType = ($voteTypeParam === 'up') ? 1 : -1;
    $newVoteCount = 0;
    $newUserVoteStatus = 0;

    $conn->begin_transaction();
    try {
        $checkVoteQuery = "SELECT vote_type FROM review_votes WHERE user_id = $userId AND review_id = $reviewId";
        $voteResult = $conn->query($checkVoteQuery);
        $existingVote = $voteResult ? $voteResult->fetch_assoc() : null;

        if ($existingVote) {
            $existingVoteType = (int)$existingVote['vote_type'];
            if ($existingVoteType === $voteType) {
                $conn->query("DELETE FROM review_votes WHERE user_id = $userId AND review_id = $reviewId");
                $conn->query("UPDATE reviews SET votes = votes - $voteType WHERE id = $reviewId");
                $newUserVoteStatus = 0;
            } else {
                $conn->query("UPDATE review_votes SET vote_type = $voteType WHERE user_id = $userId AND review_id = $reviewId");
                $conn->query("UPDATE reviews SET votes = votes + (2 * $voteType) WHERE id = $reviewId");
                $newUserVoteStatus = $voteType;
            }
        } else {
            $conn->query("INSERT INTO review_votes (user_id, review_id, vote_type) VALUES ($userId, $reviewId, $voteType)");
            $conn->query("UPDATE reviews SET votes = votes + $voteType WHERE id = $reviewId");
            $newUserVoteStatus = $voteType;
        }

        $newCountResult = $conn->query("SELECT votes FROM reviews WHERE id = $reviewId");
        if ($newCountResult) {
            $newVoteCount = (int)$newCountResult->fetch_assoc()['votes'];
        }

        $conn->commit();
        echo json_encode(['success' => true, 'newVoteCount' => $newVoteCount, 'newUserVoteStatus' => $newUserVoteStatus]);

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        error_log("Błąd głosowania AJAX w game.php: " . $exception->getMessage());
        echo json_encode(['success' => false, 'error' => 'database_error']);
    }
    exit;
}


$reviewMessage = null;
$messageType = 'error';

if (isset($_SESSION['review_message'])) {
    $reviewMessage = $_SESSION['review_message'];
    $messageType = $_SESSION['message_type'] ?? 'error';
    unset($_SESSION['review_message'], $_SESSION['message_type']);
} elseif (isset($_GET['status']) && $_GET['status'] === 'review_added') {
    $reviewMessage = "Recenzja została dodana!";
    $messageType = 'success';
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser) {
    if (isset($_POST['submit_review'])) {
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
            $insertQuery = "INSERT INTO reviews (user_id, game_id, rating, comment) VALUES ('$userId', '$gameId', '$rating', '$comment')";
            if ($conn->query($insertQuery)) {
                $_SESSION['review_message'] = "Recenzja została dodana!";
                $_SESSION['message_type'] = 'success';
                header('Location: game.php?game=' . urlencode($gameName) . '#reviews');
                exit;
            } else {
                $_SESSION['review_message'] = "Błąd: " . $conn->error;
                $_SESSION['message_type'] = 'error';
            }
        } else {
            $_SESSION['review_message'] = "Ocena i komentarz są wymagane.";
            $_SESSION['message_type'] = 'error';
        }
        header('Location: game.php?game=' . urlencode($gameName) . '#review-form-anchor');
        exit;
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
                    $_SESSION['review_message'] = "Recenzja została zaktualizowana.";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['review_message'] = "Błąd podczas aktualizacji recenzji: " . $conn->error;
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                 $_SESSION['review_message'] = "Nie masz uprawnień do edycji tej recenzji.";
                 $_SESSION['message_type'] = 'error';
            }
        } else {
             $_SESSION['review_message'] = "Ocena i komentarz nie mogą być puste podczas edycji.";
             $_SESSION['message_type'] = 'error';
        }
        header('Location: game.php?game=' . urlencode($gameName) . '#review-' . $reviewId);
        exit;
    }
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

$queryReviews = "SELECT r.id, r.rating, r.comment, r.created_at, r.votes, u.username, u.avatar_filename
                 FROM reviews r
                 LEFT JOIN users u ON r.user_id = u.id
                 WHERE r.game_id = '$gameId'
                 ORDER BY r.created_at DESC";
$resultReviews = $conn->query($queryReviews);

$userVotes = [];
if ($currentUser) {
    $votesQuery = "SELECT review_id, vote_type FROM review_votes WHERE user_id = $userId";
    $votesResult = $conn->query($votesQuery);
    if ($votesResult) {
        while ($row = $votesResult->fetch_assoc()) {
            $userVotes[$row['review_id']] = (int)$row['vote_type'];
        }
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 1024 1024"><path fill="currentColor" d="M880 836H144c-17.7 0-32 14.3-32 32v36c0 4.4 3.6 8 8 8h784c4.4 0 8-3.6 8-8v-36c0-17.7-14.3-32-32-32m-622.3-84c2 0 4-.2 6-.5L431.9 722c2-.4 3.9-1.3 5.3-2.8l423.9-423.9a9.96 9.96 0 0 0 0-14.1L694.9 114.9c-1.9-1.9-4.4-2.9-7.1-2.9s-5.2 1-7.1 2.9L256.8 538.8c-1.5 1.5-2.4 3.3-2.8 5.3l-29.5 168.2a33.5 33.5 0 0 0 9.4 29.8c6.6 6.4 14.9 9.9 23.8 9.9"/></svg>
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
                <div class="price">
                            <?php if ($isOnSale): ?>
                                <span style="background-color: #4c6b22; padding: 2px 5px; color: #a4d4a4; border-radius: 2px; font-size: 0.9em;">-<?= $discountPercent ?>%</span>
                                <span style="text-decoration: line-through; color: #76808c; margin-left: 5px;"><?= htmlspecialchars(number_format($originalPrice, 2)) ?></span>
                                <strong style="margin-left: 5px; color: #a4d4a4;"><?= htmlspecialchars(number_format($finalPrice, 2)) ?> PLN</strong>
                            <?php else: ?>
                                <?= htmlspecialchars(number_format($finalPrice, 2)) ?> PLN
                            <?php endif; ?>
                </div>
                <div class="add-to-cart">
                        <a href="game.php?game=<?= urlencode($gameName) ?>&add_to_cart=<?= $gameId ?>" class="cart-button">Do koszyka</a>
                    </div>
                </div>
            </div>
        </div>

        <hr class="divider">

        <div id="reviews" class="reviews-section">
            <h2>Recenzje użytkowników</h2>

            <a id="review-form-anchor"></a>
            <?php if ($currentUser): ?>
            <div class="review-form-container">
                <form method="post" action="game.php?game=<?= urlencode($gameName) ?>#review-form-anchor" class="review-form">
                    <div class="review-user-info">
                         <img src="img/avatars/<?= htmlspecialchars($currentUser['avatar_filename'] ?? 'default_avatar.png') ?>" alt="Twój Awatar" style="width: 50px; height: 50px; border-radius: 5px; object-fit: cover;">
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
                                <?php if ($reviewMessage): ?>
                                    <p class="review-message" style="color: <?= $messageType === 'success' ? 'lightgreen' : 'red' ?>; margin-right: auto;"><?= htmlspecialchars($reviewMessage); ?></p>
                                <?php endif; ?>
                                <button type="submit" name="submit_review">Opublikuj</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php else: ?>
                <p>Aby dodać recenzję, musisz się <a href="login.php" style="color:#67c1f5;">zalogować</a>.</p>
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
                                    <img src="img/avatars/<?= htmlspecialchars($row['avatar_filename'] ?? 'default_avatar.png') ?>" alt="Awatar <?= htmlspecialchars($row['username']) ?>" style="width: 50px; height: 50px; border-radius: 5px; object-fit: cover;">
                                    <span class="username"><?= htmlspecialchars($row['username']); ?></span>
                                </a>
                            </div>
                            <div class="review-main-content">
                                <div class="review-top">
                                    <span class="stars-display"><?= str_repeat('★', $row['rating']); ?><?= str_repeat('☆', 5 - $row['rating']); ?></span>
                                    <div class="review-top-right">
                                        <?php if ($currentUser && $currentUser['username'] === $row['username']): ?>
                                            <button class="edit-review-btn" onclick="toggleEditForm(<?= $row['id'] ?>)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 1024 1024"><path fill="currentColor" d="M880 836H144c-17.7 0-32 14.3-32 32v36c0 4.4 3.6 8 8 8h784c4.4 0 8-3.6 8-8v-36c0-17.7-14.3-32-32-32m-622.3-84c2 0 4-.2 6-.5L431.9 722c2-.4 3.9-1.3 5.3-2.8l423.9-423.9a9.96 9.96 0 0 0 0-14.1L694.9 114.9c-1.9-1.9-4.4-2.9-7.1-2.9s-5.2 1-7.1 2.9L256.8 538.8c-1.5 1.5-2.4 3.3-2.8 5.3l-29.5 168.2a33.5 33.5 0 0 0 9.4 29.8c6.6 6.4 14.9 9.9 23.8 9.9"/></svg>
                                            </button>
                                        <?php endif; ?>
                                        <span class="review-date"><?= date('d.m.Y', strtotime($row['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="review-body" id="review-body-<?= $row['id'] ?>">
                                    <p class="review-comment-content"><?= nl2br(htmlspecialchars($row['comment'])); ?></p>
                                </div>
                                <div class="review-edit-form" id="review-edit-form-<?= $row['id'] ?>" style="display: none;">
                                    <form method="post" action="game.php?game=<?= urlencode($gameName) ?>#review-<?= $row['id'] ?>">
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
                            <div class="review-voting" data-review-id="<?= $row['id'] ?>">
                                <a href="#" class="vote-arrow upvote <?= $upvoteClass ?>" data-vote="up">▲</a>
                                <span class="vote-count"><?= $row['votes'] ?></span>
                                <a href="#" class="vote-arrow downvote <?= $downvoteClass ?>" data-vote="down">▼</a>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const reviewsList = document.querySelector('.reviews-list');
    let isVoting = false;

    if (reviewsList) {
        reviewsList.addEventListener('click', function(event) {
            const target = event.target.closest('.vote-arrow');
            if (target && !isVoting) {
                event.preventDefault();

                <?php if (!$currentUser): ?>
                    window.location.href = 'login.php';
                    return;
                <?php endif; ?>

                isVoting = true;
                target.classList.add('disabled');
                const voteType = target.dataset.vote;
                const reviewVotingDiv = target.closest('.review-voting');
                const reviewId = reviewVotingDiv.dataset.reviewId;
                const voteCountSpan = reviewVotingDiv.querySelector('.vote-count');
                const upvoteArrow = reviewVotingDiv.querySelector('.upvote');
                const downvoteArrow = reviewVotingDiv.querySelector('.downvote');

                fetch('game.php?action=vote&game=<?= urlencode($gameName) ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        reviewId: reviewId,
                        voteType: voteType
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.error || 'Network response was not ok'); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        voteCountSpan.textContent = data.newVoteCount;
                        upvoteArrow.classList.toggle('voted', data.newUserVoteStatus === 1);
                        downvoteArrow.classList.toggle('voted', data.newUserVoteStatus === -1);
                    } else if (data.error === 'not_logged_in') {
                         window.location.href = 'login.php';
                    } else {
                        console.error('Błąd głosowania:', data.error || 'Nieznany błąd');
                    }
                })
                .catch(error => {
                    console.error('Błąd sieci lub przetwarzania podczas głosowania:', error);
                })
                .finally(() => {
                    isVoting = false;
                    target.classList.remove('disabled');
                     setTimeout(() => {
                         upvoteArrow.style.pointerEvents = 'auto';
                         downvoteArrow.style.pointerEvents = 'auto';
                     }, 200);
                });
                 upvoteArrow.style.pointerEvents = 'none';
                 downvoteArrow.style.pointerEvents = 'none';
            }
        });
    }

    const reviewMessageElement = document.querySelector('.review-message');
    if (reviewMessageElement && reviewMessageElement.textContent.trim() !== '') {
        setTimeout(() => {
            reviewMessageElement.style.transition = 'opacity 0.5s ease-out';
            reviewMessageElement.style.opacity = '0';
            setTimeout(() => {
                reviewMessageElement.remove();
            }, 500);
        }, 5000);
    }
});
</script>

</body>
</html>