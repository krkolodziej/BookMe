document.addEventListener('DOMContentLoaded', function() {
    const avatarUrlInput = document.getElementById(avatarConfig.inputId);
    const avatarPreview = document.getElementById('avatar-preview');
    const avatarPreviewContainer = document.getElementById(avatarConfig.previewContainerId);
    
    function updateAvatarPreview(imageUrl) {
        if (imageUrl) {
            avatarPreview.src = imageUrl;
            avatarPreviewContainer.style.display = 'block';
        } else {
            avatarPreviewContainer.style.display = 'none';
        }
    }

    if (avatarConfig.currentAvatarUrl) {
        updateAvatarPreview(avatarConfig.currentAvatarUrl);
    }

    avatarUrlInput.addEventListener('input', function() {
        updateAvatarPreview(this.value.trim());
    });
});
