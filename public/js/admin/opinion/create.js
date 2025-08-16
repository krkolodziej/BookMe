document.addEventListener('DOMContentLoaded', function() {
    // Funkcja do debugowania - sprawdź wszystkie radiobuttony w formularzu

    
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
    
    // Dodaj obsługę kliknięcia na gwiazdki
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
            
            // Znajdź i zaznacz konkretny radiobutton o tej wartości
            const radioToCheck = document.querySelector(`input[type="radio"][name="admin_opinion[rating]"][value="${rating}"]`);
            
            if (radioToCheck) {
                // Odznacz wszystkie inne
                radioButtons.forEach(radio => {
                    radio.checked = false;
                });
                
                // Zaznacz ten właściwy
                radioToCheck.checked = true;
            } 
        });
    });
    
    // Znajdź domyślnie zaznaczony radiobutton i ustaw gwiazdki
    const checkedRadio = document.querySelector('input[type="radio"][name="admin_opinion[rating]"]:checked');
    let initialRating = 5;
    
    if (checkedRadio) {
        initialRating = parseInt(checkedRadio.value);
    } 
    
    updateStars(initialRating);
    
    // Obsługa pola wyboru wizyty
    const bookingSelect = document.getElementById(bookingConfig.selectId);
    const bookingInfo = document.getElementById('bookingInfo');
    
    if (bookingSelect) {
        bookingSelect.addEventListener('change', function() {
            if (this.value) {
                const selectedText = this.options[this.selectedIndex].text;
                const parts = selectedText.split(' - ');
                let clientName = '';
                let offerName = '';
                let bookingDate = '';
                
                if (parts.length >= 3) {
                    bookingDate = parts[parts.length - 1];
                    offerName = parts[parts.length - 2];
                    clientName = parts.slice(0, parts.length - 2).join(' - ');
                } else if (parts.length === 2) {
                    clientName = parts[0];
                    offerName = parts[1];
                } else {
                    clientName = selectedText;
                }
                
                document.getElementById('clientName').textContent = clientName;
                document.getElementById('offerName').textContent = offerName;
                document.getElementById('bookingDate').textContent = bookingDate;
                
                bookingInfo.classList.add('visible');
            } else {
                bookingInfo.classList.remove('visible');
            }
        });
        
        if (bookingSelect.value) {
            bookingSelect.dispatchEvent(new Event('change'));
        }
    }
});
