document.addEventListener('DOMContentLoaded', function () {

    function setupLogoPreview(selectId, previewId) {
        const select = document.getElementById(selectId);
        const preview = document.getElementById(previewId);
        if (!select || !preview) return;

        function updateLogo() {
            const logo = select.options[select.selectedIndex]?.dataset.logo || '';
            preview.style.backgroundImage = logo ? `url('${logo}')` : 'none';
        }
        select.addEventListener('change', updateLogo);
        updateLogo();
    }
    setupLogoPreview('developer_select', 'developer_logo_preview');
    setupLogoPreview('publisher_select', 'publisher_logo_preview');
    
    const tagsContainer = document.querySelector('.tags-container');
    if (tagsContainer) {
        tagsContainer.addEventListener('click', function(e){
            if (e.target.classList.contains('tag-item')) {
                const span = e.target;
                const checkbox = this.querySelector(`input[value="${span.dataset.tagId}"]`);
                if (checkbox) {
                    span.classList.toggle('selected');
                    checkbox.checked = !checkbox.checked;
                }
            }
        });
    }

    const platformsContainer = document.querySelector('.platforms-container');
    if (platformsContainer) {
        platformsContainer.addEventListener('click', function(e){
            const span = e.target.closest('.platform-item');
            if(span){
                const checkbox = this.querySelector(`input[value="${span.dataset.platformId}"]`);
                if (checkbox) {
                    span.classList.toggle('selected');
                    checkbox.checked = !checkbox.checked;
                }
            }
        });
    }

    document.querySelectorAll('.add-new-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('addGameForm');
            if (!form) return;

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'save_state_and_redirect';
            hiddenInput.value = '1';
            form.appendChild(hiddenInput);

            const redirectInput = document.createElement('input');
            redirectInput.type = 'hidden';
            redirectInput.name = 'redirect_url';
            redirectInput.value = this.href;
            form.appendChild(redirectInput);
            
            form.submit();
        });
    });
});