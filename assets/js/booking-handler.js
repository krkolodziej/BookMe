/**
 * Booking handler class for managing booking-related functionality
 */
export default class BookingHandler {
    constructor(options = {}) {
        // Elements
        this.employeeSelect = document.querySelector(options.employeeSelectSelector || '#booking_employee');
        this.startTimeInput = document.querySelector(options.startTimeInputSelector || '#booking_startTime');
        this.selectedTimeDisplay = document.querySelector(options.selectedTimeDisplaySelector || '.selected-time-display');
        this.selectedTimeText = document.querySelector(options.selectedTimeTextSelector || '#selectedTimeText');
        this.loadingIndicator = document.querySelector(options.loadingIndicatorSelector || '.loading-indicator');
        this.availableDatesContainer = document.getElementById(options.availableDatesContainerId || 'availableDates');
        this.prevWeekBtn = document.getElementById(options.prevWeekBtnId || 'prevWeek');
        this.nextWeekBtn = document.getElementById(options.nextWeekBtnId || 'nextWeek');
        this.todayBtn = document.getElementById(options.todayBtnId || 'todayBtn');
        this.submitBtn = document.getElementById(options.submitBtnId || 'submitBtn');
        this.bookingForm = document.querySelector(options.bookingFormSelector || '.booking-form');

        // State
        this.currentStartDate = new Date();
        this.selectedDate = null;
        this.selectedTimeSlot = null;
        this.cachedData = {};
        this.currentBookingTime = options.currentBookingTime ? new Date(options.currentBookingTime) : null;
        this.isEditMode = !!options.currentBookingTime;

        // Initialize
        this.init();
    }

    // Format date as YYYY-MM-DD
    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    // Format date for display (e.g., "Pon, 12 lut")
    formatDisplayDate(date) {
        const days = ['Niedz', 'Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob'];
        const months = ['sty', 'lut', 'mar', 'kwi', 'maj', 'cze', 'lip', 'sie', 'wrz', 'paź', 'lis', 'gru'];

        return `${days[date.getDay()]}, ${date.getDate()} ${months[date.getMonth()]}`;
    }

    // Format date as required by Symfony (DD.MM.YYYY HH:MM)
    formatDateForSymfony(dateObj) {
        const day = String(dateObj.getDate()).padStart(2, '0');
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const year = dateObj.getFullYear();
        const hours = String(dateObj.getHours()).padStart(2, '0');
        const minutes = String(dateObj.getMinutes()).padStart(2, '0');

        return `${day}.${month}.${year} ${hours}:${minutes}`;
    }

    // Generate dates for display (2 weeks from currentStartDate)
    generateDateRange() {
        const dates = [];
        const startDate = new Date(this.currentStartDate);

        // Get dates for 2 weeks
        for (let i = 0; i < 14; i++) {
            const date = new Date(startDate);
            date.setDate(date.getDate() + i);
            dates.push(date);
        }

        return dates;
    }

    // Group time slots by date
    groupSlotsByDate(slots) {
        const groupedSlots = {};

        slots.forEach(slot => {
            const dateObj = new Date(slot.datetime);
            const dateStr = this.formatDate(dateObj);

            if (!groupedSlots[dateStr]) {
                groupedSlots[dateStr] = [];
            }

            groupedSlots[dateStr].push({
                time: slot.time,
                datetime: slot.datetime,
                current: slot.current || false
            });
        });

        return groupedSlots;
    }

    init() {
        // Set up event listeners
        this.setupEventListeners();

        // Initial load if employee is pre-selected
        if (this.employeeSelect.value) {
            if (this.isEditMode) {
                this.setToCurrentBookingDate();
            } else {
                this.fetchAvailableSlots();
            }
        }
    }

    setupEventListeners() {
        // Handle employee selection change
        this.employeeSelect.addEventListener('change', this.handleEmployeeChange.bind(this));

        // Handle navigation buttons
        this.prevWeekBtn.addEventListener('click', () => this.moveWeek(-1));
        this.nextWeekBtn.addEventListener('click', () => this.moveWeek(1));
        this.todayBtn.addEventListener('click', this.setToday.bind(this));

        // Form validation
        this.bookingForm.addEventListener('submit', this.validateForm.bind(this));
        this.submitBtn.addEventListener('click', this.validateForm.bind(this));
    }

    handleEmployeeChange() {
        // Reset all selections and cache when employee changes
        this.selectedDate = null;
        this.selectedTimeSlot = null;
        this.cachedData = {};

        if (this.isEditMode) {
            // Keep current booking time as default in edit mode
            this.setToCurrentBookingDate();
        } else {
            // Reset in create mode
            this.startTimeInput.value = '';
            this.selectedTimeDisplay.classList.add('d-none');
            this.setToday();
        }
    }

    validateForm(e) {
        if (!this.startTimeInput.value) {
            e.preventDefault();
            alert('Proszę wybrać termin wizyty.');
            return false;
        }
        console.log('Submit with startTime:', this.startTimeInput.value);
    }

    // Move week forward or backward
    moveWeek(direction) {
        const newDate = new Date(this.currentStartDate);
        newDate.setDate(newDate.getDate() + (direction * 7));
        this.currentStartDate = newDate;
        this.fetchAvailableSlots();
    }

    // Set date to today
    setToday() {
        this.currentStartDate = new Date();
        this.fetchAvailableSlots();
    }

    // Set date to current booking date (only in edit mode)
    setToCurrentBookingDate() {
        if (!this.currentBookingTime) return;

        this.currentStartDate = new Date(this.currentBookingTime);
        this.currentStartDate.setHours(0, 0, 0, 0); // Start of the day
        this.currentStartDate.setDate(this.currentStartDate.getDate() - 3); // 3 days before current date
        this.fetchAvailableSlots();
    }

    // Show loading state
    showLoading() {
        this.loadingIndicator.classList.add('active');
    }

    // Hide loading state
    hideLoading() {
        this.loadingIndicator.classList.remove('active');
    }

    // Select a time slot
    selectTimeSlot(element, datetime) {
        // Remove previous selection
        const allTimeSlots = document.querySelectorAll('.time-slot');
        allTimeSlots.forEach(slot => slot.classList.remove('selected'));

        // Add selection to this element
        element.classList.add('selected');

        // Set value to hidden input
        const dateObj = new Date(datetime);
        const formattedDateTime = this.formatDateForSymfony(dateObj);

        // Set the value directly to ensure it's properly populated
        this.startTimeInput.value = formattedDateTime;
        console.log("Setting startTime to:", formattedDateTime);

        // Update display
        this.selectedTimeText.textContent = formattedDateTime;
        this.selectedTimeDisplay.classList.remove('d-none');

        this.selectedTimeSlot = datetime;
    }

    // Render available dates and their time slots
    renderAvailableDates(groupedSlots) {
        this.availableDatesContainer.innerHTML = '';

        const dateRange = this.generateDateRange();
        let hasAvailableSlots = false;

        dateRange.forEach(date => {
            const dateStr = this.formatDate(date);
            const displayDate = this.formatDisplayDate(date);
            const today = new Date();
            const isToday = date.toDateString() === today.toDateString();
            const isCurrentDate = this.currentBookingTime &&
                date.toDateString() === this.currentBookingTime.toDateString();

            const slots = groupedSlots[dateStr] || [];

            // Skip dates with no slots
            if (slots.length === 0) {
                return;
            }

            hasAvailableSlots = true;

            const dateCard = document.createElement('div');
            dateCard.className = 'date-card';
            if (isCurrentDate) {
                dateCard.classList.add('current-date');
            }
            dateCard.setAttribute('data-date', dateStr);

            let headerText = displayDate;
            if (isToday) {
                headerText += ' (Dziś)';
            } else if (isCurrentDate) {
                headerText += ' (Aktualny)';
            }

            dateCard.innerHTML = `<h6>${headerText}</h6>`;

            const timeSlotsDiv = document.createElement('div');
            timeSlotsDiv.className = 'time-slots';

            // Sort slots chronologically by their datetime
            slots.sort((a, b) => {
                return new Date(a.datetime) - new Date(b.datetime);
            });

            slots.forEach(slot => {
                const timeSlot = document.createElement('span');
                timeSlot.className = 'time-slot';

                // Mark current slot in edit mode
                if (slot.current || (this.currentBookingTime &&
                    new Date(slot.datetime).getTime() === this.currentBookingTime.getTime())) {
                    timeSlot.classList.add('current');
                    timeSlot.title = 'Aktualny termin';
                }

                timeSlot.textContent = slot.time;
                timeSlot.setAttribute('data-datetime', slot.datetime);

                timeSlot.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.selectTimeSlot(timeSlot, slot.datetime);
                });

                timeSlotsDiv.appendChild(timeSlot);
            });

            dateCard.appendChild(timeSlotsDiv);
            this.availableDatesContainer.appendChild(dateCard);
        });

        if (!hasAvailableSlots) {
            this.availableDatesContainer.innerHTML = `
                <div class="empty-message">
                    Brak dostępnych terminów w wybranym okresie.
                    <button class="btn btn-sm btn-outline-primary mt-2" id="checkMoreDates">
                        Sprawdź kolejne terminy
                    </button>
                </div>
            `;

            document.getElementById('checkMoreDates').addEventListener('click', () => {
                this.moveWeek(1);
            });
        }
    }

    // Fetch available slots for the current date range
    fetchAvailableSlots() {
        if (!this.employeeSelect.value) {
            this.availableDatesContainer.innerHTML = `
                <div class="empty-message">
                    Wybierz pracownika, aby zobaczyć dostępne terminy.
                </div>
            `;
            return;
        }

        const employeeId = this.employeeSelect.value;
        const endDate = new Date(this.currentStartDate);
        endDate.setDate(endDate.getDate() + 14); // 2 weeks forward

        const startDateStr = this.formatDate(this.currentStartDate);
        const endDateStr = this.formatDate(endDate);

        // Check if we have this data in cache
        const cacheKey = `${employeeId}_${startDateStr}_${endDateStr}`;
        if (this.cachedData[cacheKey]) {
            this.renderAvailableDates(this.cachedData[cacheKey]);
            return;
        }

        this.showLoading();

        fetch(`?employee=${employeeId}&startDate=${startDateStr}&endDate=${endDateStr}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Group slots by date
                const groupedSlots = this.groupSlotsByDate(data.slots || []);

                // Cache the results
                this.cachedData[cacheKey] = groupedSlots;

                // Render the dates
                this.renderAvailableDates(groupedSlots);
                this.hideLoading();
            })
            .catch(error => {
                console.error('Error fetching available slots:', error);
                this.hideLoading();
                this.availableDatesContainer.innerHTML = `
                    <div class="alert alert-danger">
                        Wystąpił błąd podczas pobierania dostępnych terminów.
                        <button class="btn btn-sm btn-outline-primary ms-2" id="retryBtn">
                            Spróbuj ponownie
                        </button>
                    </div>
                `;

                document.getElementById('retryBtn').addEventListener('click',
                    this.fetchAvailableSlots.bind(this));
            });
    }
}