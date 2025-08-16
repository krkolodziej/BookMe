document.addEventListener('DOMContentLoaded', function() {
    // Obsługa podglądu obrazu
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

    
    const infoTabErrors = document.querySelectorAll('#info-content .form-error');
    const contactTabErrors = document.querySelectorAll('#contact-content .form-error');
    const imageTabErrors = document.querySelectorAll('#image-content .form-error');

    if (infoTabErrors.length > 0) {
        document.getElementById(validationConfig.infoTabId).click();
    } else if (contactTabErrors.length > 0) {
        document.getElementById(validationConfig.contactTabId).click();
    } else if (imageTabErrors.length > 0) {
        document.getElementById(validationConfig.imageTabId).click();
    }
});
