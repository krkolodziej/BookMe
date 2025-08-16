document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-booking-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            if (confirm('Czy na pewno chcesz usunąć tę wizytę?')) {
                const bookingId = this.dataset.bookingId;

                fetch(`/usun/${bookingId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the booking card from the DOM
                            const bookingCard = this.closest('.card').parentElement;
                            bookingCard.remove();

                            // Optional: Show success message
                            alert(data.message);
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Wystąpił błąd podczas usuwania wizyty');
                    });
            }
        });
    });
});