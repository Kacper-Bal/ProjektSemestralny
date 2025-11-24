document.addEventListener('DOMContentLoaded', () => {
    
    const iconBoxes = document.querySelectorAll('.platform-icon-box');

    if (typeof platformSvgs !== 'undefined') {
        iconBoxes.forEach(box => {
            const key = box.getAttribute('data-platform'); 
            
            if (key && platformSvgs[key]) {
                box.innerHTML = platformSvgs[key];
            } else {
                if (key) {
                    console.warn(`Brak ikony SVG dla platformy: ${key}`);
                    box.innerText = key.substring(0, 1).toUpperCase(); 
                }
            }
        });
    } else {
        console.log('Info: Obiekt platformSvgs nie jest zdefiniowany. Ikony platform mogą się nie wyświetlać.');
    }

    const searchInput = document.getElementById('searchInput');
    const priceRange = document.getElementById('priceRange');
    const priceDisplay = document.getElementById('priceValue');
    const gamesGrid = document.getElementById('gamesGrid');
    
    const getCheckboxes = () => document.querySelectorAll('.filter-checkbox');

    function fetchGames() {
        const formData = new FormData();
        formData.append('ajax_filter', '1'); 
        
        if (searchInput) {
            formData.append('search', searchInput.value);
        }
        
        if (priceRange) {
            formData.append('price', priceRange.value);
        }
        
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
            if (gamesGrid) {
                gamesGrid.innerHTML = html;
            }
        })
        .catch(error => console.error('Błąd podczas pobierania gier:', error));
    }
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(fetchGames, 300); 
        });
    }

    if (priceRange) {
        priceRange.addEventListener('input', (e) => {
            if (priceDisplay) priceDisplay.textContent = e.target.value;
        });
        priceRange.addEventListener('change', fetchGames);
    }

    getCheckboxes().forEach(cb => cb.addEventListener('change', fetchGames));
    fetchGames();
});