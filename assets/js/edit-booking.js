import BookingHandler from './booking-handler';
import '../styles/common/booking.css';

document.addEventListener('DOMContentLoaded', function() {
    // Get current booking time from data attribute
    const currentBookingTimeElement = document.querySelector('[data-booking-time]');
    const currentBookingTime = currentBookingTimeElement ?
        currentBookingTimeElement.getAttribute('data-booking-time') : null;

    // Initialize booking handler for edit mode
    const bookingHandler = new BookingHandler({
        currentBookingTime: currentBookingTime,
        isEditMode: true
    });
});