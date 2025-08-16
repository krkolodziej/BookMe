document.addEventListener('DOMContentLoaded', function() {
    const closedCheckbox = document.getElementById(formConfig.closedId);
    const timeContainer = document.getElementById('timeContainer');
    const openingTimeInput = document.getElementById(formConfig.openingTimeId);
    const closingTimeInput = document.getElementById(formConfig.closingTimeId);

    function updateTimeFieldsState() {
        if (closedCheckbox.checked) {
            timeContainer.classList.add('disabled');
            openingTimeInput.disabled = true;
            closingTimeInput.disabled = true;
        } else {
            timeContainer.classList.remove('disabled');
            openingTimeInput.disabled = false;
            closingTimeInput.disabled = false;
        }
    }

    updateTimeFieldsState();

    closedCheckbox.addEventListener('change', updateTimeFieldsState);
});
