document.addEventListener('DOMContentLoaded', function() {
    
    var tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltips.map(function(tooltip) {
        return new bootstrap.Tooltip(tooltip);
    });
});