function _updateSliderIndicator(activeButton, slider) {
    if (!activeButton || !slider) return;
 
    setTimeout(() => {
        const buttonRect = activeButton.getBoundingClientRect();
        const sliderRect = slider.getBoundingClientRect();
        
        if (buttonRect.width > 0 && sliderRect.width > 0) {
            slider.style.setProperty('--slider-highlight-left', `${buttonRect.left - sliderRect.left}px`);
            slider.style.setProperty('--slider-highlight-width', `${buttonRect.width}px`);
        }
    }, 0);
}
function initializeSortSlider(sliderSelector, onTabClick) {
    const slider = document.querySelector(sliderSelector);
    if (!slider) return;

    const tabOptions = slider.querySelectorAll('.sort-option');
    if (tabOptions.length === 0) return;

    const initialActiveTab = slider.querySelector('.sort-option.active');
    if (initialActiveTab) {
        _updateSliderIndicator(initialActiveTab, slider);
    }

    tabOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (this.classList.contains('active')) return;

            tabOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            _updateSliderIndicator(this, slider);

            const tabValue = this.dataset.tab || this.dataset.sort;
            if (onTabClick) {
                onTabClick(tabValue);
            }
        });
    });

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            const activeTab = slider.querySelector('.sort-option.active');
            if (activeTab) {
                _updateSliderIndicator(activeTab, slider);
            }
        }, 100);
    });
}