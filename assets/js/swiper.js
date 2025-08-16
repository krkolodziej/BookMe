document.addEventListener('DOMContentLoaded', function() {
    // Inicjalizacja swiper1
    const swiperElement1 = document.querySelector('.swiper1');
    if (swiperElement1) {
        new Swiper('.swiper1', {
            slidesPerView: 'auto',
            spaceBetween: 30,
            navigation: {
                nextEl: '.swiper-button-next1',
                prevEl: '.swiper-button-prev1',
            }
        });
    }

    // Inicjalizacja swiper2
    const swiperElement2 = document.querySelector('.swiper2');
    if (swiperElement2) {
        new Swiper('.swiper2', {
            slidesPerView: 'auto',
            spaceBetween: 30,
            navigation: {
                nextEl: '.swiper-button-next2',
                prevEl: '.swiper-button-prev2',
            }
        });
    }
});