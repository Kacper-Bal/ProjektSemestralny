document.addEventListener('DOMContentLoaded', () => {
    const options = {
        root: null, 
        rootMargin: '0px',
        threshold: 0.1 
    };

    const callback = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    };

    const observer = new IntersectionObserver(callback, options);
    const itemsToAnimate = document.querySelectorAll('.csg_item');
    itemsToAnimate.forEach(item => {
        observer.observe(item);
    });
});