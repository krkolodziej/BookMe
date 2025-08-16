document.addEventListener('DOMContentLoaded', function() {
    
    $(`#${select2Config.serviceId}`).select2({
        placeholder: 'Wybierz serwis...',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "Brak wyników";
            },
            searching: function() {
                return "Szukanie...";
            }
        }
    });

    
    const avatarUrlInput = document.getElementById(avatarConfig.inputId);
    const avatarPreview = document.getElementById('avatar-preview');
    const avatarPreviewContainer = document.getElementById(avatarConfig.previewContainerId);

    avatarUrlInput.addEventListener('input', function() {
        const imageUrl = this.value.trim();
        if (imageUrl) {
            avatarPreview.src = imageUrl;
            avatarPreviewContainer.style.display = 'block';
        } else {
            avatarPreviewContainer.style.display = 'none';
        }
    });
});
