// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('navLinks');
    
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }
    
    // Form handling with enhanced animations
    const formInputs = document.querySelectorAll('.form-control');
    
    formInputs.forEach(input => {
        // Add active class to form items that have content
        if (input.value !== '') {
            input.classList.add('has-value');
        }
        
        // Events for animation effects
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
            this.classList.add('active');
        });
        
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.parentElement.classList.remove('focused');
                this.classList.remove('has-value');
            } else {
                this.classList.add('has-value');
            }
            this.classList.remove('active');
        });
    });
    
    // Validate form before submission
    const contactForm = document.querySelector('.styled-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            let validationPassed = true;
            const requiredInputs = this.querySelectorAll('[required]');
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    validationPassed = false;
                    input.classList.add('error-input');
                    
                    // Show error message if not already present
                    let errorElement = input.parentElement.querySelector('.error');
                    if (!errorElement) {
                        errorElement = document.createElement('span');
                        errorElement.className = 'error';
                        errorElement.textContent = 'This field is required';
                        input.parentElement.appendChild(errorElement);
                    }
                }
            });
            
            if (!validationPassed) {
                e.preventDefault();
            }
        });
    }
    
    // Scroll animations for info items
    const infoItems = document.querySelectorAll('.info-item');
    let delay = 100;
    
    infoItems.forEach(item => {
        item.setAttribute('data-aos', 'fade-up');
        item.setAttribute('data-aos-delay', delay);
        delay += 100;
    });
});
