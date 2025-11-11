function initializeVoting(containerSelector, voteUrl, onVoteError) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    let isVoting = false;

    container.addEventListener('click', function(event) {
        const target = event.target.closest('.vote-arrow');
        if (!target || isVoting) {
            event.preventDefault();
            return;
        }
        
        event.preventDefault();
        isVoting = true;
        target.classList.add('disabled');

        const voteType = target.dataset.vote;
        const reviewVotingDiv = target.closest('.review-voting');
        const reviewId = reviewVotingDiv.dataset.reviewId;
        const voteCountSpan = reviewVotingDiv.querySelector('.vote-count');
        const upvoteArrow = reviewVotingDiv.querySelector('.upvote');
        const downvoteArrow = reviewVotingDiv.querySelector('.downvote');

        fetch(voteUrl, {
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
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                voteCountSpan.textContent = data.newVoteCount;
                upvoteArrow.classList.toggle('voted', data.newUserVoteStatus === 1);
                downvoteArrow.classList.toggle('voted', data.newUserVoteStatus === -1);
            } else if (data.error === 'not_logged_in') {
                window.location.href = 'login.php'; 
            } else {
                if (onVoteError) onVoteError(data.error);
                console.error('Błąd głosowania:', data.error || 'Nieznany błąd');
            }
        })
        .catch(error => {
            if (onVoteError) onVoteError(error);
            console.error('Błąd sieci podczas głosowania:', error);
        })
        .finally(() => {
            setTimeout(() => {
                isVoting = false;
                target.classList.remove('disabled');
            }, 300);
        });
    });
}