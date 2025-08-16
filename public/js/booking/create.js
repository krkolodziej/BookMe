class BookingForm {
    constructor(formConfig) {
        this.employeeSelect = document.getElementById(formConfig.employeeSelectId);
        this.dateInput = document.getElementById(formConfig.dateInputId);
        this.startTimeInput = document.getElementById(formConfig.startTimeInputId);
        this.slotsUrl = formConfig.slotsUrl;

        this.timeSlotsContainer = document.querySelector('.time-slots');
        this.noSlotsMessage = document.querySelector('.no-slots-message');
        this.loadingSpinner = document.querySelector('.loading-spinner');

        this.steps = document.querySelectorAll('.step');
        this.bookingSteps = document.querySelectorAll('.booking-step');
        this.nextButtons = document.querySelectorAll('.next-step');
        this.prevButtons = document.querySelectorAll('.prev-step');

        // Inicjalizacja z danymi początkowymi, jeśli są dostępne (tryb edycji)
        if (formConfig.initialData) {
            this.initialData = formConfig.initialData;
        }

        // Dodaj elementy do nawigacji datami podobnie jak w interfejsie użytkownika
        this.setupDateNavigation();

        this.initializeEventListeners();
    }

    setupDateNavigation() {
        // Dodaj kontener dla dat, jeśli nie istnieje
        if (!document.getElementById('datesContainer')) {
            const dateStepContainer = document.querySelector('.booking-step[data-step="2"]');
            const dateInputContainer = this.dateInput.closest('.mb-3');
            
            // Utwórz elementy nawigacji datami
            const dateNavigation = document.createElement('div');
            dateNavigation.className = 'date-navigation';
            dateNavigation.innerHTML = `
                <div class="btn-group w-100 mb-3">
                    <button type="button" id="prevWeek" class="btn btn-outline-secondary"><i class="fas fa-chevron-left me-1"></i> Poprzedni tydzień</button>
                    <button type="button" id="todayBtn" class="btn btn-outline-primary">Dzisiaj</button>
                    <button type="button" id="nextWeek" class="btn btn-outline-secondary">Następny tydzień <i class="fas fa-chevron-right ms-1"></i></button>
                </div>
            `;
            
            const datesContainer = document.createElement('div');
            datesContainer.id = 'datesContainer';
            datesContainer.className = 'mb-4';
            datesContainer.innerHTML = '<div class="available-dates"></div>';
            
            // Wstaw elementy po etykiecie daty
            dateInputContainer.after(datesContainer);
            dateInputContainer.after(dateNavigation);
            
            // Ukryj standardowy input daty
            dateInputContainer.style.display = 'none';
            
            // Zapisz referencje
            this.datesContainer = datesContainer.querySelector('.available-dates');
            this.prevWeekBtn = document.getElementById('prevWeek');
            this.nextWeekBtn = document.getElementById('nextWeek');
            this.todayBtn = document.getElementById('todayBtn');
            
            // Aktualna data i wybrana data
            this.currentDate = new Date();
            this.selectedDate = null;
            
            // Generuj daty dla bieżącego tygodnia
            this.generateDates(this.currentDate);
        }
    }

    initializeEventListeners() {
        this.nextButtons.forEach(button => {
            button.addEventListener('click', () => this.handleNextStep(button));
        });

        this.prevButtons.forEach(button => {
            button.addEventListener('click', () => this.handlePrevStep(button));
        });

        // Dodaj obsługę nawigacji datami
        if (this.prevWeekBtn) {
            this.prevWeekBtn.addEventListener('click', () => {
                const newDate = new Date(this.currentDate);
                newDate.setDate(newDate.getDate() - 7);
                this.currentDate = newDate;
                this.generateDates(this.currentDate);
            });
        }

        if (this.nextWeekBtn) {
            this.nextWeekBtn.addEventListener('click', () => {
                const newDate = new Date(this.currentDate);
                newDate.setDate(newDate.getDate() + 7);
                this.currentDate = newDate;
                this.generateDates(this.currentDate);
            });
        }

        if (this.todayBtn) {
            this.todayBtn.addEventListener('click', () => {
                this.currentDate = new Date();
                this.generateDates(this.currentDate);
            });
        }

        this.employeeSelect.addEventListener('change', () => this.handleEmployeeChange());
    }

    // Funkcja formatująca datę
    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    // Funkcja sprawdzająca czy data jest z przeszłości
    isPastDate(date) {
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Resetuj godzinę do początku dnia
        date.setHours(0, 0, 0, 0); // Resetuj godzinę do początku dnia
        
        return date < today;
    }

    // Funkcja generująca daty tygodnia - zaczynając od dzisiejszej daty
    generateDates(startDate) {
        if (!this.datesContainer) return;
        
        this.datesContainer.innerHTML = '';
        
        // Dla create.js, pierwsza data to dzisiejsza data
        const firstDay = new Date(startDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Resetuj godzinę do początku dnia

        for (let i = 0; i < 7; i++) {
            const date = new Date(firstDay);
            date.setDate(date.getDate() + i);

            const dateCard = document.createElement('div');
            dateCard.classList.add('date-card');

            // Sprawdź czy data jest z przeszłości
            const isPast = this.isPastDate(new Date(date));
            if (isPast) {
                dateCard.classList.add('disabled');
            }

            const dayNames = ['Nie', 'Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob'];

            dateCard.innerHTML = `
                <div class="day-name">${dayNames[date.getDay()]}</div>
                <div class="day-number">${date.getDate()}</div>
                <div class="month-name">${date.toLocaleDateString('pl-PL', { month: 'short' })}</div>
            `;

            const formattedDate = this.formatDate(date);
            dateCard.setAttribute('data-date', formattedDate);

            if (!isPast) {
                dateCard.addEventListener('click', () => {
                    document.querySelectorAll('.date-card').forEach(card => {
                        card.classList.remove('active');
                    });

                    dateCard.classList.add('active');
                    this.selectedDate = formattedDate;
                    
                    // Ustaw wartość w ukrytym polu daty
                    this.dateInput.value = formattedDate;
                    
                    this.fetchAvailableSlots();
                });
            }

            this.datesContainer.appendChild(dateCard);
        }

        // Jeśli to pierwszy widok kalendarza, wybierz dzisiejszą datę
        if (!this.selectedDate) {
            const todayFormatted = this.formatDate(today);
            const todayCard = this.datesContainer.querySelector(`[data-date="${todayFormatted}"]`);
            if (todayCard && !todayCard.classList.contains('disabled')) {
                todayCard.click();
            } else {
                // Jeśli dzisiejsza data jest niedostępna, wybierz pierwszą dostępną
                const firstAvailableCard = this.datesContainer.querySelector('.date-card:not(.disabled)');
                if (firstAvailableCard) {
                    firstAvailableCard.click();
                }
            }
        }
    }

    handleNextStep(button) {
        const currentStep = parseInt(button.getAttribute('data-step'));
        const nextStep = currentStep + 1;

        if (currentStep === 1) {
            if (!this.employeeSelect.value) {
                alert('Proszę wybrać specjalistę.');
                return;
            }
        } else if (currentStep === 2) {
            if (!this.startTimeInput.value) {
                alert('Proszę wybrać termin wizyty.');
                return;
            }
        }

        this.updateStepsVisibility(nextStep);

        if (nextStep === 3) {
            this.updateSummary();
        }
    }

    handlePrevStep(button) {
        const currentStep = parseInt(button.getAttribute('data-step'));
        const prevStep = currentStep - 1;
        this.updateStepsVisibility(prevStep);
    }

    updateStepsVisibility(activeStep) {
        this.bookingSteps.forEach(step => {
            step.classList.remove('active');
        });

        document.querySelector(`.booking-step[data-step="${activeStep}"]`).classList.add('active');

        this.steps.forEach(step => {
            step.classList.remove('active');
            if (parseInt(step.getAttribute('data-step')) <= activeStep) {
                step.classList.add('active');
            }
        });
    }

    handleEmployeeChange() {
        this.startTimeInput.value = '';
        document.querySelector('.next-step[data-step="2"]').setAttribute('disabled', 'disabled');
        
        // Jeśli mamy już wybraną datę, pobierz dostępne sloty
        if (this.selectedDate) {
            this.fetchAvailableSlots();
        }
    }

    fetchAvailableSlots() {
        const employeeId = this.employeeSelect.value;
        const date = this.selectedDate || this.dateInput.value;

        if (!employeeId || !date) {
            return;
        }

        this.noSlotsMessage.style.display = 'none';
        this.timeSlotsContainer.style.display = 'none';
        this.loadingSpinner.style.display = 'block';

        fetch(`${this.slotsUrl}?employee=${employeeId}&date=${date}`)
            .then(response => response.json())
            .then(data => this.handleSlotsResponse(data))
            .catch(error => {
                console.error('Error:', error);
                this.loadingSpinner.style.display = 'none';
                this.noSlotsMessage.textContent = 'Wystąpił błąd podczas pobierania dostępnych terminów.';
                this.noSlotsMessage.style.display = 'block';
            });
    }

    handleSlotsResponse(data) {
        this.loadingSpinner.style.display = 'none';

        if (data.error) {
            this.noSlotsMessage.textContent = data.error;
            this.noSlotsMessage.style.display = 'block';
            return;
        }

        if (!data.slots || data.slots.length === 0) {
            this.noSlotsMessage.textContent = 'Brak dostępnych terminów w wybranym dniu.';
            this.noSlotsMessage.style.display = 'block';
            return;
        }

        this.renderTimeSlots(data.slots);
    }

    renderTimeSlots(slots) {
        this.timeSlotsContainer.innerHTML = '';
        const now = new Date();
        
        // Dodaj początkową godzinę do slotów, jeśli jesteśmy w trybie edycji
        if (this.initialData && this.initialData.startTime) {
            const initialDateTime = new Date(this.initialData.startTime);
            const initialTime = initialDateTime.toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });
            
            // Sprawdź czy data się zgadza
            if (this.selectedDate === this.initialData.bookingDate) {
                // Dodaj slot z początkową godziną, jeśli nie istnieje
                const hasInitialSlot = slots.some(slot => {
                    const slotDate = new Date(slot.datetime);
                    return slotDate.getHours() === initialDateTime.getHours() 
                        && slotDate.getMinutes() === initialDateTime.getMinutes();
                });

                if (!hasInitialSlot) {
                    // Dodaj slot w odpowiednim miejscu chronologicznie
                    const newSlot = {
                        datetime: this.initialData.startTime,
                        time: initialTime
                    };
                    
                    // Wstaw slot w odpowiednim miejscu chronologicznie
                    const insertIndex = slots.findIndex(slot => {
                        return new Date(slot.datetime) > initialDateTime;
                    });
                    
                    if (insertIndex === -1) {
                        slots.push(newSlot);
                    } else {
                        slots.splice(insertIndex, 0, newSlot);
                    }
                }
            }
        }

        slots.forEach(slot => {
            const slotTime = new Date(slot.datetime);
            const isPastTime = slotTime < now;
            
            const slotElement = document.createElement('div');
            slotElement.classList.add('time-slot');
            slotElement.textContent = slot.time;
            slotElement.setAttribute('data-datetime', slot.datetime);
            
            // Sprawdź czy to jest początkowa godzina w trybie edycji
            if (this.initialData && this.initialData.startTime) {
                const initialDateTime = new Date(this.initialData.startTime);
                if (slotTime.getTime() === initialDateTime.getTime()) {
                    slotElement.classList.add('selected');
                    this.startTimeInput.value = slot.datetime;
                    document.querySelector('.next-step[data-step="2"]').removeAttribute('disabled');
                }
            }
            
            if (isPastTime) {
                slotElement.classList.add('disabled');
            } else {
                slotElement.addEventListener('click', () => {
                    document.querySelectorAll('.time-slot').forEach(el => {
                        el.classList.remove('selected');
                    });

                    slotElement.classList.add('selected');
                    this.startTimeInput.value = slotElement.getAttribute('data-datetime');
                    document.querySelector('.next-step[data-step="2"]').removeAttribute('disabled');
                });
            }

            this.timeSlotsContainer.appendChild(slotElement);
        });

        this.timeSlotsContainer.style.display = 'flex';
    }

    updateSummary() {
        const employeeIndex = this.employeeSelect.selectedIndex;

        const selectedDatetime = new Date(this.startTimeInput.value);
        const formattedDate = selectedDatetime.toLocaleDateString('pl-PL');
        const formattedTime = selectedDatetime.toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });

        document.getElementById('summary-employee').textContent = this.employeeSelect.options[employeeIndex].text;
        document.getElementById('summary-date').textContent = formattedDate;
        document.getElementById('summary-time').textContent = formattedTime;
    }
}