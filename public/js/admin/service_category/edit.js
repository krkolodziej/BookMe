document.addEventListener('DOMContentLoaded', function() {
    const imageUrlInput = document.getElementById(imageConfig.inputId);
    const imagePreview = document.getElementById('image-preview');
    const imagePreviewContainer = document.getElementById(imageConfig.previewContainerId);

    
    function updateImagePreview(imageUrl) {
        if (imageUrl) {
            imagePreview.src = imageUrl;
            imagePreviewContainer.classList.remove('d-none');
        } else {
            imagePreviewContainer.classList.add('d-none');
        }
    }

    
    if (imageConfig.currentImageUrl) {
        updateImagePreview(imageConfig.currentImageUrl);
    }

    
    imageUrlInput.addEventListener('input', function() {
        updateImagePreview(this.value.trim());
    });

    
    const invalidFields = document.querySelectorAll('.form-error');
    invalidFields.forEach(function(field) {
        const input = field.closest('.mb-3').querySelector('.form-control, .form-select');
        if (input) {
            input.classList.add('is-invalid');
        }
    });
});
