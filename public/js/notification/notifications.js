
document.addEventListener('DOMContentLoaded', function() {
    // 1. Na początku sprawdzamy, czy jesteśmy na stronie powiadomień
    const isNotificationsPage = window.location.pathname.includes('/notifications');
    
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationDropdownContent = document.getElementById('notificationDropdownContent');
    
    // 2. Jeśli jesteśmy na stronie powiadomień, całkowicie wyłączamy dropdown
    if (isNotificationsPage && notificationDropdown) {
        console.log('Wyłączam dropdown na stronie powiadomień');
        
        // Usunięcie atrybutów Bootstrap dropdown
        notificationDropdown.removeAttribute('data-bs-toggle');
        notificationDropdown.removeAttribute('data-bs-auto-close');
        notificationDropdown.removeAttribute('aria-expanded');
        
        // Usunięcie wszystkich event listenerów (starsza metoda, ale skuteczna)
        const newDropdown = notificationDropdown.cloneNode(true);
        if (notificationDropdown.parentNode) {
            notificationDropdown.parentNode.replaceChild(newDropdown, notificationDropdown);
        }
        
        // Przypisanie nowego handlera, który nic nie robi
        newDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Dropdown jest wyłączony na stronie powiadomień');
            return false;
        });
        
        // Jeśli dropdown content istnieje, usuń jego zawartość
        if (notificationDropdownContent) {
            notificationDropdownContent.innerHTML = '';
            // Ukryj go na wszelki wypadek
            notificationDropdownContent.style.display = 'none';
        }
        
        // Wyjdź z funkcji - nie inicjalizujemy nic więcej na stronie powiadomień
        return;
    }
    
    // 3. Tylko gdy NIE jesteśmy na stronie powiadomień, kontynuujemy normalne działanie
    let isLoaded = false;
    
    if (notificationDropdown && notificationDropdownContent) {
        notificationDropdown.addEventListener('click', function(e) {
            if (!isLoaded) {
                loadNotifications();
            }
        });

        // Nasłuchiwanie na otwarcie dropdown przez Bootstrap
        notificationDropdown.addEventListener('shown.bs.dropdown', function() {
            if (!isLoaded) {
                loadNotifications();
            }
        });
    }

    function loadNotifications() {
        // Pokaż spinner
        notificationDropdownContent.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Ładowanie...</span>
                </div>
            </div>
        `;

        fetch('/notifications/dropdown', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            notificationDropdownContent.innerHTML = html;
            isLoaded = true;
            
            // Ustaw obsługę dropdown po załadowaniu
            setupDropdownHandlers();
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            notificationDropdownContent.innerHTML = `
                <div class="dropdown-item-text text-center py-3 text-danger">
                    <i class="fas fa-exclamation-triangle mb-2"></i>
                    <div class="small">Błąd ładowania powiadomień</div>
                </div>
            `;
        });
    }

    function setupDropdownHandlers() {
        // Obsługa kliknięcia na powiadomienie w dropdown
        document.querySelectorAll('.notification-dropdown-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-notification-id');
                const isRead = !this.classList.contains('bg-light');
                
                if (!isRead) {
                    markAsReadDropdown(notificationId, this);
                }
                
                // Przekieruj do pełnej strony powiadomień tylko jeśli nie jesteśmy już tam
                if (!window.location.pathname.includes('/notifications')) {
                    window.location.href = '/notifications';
                }
            });
        });

        // Oznacz wszystkie jako przeczytane w dropdown
        const markAllDropdownBtn = document.getElementById('markAllReadDropdown');
        if (markAllDropdownBtn) {
            markAllDropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                markAllAsReadDropdown();
            });
        }
    }

    function markAsReadDropdown(notificationId, element) {
        fetch(`/notifications/mark-read/${notificationId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.classList.remove('bg-light');
                const badge = element.querySelector('.badge');
                if (badge) badge.remove();
                updateNotificationCounter();
                
                // Jeśli jesteśmy na stronie powiadomień, ale nie w dropdown - odśwież stronę
                if (window.location.pathname.includes('/notifications') && 
                    !element.closest('#notificationDropdownContent')) {
                    setTimeout(() => window.location.reload(), 500);
                }
            }
        });
    }

    function markAllAsReadDropdown() {
        fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Usuń oznaczenia "nowe" z wszystkich powiadomień w dropdown
                document.querySelectorAll('.notification-dropdown-item.bg-light').forEach(item => {
                    item.classList.remove('bg-light');
                    const badge = item.querySelector('.badge');
                    if (badge) badge.remove();
                });
                
                // Ukryj przycisk
                const markAllBtn = document.getElementById('markAllReadDropdown');
                if (markAllBtn) markAllBtn.style.display = 'none';
                
                updateNotificationCounter();
                
                // Jeśli jesteśmy na stronie powiadomień, ale nie w dropdown - odśwież stronę
                const dropdown = document.getElementById('notificationDropdownContent');
                if (window.location.pathname.includes('/notifications') && 
                    (!dropdown || !dropdown.contains(document.activeElement))) {
                    setTimeout(() => window.location.reload(), 500);
                }
            }
        });
    }

    // Globalna funkcja updateNotificationCounter dostępna z innych miejsc
    window.updateNotificationCounter = updateNotificationCounter;

    // Odświeżanie powiadomień co 5 minut
    setInterval(function() {
        if (isLoaded && !notificationDropdown.closest('.dropdown').classList.contains('show')) {
            // Odśwież tylko jeśli dropdown nie jest otwarty
            isLoaded = false;
        }
        updateNotificationCounter();
    }, 300000); // 5 minut

    function updateNotificationCounter() {
        fetch('/notifications/count-unread')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('#notificationDropdown .badge');
            if (badge) {
                if (data.count === 0) {
                    badge.style.display = 'none';
                } else {
                    badge.textContent = data.count;
                    badge.style.display = 'inline';
                }
            }
        })
        .catch(error => {
            console.error('Error updating notification counter:', error);
        });
    }
});