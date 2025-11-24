function toggleEditForm(reviewId) {
    const body = document.getElementById('review-body-' + reviewId);
    const form = document.getElementById('review-edit-form-' + reviewId);
    if (body && form) {
        body.style.display = (body.style.display === 'none') ? 'block' : 'none';
        form.style.display = (form.style.display === 'none') ? 'block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {

    const gameName = document.body.dataset.gameName;
    if (gameName) {
        const voteUrl = `game.php?action=vote&game=${encodeURIComponent(gameName)}`;
        initializeVoting('.reviews-list', voteUrl);
    }

    const messages = document.querySelectorAll('.game-message');
    messages.forEach(message => {
        if (message) {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            }, 3000);
        }
    });

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