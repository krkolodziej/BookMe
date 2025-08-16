document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const bookingLink = document.querySelector('a[href="/wizyty"]');

    if (bookingLink && currentPath === bookingLink.getAttribute('href')) {
        const underline = bookingLink.querySelector('.position-absolute');
        if (underline) underline.style.opacity = '1';
    }
});
