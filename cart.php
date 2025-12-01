<?php
session_start(); // KROK 1: Dodano start sesji na samym początku
require_once 'conn.php';
require_once 'auth.php';

if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$userId = $currentUser['user_id'];
$message = '';
$messageType = '';

function calculateGamePrice($basePrice, $discountPercent) {
    if ($discountPercent !== null && $discountPercent > 0) {
        $discountAmount = ($basePrice * $discountPercent) / 100;
        return round($basePrice - $discountAmount, 2);
    }
    return $basePrice;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['remove_game_id'])) {
        $gameIdToRemove = (int)$_POST['remove_game_id'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND game_id = ?");
        $stmt->bind_param("ii", $userId, $gameIdToRemove);
        if ($stmt->execute()) {
            $message = "Gra została usunięta z koszyka.";
            $messageType = "success";
        } else {
            $message = "Wystąpił błąd podczas usuwania.";
            $messageType = "error";
        }
    }

    if (isset($_POST['checkout'])) {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
            SELECT g.id, g.price, MAX(p.discount_percent) as discount_percent 
            FROM cart c
            JOIN games g ON c.game_id = g.id
            LEFT JOIN promotion_games pg ON g.id = pg.game_id
            LEFT JOIN promotions p ON pg.promotion_id = p.id AND NOW() BETWEEN p.start_date AND p.end_date
            WHERE c.user_id = ?
            GROUP BY c.game_id
        ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            if (empty($cartItems)) {
                throw new Exception("Twój koszyk jest pusty.");
            }

            $totalAmount = 0.00;
            foreach ($cartItems as $item) {
                $price = calculateGamePrice($item['price'], $item['discount_percent']);
                $totalAmount += $price;
            }

            $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE"); 
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userBalance = $stmt->get_result()->fetch_assoc()['balance'];

            if ($userBalance < $totalAmount) {
                throw new Exception("Niewystarczające środki na koncie. Brakuje: " . number_format($totalAmount - $userBalance, 2) . " PLN");
            }

            $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $totalAmount, $userId);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO transactions (user_id, total_amount) VALUES (?, ?)");
            $stmt->bind_param("id", $userId, $totalAmount);
            $stmt->execute();
            $transactionId = $conn->insert_id;

            foreach ($cartItems as $item) {
                $finalPrice = calculateGamePrice($item['price'], $item['discount_percent']);
                
                $stmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, game_id, purchase_price, discount_percent_applied) VALUES (?, ?, ?, ?)");
                $discountToSave = $item['discount_percent'] ?? 0;
                $stmt->bind_param("iidi", $transactionId, $item['id'], $finalPrice, $discountToSave);
                $stmt->execute();

                $stmt = $conn->prepare("INSERT IGNORE INTO user_games (user_id, game_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $userId, $item['id']);
                $stmt->execute();
            }

            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            $conn->commit();
            
            $_SESSION['flash_message'] = "Zakup udany! Gry zostały dodane do Twojej biblioteki.";
            $_SESSION['flash_message_type'] = "success"; 

            session_write_close();

            header("Location: user.php?user=" . urlencode($currentUser['username']));
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Błąd transakcji: " . $e->getMessage();
        }
    }
}

$stmt = $conn->prepare("
    SELECT g.id, g.name, g.price, MAX(p.discount_percent) as discount_percent, g.name AS img_name
    FROM cart c
    JOIN games g ON c.game_id = g.id
    LEFT JOIN promotion_games pg ON g.id = pg.game_id
    LEFT JOIN promotions p ON pg.promotion_id = p.id AND NOW() BETWEEN p.start_date AND p.end_date
    WHERE c.user_id = ?
    GROUP BY c.game_id
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cartTotal = 0;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twój Koszyk</title>
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="style/styleCart.css">
    <link rel="icon" type="image/png" href="img/others/logo_steam_icon.png">

</head>
<body>
<?php include 'header.php'; ?>

<div class="cart-container">
    <h1 class="cart-title">TWÓJ KOSZYK</h1>

    <?php if ($message): ?>
        <div class="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="cart-items">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $originalPrice = (float)$row['price'];
                    $finalPrice = calculateGamePrice($originalPrice, $row['discount_percent']);
                    $cartTotal += $finalPrice;
                    $safeGameName = preg_replace('/[^a-z0-9_-]/', '_', strtolower($row['img_name']));
                    $imgSrc = "img/games/" . $safeGameName . "_1.jpg";
                    if (!file_exists($imgSrc)) $imgSrc = "img/others/placeholder.jpg";
                ?>
                <div class="cart-item">
                    <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="cart-item-img">
                    
                    <div class="cart-item-details">
                        <a href="game.php?game=<?= urlencode($row['name']) ?>" class="cart-item-name">
                            <?= htmlspecialchars($row['name']) ?>
                        </a>
                    </div>

                    <div class="cart-item-actions">
                        <div class="cart-price-box">
                            <?php if ($row['discount_percent'] > 0): ?>
                                <span class="discount-badge">-<?= $row['discount_percent'] ?>%</span>
                                <span class="price-original"><?= number_format($originalPrice, 2) ?> PLN</span>
                                <span class="price-final price-discounted"><?= number_format($finalPrice, 2) ?> PLN</span>
                            <?php else: ?>
                                <span class="price-final"><?= number_format($finalPrice, 2) ?> PLN</span>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" action="cart.php" style="margin: 0;">
                            <input type="hidden" name="remove_game_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="remove-btn">Usuń</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="cart-summary">
            <div>
                <div class="total-label">Przewidywana suma:</div>
                <div class="total-price"><?= number_format($cartTotal, 2) ?> PLN</div>
                <div class="balance">
                    Twój portfel: <?= number_format($currentUser['balance'], 2) ?> PLN
                </div>
            </div>
            
            <form method="POST" action="cart.php">
                <input type="hidden" name="checkout" value="1">
                <?php if ($currentUser['balance'] >= $cartTotal): ?>
                    <button type="submit" class="checkout-btn">Kup dla siebie</button>
                <?php else: ?>
                    <button type="button" class="checkout-btn" disabled title="Brak wystarczających środków">Za mało środków</button>
                <?php endif; ?>
            </form>
        </div>

    <?php else: ?>
        <div class="empty-cart">
            Twój koszyk jest pusty.<br><br>
            <a href="store.php" class="checkout-btn" style="padding: 10px 20px; font-size: 14px;">Przeglądaj sklep</a>
        </div>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>

</body>
</html>