let slideIndex = 1;

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

function plusSlides(n) {
    showSlides(slideIndex += n);
}

function currentSlide(n) {
    showSlides(slideIndex = n);
}

document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('.main-image-container')) {
        showSlides(slideIndex);
    }
});