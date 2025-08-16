document.addEventListener('DOMContentLoaded', function() {
    const closedCheckbox = document.getElementById(formConfig.closedId);
    const timeContainer = document.getElementById('timeContainer');

    function updateTimeFieldsState() {
        if (closedCheckbox.checked) {
            timeContainer.classList.add('disabled');
        } else {
            timeContainer.classList.remove('disabled');
        }
    }
    
    updateTimeFieldsState();

    closedCheckbox.addEventListener('change', updateTimeFieldsState);
});
