document.addEventListener('DOMContentLoaded', function () {
    
    const stars = document.querySelectorAll('.pure-stars-rating .star');
    const hiddenInput = document.getElementById('opinion_rating_value');

    
    updateStars(5);

    
    stars.forEach(star => {
        star.addEventListener('click', function () {
            
            const rating = this.dataset.value;

            
            this.classList.add('selected');
            setTimeout(() => {
                this.classList.remove('selected');
            }, 300);

            
            updateStars(rating);

            
            hiddenInput.value = rating;
        });
    });

    
    function updateStars(rating) {
        stars.forEach(star => {
            const starValue = parseInt(star.dataset.value);

            if (starValue <= rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }
});