
//Zmienna globalna przechowująca indeks aktualnie wyświetlanego slajdu w galerii
let slideIndex = 1;

//Przesuwa galerię obrazów do przodu lub do tyłu.
function plusSlides(n) {
    showSlides(slideIndex += n);
}

//Wyświetla konkretny slajd po kliknięciu na jego miniaturkę.
function currentSlide(n) {
    showSlides(slideIndex = n);
}

//Przełącza widoczność formularza edycji recenzji - Ukrywa treść komentarza i pokazuje formularz edycji lub odwrotnie.
function toggleEditForm(reviewId) {
    const body = document.getElementById('review-body-' + reviewId);
    const form = document.getElementById('review-edit-form-' + reviewId);
    if (body && form) {
        body.style.display = (body.style.display === 'none') ? 'block' : 'none';
        form.style.display = (form.style.display === 'none') ? 'block' : 'none';
    }
}


document.addEventListener('DOMContentLoaded', function () {

    //Główna funkcja obsługująca galerie obrazów na stronie gry.
    function showSlides(n) {
        let slides = document.getElementsByClassName("slide");
        let thumbnails = document.getElementsByClassName("thumbnail");
        if (slides.length === 0) return;

        if (n > slides.length) { slideIndex = 1; }
        if (n < 1) { slideIndex = slides.length; }

        for (let i = 0; i < slides.length; i++) {
            slides[i].style.opacity = "0";
        }
        for (let i = 0; i < thumbnails.length; i++) {
            thumbnails[i].className = thumbnails[i].className.replace(" active", "");
        }
        slides[slideIndex - 1].style.opacity = "1";
        thumbnails[slideIndex - 1].className += " active";
    }

    // Inicjalizacja galerii obrazów, jeśli istnieje na stronie.
    if (document.querySelector('.main-image-container')) {
        showSlides(slideIndex);
    }

    //Wstawia ikony SVG dla platform na podstawie atrybutów danych w elementach HTML.
    const platformSvgs = {
        'windows': `<svg viewBox="0 0 56.693 56.693" xmlns="http://www.w3.org/2000/svg"><g><path d="M3.765,46.362l19.836,2.873V30.257H3.765V46.362z M3.765,27.546h19.836V8.566L3.765,11.439V27.546z M26.312,49.628 l26.616,3.855V30.257H26.312V49.628z M26.312,8.172v19.374h26.616V4.319L26.312,8.172z"/></g></svg>`,
        'playstation': `<svg fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M15.858 11.451c-.313.395-1.079.676-1.079.676l-5.696 2.046v-1.509l4.192-1.493c.476-.17.549-.412.162-.538-.386-.127-1.085-.09-1.56.08l-2.794.984v-1.566l.161-.054s.807-.286 1.942-.412c1.135-.125 2.525.017 3.616.43 1.23.39 1.368.962 1.056 1.356ZM9.625 8.883v-3.86c0-.453-.083-.87-.508-.988-.326-.105-.528.198-.528.65v9.664l-2.606-.827V2c1.108.206 2.722.692 3.59.985 2.207.757 2.955 1.7 2.955 3.825 0 2.071-1.278 2.856-2.903 2.072Zm-8.424 3.625C-.061 12.15-.271 11.41.304 10.984c.532-.394 1.436-.69 1.436-.69l3.737-1.33v1.515l-2.69.963c-.474.17-.547.411-.161.538.386.126 1.085.09 1.56-.08l1.29-.469v1.356l-.257.043a8.454 8.454 0 0 1-4.018-.323Z"/></svg>`,
        'xbox': `<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M369.9 318.2c44.3 54.3 64.7 98.8 54.4 118.7-7.9 15.1-56.7 44.6-92.6 55.9-29.6 9.3-68.4 13.3-100.4 10.2-38.2-3.7-76.9-17.4-110.1-39C93.3 445.8 87 438.3 87 423.4c0-29.9 32.9-82.3 89.2-142.1 32-33.9 76.5-73.7 81.4-72.6 9.4 2.1 84.3 75.1 112.3 109.5zM188.6 143.8c-29.7-26.9-58.1-53.9-86.4-63.4-15.2-5.1-16.3-4.8-28.7 8.1-29.2 30.4-53.5 79.7-60.3 122.4-5.4 34.2-6.1 43.8-4.2 60.5 5.6 50.5 17.3 85.4 40.5 120.9 9.5 14.6 12.1 17.3 9.3 9.9-4.2-11-.3-37.5 9.5-64 14.3-39 53.9-112.9 120.3-194.4zm311.6 63.5C483.3 127.3 432.7 77 425.6 77c-7.3 0-24.2 6.5-36 13.9-23.3 14.5-41 31.4-64.3 52.8C367.7 197 427.5 283.1 448.2 346c6.8 20.7 9.7 41.1 7.4 52.3-1.7 8.5-1.7 8.5 1.4 4.6 6.1-7.7 19.9-31.3 25.4-43.5 7.4-16.2 15-40.2 18.6-58.7 4.3-22.5 3.9-70.8-.8-93.4zM141.3 43C189 40.5 251 77.5 255.6 78.4c.7.1 10.4-4.2 21.6-9.7 63.9-31.1 94-25.8 107.4-25.2-63.9-39.3-152.7-50-233.9-11.7-23.4 11.1-24 11.9-9.4 11.2z"/></svg>`,
        'nintendo': `<svg fill="currentColor" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M9.34 8.005c0-4.38.01-7.972.023-7.982C9.373.01 10.036 0 10.831 0c1.153 0 1.51.01 1.743.05 1.73.298 3.045 1.6 3.373 3.326.046.242.053.809.053 4.61 0 4.06.005 4.537-.123 4.976-.022.076-.048.15-.08.242a4.136 4.136 0 0 1-3.426 2.767c-.317.033-2.889.046-2.978.013-.05-.02-.053-.752-.053-7.979Zm4.675.269a1.621 1.621 0 0 0-1.113-1.034 1.609 1.609 0 0 0-1.938 1.073 1.9 1.9 0 0 0-.014.935 1.632 1.632 0 0 0 1.952 1.107c.51-.136.908-.504 1.11-1.028.11-.285.113-.742.003-1.053ZM3.71 3.317c-.208.04-.526.199-.695.348-.348.301-.52.729-.494 1.232.013.262.03.332.136.544.155.321.39.556.712.715.222.11.278.123.567.133.261.01.354 0 .53-.06.719-.242 1.153-.94 1.03-1.656-.142-.852-.95-1.422-1.786-1.256Z"/><path d="M3.425.053a4.136 4.136 0 0 0-3.28 3.015C0 3.628-.01 3.956.005 8.3c.01 3.99.014 4.082.08 4.39.368 1.66 1.548 2.844 3.224 3.235.22.05.497.06 2.29.07 1.856.012 2.048.009 2.097-.04.05-.05.053-.69.053-7.94 0-5.374-.01-7.906-.033-7.952-.033-.06-.09-.063-2.03-.06-1.578.004-2.052.014-2.26.05Zm3 14.665-1.35-.016c-1.242-.013-1.375-.02-1.623-.083a2.81 2.81 0 0 1-2.08-2.167c-.074-.335-.074-8.579-.004-8.907a2.845 2.845 0 0 1 1.716-2.05c.438-.176.64-.196 2.058-.2l1.282-.003v13.426Z"/></svg>`
    };
    document.querySelectorAll('.platform-item').forEach(element => {
        const platformName = element.dataset.platformName.toLowerCase().trim();
        if (platformSvgs[platformName]) {
            element.innerHTML = platformSvgs[platformName];
        }
    });

    //Obsługuje podgląd obrazków po wybraniu w formularzu.
    document.querySelectorAll('.uploader-input').forEach(input => {
        input.addEventListener('change', function() {
            const previewBox = document.getElementById(this.dataset.previewTarget);
            const label = previewBox.querySelector('.uploader-label');
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    previewBox.style.backgroundImage = `url('${e.target.result}')`;
                    if (label) label.classList.remove('visible');
                };
                reader.readAsDataURL(file);
            }
        });
    });

    //Funkcja pomocnicza do ustawiania podglądu logo dewelopera/wydawcy.
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
    
    //Obsługuje klikalne tagi w formularzu dodawania/edycji gry.
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

    //Obsługuje klikalne platformy w formularzu dodawania/edycji gry.
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

    /**
     * Obsługuje przyciski "Dodaj nowego" (dewelopera/publishera).
     * Zapisuje stan formularza w sesji przed przekierowaniem, aby użytkownik nie stracił danych.
     */
    document.querySelectorAll('.add-new-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.getElementById('addGameForm');
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