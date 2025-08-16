document.addEventListener('DOMContentLoaded', function() {
    
    const avatarUrlInput = document.getElementById('user_avatarUrl');
    const avatarPreview = document.getElementById('avatar-preview');
    const avatarPreviewContainer = document.getElementById('user_avatarUrl_preview_container');

    if (avatarUrlInput && avatarPreview && avatarPreviewContainer) {
        avatarUrlInput.addEventListener('input', function() {
            const imageUrl = this.value.trim();
            if (imageUrl) {
                avatarPreview.src = imageUrl;
                avatarPreviewContainer.classList.remove('d-none');
            } else {
                avatarPreviewContainer.classList.add('d-none');
            }
        });
    }
});