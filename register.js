// Registration Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const selectionCards = document.querySelectorAll('.selection-card');
    const backButton = document.getElementById('backButton');
    const registrationForm = document.getElementById('registrationForm');
    const userTypeInput = document.getElementById('userType');
    const selectedTypeSpan = document.getElementById('selectedType');
    const userTypeLabel = document.getElementById('userTypeLabel');
    
    // Handle card selection
    selectionCards.forEach(card => {
        card.addEventListener('click', function() {
            const userType = this.getAttribute('data-type');
            const userTypeDisplay = userType === 'customer' ? 'Customer / Buyer' : 'Designer / Producer';
            
            // Store selected type
            userTypeInput.value = userType;
            selectedTypeSpan.textContent = userTypeDisplay;
            
            // Switch to step 2
            step1.classList.remove('active');
            step2.classList.add('active');
        });
    });
    
    // Handle back button
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            e.preventDefault();
            step2.classList.remove('active');
            step1.classList.add('active');
        });
    }
    
    // Form validation
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const terms = document.getElementById('terms').checked;
            
            // Check password match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
                return false;
            }
            
            // Check password strength (optional - minimum 8 characters)
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            // Check terms acceptance
            if (!terms) {
                e.preventDefault();
                alert('Please accept the Terms and Conditions to continue.');
                return false;
            }
            
            // If all validations pass, form will submit normally
        });
    }
    
    // Add visual feedback for card selection
    selectionCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });
        
        card.addEventListener('mouseleave', function() {
            if (!this.classList.contains('selected')) {
                this.style.transform = 'translateY(0)';
            }
        });
    });
});

