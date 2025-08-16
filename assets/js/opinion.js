// opinion.js - Obsługa asynchronicznego usuwania opinii dla wszystkich widoków

// Użycie IIFE (Immediately Invoked Function Expression) dla izolacji kodu
(function() {
    // Flaga zapobiegająca wielokrotnemu wykonaniu inicjalizacji
    if (window.opinionJsInitialized) return;
    window.opinionJsInitialized = true;

    // Flaga kontrolująca czy aktualnie trwa usuwanie
    let isDeleteInProgress = false;

    // Przechowujemy ścieżkę do strony bookingów
    // Ta wartość będzie ustawiona przy inicjalizacji przycisku z atrybutu data-redirect-url
    let bookingIndexPath = '/bookings'; // domyślna wartość

    // Funkcja inicjalizująca
    function initOpinionHandlers() {
        // Usuń wszystkie istniejące listenery, jeśli istnieją
        document.removeEventListener('click', handleDocumentClick);

        // Dodaj jeden listener na poziomie dokumentu
        document.addEventListener('click', handleDocumentClick);

        // Wyłącz istniejące listenery inline i pobierz ścieżkę przekierowania
        const deleteButtons = document.querySelectorAll('.delete-opinion-btn, .delete-opinion-list-btn');
        deleteButtons.forEach(button => {
            // Pobierz ścieżkę przekierowania, jeśli jest dostępna
            if (button.hasAttribute('data-redirect-url')) {
                bookingIndexPath = button.getAttribute('data-redirect-url');
            }

            // Sklonuj przycisk, aby usunąć wszystkie event listenery
            const newButton = button.cloneNode(true);
            if (button.parentNode) {
                button.parentNode.replaceChild(newButton, button);
            }
        });

        console.log('Opinion handlers initialized with redirect path:', bookingIndexPath);
    }

    // Handler dla kliknięć na dokumencie
    function handleDocumentClick(e) {
        // Jeśli usuwanie jest w trakcie, ignoruj kliknięcia
        if (isDeleteInProgress) return;

        // Znajdź czy kliknięto na przycisk usuwania
        const deleteButton = e.target.closest('.delete-opinion-btn, .delete-opinion-list-btn');
        if (!deleteButton) return;

        e.preventDefault();
        e.stopPropagation(); // Zatrzymaj propagację, aby uniknąć podwójnego wykonania

        // Wywołaj funkcję usuwania
        handleOpinionDelete(deleteButton);
    }

    /**
     * Obsługuje usuwanie opinii
     * @param {HTMLElement} button - Przycisk usuwania
     */
    function handleOpinionDelete(button) {
        // Jeśli usuwanie jest już w trakcie, nie pozwól na ponowne kliknięcie
        if (isDeleteInProgress) return;

        if (confirm('Czy na pewno chcesz usunąć tę opinię?')) {
            isDeleteInProgress = true; // Ustaw flagę blokującą

            const id = button.getAttribute('data-id');
            const token = button.getAttribute('data-token');
            const bookingId = button.getAttribute('data-booking-id');

            // Sprawdź, czy przycisk ma własną ścieżkę przekierowania
            const redirectUrl = button.getAttribute('data-redirect-url') || bookingIndexPath;

            // Pokaż loader
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Usuwanie...';
            button.disabled = true;

            fetch(`/opinions/delete/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Określ odpowiednie przekierowanie w zależności od strony
                        const isEditPage = window.location.pathname.includes('/opinions/edit/');

                        if (isEditPage) {
                            // Jesteśmy na stronie edycji - przekieruj do listy rezerwacji
                            window.location.href = redirectUrl;
                        } else {
                            // Jesteśmy na liście bookingów, zaktualizuj widok
                            const bookingElement = document.querySelector(`.card[data-id="${bookingId}"], .booking-item[data-id="${bookingId}"]`);
                            if (bookingElement) {
                                // Znajdź sekcję opinii i zamień ją na przycisk 'Dodaj opinię'
                                const buttonContainer = bookingElement.querySelector('.d-flex.gap-2');
                                if (buttonContainer) {
                                    // Wyczyść kontener przycisków
                                    buttonContainer.innerHTML = '';

                                    // Utwórz przycisk 'Dodaj opinię'
                                    const addOpinionButton = document.createElement('a');
                                    addOpinionButton.href = `/opinions/create/${bookingId}`;
                                    addOpinionButton.className = 'btn btn-outline-success';
                                    addOpinionButton.textContent = 'Dodaj opinię';
                                    buttonContainer.appendChild(addOpinionButton);

                                    // Usuń również treść opinii, jeśli istnieje
                                    const opinionContent = bookingElement.querySelector('.opinion-content, .mt-3.p-3.bg-light.rounded');
                                    if (opinionContent) {
                                        opinionContent.remove();
                                    }
                                }

                                // Pokaż powiadomienie o sukcesie
                                showNotification('Opinia została pomyślnie usunięta', 'success');
                            }
                        }
                    } else {
                        isDeleteInProgress = false; // Zresetuj flagę

                        // Przywróć stan przycisku
                        button.innerHTML = originalText;
                        button.disabled = false;

                        // Pokaż błąd
                        const errorMessage = data.error || 'Wystąpił błąd podczas usuwania opinii';

                        if (window.location.pathname.includes('/opinions/edit/')) {
                            // Na stronie edycji użyj alert()
                            alert(errorMessage);
                        } else {
                            // Na stronie listy użyj notyfikacji
                            showNotification(errorMessage, 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    isDeleteInProgress = false; // Zresetuj flagę

                    // Przywróć stan przycisku
                    button.innerHTML = originalText;
                    button.disabled = false;

                    // Pokaż błąd
                    const errorMessage = 'Wystąpił błąd podczas usuwania opinii';

                    if (window.location.pathname.includes('/opinions/edit/')) {
                        // Na stronie edycji użyj alert()
                        alert(errorMessage);
                    } else {
                        // Na stronie listy użyj notyfikacji
                        showNotification(errorMessage, 'error');
                    }
                });
        } else {
            // Reset flagi jeśli użytkownik anulował
            isDeleteInProgress = false;
        }
    }

    /**
     * Wyświetla powiadomienie w stylu Bootstrap
     * @param {string} message - Treść powiadomienia
     * @param {string} type - Typ powiadomienia (success, error, info)
     */
    function showNotification(message, type = 'info') {
        // Sprawdź, czy element powiadomień już istnieje
        let notificationContainer = document.getElementById('notification-container');

        // Jeśli nie, stwórz nowy
        if (!notificationContainer) {
            notificationContainer = document.createElement('div');
            notificationContainer.id = 'notification-container';
            notificationContainer.className = 'position-fixed top-0 end-0 p-3';
            notificationContainer.style.zIndex = '1050';
            document.body.appendChild(notificationContainer);
        }

        // Określ klasę alertu na podstawie typu
        let alertClass = 'alert-info';
        let icon = '';

        if (type === 'success') {
            alertClass = 'alert-success';
            icon = '<i class="bi bi-check-circle-fill me-2"></i>';
        } else if (type === 'error') {
            alertClass = 'alert-danger';
            icon = '<i class="bi bi-exclamation-circle-fill me-2"></i>';
        } else {
            icon = '<i class="bi bi-info-circle-fill me-2"></i>';
        }

        // Stwórz element powiadomienia
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show`;
        notification.innerHTML = `
            ${icon}
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Dodaj do kontenera
        notificationContainer.appendChild(notification);

        // Automatyczne zamknięcie po 5 sekundach
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const alert = bootstrap.Alert.getOrCreateInstance(notification);
                alert.close();
            } else {
                notification.remove();
            }
        }, 5000);
    }

    // Uruchom inicjalizację po załadowaniu DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOpinionHandlers);
    } else {
        initOpinionHandlers();
    }
})();