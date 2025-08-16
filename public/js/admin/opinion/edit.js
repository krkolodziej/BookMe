document.addEventListener('DOMContentLoaded', function() {
    // Znajdź elementy
    const stars = document.querySelectorAll('.pure-stars-rating .star');
    
    // Znajdź radiobuttony bezpośrednio (dokładny selektor)
    const radioButtons = document.querySelectorAll('input[type="radio"][name="admin_opinion[rating]"]');
    
    // Funkcja do aktualizacji gwiazdek
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
    
    // Inicjalizacja na podstawie aktualnie zaznaczonego radiobuttona
    const checkedRadio = document.querySelector('input[type="radio"][name="admin_opinion[rating]"]:checked');
    if (checkedRadio) {
        const currentRating = parseInt(checkedRadio.value);
        updateStars(currentRating);
    }
    
    // Obsługa kliknięcia na gwiazdki
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.value);
            
            // Efekt animacji
            this.classList.add('selected');
            setTimeout(() => {
                this.classList.remove('selected');
            }, 300);
            
            // Aktualizuj gwiazdki
            updateStars(rating);
            
            // Znajdź i zaznacz odpowiedni radiobutton
            const radioToCheck = document.querySelector(`input[type="radio"][name="admin_opinion[rating]"][value="${rating}"]`);
            if (radioToCheck) {
                radioToCheck.checked = true;
                
                // Wymuś zmianę wartości w formularzu
                const event = new Event('change', { bubbles: true });
                radioToCheck.dispatchEvent(event);
            }
        });
    });
    
    // Dodaj obsługę zmiany radiobuttonów (dla pewności)
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            updateStars(parseInt(this.value));
        });
    });
});
