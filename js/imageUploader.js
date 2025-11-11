document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.uploader-input').forEach(input => {
        input.addEventListener('change', function() {
            const previewBox = document.getElementById(this.dataset.previewTarget);
            if (!previewBox) {
                console.error('Nie znaleziono elementu podglądu: #' + this.dataset.previewTarget);
                return;
            }
            
            const file = this.files[0];
            
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    previewBox.style.backgroundImage = `url('${e.target.result}')`;
                    
                    const label = previewBox.querySelector('.uploader-label');
                    if (label) {
                        label.classList.remove('visible');
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
});