document.addEventListener('DOMContentLoaded', function() {
    // Obsługa usuwania rezerwacji
    const deleteBookingButtons = document.querySelectorAll('.delete-booking-btn');
    deleteBookingButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Czy na pewno chcesz usunąć tę wizytę?')) {
                const bookingId = this.getAttribute('data-booking-id');
                const token = this.getAttribute('data-token');

                fetch(`/usun/${bookingId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Usuń kartę rezerwacji z DOM
                            this.closest('.col-md-6').remove();

                            // Pokaż powiadomienie
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-success alert-dismissible fade show';
                            alertDiv.innerHTML = `
                            ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                            document.querySelector('.container').prepend(alertDiv);

                            // Sprawdź, czy to była ostatnia karta w tej zakładce
                            const activeTab = document.querySelector('.tab-pane.active');
                            const remainingCards = activeTab.querySelectorAll('.booking-card');

                            if (remainingCards.length === 0) {
                                // Pokaż komunikat o braku rezerwacji
                                const emptyState = document.createElement('div');
                                emptyState.className = 'col-12';
                                emptyState.innerHTML = `
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="far fa-calendar-times"></i>
                                    </div>
                                    <h4>Brak nadchodzących wizyt</h4>
                                    <p class="text-muted">Nie masz żadnych nadchodzących wizyt. Możesz zarezerwować wizytę przechodząc do oferty wybranego serwisu.</p>
                                </div>
                            `;
                                activeTab.querySelector('.row').appendChild(emptyState);
                            }
                        } else {
                            // Pokaż błąd
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                            alertDiv.innerHTML = `
                            ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                            document.querySelector('.container').prepend(alertDiv);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        });
    });

    // Obsługa usuwania opinii
    const deleteOpinionButtons = document.querySelectorAll('.delete-opinion-btn');
    deleteOpinionButtons.forEach(button => {
        button.addEventListener('click', function() {

            if (confirm('Czy na pewno chcesz usunąć tę opinię?')) {
                const opinionId = this.getAttribute('data-id');
                const token = this.getAttribute('data-token');
            
                    
                fetch(`/opinions/delete/${opinionId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Odśwież stronę
                            location.reload();
                        } else {
                            // Pokaż błąd
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                            alertDiv.innerHTML = `
                            ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                            document.querySelector('.container').prepend(alertDiv);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        });
    });
});