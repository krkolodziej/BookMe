document.addEventListener('DOMContentLoaded', function() {
    const urlInput = document.getElementById(imageConfig.inputId);
    const imagePreview = document.getElementById('image-preview');
    const imagePreviewContainer = document.getElementById(imageConfig.previewContainerId);
    const currentImage = document.getElementById('current-image');

    urlInput.addEventListener('input', function() {
        const imageUrl = this.value.trim();
        if (imageUrl) {
            const img = new Image();
            img.onload = function() {
                imagePreview.src = imageUrl;
                imagePreviewContainer.classList.add('d-block');

                if (imageUrl !== currentImage.src) {
                    imagePreviewContainer.querySelector('h5').textContent = 'Nowy podgląd zdjęcia:';
                } else {
                    imagePreviewContainer.querySelector('h5').textContent = 'Podgląd zdjęcia:';
                }
            };
            img.onerror = function() {
                imagePreviewContainer.classList.remove('d-block');
            };
            img.src = imageUrl;
        } else {
            imagePreviewContainer.classList.remove('d-block');
        }
    });
});
