<?php
require_once 'conn.php';
require_once 'auth.php';

$gameName = $_GET["game"] ?? null;

if ($gameName == null) {
    header('location: index.php');
    exit;
}

$queryGame = "SELECT games.id, games.name, description, publishers.name as publisher, developers.name AS developer, price, date FROM games LEFT JOIN developers ON games.developer_id=developers.id LEFT JOIN publishers ON games.publisher_id=publishers.id WHERE games.name='$gameName'";
$resultGame = $conn->query($queryGame);
$gameData = $resultGame->fetch_assoc();

if (!$gameData) {
    header('location: index.php');
    exit;
}

$gameId = $gameData['id'];
$reviewMessage = null;

if (isset($_GET['add_to_cart'])) {
    if ($currentUser) {
        $userId = $currentUser['user_id'];
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

if (isset($_GET['vote']) && isset($_GET['review_id'])) {
    if ($currentUser) {
        $reviewId = (int)$_GET['review_id'];
        $voteType = $_GET['vote'];
        if ($voteType === 'up') {
            $conn->query("UPDATE reviews SET votes = votes + 1 WHERE id = $reviewId");
        } elseif ($voteType === 'down') {
            $conn->query("UPDATE reviews SET votes = votes - 1 WHERE id = $reviewId");
        }
        header('Location: game.php?game=' . urlencode($gameName) . '#reviews');
        exit;
    } else {
        header('Location: login.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser) {
    if (isset($_POST['submit_review'])) {
        $userId = $currentUser['user_id'];
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
        $userId = $currentUser['user_id'];
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
            <?= htmlspecialchars($gameName) ?>
            <?php if ($currentUser && $currentUser['role'] == 1): ?>
                <a href="gamedit.php?game=<?= urlencode($gameName) ?>" title="Edytuj grę">
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
                <h2>Kup <?= htmlspecialchars($gameName) ?></h2>
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
                        <div class="review-item" id="review-<?= $row['id'] ?>">
                            <div class="review-user-info">
                                <div class="avatar-placeholder"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="white" d="M6 22h13a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2zm6-17.001c1.647 0 3 1.351 3 3C15 9.647 13.647 11 12 11S9 9.647 9 7.999c0-1.649 1.353-3 3-3M6 17.25c0-2.219 2.705-4.5 6-4.5s6 2.281 6 4.5V18H6z"/></svg></div>
                                <span class="username"><?= htmlspecialchars($row['username']); ?></span>
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
                            <div class="review-voting" data-review-id="<?= $row['id'] ?>">
                                <a href="game.php?game=<?= urlencode($gameName) ?>&vote=up&review_id=<?= $row['id'] ?>" class="vote-arrow upvote">▲</a>
                                <span class="vote-count"><?= $row['votes'] ?></span>
                                <a href="game.php?game=<?= urlencode($gameName) ?>&vote=down&review_id=<?= $row['id'] ?>" class="vote-arrow downvote">▼</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Brak opinii dla tej gry. Bądź pierwszy!</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

<?php include 'footer.php'; ?>

<script>
let slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) { showSlides(slideIndex += n); }
function currentSlide(n) { showSlides(slideIndex = n); }

function showSlides(n) {
  let i;
  let slides = document.getElementsByClassName("slide");
  let thumbnails = document.getElementsByClassName("thumbnail");
  if (n > slides.length) {slideIndex = 1}
  if (n < 1) {slideIndex = slides.length}
  for (i = 0; i < slides.length; i++) { slides[i].style.opacity = "0"; }
  for (i = 0; i < thumbnails.length; i++) { thumbnails[i].className = thumbnails[i].className.replace(" active", ""); }
  slides[slideIndex-1].style.opacity = "1";
  thumbnails[slideIndex-1].className += " active";
}

function toggleEditForm(reviewId) {
    const body = document.getElementById('review-body-' + reviewId);
    const form = document.getElementById('review-edit-form-' + reviewId);
    if (body.style.display === 'none') {
        body.style.display = 'block';
        form.style.display = 'none';
    } else {
        body.style.display = 'none';
        form.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const platformSvgs = {
        'windows': `<svg viewBox="0 0 56.693 56.693" xmlns="http://www.w3.org/2000/svg"><g><path d="M3.765,46.362l19.836,2.873V30.257H3.765V46.362z M3.765,27.546h19.836V8.566L3.765,11.439V27.546z M26.312,49.628 l26.616,3.855V30.257H26.312V49.628z M26.312,8.172v19.374h26.616V4.319L26.312,8.172z"/></g></svg>`,
        'playstation': `<svg fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M15.858 11.451c-.313.395-1.079.676-1.079.676l-5.696 2.046v-1.509l4.192-1.493c.476-.17.549-.412.162-.538-.386-.127-1.085-.09-1.56.08l-2.794.984v-1.566l.161-.054s.807-.286 1.942-.412c1.135-.125 2.525.017 3.616.43 1.23.39 1.368.962 1.056 1.356ZM9.625 8.883v-3.86c0-.453-.083-.87-.508-.988-.326-.105-.528.198-.528.65v9.664l-2.606-.827V2c1.108.206 2.722.692 3.59.985 2.207.757 2.955 1.7 2.955 3.825 0 2.071-1.278 2.856-2.903 2.072Zm-8.424 3.625C-.061 12.15-.271 11.41.304 10.984c.532-.394 1.436-.69 1.436-.69l3.737-1.33v1.515l-2.69.963c-.474.17-.547.411-.161.538.386.126 1.085.09 1.56-.08l1.29-.469v1.356l-.257.043a8.454 8.454 0 0 1-4.018-.323Z"/></svg>`,
        'xbox': `<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M369.9 318.2c44.3 54.3 64.7 98.8 54.4 118.7-7.9 15.1-56.7 44.6-92.6 55.9-29.6 9.3-68.4 13.3-100.4 10.2-38.2-3.7-76.9-17.4-110.1-39C93.3 445.8 87 438.3 87 423.4c0-29.9 32.9-82.3 89.2-142.1 32-33.9 76.5-73.7 81.4-72.6 9.4 2.1 84.3 75.1 112.3 109.5zM188.6 143.8c-29.7-26.9-58.1-53.9-86.4-63.4-15.2-5.1-16.3-4.8-28.7 8.1-29.2 30.4-53.5 79.7-60.3 122.4-5.4 34.2-6.1 43.8-4.2 60.5 5.6 50.5 17.3 85.4 40.5 120.9 9.5 14.6 12.1 17.3 9.3 9.9-4.2-11-.3-37.5 9.5-64 14.3-39 53.9-112.9 120.3-194.4zm311.6 63.5C483.3 127.3 432.7 77 425.6 77c-7.3 0-24.2 6.5-36 13.9-23.3 14.5-41 31.4-64.3 52.8C367.7 197 427.5 283.1 448.2 346c6.8 20.7 9.7 41.1 7.4 52.3-1.7 8.5-1.7 8.5 1.4 4.6 6.1-7.7 19.9-31.3 25.4-43.5 7.4-16.2 15-40.2 18.6-58.7 4.3-22.5 3.9-70.8-.8-93.4zM141.3 43C189 40.5 251 77.5 255.6 78.4c.7.1 10.4-4.2 21.6-9.7 63.9-31.1 94-25.8 107.4-25.2-63.9-39.3-152.7-50-233.9-11.7-23.4 11.1-24 11.9-9.4 11.2z"/></svg>`,
        'nintendo': `<svg fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M9.34 8.005c0-4.38.01-7.972.023-7.982C9.373.01 10.036 0 10.831 0c1.153 0 1.51.01 1.743.05 1.73.298 3.045 1.6 3.373 3.326.046.242.053.809.053 4.61 0 4.06.005 4.537-.123 4.976-.022.076-.048.15-.08.242a4.136 4.136 0 0 1-3.426 2.767c-.317.033-2.889.046-2.978.013-.05-.02-.053-.752-.053-7.979Zm4.675.269a1.621 1.621 0 0 0-1.113-1.034 1.609 1.609 0 0 0-1.938 1.073 1.9 1.9 0 0 0-.014.935 1.632 1.632 0 0 0 1.952 1.107c.51-.136.908-.504 1.11-1.028.11-.285.113-.742.003-1.053ZM3.71 3.317c-.208.04-.526.199-.695.348-.348.301-.52.729-.494 1.232.013.262.03.332.136.544.155.321.39.556.712.715.222.11.278.123.567.133.261.01.354 0 .53-.06.719-.242 1.153-.94 1.03-1.656-.142-.852-.95-1.422-1.786-1.256Z"/><path d="M3.425.053a4.136 4.136 0 0 0-3.28 3.015C0 3.628-.01 3.956.005 8.3c.01 3.99.014 4.082.08 4.39.368 1.66 1.548 2.844 3.224 3.235.22.05.497.06 2.29.07 1.856.012 2.048.009 2.097-.04.05-.05.053-.69.053-7.94 0-5.374-.01-7.906-.033-7.952-.033-.06-.09-.063-2.03-.06-1.578.004-2.052.014-2.26.05Zm3 14.665-1.35-.016c-1.242-.013-1.375-.02-1.623-.083a2.81 2.81 0 0 1-2.08-2.167c-.074-.335-.074-8.579-.004-8.907a2.845 2.845 0 0 1 1.716-2.05c.438-.176.64-.196 2.058-.2l1.282-.003v13.426Z"/></svg>`
    };

    const platformElements = document.querySelectorAll('.platform-item');
    platformElements.forEach(element => {
        const platformName = element.dataset.platformName.toLowerCase().trim();
        if (platformSvgs[platformName]) {
            element.innerHTML = platformSvgs[platformName];
        }
    });

    const votingBlocks = document.querySelectorAll('.review-voting');
    votingBlocks.forEach(block => {
        const reviewId = block.dataset.reviewId;
        const upvoteLink = block.querySelector('.upvote');
        const downvoteLink = block.querySelector('.downvote');
        const currentVote = localStorage.getItem('vote_review_' + reviewId);

        if (currentVote) {
            upvoteLink.classList.add('disabled');
            downvoteLink.classList.add('disabled');
            if (currentVote === 'up') { upvoteLink.classList.add('voted'); } 
            else if (currentVote === 'down') { downvoteLink.classList.add('voted'); }
        }

        upvoteLink.addEventListener('click', function(e) {
            if (localStorage.getItem('vote_review_' + reviewId)) { e.preventDefault(); } 
            else { localStorage.setItem('vote_review_' + reviewId, 'up'); }
        });

        downvoteLink.addEventListener('click', function(e) {
            if (localStorage.getItem('vote_review_' + reviewId)) { e.preventDefault(); } 
            else { localStorage.setItem('vote_review_' + reviewId, 'down'); }
        });
    });
});
</script>

</body>
</html>