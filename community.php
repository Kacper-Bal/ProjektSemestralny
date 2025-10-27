<?php
require_once 'conn.php';
require_once 'auth.php';

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
        error_log("Błąd głosowania AJAX: " . $exception->getMessage());
        echo json_encode(['success' => false, 'error' => 'database_error']);
    }
    exit;

}
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

    $userVotes = [];
    if ($userId) {
        $reviewIdsOnPageQuery = "SELECT r.id FROM reviews r JOIN users u ON r.user_id = u.id JOIN games g ON r.game_id = g.id $orderBy LIMIT $itemsPerPage OFFSET $offset";
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

    $reviews = [];
    $query = "SELECT r.id, r.rating, r.comment, r.created_at, r.votes, u.username, u.avatar_filename, g.name AS game_name, g.id as game_id
              FROM reviews r
              JOIN users u ON r.user_id = u.id
              JOIN games g ON r.game_id = g.id
              $orderBy
              LIMIT $itemsPerPage OFFSET $offset";

    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) { $reviews[] = $row; }
    }

    $html = '';
    if (!empty($reviews)) {
        foreach ($reviews as $review) {
            $randomRoll = rand(1, 10);

            switch ($randomRoll) {
                case 1: $tileClass = 'review-tile--m'; break;
                case 2: $tileClass = 'review-tile--m'; break;
                case 3: $tileClass = 'review-tile--l'; break;
                case 4: $tileClass = 'review-tile--tall-s'; break;
                case 5: $tileClass = 'review-tile--tall-s'; break;
                case 6: $tileClass = 'review-tile--tall-m'; break;
                default: $tileClass = 'review-tile--s'; break;
            }


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
            $gameUrl = 'game.php?game=' . urlencode($review['game_name']);
            $userUrl = 'user.php?user=' . urlencode($review['username']);
            $avatarFilename = htmlspecialchars($review['avatar_filename'] ?? 'default_avatar.png');
            $avatarAlt = "Awatar " . htmlspecialchars($review['username']);
            $avatarImgTag = "<img src='img/avatars/{$avatarFilename}' alt='{$avatarAlt}' style='width: 30px; height: 30px; border-radius: 3px; object-fit: cover;'>";


            $html .= <<<HTML
            <div class="review-tile {$tileClass}">
                <a href="{$gameUrl}" class="review-game-link">
                    <div class="review-game-image-container"> <img src="{$imagePath}" alt="Okładka gry {$review['game_name']}" class="review-game-image">
                    </div>
                </a>
                <div class="review-tile-content"> <div class="review-tile-header">
                        <a href="{$userUrl}" class="review-user-link">
                           {$avatarImgTag}
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
            </div>
HTML;
        }
    } else {
        $html = '<p class="loading-indicator">Brak recenzji do wyświetlenia dla wybranych kryteriów.</p>';
    }

    echo json_encode([ 'html' => $html, 'totalPages' => $totalPages, 'currentPage' => $page ]);
    exit;

}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Społeczność - Recenzje</title>
    <link rel="stylesheet" href="style/styleCommon.css">
    <link rel="stylesheet" href="style/styleCommunity.css">
    <style>
        .review-user-link img { vertical-align: middle; } /* Dodatkowy styl dla lepszego wyrównania */
    </style>
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
        </div>

        <div class="reviews-container">
            <div class="loading-indicator">Ładowanie recenzji...</div>
        </div>

        <div class="pagination-controls">
            <button class="pagination-button prev" disabled>&laquo; Poprzednia</button>
            <span class="page-info">Strona <span class="current-page">1</span> z <span class="total-pages">1</span></span>
            <button class="pagination-button next" disabled>Następna &raquo;</button>
        </div>

    </div>

    <?php include('footer.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reviewsContainer = document.querySelector('.reviews-container');
            const sortOptions = document.querySelectorAll('.sort-option');
            const prevButton = document.querySelector('.pagination-button.prev');
            const nextButton = document.querySelector('.pagination-button.next');
            const currentPageSpan = document.querySelector('.current-page');
            const totalPagesSpan = document.querySelector('.total-pages');
            const initialLoadingIndicator = reviewsContainer.querySelector('.loading-indicator');

            let currentPage = 1;
            let currentSort = 'popular';
            let totalPages = 1;
            let isLoading = false;

            function loadReviews(page = 1, sort = 'popular') {
                if (isLoading) return;
                isLoading = true;
                reviewsContainer.innerHTML = '<div class="loading-indicator">Ładowanie recenzji...</div>';
                currentPage = page;
                currentSort = sort;

                fetch(`community.php?ajax=1&page=${page}&sort=${sort}`)
                    .then(response => response.ok ? response.json() : Promise.reject(`HTTP error! status: ${response.status}`))
                    .then(data => {
                        if(!document.body.contains(reviewsContainer)) return;
                        reviewsContainer.innerHTML = data.html;
                        totalPages = data.totalPages;
                        currentPage = data.currentPage;
                        currentPageSpan.textContent = currentPage;
                        totalPagesSpan.textContent = totalPages;
                        prevButton.disabled = (currentPage === 1);
                        nextButton.disabled = (currentPage >= totalPages);
                        updateSortIndicator(document.querySelector(`.sort-option[data-sort="${sort}"]`));
                    })
                    .catch(error => {
                        console.error('Błąd ładowania recenzji:', error);
                        if(document.body.contains(reviewsContainer)) {
                            reviewsContainer.innerHTML = '<p class="loading-indicator" style="color: red;">Wystąpił błąd podczas ładowania recenzji.</p>';
                        }
                    })
                    .finally(() => { isLoading = false; });
            }

            function updateSortIndicator(activeButton) {
                const slider = document.querySelector('.sort-slider');
                if (!activeButton || !slider) return;
                setTimeout(() => {
                    const buttonRect = activeButton.getBoundingClientRect();
                    const sliderRect = slider.getBoundingClientRect();
                    if (buttonRect.width > 0 && sliderRect.width > 0) {
                        slider.style.setProperty('--slider-highlight-left', `${buttonRect.left - sliderRect.left}px`);
                        slider.style.setProperty('--slider-highlight-width', `${buttonRect.width}px`);
                    }
                 }, 0);
                 sortOptions.forEach(opt => opt.classList.remove('active'));
                 activeButton.classList.add('active');
            }

            sortOptions.forEach(option => option.addEventListener('click', function() {
                if (isLoading) return;
                const newSort = this.dataset.sort;
                if (newSort !== currentSort) loadReviews(1, newSort);
            }));
            prevButton.addEventListener('click', () => { if (currentPage > 1 && !isLoading) loadReviews(currentPage - 1, currentSort); });
            nextButton.addEventListener('click', () => { if (currentPage < totalPages && !isLoading) loadReviews(currentPage + 1, currentSort); });

            loadReviews(currentPage, currentSort);
            setTimeout(() => { updateSortIndicator(document.querySelector('.sort-option.active')); }, 100);
            window.addEventListener('resize', () => { updateSortIndicator(document.querySelector('.sort-option.active')); });

            reviewsContainer.addEventListener('click', function(event) {
                const target = event.target.closest('.vote-arrow');
                if (target && !isLoading) {
                    event.preventDefault();
                     <?php if (!$currentUser): ?> window.location.href = 'login.php'; return; <?php endif; ?>
                     let isVotingInternal = false;
                    const voteType = target.dataset.vote;
                    const reviewItem = target.closest('.review-voting');
                    const reviewId = reviewItem.dataset.reviewId;
                    const voteCountSpan = reviewItem.querySelector('.vote-count');
                    const upvoteArrow = reviewItem.querySelector('.upvote');
                    const downvoteArrow = reviewItem.querySelector('.downvote');
                    upvoteArrow.style.pointerEvents = 'none'; downvoteArrow.style.pointerEvents = 'none';


                    fetch('community.php?action=vote', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ reviewId: reviewId, voteType: voteType }) })
                    .then(response => response.ok ? response.json() : Promise.reject(`HTTP error! status: ${response.status}`))
                    .then(data => {
                        if (data.success) {
                            voteCountSpan.textContent = data.newVoteCount;
                            upvoteArrow.classList.toggle('voted', data.newUserVoteStatus === 1);
                            downvoteArrow.classList.toggle('voted', data.newUserVoteStatus === -1);
                        } else if (data.error === 'not_logged_in') { window.location.href = 'login.php';
                        } else { console.error('Błąd głosowania:', data.error || 'Nieznany błąd'); }
                    })
                    .catch(error => { console.error('Błąd sieci podczas głosowania:', error); })
                    .finally(() => {
                         setTimeout(() => {
                              upvoteArrow.style.pointerEvents = 'auto';
                              downvoteArrow.style.pointerEvents = 'auto';
                         }, 200);
                    });
                }
            });
        });
    </script>
</body>
</html>