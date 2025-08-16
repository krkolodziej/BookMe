document.addEventListener('DOMContentLoaded', function() {
    const imageFullscreen = document.getElementById(imageGalleryConfig.overlayId);
    const fullscreenImage = document.getElementById(imageGalleryConfig.fullscreenImageId);
    const closeFullscreen = document.getElementById(imageGalleryConfig.closeButtonId);
    const prevImage = document.getElementById(imageGalleryConfig.prevButtonId);
    const nextImage = document.getElementById(imageGalleryConfig.nextButtonId);
    const viewButtons = document.querySelectorAll('.image-action.view');

    let currentImageIndex = 0;
    const images = document.querySelectorAll('.gallery-image');
    const imageUrls = Array.from(images).map(img => img.src);

    viewButtons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            currentImageIndex = index;
            openFullscreen(this.getAttribute('data-image-url'));
        });
    });

    function openFullscreen(imageUrl) {
        fullscreenImage.src = imageUrl;
        imageFullscreen.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    closeFullscreen.addEventListener('click', function() {
        imageFullscreen.style.display = 'none';
        document.body.style.overflow = 'auto';
    });

    imageFullscreen.addEventListener('click', function(e) {
        if (e.target === imageFullscreen) {
            imageFullscreen.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

    
    prevImage.addEventListener('click', function() {
        currentImageIndex = (currentImageIndex - 1 + imageUrls.length) % imageUrls.length;
        fullscreenImage.src = imageUrls[currentImageIndex];
    });

    nextImage.addEventListener('click', function() {
        currentImageIndex = (currentImageIndex + 1) % imageUrls.length;
        fullscreenImage.src = imageUrls[currentImageIndex];
    });

    
    document.addEventListener('keydown', function(e) {
        if (imageFullscreen.style.display === 'flex') {
            if (e.key === 'Escape') {
                imageFullscreen.style.display = 'none';
                document.body.style.overflow = 'auto';
            } else if (e.key === 'ArrowLeft') {
                currentImageIndex = (currentImageIndex - 1 + imageUrls.length) % imageUrls.length;
                fullscreenImage.src = imageUrls[currentImageIndex];
            } else if (e.key === 'ArrowRight') {
                currentImageIndex = (currentImageIndex + 1) % imageUrls.length;
                fullscreenImage.src = imageUrls[currentImageIndex];
            }
        }
    });
});
