document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('headerSearchInput');
    const resultsBox = document.getElementById('searchResults');
    let debounceTimeout;

    if (input && resultsBox) {
        input.addEventListener('input', function() {
            const term = this.value.trim();
            
            clearTimeout(debounceTimeout);

            if (term.length < 2) {
                resultsBox.style.display = 'none';
                resultsBox.innerHTML = '';
                return;
            }

            debounceTimeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('live_search_query', term);

                fetch('header.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    resultsBox.innerHTML = '';
                    if (data.length > 0) {
                        resultsBox.style.display = 'block';
                        data.forEach(game => {
                            const a = document.createElement('a');
                            a.href = 'game.php?game=' + encodeURIComponent(game.name);
                            a.className = 'live-search-item';

                            const img = document.createElement('img');
                            img.src = game.image;
                            img.className = 'live-search-img';

                            const name = document.createElement('span');
                            name.textContent = game.name;
                            name.className = 'live-search-name';

                            a.appendChild(img);
                            a.appendChild(name);
                            resultsBox.appendChild(a);
                        });
                    } else {
                        resultsBox.style.display = 'none';
                    }
                })
                .catch(err => console.error('Błąd wyszukiwania:', err));
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
                resultsBox.style.display = 'none';
            }
        });
    }
});