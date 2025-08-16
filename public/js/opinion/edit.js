document.addEventListener('DOMContentLoaded', function() {
    
    const stars = document.querySelectorAll('.pure-stars-rating .star');
    const container = document.getElementById('opinion-container');
    
    
    const ratingFields = document.querySelectorAll('form [name$="[rating]"]');
    
    
    console.log('Rating fields found:', ratingFields.length);
    ratingFields.forEach((field, index) => {
        console.log(`Field ${index}:`, field.name, field.value);
    });
    
    
    const hiddenInput = ratingFields.length > 0 ? ratingFields[0] : null;
    console.log('Using field:', hiddenInput);
    
    
    let currentRating = 5;
    if (container && container.dataset.currentRating) {
        currentRating = parseInt(container.dataset.currentRating);
        console.log('Initial rating from data attribute:', currentRating);
        
        
        if (hiddenInput) {
            hiddenInput.value = currentRating;
            console.log('Set initial field value to:', currentRating);
        }
    }
    
    
    updateStars(currentRating);
    
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.value);
            console.log('Star clicked:', rating);
            
            
            this.classList.add('selected');
            setTimeout(() => {
                this.classList.remove('selected');
            }, 300);
            
            
            updateStars(rating);
            
            
            if (ratingFields.length > 0) {
                ratingFields.forEach(field => {
                    field.value = rating;
                    console.log('Updated field', field.name, 'to', rating);
                });
            }
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
    
    
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (hiddenInput) {
                console.log('Submitting form with rating:', hiddenInput.value);
            }
            

            const formData = new FormData(form);
            console.log('All form values:');
            for (let [key, value] of formData.entries()) {
                console.log(key, '=', value);
            }
        });
    }

    
    const deleteButton = document.querySelector('.delete-opinion-btn');
    if (deleteButton) {
        deleteButton.addEventListener('click', function() {
            if (confirm('Czy na pewno chcesz usunąć tę opinię?')) {
                const id = this.getAttribute('data-id');
                const token = this.getAttribute('data-token');
                const redirectUrl = this.getAttribute('data-redirect-url');

                fetch(`/opinions/delete/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = redirectUrl;
                        } else {
                            alert(data.error || 'Wystąpił błąd podczas usuwania opinii.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Wystąpił błąd podczas usuwania opinii.');
                    });
            }
        });
    }
});