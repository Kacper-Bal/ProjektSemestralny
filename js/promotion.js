document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('gameSearch');
    const cards = document.querySelectorAll('.selectable-game-card');
    const selectedCountSpan = document.getElementById('selectedCount');
    const hiddenInput = document.getElementById('selectedGamesInput');
    const createForm = document.getElementById('createPromoForm');
    
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

    if (searchInput) {
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
    }

    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            if (selectedGames.size === 0) {
                e.preventDefault();
                alert("Wybierz przynajmniej jedną grę z listy po prawej stronie!");
            }
        });
    }
});