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
    <link rel="stylesheet" href="style/styleCommon.css">
</head>
<body>
<?php include 'header.php'; ?>

    <div id="container">
        <?php echo"<h1>$gameName</h1>"; ?>
    </div>
<?php include 'footer.php'; ?>
<script src="skrypty.js"></script>
</body>
</html>