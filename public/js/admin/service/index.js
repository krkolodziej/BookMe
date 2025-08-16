document.addEventListener('DOMContentLoaded', function() {
    
    const serviceImages = document.querySelectorAll('.service-image');
    serviceImages.forEach(img => {
        img.addEventListener('error', function() {

            const container = this.parentElement;
            this.remove();

            const placeholder = document.createElement('div');
            placeholder.className = 'no-image';
            placeholder.innerHTML = '<i class="fas fa-image"></i>';
            container.appendChild(placeholder);
        });
    });
});
