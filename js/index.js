document.addEventListener('DOMContentLoaded', () => {
    console.log('Index script loaded');

    function setupCarousel(containerId, prevBtnId, nextBtnId, indicatorsId) {
        const container = document.getElementById(containerId);

        let slideIndex = 0;
        const slides = container.querySelectorAll('.featured-item');
        
        const indicatorsContainer = document.getElementById(indicatorsId);
        const dots = indicatorsContainer ? indicatorsContainer.querySelectorAll('.dot') : [];
        
        const prevBtn = document.getElementById(prevBtnId);
        const nextBtn = document.getElementById(nextBtnId);

        function showSlide(n) {
            if (slides.length === 0) return;

            if (n >= slides.length) { slideIndex = 0; }
            else if (n < 0) { slideIndex = slides.length - 1; }
            else { slideIndex = n; }

            slides.forEach(slide => {
                slide.classList.remove('active');
                resetMainImage(slide);
            });
            
            if(dots.length > 0) {
                dots.forEach(dot => dot.classList.remove('active'));
                if(dots[slideIndex]) dots[slideIndex].classList.add('active');
            }

            slides[slideIndex].classList.add('active');
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                showSlide(slideIndex - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                showSlide(slideIndex + 1);
            });
        }

        return showSlide;
    }

    function resetMainImage(slideElement) {
        const mainImg = slideElement.querySelector('.main-featured-img');
        const firstThumb = slideElement.querySelector('.thumb-box img');
        if (mainImg && firstThumb) {
            mainImg.src = firstThumb.src;
        }
    }

    const mainSliderGoTo = setupCarousel('featuredCarousel', 'btnPrev', 'btnNext', 'featuredIndicators');
    
    window.currentSlide = function(n) {
        if(mainSliderGoTo) mainSliderGoTo(n);
    }

    const discountSliderGoTo = setupCarousel('discountCarousel', 'btnPrevDiscount', 'btnNextDiscount', 'discountIndicators');

    window.currentSlideDiscount = function(n) {
        if(discountSliderGoTo) discountSliderGoTo(n);
    }

    function setupTagCarousel() {
        const stage = document.getElementById('tagsStage');
        const prevBtn = document.getElementById('btnPrevTag');
        const nextBtn = document.getElementById('btnNextTag');
        const indicatorsContainer = document.getElementById('tagIndicators');
        
        if (!stage || !prevBtn || !nextBtn) return;

        const items = Array.from(stage.querySelectorAll('.tag-tile-3d'));
        const totalItems = items.length;
        if (totalItems === 0) return;

        let currentIndex = 0; 
        function updateCarousel() {
            const prevIndex = (currentIndex - 1 + totalItems) % totalItems;
            const nextIndex = (currentIndex + 1) % totalItems;

            items.forEach((item, index) => {
                item.classList.remove('active', 'prev', 'next');
                
                if (index === currentIndex) {
                    item.classList.add('active');
                } else if (index === prevIndex) {
                    item.classList.add('prev');
                } else if (index === nextIndex) {
                    item.classList.add('next');
                }
            });

            updateIndicators();
        }

        function createIndicators() {
            if (!indicatorsContainer) return;
            indicatorsContainer.innerHTML = '';
            
            items.forEach((_, idx) => {
                const rect = document.createElement('div');
                rect.className = 'dot'; 
                
                if (idx === currentIndex) rect.classList.add('active');
                
                rect.addEventListener('click', () => {
                    currentIndex = idx;
                    updateCarousel();
                });
                
                indicatorsContainer.appendChild(rect);
            });
        }

        function updateIndicators() {
            if (!indicatorsContainer) return;
            const dots = indicatorsContainer.querySelectorAll('.dot');
            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentIndex);
            });
        }

        prevBtn.addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + totalItems) % totalItems;
            updateCarousel();
        });

        nextBtn.addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % totalItems;
            updateCarousel();
        });

        createIndicators();
        updateCarousel();
    }

    setupTagCarousel();
});

function updateMainImage(element, newSrc) {
    const slide = element.closest('.featured-item');
    if (slide) {
        const mainImg = slide.querySelector('.main-featured-img');
        if (mainImg) {
            mainImg.src = newSrc;
        }
    }
}