document.addEventListener('DOMContentLoaded', function() {
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
