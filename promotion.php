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
    <style>
        .promo-editor-container {
            display: flex;
            gap: 20px;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
            align-items: flex-start;
        }
        
        .promo-sidebar {
            width: 30%;
            min-width: 300px;
            background-color: var(--color-bg-container);
            padding: 20px;
            border-radius: 5px;
            position: sticky;
            top: 100px;
            max-height: 85vh;
            overflow-y: auto;
            border: 1px solid var(--color-border-transparent);
        }

        .promo-main {
            width: 70%;
        }

        .promo-form-group {
            margin-bottom: 15px;
        }
        .promo-form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--color-text-secondary);
        }
        .promo-input {
            width: 100%;
            padding: 10px;
            background-color: var(--color-bg-input);
            border: 1px solid var(--color-border-mid);
            color: var(--color-text-light);
            border-radius: 3px;
        }
        .btn-create {
            width: 100%;
            padding: 12px;
            background-color: var(--color-button-blue);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            border-radius: 3px;
            transition: background 0.2s;
        }
        .btn-create:hover {
            background-color: var(--color-button-blue-hover);
        }

        .promo-list-item {
            background-color: var(--color-bg-panel-dark);
            border: 1px solid var(--color-border-mid);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .promo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            border-bottom: 1px solid var(--color-border-dark-transparent);
            padding-bottom: 8px;
        }
        .promo-title {
            font-weight: bold;
            color: var(--color-text-light);
            font-size: 1.1rem;
        }
        .promo-meta {
            font-size: 0.85rem;
            color: var(--color-text-secondary);
            margin-bottom: 10px;
        }
        .promo-games-list {
            list-style: none;
            padding: 0;
            max-height: 150px;
            overflow-y: auto;
        }
        .promo-game-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 0.9rem;
            color: var(--color-text-main);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .btn-remove-small {
            background: none;
            border: none;
            color: #c0392b;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .btn-remove-small:hover { text-decoration: underline; }

        .btn-delete-promo {
            background-color: #c0392b;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .selectable-game-card {
            cursor: pointer;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .selectable-game-card.selected {
            border: 3px solid #2ecc71; 
            transform: scale(0.98);
        }
        .selectable-game-card.selected::after {
            content: "✓";
            position: absolute;
            top: 10px;
            right: 10px;
            background: #2ecc71;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }
        
        .section-title {
            color: var(--color-text-light);
            margin-bottom: 15px;
            border-bottom: 1px solid var(--color-border-main);
            padding-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="promo-editor-container">
    <aside class="promo-sidebar">
        
        <?php if ($message): ?>
            <div style="padding: 10px; margin-bottom: 15px; border-radius: 3px; 
                background-color: <?php echo $messageType == 'success' ? '#2ecc71' : '#c0392b'; ?>; color: white;">
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

            <p style="margin-bottom: 10px; color: var(--color-text-secondary);">
                Wybrano gier: <strong id="selectedCount" style="color: var(--color-text-light);">0</strong>
            </p>

            <button type="submit" class="btn-create" onclick="return validateSelection()">Utwórz Promocję</button>
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
                        <form method="POST" onsubmit="return confirm('Czy na pewno usunąć całą promocję?');" style="margin:0;">
                            <input type="hidden" name="promo_id" value="<?php echo $pid; ?>">
                            <input type="hidden" name="delete_promotion" value="1">
                            <button type="submit" class="btn-delete-promo">Usuń</button>
                        </form>
                    </div>
                    
                    <div class="promo-meta">
                        Od: <?php echo date('d.m.Y H:i', strtotime($pdata['start'])); ?><br>
                        Do: <?php echo date('d.m.Y H:i', strtotime($pdata['end'])); ?>
                    </div>

                    <div style="font-size: 0.9rem; font-weight:bold; margin-bottom: 5px;">Gry w promocji:</div>
                    <ul class="promo-games-list">
                        <?php if (empty($pdata['games'])): ?>
                            <li style="color: grey; font-style: italic;">Brak gier</li>
                        <?php else: ?>
                            <?php foreach ($pdata['games'] as $pgame): ?>
                                <li class="promo-game-item">
                                    <span><?php echo htmlspecialchars($pgame['game_name']); ?></span>
                                    <form method="POST" style="margin:0;">
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

    <main class="promo-main">
        <div class="store-search-bar">
            <input type="text" id="gameSearch" placeholder="Filtruj gry do wyboru...">
        </div>

        <h3 class="section-title">Kliknij na okładkę, aby dodać grę do nowej promocji</h3>
        
        <div class="games-grid" id="gamesGrid">
            <?php 
            if ($resultGames && $resultGames->num_rows > 0) {
                while($row = $resultGames->fetch_assoc()) {
                    $imgSrc = getGameImage($row['name']);
                    // Używamy klasy game-card z store.css ale dodajemy klasę 'selectable-game-card' do logiki JS
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

<script>
    const searchInput = document.getElementById('gameSearch');
    const cards = document.querySelectorAll('.selectable-game-card');
    const selectedCountSpan = document.getElementById('selectedCount');
    const hiddenInput = document.getElementById('selectedGamesInput');
    
    let selectedGames = new Set();

    cards.forEach(card => {
        card.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            
            if (selectedGames.has(id)) {
                selectedGames.delete(id);
                this.classList.remove('selected');
            } else {
                selectedGames.add(id);
                this.classList.add('selected');
            }
            updateForm();
        });
    });

    function updateForm() {
        const array = Array.from(selectedGames);
        hiddenInput.value = array.join(',');
        selectedCountSpan.textContent = array.length;
    }

    function validateSelection() {
        if (selectedGames.size === 0) {
            alert("Wybierz przynajmniej jedną grę z listy po prawej stronie!");
            return false;
        }
        return true;
    }

    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            if (name.includes(filter)) {
                card.style.display = "block";
            } else {
                card.style.display = "none";
            }
        });
    });
</script>

</body>
</html>