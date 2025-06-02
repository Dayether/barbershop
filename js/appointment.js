document.addEventListener('DOMContentLoaded', function() {
    // Check if we were redirected with a hash
    if (window.location.hash === '#appointments') {
        // Find the appointments tab in profile.php and activate it
        const appointmentsTab = document.querySelector('.tab[data-tab="appointments"]');
        if (appointmentsTab) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Add active class to appointments tab and content
            appointmentsTab.classList.add('active');
            document.getElementById('appointments-content').classList.add('active');
        }
    }
    
    // Initialize date picker with better styling
    flatpickr("#appointment-date", {
        minDate: "today",
        maxDate: new Date().fp_incr(30), // 30 days from now
        disable: [
            function(date) {
                // Disable Sundays
                return date.getDay() === 0;
            }
        ],
        dateFormat: "Y-m-d",
        onChange: function(selectedDates, dateStr, instance) {
            // When date is selected, generate time slots
            generateTimeSlots(dateStr);
            
            // Update loading message
            const timeSlotsContainer = document.getElementById('time-slots');
            if (timeSlotsContainer) {
                timeSlotsContainer.innerHTML = '<div class="time-slots-loading"><i class="fas fa-spinner fa-pulse"></i><span>Loading available time slots...</span></div>';
                
                // Simulate loading delay for better UX
                setTimeout(() => {
                    generateTimeSlots(dateStr);
                }, 500);
            }
        }
    });

    // Generate time slots based on selected date
    function generateTimeSlots(selectedDate) {
        const timeSlotsContainer = document.getElementById('time-slots');
        if (!timeSlotsContainer) return;
        
        timeSlotsContainer.innerHTML = '';
        
        // Get the day of the week (0 = Sunday, 1 = Monday, etc.)
        const dayOfWeek = new Date(selectedDate).getDay();
        
        // Set opening and closing times based on the day of the week
        let openingHour = 9; // 9 AM
        let closingHour = 18; // 6 PM
        
        // Saturday has different hours
        if (dayOfWeek === 6) { // Saturday
            openingHour = 10; // 10 AM
            closingHour = 16; // 4 PM
        }
        
        // Generate time slots (30 min intervals)
        for (let hour = openingHour; hour < closingHour; hour++) {
            for (let minute of [0, 30]) {
                // Some random slots are unavailable for demo
                const isUnavailable = Math.random() < 0.3;
                
                const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                const timeSlot = document.createElement('div');
                timeSlot.classList.add('time-slot');
                if (isUnavailable) {
                    timeSlot.classList.add('unavailable');
                }
                
                const input = document.createElement('input');
                input.type = 'radio';
                input.name = 'appointment-time';
                input.id = `time-${timeString}`;
                input.value = timeString;
                if (isUnavailable) {
                    input.disabled = true;
                }
                
                const label = document.createElement('label');
                label.htmlFor = `time-${timeString}`;
                label.textContent = convertTo12HourFormat(timeString);
                
                timeSlot.appendChild(input);
                timeSlot.appendChild(label);
                timeSlotsContainer.appendChild(timeSlot);
                
                // Add event listener to each time slot
                input.addEventListener('change', function() {
                    document.querySelectorAll('.time-slot').forEach(slot => {
                        slot.classList.remove('active');
                    });
                    if (this.checked) {
                        this.closest('.time-slot').classList.add('active');
                    }
                });
            }
        }
    }
    
    // Convert 24-hour time to 12-hour format
    function convertTo12HourFormat(time24) {
        if (!time24) return '';
        const [hours, minutes] = time24.split(':');
        let period = 'AM';
        let hours12 = parseInt(hours);
        
        if (hours12 >= 12) {
            period = 'PM';
            if (hours12 > 12) {
                hours12 -= 12;
            }
        }
        
        if (hours12 === 0) {
            hours12 = 12;
        }
        
        return `${hours12}:${minutes} ${period}`;
    }
    
    // Handle multi-step form navigation
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    const steps = document.querySelectorAll('.form-step');
    const stepIndicators = document.querySelectorAll('.appointment-steps .step');
    
    // Next button event listeners
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get current active step
            const currentStep = document.querySelector('.form-step.active');
            const currentIndex = Array.from(steps).indexOf(currentStep);
            
            // Validate current step
            if (validateStep(currentIndex + 1)) {
                // Hide current step with animation
                currentStep.classList.add('fade-out');
                
                setTimeout(() => {
                    // Hide current step
                    currentStep.classList.remove('active');
                    currentStep.classList.remove('fade-out');
                    
                    // Show next step with animation
                    steps[currentIndex + 1].classList.add('active');
                    
                    // Update step indicators
                    updateStepIndicators(currentIndex + 1);
                    
                    // Scroll to top of form
                    document.querySelector('.appointment-form-container').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // If this is the review step, populate summary
                    if (currentIndex + 1 === 3) { // Index 3 is the review step (step 4)
                        populateSummary();
                    }
                }, 300);
            }
        });
    });
    
    // Previous button event listeners
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get current active step
            const currentStep = document.querySelector('.form-step.active');
            const currentIndex = Array.from(steps).indexOf(currentStep);
            
            // Hide current step with animation
            currentStep.classList.add('fade-out');
            
            setTimeout(() => {
                // Hide current step
                currentStep.classList.remove('active');
                currentStep.classList.remove('fade-out');
                
                // Show previous step with animation
                steps[currentIndex - 1].classList.add('active');
                
                // Update step indicators
                updateStepIndicators(currentIndex - 1);
                
                // Scroll to top of form
                document.querySelector('.appointment-form-container').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 300);
        });
    });
    
    // Update step indicators
    function updateStepIndicators(activeIndex) {
        stepIndicators.forEach((indicator, index) => {
            if (index <= activeIndex) {
                indicator.classList.add('active');
            } else {
                indicator.classList.remove('active');
            }
        });
        
        // Update progress bar
        const progressPercentage = (activeIndex + 1) * 25;
        document.querySelector('.progress-bar').style.width = `${progressPercentage}%`;
    }
    
    // Populate booking summary
    function populateSummary() {
        // Get selected service
        const selectedService = document.querySelector('input[name="service"]:checked');
        if (selectedService) {
            document.getElementById('summary-service').textContent = selectedService.value;
            document.getElementById('summary-price').textContent = `$${selectedService.dataset.price}`;
        }
        
        // Get date and time
        const selectedDate = document.getElementById('appointment-date');
        const selectedTime = document.querySelector('input[name="appointment-time"]:checked');
        
        if (selectedDate && selectedDate.value) {
            // Format date to be more readable
            const formattedDate = new Date(selectedDate.value).toLocaleDateString('en-US', {
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric'
            });
            document.getElementById('summary-date').textContent = formattedDate;
        }
        
        if (selectedTime) {
            document.getElementById('summary-time').textContent = convertTo12HourFormat(selectedTime.value);
        }
        
        // Get barber
        const selectedBarber = document.getElementById('barber');
        if (selectedBarber) {
            document.getElementById('summary-barber').textContent = selectedBarber.value || 'Any Available Barber';
        }
        
        // Get personal details
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const notesInput = document.getElementById('notes');
        
        if (nameInput) {
            document.getElementById('summary-name').textContent = nameInput.value;
        }
        
        if (emailInput) {
            document.getElementById('summary-email').textContent = emailInput.value;
        }
        
        if (phoneInput) {
            document.getElementById('summary-phone').textContent = phoneInput.value;
        }
        
        // Handle notes (optional)
        if (notesInput && notesInput.value.trim()) {
            document.getElementById('summary-notes').textContent = notesInput.value;
            document.getElementById('summary-notes-container').style.display = 'flex';
        } else {
            document.getElementById('summary-notes-container').style.display = 'none';
        }
    }
    
    // Validate each step
    function validateStep(stepNumber) {
        switch(stepNumber) {
            case 1: // Service selection
                return true; // Always valid as one is pre-selected
            
            case 2: // Date and time selection
                const dateInput = document.getElementById('appointment-date');
                const timeSelected = document.querySelector('input[name="appointment-time"]:checked');
                
                if (!dateInput || !dateInput.value) {
                    showValidationError('Please select a date for your appointment');
                    return false;
                }
                
                if (!timeSelected) {
                    showValidationError('Please select a time slot for your appointment');
                    return false;
                }
                
                hideValidationError();
                return true;
            
            case 3: // Personal details
                const nameInput = document.getElementById('name');
                const emailInput = document.getElementById('email');
                const phoneInput = document.getElementById('phone');
                
                if (!nameInput || !nameInput.value.trim()) {
                    showValidationError('Please enter your full name');
                    nameInput.focus();
                    return false;
                }
                
                if (!emailInput || !emailInput.value.trim()) {
                    showValidationError('Please enter your email address');
                    emailInput.focus();
                    return false;
                }
                
                if (!validateEmail(emailInput.value)) {
                    showValidationError('Please enter a valid email address');
                    emailInput.focus();
                    return false;
                }
                
                if (!phoneInput || !phoneInput.value.trim()) {
                    showValidationError('Please enter your phone number');
                    phoneInput.focus();
                    return false;
                }
                
                hideValidationError();
                return true;
            
            default:
                return true;
        }
    }
    
    // Email validation helper
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Show validation error
    function showValidationError(message) {
        let errorElement = document.getElementById('validation-error');
        
        // Create error element if it doesn't exist
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.id = 'validation-error';
            errorElement.className = 'validation-error';
            errorElement.style.color = '#dc3545';
            errorElement.style.padding = '10px 15px';
            errorElement.style.marginBottom = '20px';
            errorElement.style.backgroundColor = 'rgba(220, 53, 69, 0.1)';
            errorElement.style.borderRadius = '5px';
            errorElement.style.textAlign = 'center';
            errorElement.style.display = 'none';
            
            // Insert at top of current active step
            const activeStep = document.querySelector('.form-step.active');
            activeStep.insertBefore(errorElement, activeStep.firstChild);
        }
        
        // Show error message
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    
    // Hide validation error
    function hideValidationError() {
        const errorElement = document.getElementById('validation-error');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }
    
    // Handle form submission
    const appointmentForm = document.getElementById('appointment-form');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Show processing state on button
            const confirmBtn = document.getElementById('confirm-booking');
            if (confirmBtn) {
                const originalText = confirmBtn.innerHTML;
                confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                confirmBtn.disabled = true;
            }
            
            // Get form data
            const formData = new FormData(this);
            
            // Submit via AJAX
            fetch('process_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide current step
                    const currentStep = document.querySelector('.form-step.active');
                    if (currentStep) currentStep.classList.remove('active');
                    
                    // Show confirmation step
                    const confirmationStep = document.getElementById('step-confirmation');
                    if (confirmationStep) confirmationStep.classList.add('active');
                    
                    // Update step indicators
                    stepIndicators.forEach(indicator => {
                        indicator.classList.remove('active');
                    });
                    
                    // Update confirmation details
                    if (document.getElementById('booking-reference')) {
                        document.getElementById('booking-reference').textContent = data.booking_reference;
                    }
                    
                    if (document.getElementById('confirmation-service') && document.getElementById('summary-service')) {
                        document.getElementById('confirmation-service').textContent = 
                            document.getElementById('summary-service').textContent;
                    }
                    
                    if (document.getElementById('confirmation-datetime') && 
                        document.getElementById('summary-date') && 
                        document.getElementById('summary-time')) {
                        document.getElementById('confirmation-datetime').textContent = 
                            document.getElementById('summary-date').textContent + ' at ' + 
                            document.getElementById('summary-time').textContent;
                    }
                    
                    // Scroll to top of form
                    document.querySelector('.appointment-form-container').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                } else {
                    showValidationError('Error: ' + (data.message || 'Failed to book appointment'));
                    if (confirmBtn) {
                        confirmBtn.innerHTML = originalText;
                        confirmBtn.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showValidationError('An error occurred. Please try again.');
                if (confirmBtn) {
                    confirmBtn.innerHTML = originalText;
                    confirmBtn.disabled = false;
                }
            });
        });
    }
    
    // Pre-fill form fields if user is logged in
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('service')) {
        const serviceId = urlParams.get('service');
        const serviceRadio = document.getElementById(`service-${serviceId}`);
        if (serviceRadio) {
            serviceRadio.checked = true;
        }
    }
    
    // Visual animations for service cards
    const serviceOptions = document.querySelectorAll('.service-option');
    serviceOptions.forEach(option => {
        option.addEventListener('mouseover', function() {
            this.querySelector('.service-icon').style.transform = 'scale(1.1) rotate(10deg)';
        });
        
        option.addEventListener('mouseout', function() {
            this.querySelector('.service-icon').style.transform = 'scale(1) rotate(0)';
        });
    });
});
