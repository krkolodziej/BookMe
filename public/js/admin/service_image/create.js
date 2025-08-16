document.addEventListener('DOMContentLoaded', function() {
    const urlInput = document.getElementById(imageConfig.inputId);
    const imagePreview = document.getElementById('image-preview');
    const imagePreviewContainer = document.getElementById(imageConfig.previewContainerId);
    const imagePlaceholder = document.getElementById(imageConfig.placeholderId);

    urlInput.addEventListener('input', function() {
        const imageUrl = this.value.trim();
        if (imageUrl) {
            
            const img = new Image();
            img.onload = function() {
                imagePreview.src = imageUrl;
                imagePreviewContainer.style.display = 'block';
                imagePlaceholder.style.display = 'none';
            };
            img.onerror = function() {
                imagePreviewContainer.style.display = 'none';
                imagePlaceholder.style.display = 'flex';
            };
            img.src = imageUrl;
        } else {
            imagePreviewContainer.style.display = 'none';
            imagePlaceholder.style.display = 'flex';
        }
    });
});
