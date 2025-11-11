document.addEventListener('DOMContentLoaded', function() {
    const reviewsContainer = document.querySelector('.reviews-container');
    const prevButton = document.querySelector('.pagination-button.prev');
    const nextButton = document.querySelector('.pagination-button.next');
    const currentPageSpan = document.querySelector('.current-page');
    const totalPagesSpan = document.querySelector('.total-pages');

    if (!reviewsContainer) return;

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

            })
            .catch(error => {
                console.error('Błąd ładowania recenzji:', error);
                if(document.body.contains(reviewsContainer)) {
                    reviewsContainer.innerHTML = '<p class="loading-indicator" style="color: red;">Wystąpił błąd podczas ładowania recenzji.</p>';
                }
            })
            .finally(() => { isLoading = false; });
    }

    prevButton.addEventListener('click', () => { if (currentPage > 1 && !isLoading) loadReviews(currentPage - 1, currentSort); });
    nextButton.addEventListener('click', () => { if (currentPage < totalPages && !isLoading) loadReviews(currentPage + 1, currentSort); });

    initializeSortSlider('.sort-slider', (sortType) => {
        if (isLoading || !sortType) return;
        if (sortType !== currentSort) {
            loadReviews(1, sortType);
        }
    });
    
    loadReviews(currentPage, currentSort);

    initializeVoting('.reviews-container', 'community.php?action=vote');
});