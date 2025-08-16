document.addEventListener('DOMContentLoaded', function() {
    var swiper = new Swiper('.details-swiper-container', {
        loop: true,
        speed: 500,
        slidesPerView: 1,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        effect: 'fade',
        fadeEffect: {
            crossFade: true
        },
        navigation: {
            nextEl: '.details-swiper-button-next',
            prevEl: '.details-swiper-button-prev',
        },
        pagination: {
            el: '.details-swiper-pagination',
            clickable: true,
        }
    });

    // Obsługa błędów ładowania obrazów
    const fallbackImageUrl = "https://fastly.picsum.photos/id/40/640/480.jpg?hmac=g_iGJ4xMON_SqGk0lEb_9nIhJG1-H783dsL3S5QQw2g";
    const images = document.querySelectorAll('.details-swiper-container .swiper-slide img');
    images.forEach(function(img) {
        img.onerror = function() {
            img.src = fallbackImageUrl;
        };
    });
});