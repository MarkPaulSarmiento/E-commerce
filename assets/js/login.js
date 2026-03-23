// Login Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Password Toggle Function
    window.togglePassword = function() {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.querySelector('.toggle-password i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleBtn.classList.remove('fa-eye');
            toggleBtn.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleBtn.classList.remove('fa-eye-slash');
            toggleBtn.classList.add('fa-eye');
        }
    };
    
    // Form Validation
    const loginForm = document.getElementById('login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            let isValid = true;
            let errorMessage = '';
            
            // Remove any existing error messages
            removeExistingErrors();
            
            // Validate username
            if (username === '') {
                showFieldError('username', 'Please enter your username or email');
                isValid = false;
            }
            
            // Validate password
            if (password === '') {
                showFieldError('password', 'Please enter your password');
                isValid = false;
            } else if (password.length < 6) {
                showFieldError('password', 'Password must be at least 6 characters');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            } else {
                // Show loading state
                const submitBtn = document.querySelector('.login-btn');
                submitBtn.classList.add('loading');
                submitBtn.textContent = 'Logging in...';
            }
        });
    }
    
    // Function to show field-specific error
    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const formGroup = field.closest('.form-group');
        
        // Remove existing error
        const existingError = formGroup.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = '#dc3545';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '5px';
        errorDiv.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + message;
        
        formGroup.appendChild(errorDiv);
        
        // Highlight field
        field.style.borderColor = '#dc3545';
        
        // Remove highlight on input
        field.addEventListener('input', function() {
            field.style.borderColor = '#e5e5e5';
            const error = formGroup.querySelector('.field-error');
            if (error) {
                error.remove();
            }
        });
    }
    
    // Remove all existing field errors
    function removeExistingErrors() {
        const errors = document.querySelectorAll('.field-error');
        errors.forEach(error => error.remove());
        
        const inputs = document.querySelectorAll('.form-group input');
        inputs.forEach(input => {
            input.style.borderColor = '#e5e5e5';
        });
    }
    
    // Remember me functionality
    const rememberCheckbox = document.getElementById('remember');
    const usernameInput = document.getElementById('username');
    
    // Load saved username if exists
    if (localStorage.getItem('rememberedUsername')) {
        usernameInput.value = localStorage.getItem('rememberedUsername');
        if (rememberCheckbox) {
            rememberCheckbox.checked = true;
        }
    }
    
    // Save username when form is submitted
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            if (rememberCheckbox && rememberCheckbox.checked) {
                localStorage.setItem('rememberedUsername', usernameInput.value);
            } else {
                localStorage.removeItem('rememberedUsername');
            }
        });
    }
    
    // Add animation to form elements
    const formElements = document.querySelectorAll('.form-group');
    formElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        setTimeout(() => {
            element.style.transition = 'all 0.5s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Prevent multiple form submissions
    let isSubmitting = false;
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            isSubmitting = true;
            
            // Reset after 3 seconds (in case of error)
            setTimeout(() => {
                isSubmitting = false;
                const submitBtn = document.querySelector('.login-btn');
                if (submitBtn) {
                    submitBtn.classList.remove('loading');
                    submitBtn.textContent = 'Login Now';
                }
            }, 3000);
        });
    }
    
    // Add floating label effect (optional)
    const inputs = document.querySelectorAll('.form-group input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Check if input has value on load
        if (input.value !== '') {
            input.parentElement.classList.add('focused');
        }
    });
});

// Add CSRF protection (optional)
function generateCSRFToken() {
    const token = Math.random().toString(36).substring(2);
    document.cookie = `csrf_token=${token}; path=/`;
    return token;
}

// Handle social login buttons (placeholder)
document.addEventListener('DOMContentLoaded', function() {
    const socialBtns = document.querySelectorAll('.social-btn');
    socialBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Social login feature coming soon!');
        });
    });
    
    const forgotPassword = document.querySelector('.forgot-password');
    if (forgotPassword) {
        forgotPassword.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Password reset feature coming soon! Please contact support.');
        });
    }
});