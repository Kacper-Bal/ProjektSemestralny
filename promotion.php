<?php
session_start();
require_once('conn.php');
require_once('auth.php');

if (!$currentUser || $currentUser['role'] != 1) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

if (isset($_SESSION['promo_message'])) {
    $message = $_SESSION['promo_message'];
    $messageType = $_SESSION['promo_message_type'] ?? 'info';
    unset($_SESSION['promo_message']);
    unset($_SESSION['promo_message_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['create_promotion'])) {
        $name = trim($_POST['promo_name']);
        $discount = (int)$_POST['discount'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $selectedGames = isset($_POST['selected_games']) ? explode(',', $_POST['selected_games']) : [];

        if (!empty($name) && $discount > 0 && $discount < 100 && !empty($startDate) && !empty($endDate) && !empty($selectedGames)) {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO promotions (name, discount_percent, start_date, end_date) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siss", $name, $discount, $startDate, $endDate);
                $stmt->execute();
                $promoId = $conn->insert_id;

                $stmtGame = $conn->prepare("INSERT INTO promotion_games (promotion_id, game_id) VALUES (?, ?)");
                foreach ($selectedGames as $gameId) {
                    if (is_numeric($gameId)) {
                        $stmtGame->bind_param("ii", $promoId, $gameId);
                        $stmtGame->execute();
                    }
                }
                
                $conn->commit();
                
                $_SESSION['promo_message'] = "Promocja została utworzona pomyślnie!";
                $_SESSION['promo_message_type'] = "success";
                header("Location: promotion.php");
                exit;

            } catch (Exception $e) {
                $conn->rollback();
                $message = "Błąd podczas tworzenia promocji: " . $e->getMessage();
                $messageType = "error";
                }
        } else {
            $message = "Wypełnij wszystkie pola i wybierz przynajmniej jedną grę.";
            $messageType = "error";
        }
    }

    if (isset($_POST['delete_promotion'])) {
        $promoId = (int)$_POST['promo_id'];
        $stmt = $conn->prepare("DELETE FROM promotions WHERE id = ?");
        $stmt->bind_param("i", $promoId);
        if ($stmt->execute()) {
            $_SESSION['promo_message'] = "Promocja została usunięta.";
            $_SESSION['promo_message_type'] = "success";
            header("Location: promotion.php");
            exit;
        } else {
            $message = "Błąd usuwania promocji.";
            $messageType = "error";
        }
    }

    if (isset($_POST['remove_game_from_promo'])) {
        $linkId = (int)$_POST['link_id']; 
        $stmt = $conn->prepare("DELETE FROM promotion_games WHERE id = ?");
        $stmt->bind_param("i", $linkId);
        if ($stmt->execute()) {
            $_SESSION['promo_message'] = "Gra została usunięta z promocji.";
            $_SESSION['promo_message_type'] = "success";
            header("Location: promotion.php");
            exit;
        } else {
            $message = "Błąd usuwania gry.";
            $messageType = "error";
        }
    }
}

$sqlGames = "SELECT id, name FROM games ORDER BY name ASC";
$resultGames = $conn->query($sqlGames);

$promos = [];
$sqlPromos = "SELECT p.id as promo_id, p.name as promo_name, p.discount_percent, p.start_date, p.end_date, 
                     pg.id as link_id, g.name as game_name 
              FROM promotions p 
              LEFT JOIN promotion_games pg ON p.id = pg.promotion_id 
              LEFT JOIN games g ON pg.game_id = g.id 
              ORDER BY p.start_date DESC, p.id DESC";
$resultPromos = $conn->query($sqlPromos);

if ($resultPromos) {
    while ($row = $resultPromos->fetch_assoc()) {
        $pid = $row['promo_id'];
        if (!isset($promos[$pid])) {
            $promos[$pid] = [
                'name' => $row['promo_name'],
                'discount' => $row['discount_percent'],
                'start' => $row['start_date'],
                'end' => $row['end_date'],
                'games' => []
            ];
        }
        if ($row['link_id']) {
            $promos[$pid]['games'][] = [
                'link_id' => $row['link_id'],
                'game_name' => $row['game_name']
            ];
        }
    }
}

function getGameImage($gameName) {
    $name = strtolower($gameName);
    $filename = preg_replace('/[^a-z0-9\-]/', '_', $name);
    $possiblePath = "img/games/{$filename}_1.jpg";
    if (file_exists($possiblePath)) return $possiblePath;
    
    $simpleName = str_replace(' ', '_', $name);
    $simplePath = "img/games/{$simpleName}_1.jpg";
    if (file_exists($simplePath)) return $simplePath;

    return "img/others/loginBackground.jpg"; 
}

require_once('header.php');
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Promotions</title>
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="style/styleStore.css">
    
</head>
<body>

<div class="store-container">
    <aside class="promo-sidebar">
        
        <?php if ($message): ?>
            <div class="profile-message <?php echo $messageType == 'success' ? 'msg-success' : 'msg-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <h2 class="section-title">Nowa Promocja</h2>
        <form method="POST" id="createPromoForm">
            <div class="promo-form-group">
                <label>Nazwa promocji</label>
                <input type="text" name="promo_name" class="promo-input" placeholder="np. Wyprzedaż Zimowa" required>
            </div>
            
            <div class="promo-form-group">
                <label>Zniżka (%)</label>
                <input type="number" name="discount" class="promo-input" min="1" max="99" placeholder="np. 20" required>
            </div>

            <div class="promo-form-group">
                <label>Data rozpoczęcia</label>
                <input type="datetime-local" name="start_date" class="promo-input" required>
            </div>

            <div class="promo-form-group">
                <label>Data zakończenia</label>
                <input type="datetime-local" name="end_date" class="promo-input" required>
            </div>

            <input type="hidden" name="selected_games" id="selectedGamesInput">
            <input type="hidden" name="create_promotion" value="1">

            <p class="selection-info">
                Wybrano gier: <strong id="selectedCount" class="selection-count">0</strong>
            </p>

            <button type="submit" class="btn-create">Utwórz Promocję</button>
        </form>

        <br><br>

        <h2 class="section-title">Aktywne Promocje</h2>
        <?php if (empty($promos)): ?>
            <p style="color: var(--color-text-secondary);">Brak zdefiniowanych promocji.</p>
        <?php else: ?>
            <?php foreach ($promos as $pid => $pdata): ?>
                <div class="promo-list-item">
                    <div class="promo-header">
                        <span class="promo-title"><?php echo htmlspecialchars($pdata['name']); ?> (-<?php echo $pdata['discount']; ?>%)</span>
                        <form method="POST" onsubmit="return confirm('Czy na pewno usunąć całą promocję?');" class="inline-form">
                            <input type="hidden" name="promo_id" value="<?php echo $pid; ?>">
                            <input type="hidden" name="delete_promotion" value="1">
                            <button type="submit" class="btn-delete-promo">Usuń</button>
                        </form>
                    </div>
                    
                    <div class="promo-meta">
                        Od: <?php echo date('d.m.Y H:i', strtotime($pdata['start'])); ?><br>
                        Do: <?php echo date('d.m.Y H:i', strtotime($pdata['end'])); ?>
                    </div>

                    <div class="promo-games-label">Gry w promocji:</div>
                    <ul class="promo-games-list">
                        <?php if (empty($pdata['games'])): ?>
                            <li class="promo-no-games">Brak gier</li>
                        <?php else: ?>
                            <?php foreach ($pdata['games'] as $pgame): ?>
                                <li class="promo-game-item">
                                    <span><?php echo htmlspecialchars($pgame['game_name']); ?></span>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="link_id" value="<?php echo $pgame['link_id']; ?>">
                                        <input type="hidden" name="remove_game_from_promo" value="1">
                                        <button type="submit" class="btn-remove-small">x</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </aside>

    <main class="store-content">
        <div class="store-search-bar">
            <input type="text" id="gameSearch" placeholder="Filtruj gry do wyboru...">
        </div>

        <h3 class="section-title">Kliknij na okładkę, aby dodać grę do nowej promocji</h3>
        
        <div class="games-grid" id="gamesGrid">
            <?php 
            if ($resultGames && $resultGames->num_rows > 0) {
                while($row = $resultGames->fetch_assoc()) {
                    $imgSrc = getGameImage($row['name']);
                    echo '
                    <div class="game-card selectable-game-card" data-id="' . $row['id'] . '" data-name="' . strtolower(htmlspecialchars($row['name'])) . '">
                        <img src="' . $imgSrc . '" alt="' . htmlspecialchars($row['name']) . '" class="game-card-img" loading="lazy">
                        <div class="game-info-overlay">
                            <div class="game-title">' . htmlspecialchars($row['name']) . '</div>
                        </div>
                    </div>';
                }
            } else {
                echo '<p>Brak gier w bazie.</p>';
            }
            ?>
        </div>
    </main>
</div>

<?php require_once('footer.php'); ?>

<script src="js/user.js"></script>
<script src="js/promotion.js"></script>

</body>
</html>