/* ==========================================================================
   Feedback Form Logic
   ========================================================================== */

// Global Functions (Accessible by HTML onclick="")
function submitFeedback() {
    handleValidation('rating-form-group', 'rating-error');
}

function submitFeedbackMobile() {
    handleValidation('rating-form-group-mobile', 'rating-error-mobile');
}

function handleValidation(groupId, errorId) {
    const group = document.getElementById(groupId);
    const errorMsg = document.getElementById(errorId);

    // Safety check: if elements don't exist, stop to avoid errors
    if (!group || !errorMsg) return;

    const firstRadio = group.querySelector('input[type="radio"]');
    const isSelected = group.querySelector('input[type="radio"]:checked');

    if (!isSelected) {
        // 3. Validation logic: If no rating is selected
        group.classList.add('govuk-form-group--error'); // Add red border to container

        errorMsg.classList.remove('govuk-visually-hidden'); // Show error text
        errorMsg.style.display = 'block'; // Ensure it's visible if hidden by other CSS

        if (firstRadio) firstRadio.focus(); // Focus for accessibility
        return false; // Stop submission
    }

    // If valid, proceed with AJAX
    processAjaxSubmission();
}

async function processAjaxSubmission() {
    // 1. Get the form with the NEW ID
    const form = document.getElementById('service-feedback-form');
    const submitBtn = document.getElementById('submit-feedback-btn');

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerText = "Sending...";
    }

    try {
        // 2. Grab all data automatically
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // 3. CLEANUP: Remove 'feedback-comments' if it is empty
        // This ensures the key is only sent if the user actually typed something
        if (!data['feedback-comments'] || data['feedback-comments'].trim() === "") {
            delete data['feedback-comments'];
        }

        // 4. Send the payload
        // The body will now look like { rating: "5" } OR { rating: "5", "feedback-comments": "Great service" }
        const response = await fetch('/csat/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            showThankYouMessage();
        } else {
            throw new Error('Network response was not ok');
        }

    } catch (error) {
        console.error('Error submitting feedback:', error);
        const globalError = document.getElementById('feedback-global-error');

        if (globalError) {
            globalError.classList.remove('govuk-visually-hidden');
            globalError.style.display = 'block';
            globalError.focus(); // Moves screen reader focus to the error
        }

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerText = "Submit feedback";
        }
    }
}

function showThankYouMessage() {
    const formContainer = document.querySelector('.feedback-form');
    const thankYouMsg = document.getElementById('feedback-thank-you');
    const feedbackTrigger = document.getElementById('feedback-trigger-section');

    if (formContainer) formContainer.style.display = 'none';
    if (thankYouMsg) thankYouMsg.style.display = 'block';
    if (feedbackTrigger) feedbackTrigger.style.display = 'none';
}

function cancelFeedback() {
    const form = document.getElementById('service-feedback-form');

    // Reset the form values
    if (form) form.reset();

    // Clear all error states
    clearErrors();

    // Close the toggle checkbox (Mobile/Desktop)
    const toggle = document.getElementById('feedback-toggle');
    const toggleMobile = document.getElementById('feedback-toggle-mobile');

    if (toggle) toggle.checked = false;
    if (toggleMobile) toggleMobile.checked = false;
}

function clearErrors() {
    // 4. Error clearing: Remove classes and hide messages
    const groups = ['rating-form-group', 'rating-form-group-mobile'];
    const errors = ['rating-error', 'rating-error-mobile'];

    // Remove the red border class
    groups.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.remove('govuk-form-group--error');
    });

    // Hide the error text
    errors.forEach(id => {
        const err = document.getElementById(id);
        if (err) {
            err.classList.add('govuk-visually-hidden');
            err.style.display = 'none'; // Force hide
        }
    });
}

document.addEventListener("DOMContentLoaded", function (event) {
    //Error clearing: Selecting a rating instantly clears errors
    document.addEventListener('change', function(e) {
        if (e.target.name === 'rating') {
            clearErrors();
        }
    });
});