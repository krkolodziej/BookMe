class BookingForm {
    constructor(formConfig) {
        this.userSelect = document.getElementById(formConfig.userSelectId);
        this.offerSelect = document.getElementById(formConfig.offerSelectId);
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

        this.setupDateNavigation();

        this.initializeEventListeners();
    }

    setupDateNavigation() {
        if (!document.getElementById('datesContainer')) {
            const dateStepContainer = document.querySelector('.booking-step[data-step="2"]');
            const dateInputContainer = this.dateInput.closest('.mb-3');
            
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
            
            dateInputContainer.after(datesContainer);
            dateInputContainer.after(dateNavigation);
            
            dateInputContainer.style.display = 'none';
            
            this.datesContainer = datesContainer.querySelector('.available-dates');
            this.prevWeekBtn = document.getElementById('prevWeek');
            this.nextWeekBtn = document.getElementById('nextWeek');
            this.todayBtn = document.getElementById('todayBtn');
            
            this.currentDate = new Date();
            this.selectedDate = null;
            
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

        this.offerSelect.addEventListener('change', () => this.handleOfferEmployeeChange());
        this.employeeSelect.addEventListener('change', () => this.handleOfferEmployeeChange());
    }

    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    isPastDate(date) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        date.setHours(0, 0, 0, 0);
        
        return date < today;
    }

    generateDates(startDate) {
        if (!this.datesContainer) return;
        
        this.datesContainer.innerHTML = '';

        for (let i = 0; i < 7; i++) {
            const date = new Date(startDate);
            date.setDate(date.getDate() + i);

            const isPast = this.isPastDate(new Date(date));

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

            const formattedDate = this.formatDate(date);
            dateCard.setAttribute('data-date', formattedDate);

            if (!isPast) {
                dateCard.addEventListener('click', () => {
                    document.querySelectorAll('.date-card').forEach(card => {
                        card.classList.remove('active');
                    });

                    dateCard.classList.add('active');
                    this.selectedDate = formattedDate;
                    
                    this.dateInput.value = formattedDate;
                    
                    this.fetchAvailableSlots();
                });
            }

            this.datesContainer.appendChild(dateCard);
        }
    }

    handleNextStep(button) {
        const currentStep = parseInt(button.getAttribute('data-step'));
        const nextStep = currentStep + 1;

        if (currentStep === 1) {
            if (!this.userSelect.value || !this.offerSelect.value || !this.employeeSelect.value) {
                alert('Proszę wypełnić wszystkie pola.');
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

    handleOfferEmployeeChange() {
        this.startTimeInput.value = '';
        document.querySelector('.next-step[data-step="2"]').setAttribute('disabled', 'disabled');
        
        if (this.selectedDate) {
            this.fetchAvailableSlots();
        }
    }

    fetchAvailableSlots() {
        const offerId = this.offerSelect.value;
        const employeeId = this.employeeSelect.value;
        const date = this.selectedDate || this.dateInput.value;

        if (!offerId || !employeeId || !date) {
            return;
        }

        this.noSlotsMessage.style.display = 'none';
        this.timeSlotsContainer.style.display = 'none';
        this.loadingSpinner.style.display = 'block';

        fetch(`${this.slotsUrl}?offer=${offerId}&employee=${employeeId}&date=${date}`)
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
        slots.forEach(slot => {
            const slotElement = document.createElement('div');
            slotElement.classList.add('time-slot');
            slotElement.textContent = slot.time;
            slotElement.setAttribute('data-datetime', slot.datetime);

            slotElement.addEventListener('click', () => {
                document.querySelectorAll('.time-slot').forEach(el => {
                    el.classList.remove('selected');
                });

                slotElement.classList.add('selected');
                this.startTimeInput.value = slotElement.getAttribute('data-datetime');
                document.querySelector('.next-step[data-step="2"]').removeAttribute('disabled');
            });

            this.timeSlotsContainer.appendChild(slotElement);
        });

        this.timeSlotsContainer.style.display = 'flex';
    }

    updateSummary() {
        const userIndex = this.userSelect.selectedIndex;
        const offerIndex = this.offerSelect.selectedIndex;
        const employeeIndex = this.employeeSelect.selectedIndex;

        const selectedDatetime = new Date(this.startTimeInput.value);
        const formattedDate = selectedDatetime.toLocaleDateString('pl-PL');
        const formattedTime = selectedDatetime.toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit' });

        document.getElementById('summary-client').textContent = this.userSelect.options[userIndex].text;
        document.getElementById('summary-offer').textContent = this.offerSelect.options[offerIndex].text;
        document.getElementById('summary-employee').textContent = this.employeeSelect.options[employeeIndex].text;
        document.getElementById('summary-date').textContent = formattedDate;
        document.getElementById('summary-time').textContent = formattedTime;

        const offerDuration = this.getOfferDuration(this.offerSelect.value);
        document.getElementById('summary-duration').textContent = offerDuration || 'n/a';
    }
    
    getOfferDuration(offerId) {
        const offerOption = Array.from(this.offerSelect.options).find(option => option.value === offerId);
        if (!offerOption) return null;
        
        const durationMatch = offerOption.text.match(/\((\d+) min\)/);
        return durationMatch ? durationMatch[1] : null;
    }
}