document.addEventListener('DOMContentLoaded', () => {
    
    const iconBoxes = document.querySelectorAll('.platform-icon-box');

    if (typeof platformSvgs !== 'undefined') {
        iconBoxes.forEach(box => {
            const key = box.getAttribute('data-platform'); 
            
            if (platformSvgs[key]) {
                box.innerHTML = platformSvgs[key];
            } else {
                console.warn(`Brak ikony w common.js dla klucza: ${key}`);
                box.innerText = key.substring(0, 1).toUpperCase(); 
            }
        });
    } else {
        console.error('Błąd: Nie znaleziono obiektu platformSvgs. Upewnij się, że common.js jest załadowany.');
    }

    const searchInput = document.getElementById('searchInput');
    const priceRange = document.getElementById('priceRange');
    const priceDisplay = document.getElementById('priceValue');
    const gamesGrid = document.getElementById('gamesGrid');
    
    const getCheckboxes = () => document.querySelectorAll('.filter-checkbox');

    function fetchGames() {
        const formData = new FormData();
        formData.append('ajax_filter', '1'); 
        formData.append('search', searchInput.value);
        formData.append('price', priceRange.value);
        
        getCheckboxes().forEach(cb => {
            if (cb.checked) {
                formData.append(cb.name + '[]', cb.value);
            }
        });

        fetch('store.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            gamesGrid.innerHTML = html;
        })
        .catch(error => console.error('Błąd:', error));
    }

    let searchTimeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchGames, 300);
    });

    priceRange.addEventListener('input', (e) => priceDisplay.textContent = e.target.value);
    priceRange.addEventListener('change', fetchGames);

    getCheckboxes().forEach(cb => cb.addEventListener('change', fetchGames));

    fetchGames();
});