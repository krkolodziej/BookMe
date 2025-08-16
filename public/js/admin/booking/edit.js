document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.getElementById(formVars.userId);
    const offerSelect = document.getElementById(formVars.offerId);
    const employeeSelect = document.getElementById(formVars.employeeId);
    const dateInput = document.getElementById(formVars.bookingDateId);
    const startTimeInput = document.getElementById(formVars.startTimeId);

    const timeSlotsContainer = document.querySelector('.time-slots');
    const noSlotsMessage = document.querySelector('.no-slots-message');
    const loadingSpinner = document.querySelector('.loading-spinner');

    const detailsTab = document.getElementById('details-tab');
    const timeTab = document.getElementById('time-tab');
    const goToTimeTabButton = document.getElementById('goToTimeTab');
    const backToDetailsTabButton = document.getElementById('backToDetailsTab');

    let currentDate = new Date();
    let selectedDate = null;
    let datesContainer = null;

    goToTimeTabButton.addEventListener('click', function() {
        timeTab.click();
    });

    backToDetailsTabButton.addEventListener('click', function() {
        detailsTab.click();
    });

    function setupDateNavigation() {
        const dateInputContainer = dateInput.closest('.mb-3');
        
        const dateNavigation = document.createElement('div');
        dateNavigation.className = 'date-navigation';
        dateNavigation.innerHTML = `
            <div class="btn-group w-100 mb-3">
                <button type="button" id="prevWeek" class="btn btn-outline-secondary"><i class="fas fa-chevron-left me-1"></i> Poprzedni tydzień</button>
                <button type="button" id="todayBtn" class="btn btn-outline-primary">Dzisiaj</button>
                <button type="button" id="nextWeek" class="btn btn-outline-secondary">Następny tydzień <i class="fas fa-chevron-right ms-1"></i></button>
            </div>
        `;
        
        const datesContainerElement = document.createElement('div');
        datesContainerElement.id = 'datesContainer';
        datesContainerElement.className = 'mb-4';
        datesContainerElement.innerHTML = '<div class="available-dates"></div>';
        
        dateInputContainer.after(datesContainerElement);
        dateInputContainer.after(dateNavigation);
        
        dateInputContainer.style.display = 'none';
        
        datesContainer = datesContainerElement.querySelector('.available-dates');
        const prevWeekBtn = document.getElementById('prevWeek');
        const nextWeekBtn = document.getElementById('nextWeek');
        const todayBtn = document.getElementById('todayBtn');
        
        prevWeekBtn.addEventListener('click', () => {
            const newDate = new Date(currentDate);
            newDate.setDate(newDate.getDate() - 7);
            currentDate = newDate;
            generateDates(currentDate);
        });

        nextWeekBtn.addEventListener('click', () => {
            const newDate = new Date(currentDate);
            newDate.setDate(newDate.getDate() + 7);
            currentDate = newDate;
            generateDates(currentDate);
        });

        todayBtn.addEventListener('click', () => {
            currentDate = new Date();
            generateDates(currentDate);
        });
        
        generateDates(currentDate);
        
        if (bookingConfig.currentDateTime) {
            const initialDate = new Date(bookingConfig.currentDateTime);
            currentDate = new Date(initialDate);
            generateDates(currentDate);
            
            setTimeout(() => {
                const formattedDate = formatDate(initialDate);
                const dateCard = document.querySelector(`.date-card[data-date="${formattedDate}"]`);
                if (dateCard && !dateCard.classList.contains('disabled')) {
                    dateCard.click();
                }
            }, 100);
        }
    }

    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    function isPastDate(date) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        date.setHours(0, 0, 0, 0);
        
        return date < today;
    }

    function generateDates(startDate) {
        if (!datesContainer) return;
        
        datesContainer.innerHTML = '';

        for (let i = 0; i < 7; i++) {
            const date = new Date(startDate);
            date.setDate(date.getDate() + i);

            const isPast = isPastDate(new Date(date));

            const dateCard = document.createElement('div');
            dateCard.classList.add('date-card');
            
            if (isPast) {
                dateCard.classList.add('disabled');
            }

            const dayNames = ['Nie', 'Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob'];

            dateCard.innerHTML = `
                <div class="day-name">${dayNames[date.getDay()]}</div>
                <div class="day-number">${date.getDate()}</div>
                <div class="month-name">${date.toLocaleDateString('pl-PL', { month: 'short' })}</div>
            `;

            const formattedDate = formatDate(date);
            dateCard.setAttribute('data-date', formattedDate);

            if (!isPast) {
                dateCard.addEventListener('click', () => {
                    document.querySelectorAll('.date-card').forEach(card => {
                        card.classList.remove('active');
                    });

                    dateCard.classList.add('active');
                    selectedDate = formattedDate;
                    
                    dateInput.value = formattedDate;
                    
                    fetchAvailableSlots();
                });
            }

            datesContainer.appendChild(dateCard);
        }
    }

    function fetchAvailableSlots() {
        const offerId = offerSelect.value;
        const employeeId = employeeSelect.value;
        const date = dateInput.value;

        if (!offerId || !employeeId || !date) {
            return;
        }

        noSlotsMessage.style.display = 'none';
        timeSlotsContainer.style.display = 'none';
        loadingSpinner.style.display = 'block';

        fetch(`${bookingConfig.slotsUrl}?offer=${offerId}&employee=${employeeId}&date=${date}&booking=${bookingConfig.bookingId}`)
            .then(response => response.json())
            .then(data => {
                loadingSpinner.style.display = 'none';

                if (data.error) {
                    noSlotsMessage.textContent = data.error;
                    noSlotsMessage.style.display = 'block';
                    return;
                }

                if (!data.slots || data.slots.length === 0) {
                    noSlotsMessage.textContent = 'Brak dostępnych terminów w wybranym dniu.';
                    noSlotsMessage.style.display = 'block';
                    return;
                }

                const currentDateTime = new Date(bookingConfig.currentDateTime);
                const currentDate = currentDateTime.toISOString().split('T')[0];
                
                if (date === currentDate) {
                    const currentTime = currentDateTime.toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
                    
                    const hasCurrentSlot = data.slots.some(slot => {
                        const slotDate = new Date(slot.datetime);
                        return slotDate.getTime() === currentDateTime.getTime();
                    });

                    if (!hasCurrentSlot) {
                        const currentSlot = {
                            datetime: currentDateTime.toISOString(),
                            time: currentTime,
                            current: true
                        };
                        
                        const insertIndex = data.slots.findIndex(slot => {
                            return new Date(slot.datetime) > currentDateTime;
                        });
                        
                        if (insertIndex === -1) {
                            data.slots.push(currentSlot);
                        } else {
                            data.slots.splice(insertIndex, 0, currentSlot);
                        }
                    }
                }

                timeSlotsContainer.innerHTML = '';
                const now = new Date();

                data.slots.forEach(slot => {
                    const slotTime = new Date(slot.datetime);
                    const isPastTime = slotTime < now;
                    
                    const slotElement = document.createElement('div');
                    slotElement.classList.add('time-slot');
                    slotElement.textContent = slot.time;
                    slotElement.setAttribute('data-datetime', slot.datetime);

                    if (isPastTime) {
                        slotElement.classList.add('disabled');
                    } else {
                        if (slot.current || slotTime.getTime() === currentDateTime.getTime()) {
                            slotElement.classList.add('current');
                            slotElement.classList.add('selected');
                            startTimeInput.value = slot.datetime;
                        }
                        
                        slotElement.addEventListener('click', function() {
                            document.querySelectorAll('.time-slot').forEach(el => {
                                el.classList.remove('selected');
                            });
                            this.classList.add('selected');
                            startTimeInput.value = this.getAttribute('data-datetime');
                        });
                    }

                    timeSlotsContainer.appendChild(slotElement);
                });

                timeSlotsContainer.style.display = 'flex';
            })
            .catch(error => {
                console.error('Error:', error);
                loadingSpinner.style.display = 'none';
                noSlotsMessage.textContent = 'Wystąpił błąd podczas pobierania dostępnych terminów.';
                noSlotsMessage.style.display = 'block';
            });
    }

    offerSelect.addEventListener('change', fetchAvailableSlots);
    employeeSelect.addEventListener('change', fetchAvailableSlots);

    timeTab.addEventListener('shown.bs.tab', function() {
        if (!datesContainer) {
            setupDateNavigation();
        } else {
            fetchAvailableSlots();
        }
    });
});
