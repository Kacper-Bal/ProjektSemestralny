<?php
require_once 'conn.php'; // Połączenie z bazą danych
require_once 'auth.php'; // Autoryzacja użytkownika ($currentUser)

$userId = $currentUser['user_id'] ?? null; // Pobierz ID zalogowanego użytkownika, jeśli istnieje

// --- Sprawdzenie, czy to żądanie AJAX do GŁOSOWANIA ---
if (isset($_GET['action']) && $_GET['action'] == 'vote' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$userId) { // Sprawdź, czy użytkownik jest zalogowany
        echo json_encode(['success' => false, 'error' => 'not_logged_in']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $reviewId = isset($input['reviewId']) ? (int)$input['reviewId'] : 0;
    $voteTypeParam = isset($input['voteType']) ? $input['voteType'] : null; // 'up' or 'down'

    if ($reviewId <= 0 || !in_array($voteTypeParam, ['up', 'down'])) {
        echo json_encode(['success' => false, 'error' => 'invalid_input']);
        exit;
    }

    $voteType = ($voteTypeParam === 'up') ? 1 : -1;
    $newVoteCount = 0;
    $newUserVoteStatus = 0; // 0 = brak, 1 = up, -1 = down

    $conn->begin_transaction();
    try {
        // Sprawdź istniejący głos
        $checkVoteQuery = "SELECT vote_type FROM review_votes WHERE user_id = $userId AND review_id = $reviewId";
        $voteResult = $conn->query($checkVoteQuery);
        $existingVote = $voteResult ? $voteResult->fetch_assoc() : null;

        if ($existingVote) {
            $existingVoteType = (int)$existingVote['vote_type'];
            if ($existingVoteType === $voteType) { // Odwołanie głosu
                $conn->query("DELETE FROM review_votes WHERE user_id = $userId AND review_id = $reviewId");
                $conn->query("UPDATE reviews SET votes = votes - $voteType WHERE id = $reviewId");
                $newUserVoteStatus = 0;
            } else { // Zmiana głosu
                $conn->query("UPDATE review_votes SET vote_type = $voteType WHERE user_id = $userId AND review_id = $reviewId");
                $conn->query("UPDATE reviews SET votes = votes + (2 * $voteType) WHERE id = $reviewId"); // +1 za nowy, +1 za usunięcie przeciwnego
                $newUserVoteStatus = $voteType;
            }
        } else { // Nowy głos
            $conn->query("INSERT INTO review_votes (user_id, review_id, vote_type) VALUES ($userId, $reviewId, $voteType)");
            $conn->query("UPDATE reviews SET votes = votes + $voteType WHERE id = $reviewId");
            $newUserVoteStatus = $voteType;
        }

        // Pobierz zaktualizowaną liczbę głosów
        $newCountResult = $conn->query("SELECT votes FROM reviews WHERE id = $reviewId");
        if ($newCountResult) {
            $newVoteCount = (int)$newCountResult->fetch_assoc()['votes'];
        }

        $conn->commit();
        echo json_encode(['success' => true, 'newVoteCount' => $newVoteCount, 'newUserVoteStatus' => $newUserVoteStatus]);

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        error_log("Błąd głosowania AJAX: " . $exception->getMessage()); // Logowanie błędu
        echo json_encode(['success' => false, 'error' => 'database_error']);
    }
    exit; // --- WAŻNE: Zakończ skrypt po wysłaniu JSON ---

}
// --- Sprawdzenie, czy to żądanie AJAX do POBIERANIA RECENZJI ---
elseif (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');

    $itemsPerPage = 30;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'popular';
    $allowedSorts = ['popular', 'newest', 'oldest'];
    if (!in_array($sort, $allowedSorts)) $sort = 'popular';

    $orderBy = '';
    switch ($sort) {
        case 'newest': $orderBy = 'ORDER BY r.created_at DESC'; break;
        case 'oldest': $orderBy = 'ORDER BY r.created_at ASC'; break;
        case 'popular': default: $orderBy = 'ORDER BY r.votes DESC, r.created_at DESC'; break;
    }

    $offset = ($page - 1) * $itemsPerPage;

    $totalResult = $conn->query("SELECT COUNT(*) as total FROM reviews");
    $totalReviews = ($totalResult) ? (int)$totalResult->fetch_assoc()['total'] : 0;
    $totalPages = ceil($totalReviews / $itemsPerPage);
    if ($totalPages == 0) $totalPages = 1;

    // --- Pobieranie głosów użytkownika (jeśli zalogowany) ---
    $userVotes = [];
    if ($userId) {
        $reviewIdsOnPageQuery = "SELECT r.id FROM reviews r $orderBy LIMIT $itemsPerPage OFFSET $offset";
        $idsResult = $conn->query($reviewIdsOnPageQuery);
        $reviewIds = [];
        if($idsResult) {
            while($idRow = $idsResult->fetch_assoc()) { $reviewIds[] = $idRow['id']; }
        }
        if (!empty($reviewIds)) {
            $idsString = implode(',', $reviewIds);
            $votesQuery = "SELECT review_id, vote_type FROM review_votes WHERE user_id = $userId AND review_id IN ($idsString)";
            $votesResult = $conn->query($votesQuery);
            if ($votesResult) {
                while ($row = $votesResult->fetch_assoc()) { $userVotes[$row['review_id']] = (int)$row['vote_type']; }
            }
        }
    }

    // --- Pobieranie recenzji ---
    $reviews = [];
    $query = "SELECT r.id, r.rating, r.comment, r.created_at, r.votes, u.username, g.name AS game_name, g.id as game_id
              FROM reviews r
              JOIN users u ON r.user_id = u.id
              JOIN games g ON r.game_id = g.id
              $orderBy
              LIMIT $itemsPerPage OFFSET $offset"; // Dodano g.id

    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) { $reviews[] = $row; }
    }

    // --- Generowanie HTML ---
    $html = '';
    if (!empty($reviews)) {
        foreach ($reviews as $review) {
            $isLarge = ($sort === 'popular' && $review['votes'] > 5);
            $tileClass = $isLarge ? 'review-tile--large' : 'review-tile--standard';

            $safeGameName = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($review['game_name']));
            $imagePath = "img/games/{$safeGameName}_1.jpg";
             if (!file_exists($imagePath)) {
                 $files = glob("img/games/{$safeGameName}_*.{jpg,png,jpeg}", GLOB_BRACE);
                 $imagePath = !empty($files) ? $files[0] : 'img/others/placeholder.jpg';
             }

            $ratingStars = str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']);
            $formattedDate = date('d.m.Y', strtotime($review['created_at']));
            $userVote = $userVotes[$review['id']] ?? 0;
            $upvoteClass = ($userVote === 1) ? 'voted' : '';
            $downvoteClass = ($userVote === -1) ? 'voted' : '';
            $commentText = nl2br(htmlspecialchars($review['comment']));

            // --- ZMIANA: Dodanie linku do gry na obrazku ---
            $gameUrl = 'game.php?game=' . urlencode($review['game_name']);
            // --- ZMIANA: Dodanie linku do profilu użytkownika ---
            $userUrl = 'user.php?user=' . urlencode($review['username']);
            // --- ZMIANA: Dodanie SVG awatara ---
            $avatarSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M6 22h13a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2zm6-17.001c1.647 0 3 1.351 3 3C15 9.647 13.647 11 12 11S9 9.647 9 7.999c0-1.649 1.353-3 3-3M6 17.25c0-2.219 2.705-4.5 6-4.5s6 2.281 6 4.5V18H6z"/></svg>';

            $html .= <<<HTML
            <div class="review-tile {$tileClass}">
                <a href="{$gameUrl}" class="review-game-link">
                    <img src="{$imagePath}" alt="Okładka gry {$review['game_name']}" class="review-game-image">
                </a>
                <div class="review-tile-header">
                    <a href="{$userUrl}" class="review-user-link">
                        <div class="avatar-placeholder">{$avatarSvg}</div>
                        <span class="review-username">{$review['username']}</span>
                    </a>
                    <span class="review-rating">{$ratingStars}</span>
                </div>
                <div class="review-comment-text-container">
                    <p class="review-comment-text">{$commentText}</p>
                </div>
                <div class="review-tile-footer">
                    <span class="review-date">{$formattedDate}</span>
                    <div class="review-voting" data-review-id="{$review['id']}">
                         <a href="#" class="vote-arrow upvote {$upvoteClass}" data-vote="up">▲</a>
                         <span class="vote-count">{$review['votes']}</span>
                         <a href="#" class="vote-arrow downvote {$downvoteClass}" data-vote="down">▼</a>
                    </div>
                </div>
            </div>
HTML;
        }
    } else {
        $html = '<p style="text-align: center; width: 100%; color: #76808c;">Brak recenzji do wyświetlenia dla wybranych kryteriów.</p>';
    }

    echo json_encode([ 'html' => $html, 'totalPages' => $totalPages, 'currentPage' => $page ]);
    exit;

} // Koniec bloku obsługi AJAX POBIERANIA

// --- Normalne ładowanie strony HTML ---
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Społeczność - Recenzje</title>
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="style/styleCommunity.css">
</head>
<body>
    <?php include('header.php'); ?>

    <div id="community-content">
        <h1>Recenzje Społeczności</h1>

        <div class="sort-slider">
            <div class="sort-options">
                <button class="sort-option active" data-sort="popular">Najpopularniejsze</button>
                <button class="sort-option" data-sort="newest">Najnowsze</button>
                <button class="sort-option" data-sort="oldest">Najstarsze</button>
            </div>
            <div class="sort-indicator"></div>
        </div>

        <div class="reviews-container">
            <div class="loading-indicator" style="text-align: center; padding: 50px; color: #76808c;">Ładowanie recenzji...</div>
        </div>

        <div class="pagination-controls">
            <button class="pagination-button prev" disabled>&laquo; Poprzednia</button>
            <span class="page-info">Strona <span class="current-page">1</span> z <span class="total-pages">1</span></span>
            <button class="pagination-button next" disabled>Następna &raquo;</button>
        </div>

    </div>

    <?php include('footer.php'); ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/masonry/4.2.2/masonry.pkgd.min.js" integrity="sha512-JRlcvSZAXT8+5SQQjsjPbyIF5TC8tfz0aD6AnLDODw/bLyFOhZr+svsUeNgIZaBfWb/yUqQisYhI2xkL35PZew==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js"></script>

    <script>
        // Cały kod JavaScript pozostaje taki sam jak w poprzedniej wersji
        document.addEventListener('DOMContentLoaded', function() {
            const reviewsContainer = document.querySelector('.reviews-container');
            const sortOptions = document.querySelectorAll('.sort-option');
            const sortIndicator = document.querySelector('.sort-indicator');
            const prevButton = document.querySelector('.pagination-button.prev');
            const nextButton = document.querySelector('.pagination-button.next');
            const currentPageSpan = document.querySelector('.current-page');
            const totalPagesSpan = document.querySelector('.total-pages');
            const loadingIndicator = reviewsContainer.querySelector('.loading-indicator');


            let currentPage = 1;
            let currentSort = 'popular';
            let totalPages = 1;
            let msnry;
            let isLoading = false;

             function initializeMasonry() {
                if (msnry) {
                    try { msnry.destroy(); } catch (e) { console.warn("Error destroying Masonry:", e); }
                    msnry = null;
                }
                setTimeout(() => {
                    const itemsExist = reviewsContainer.querySelector('.review-tile');
                    if (itemsExist) {
                        imagesLoaded(reviewsContainer, function() {
                            const currentLoading = reviewsContainer.querySelector('.loading-indicator');
                            if(currentLoading) currentLoading.remove();
                             if(!document.body.contains(reviewsContainer)) return;
                            try {
                                msnry = new Masonry(reviewsContainer, {
                                    itemSelector: '.review-tile',
                                    columnWidth: '.review-tile--standard',
                                    gutter: 20,
                                    percentPosition: true,
                                });
                                console.log("Masonry initialized.");
                             } catch (e) { console.error("Error initializing Masonry:", e); }
                        });
                    } else {
                        const currentLoading = reviewsContainer.querySelector('.loading-indicator');
                        if(currentLoading) currentLoading.remove();
                        console.log("No items found, Masonry not initialized.");
                    }
                }, 100);
            }

            function loadReviews(page = 1, sort = 'popular') {
                if (isLoading) return;
                isLoading = true;

                reviewsContainer.innerHTML = '<div class="loading-indicator" style="text-align: center; padding: 50px; color: #76808c; width: 100%;">Ładowanie recenzji...</div>';
                 if (msnry) {
                    try { msnry.destroy(); } catch (e) { console.warn("Error destroying Masonry:", e); }
                    msnry = null; console.log("Masonry destroyed before loading.");
                }

                currentPage = page;
                currentSort = sort;
                console.log(`Requesting: community.php?ajax=1&page=${page}&sort=${sort}`);

                fetch(`community.php?ajax=1&page=${page}&sort=${sort}`)
                    .then(response => {
                        if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
                        return response.json();
                    })
                    .then(data => {
                        console.log("Data received:", data);
                        if(!document.body.contains(reviewsContainer)) return;

                        reviewsContainer.innerHTML = data.html;
                        totalPages = data.totalPages;
                        currentPage = data.currentPage;
                        currentPageSpan.textContent = currentPage;
                        totalPagesSpan.textContent = totalPages;
                        prevButton.disabled = (currentPage === 1);
                        nextButton.disabled = (currentPage >= totalPages);

                        initializeMasonry();
                        updateSortIndicator(document.querySelector(`.sort-option[data-sort="${sort}"]`));
                    })
                    .catch(error => {
                        console.error('Błąd ładowania recenzji:', error);
                         if(document.body.contains(reviewsContainer)) {
                             reviewsContainer.innerHTML = '<p style="color: red; text-align: center; width: 100%;">Wystąpił błąd podczas ładowania recenzji. Spróbuj ponownie później.</p>';
                         }
                    })
                    .finally(() => { isLoading = false; });
            }

            function updateSortIndicator(activeButton) {
    const slider = document.querySelector('.sort-slider'); // Znajdź główny element slidera
    if (!activeButton || !slider) return;

    setTimeout(() => { // Używamy setTimeout jak poprzednio
        const buttonRect = activeButton.getBoundingClientRect();
        const sliderRect = slider.getBoundingClientRect(); // Pobierz wymiary slidera

        if (buttonRect.width > 0 && sliderRect.width > 0) {
            const highlightLeft = buttonRect.left - sliderRect.left;
            const highlightWidth = buttonRect.width;

            // Ustaw zmienne CSS na elemencie .sort-slider
            slider.style.setProperty('--slider-highlight-left', `${highlightLeft}px`);
            slider.style.setProperty('--slider-highlight-width', `${highlightWidth}px`);
            console.log(`Slider highlight updated: left=${highlightLeft}px, width=${highlightWidth}px`); // Debug log
        } else {
            console.warn("Could not get valid button dimensions for slider highlight.");
        }
     }, 0);

     // Logika dodawania/usuwania klasy 'active' pozostaje bez zmian
     sortOptions.forEach(opt => opt.classList.remove('active'));
     activeButton.classList.add('active');
}

            sortOptions.forEach(option => {
                option.addEventListener('click', function() {
                    if (isLoading) return;
                    const newSort = this.dataset.sort;
                    if (newSort !== currentSort) { loadReviews(1, newSort); }
                });
            });

            prevButton.addEventListener('click', function() {
                if (currentPage > 1 && !isLoading) { loadReviews(currentPage - 1, currentSort); }
            });

            nextButton.addEventListener('click', function() {
                if (currentPage < totalPages && !isLoading) { loadReviews(currentPage + 1, currentSort); }
            });

            loadReviews(currentPage, currentSort);
             setTimeout(() => { updateSortIndicator(document.querySelector('.sort-option.active')); }, 100);

            window.addEventListener('resize', () => {
                 updateSortIndicator(document.querySelector('.sort-option.active'));
                 if (msnry) { try { msnry.layout(); } catch (e) { console.warn("Error relaying out Masonry:", e); } }
            });

            reviewsContainer.addEventListener('click', function(event) {
                const target = event.target.closest('.vote-arrow');
                if (target && !isLoading) {
                    event.preventDefault();
                     <?php if (!$currentUser): ?> window.location.href = 'login.php'; return; <?php endif; ?>

                    const voteType = target.dataset.vote;
                    const reviewItem = target.closest('.review-voting');
                    const reviewId = reviewItem.dataset.reviewId;
                    const voteCountSpan = reviewItem.querySelector('.vote-count');
                    const upvoteArrow = reviewItem.querySelector('.upvote');
                    const downvoteArrow = reviewItem.querySelector('.downvote');

                    upvoteArrow.style.pointerEvents = 'none';
                    downvoteArrow.style.pointerEvents = 'none';

                    fetch('community.php?action=vote', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ reviewId: reviewId, voteType: voteType })
                    })
                    .then(response => {
                         if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
                         return response.json();
                    })
                    .then(data => {
                        console.log("Vote response:", data);
                        if (data.success) {
                            voteCountSpan.textContent = data.newVoteCount;
                            upvoteArrow.classList.toggle('voted', data.newUserVoteStatus === 1);
                            downvoteArrow.classList.toggle('voted', data.newUserVoteStatus === -1);
                        } else if (data.error === 'not_logged_in') { window.location.href = 'login.php';
                        } else { console.error('Błąd głosowania:', data.error || 'Nieznany błąd serwera'); }
                    })
                    .catch(error => { console.error('Błąd sieci podczas głosowania:', error); })
                    .finally(() => {
                        upvoteArrow.style.pointerEvents = 'auto';
                        downvoteArrow.style.pointerEvents = 'auto';
                    });
                }
            });
        });
    </script>
</body>
</html>