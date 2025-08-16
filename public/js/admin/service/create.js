document.addEventListener('DOMContentLoaded', function() {
    // Obsługa kroków formularza
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    const steps = document.querySelectorAll('.step');
    const formSteps = document.querySelectorAll('.form-step');

    function updateSteps(currentStep) {
        formSteps.forEach(step => step.classList.remove('active'));
        document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.add('active');

        steps.forEach(step => {
            step.classList.remove('active');
            if (parseInt(step.dataset.step) <= currentStep) {
                step.classList.add('active');
            }
        });
    }

    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            updateSteps(parseInt(this.dataset.next));
        });
    });

    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            updateSteps(parseInt(this.dataset.prev));
        });
    });

    
    const imageUrlInput = document.getElementById(formConfig.imageUrlId);
    const imagePreview = document.getElementById('image-preview');
    const imagePreviewContainer = document.getElementById(formConfig.previewContainerId);

    imageUrlInput.addEventListener('input', function() {
        const imageUrl = this.value.trim();
        if (imageUrl) {
            imagePreview.src = imageUrl;
            imagePreviewContainer.classList.remove('d-none');
        } else {
            imagePreviewContainer.classList.add('d-none');
        }
    });

    
    const invalidFields = document.querySelectorAll('.form-error');
    invalidFields.forEach(function(field) {
        const input = field.closest('.mb-3').querySelector('.form-control, .form-select');
        if (input) {
            input.classList.add('is-invalid');
        }
    });

    
    validationConfig.steps.forEach(step => {
        const stepErrors = document.querySelector(`.form-step[data-step="${step}"]`)
            .querySelectorAll('.form-error');
        if (stepErrors.length > 0) {
            updateSteps(step);
            return false;
        }
    });
});
