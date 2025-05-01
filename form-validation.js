/**
 * Finance Portal - Form Validation Functions
 * This file contains reusable validation functions for forms
 */

// Password validation functionality
function initPasswordValidation(formId, options = {})
{
    // Default options
    const defaults = {
        passwordId: 'password',
        confirmPasswordId: 'confirm_password',
        requirementsId: 'password-requirements',
        lengthCheckId: 'length-check',
        uppercaseCheckId: 'uppercase-check',
        numberCheckId: 'number-check',
        symbolCheckId: 'symbol-check',
        submitButtonId: 'submit-button',
        showRequirements: true,
        minLength: 8,
        maxLength: 20
    };
    
    // Merge defaults with provided options
    const config = {...defaults, ...options};
    
    // Get DOM elements
    const form = document.getElementById(formId);
    const passwordInput = document.getElementById(config.passwordId);
    const confirmPasswordInput = document.getElementById(config.confirmPasswordId);
    const passwordRequirements = document.getElementById(config.requirementsId);
    const lengthCheck = document.getElementById(config.lengthCheckId);
    const uppercaseCheck = document.getElementById(config.uppercaseCheckId);
    const numberCheck = document.getElementById(config.numberCheckId);
    const symbolCheck = document.getElementById(config.symbolCheckId);
    const submitButton = document.getElementById(config.submitButtonId);
    
    // If any elements are missing, log error and exit gracefully
    if (!form || !passwordInput || !confirmPasswordInput)
    {
        console.error('Required form elements not found. Password validation not initialized.');
        return;
    }
    
    // Show password requirements when password field is focused
    if (passwordRequirements)
    {
        passwordInput.addEventListener('focus', function ()
        {
            passwordRequirements.style.display = 'block';
        });
        
        // Optionally hide requirements when focus moves away
        if (!config.showRequirements)
        {
            passwordInput.addEventListener('blur', function ()
            {
                passwordRequirements.style.display = 'none';
            });
        }
    }
    
    // Function to validate password
    function validatePassword()
    {
        const password = passwordInput.value;
        
        // Track requirements
        let lengthValid = false;
        let uppercaseValid = false;
        let numberValid = false;
        let symbolValid = false;
        
        // Check length
        lengthValid = password.length >= config.minLength && password.length <= config.maxLength;
        if (lengthCheck)
        {
            lengthCheck.className = lengthValid ? 'requirement-met' : 'requirement-unmet';
        }
        
        // Check uppercase
        uppercaseValid = /[A-Z]/.test(password);
        if (uppercaseCheck)
        {
            uppercaseCheck.className = uppercaseValid ? 'requirement-met' : 'requirement-unmet';
        }
        
        // Check number
        numberValid = /[0-9]/.test(password);
        if (numberCheck)
        {
            numberCheck.className = numberValid ? 'requirement-met' : 'requirement-unmet';
        }
        
        // Check symbol
        symbolValid = /[^A-Za-z0-9]/.test(password);
        if (symbolCheck)
        {
            symbolCheck.className = symbolValid ? 'requirement-met' : 'requirement-unmet';
        }
        
        return lengthValid && uppercaseValid && numberValid && symbolValid;
    }
    
    // Function to check if passwords match
    function checkPasswordsMatch()
    {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (password !== confirmPassword) {
            confirmPasswordInput.setCustomValidity("Passwords don't match");
            return false;
        } else
        {
            confirmPasswordInput.setCustomValidity('');
            return true;
        }
    }
    
    // Calculate and return password strength
    function calculatePasswordStrength()
    {
        const password = passwordInput.value;
        let strength = 0;
        
        // Base strength on meeting requirements
        if (password.length >= config.minLength) strength += 1;
        if (password.length >= config.minLength + 4) strength += 1; // Additional points for longer password
        if (/[A-Z]/.test(password)) strength += 1;
        if (/[0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        if (/[^A-Za-z0-9\s]/.test(password)) strength += 1; // Additional complexity for non-common symbols
        
        // Calculate entropy (roughly)
        const hasLower = /[a-z]/.test(password);
        const hasUpper = /[A-Z]/.test(password);
        const hasDigit = /[0-9]/.test(password);
        const hasSymbol = /[^A-Za-z0-9]/.test(password);
        
        // Set pool size based on character types used
        let poolSize = 0;
        if (hasLower) poolSize += 26;
        if (hasUpper) poolSize += 26;
        if (hasDigit) poolSize += 10;
        if (hasSymbol) poolSize += 33; // Approximate number of common symbols
        
        // Basic entropy calculation
        if (poolSize > 0 && password.length > 0)
        {
            const entropy = Math.log2(Math.pow(poolSize, password.length));
            
            // Add strength based on entropy
            if (entropy > 60) strength += 1;
            if (entropy > 80) strength += 1;
        }
        
        // Determine strength text and color
        let strengthText = '';
        let strengthColor = '';
        
        if (strength < 3)
        {
            strengthText = 'Weak';
            strengthColor = '#dd0000';
        }
        else if (strength < 5)
        {
            strengthText = 'Medium';
            strengthColor = '#ff9900';
        }
        else if (strength < 7)
        {
            strengthText = 'Strong';
            strengthColor = '#00aa00';
        } else
        {
            strengthText = 'Very Strong';
            strengthColor = '#007700';
        }
        
        return {
            score: strength,
            text: strengthText,
            color: strengthColor
        };
    }
    
    // Add event listeners
    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', checkPasswordsMatch);
    
    // Form submission validation
    form.addEventListener('submit', function (event)
    {
        // Validate password
        if (!validatePassword())
        {
            event.preventDefault();
            alert('Password does not meet all requirements');
            return false;
        }
        
        // Check if passwords match
        if (!checkPasswordsMatch())
        {
            event.preventDefault();
            alert('Passwords do not match');
            return false;
        }
        
        // All validation passed
        return true;
    });
    
    // Return validation functions for external use
    return {
        validatePassword,
        checkPasswordsMatch,
        calculatePasswordStrength
    };
}

// Email validation function
function validateEmail(email)
{
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

// Name validation function (letters only, no spaces)
function validateName(name)
{
    const re = /^[A-Za-z]+$/;
    return re.test(name);
}

// Username validation function (letters, numbers, underscores, 3-20 chars)
function validateUsername(username)
{
    const re = /^[A-Za-z0-9_]{3,20}$/;
    return re.test(username);
}

// Initialize form validation - can be called on any form
function initFormValidation(formId)
{
    const form = document.getElementById(formId);
    
    if (!form)
    {
        console.error('Form not found: ' + formId);
        return;
    }
    
    // Add custom validation to specific input types
    const inputs = form.querySelectorAll('input');
    
    inputs.forEach(input =>
    {
        // Add validation based on input type or name
        if (input.name === 'email' || input.type === 'email')
        {
            input.addEventListener('input', function ()
            {
                if (this.value && !validateEmail(this.value))
                {
                    this.setCustomValidity('Please enter a valid email address');
                }
                else
                {
                    this.setCustomValidity('');
                }
            });
        }
        
        if (input.name === 'first_name' || input.name === 'last_name')
        {
            input.addEventListener('input', function ()
            {
                if (this.value && !validateName(this.value))
                {
                    this.setCustomValidity('Only letters are allowed (no spaces)');
                }
                else
                {
                    this.setCustomValidity('');
                }
            });
        }
        
        if (input.name === 'username')
        {
            input.addEventListener('input', function ()
            {
                if (this.value && !validateUsername(this.value))
                {
                    this.setCustomValidity('Username must be 3-20 characters and contain only letters, numbers, and underscores');
                }
                else
                {
                    this.setCustomValidity('');
                }
            });
        }
    });
}

/**
 * Initialize password toggle visibility for password fields
 * This function adds show/hide functionality to password inputs
 * Only allows one password to be visible at a time for security
 * 
 * @param {Array} fieldIds - Array of password field IDs or single field ID
 */
function initPasswordToggle(fieldIds) {
    // Allow both array and single string
    if (!Array.isArray(fieldIds)) {
        fieldIds = [fieldIds];
    }
    
    // Create the toggle buttons for each field
    fieldIds.forEach(fieldId => {
        const passwordField = document.getElementById(fieldId);
        if (!passwordField) return;
        
        // Create container if needed
        let container = passwordField.parentElement;
        if (!container.classList.contains('password-input-container')) {
            // Create a container
            container = document.createElement('div');
            container.className = 'password-input-container';
            
            // Insert container before the password field
            passwordField.parentNode.insertBefore(container, passwordField);
            
            // Move password field inside container
            container.appendChild(passwordField);
        }
        
        // Create toggle button
        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'password-toggle';
        toggleButton.setAttribute('data-target', fieldId);
        toggleButton.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" 
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" 
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-slash-icon" style="display:none">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
        `;
        
        // Add button to container
        container.appendChild(toggleButton);
    });
    
    // Add the necessary CSS
    addPasswordToggleStyles();
    
    // Initialize toggle functionality
    let currentlyVisible = null;
    
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.password-toggle')) return;
        
        const toggle = event.target.closest('.password-toggle');
        const targetId = toggle.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        const eyeIcon = toggle.querySelector('.eye-icon');
        const eyeSlashIcon = toggle.querySelector('.eye-slash-icon');
        
        // If another password field is currently visible, hide it first
        if (currentlyVisible && currentlyVisible !== passwordInput) {
            const otherToggle = document.querySelector(`.password-toggle[data-target="${currentlyVisible.id}"]`);
            const otherEyeIcon = otherToggle.querySelector('.eye-icon');
            const otherEyeSlashIcon = otherToggle.querySelector('.eye-slash-icon');
            
            currentlyVisible.type = 'password';
            otherEyeIcon.style.display = 'block';
            otherEyeSlashIcon.style.display = 'none';
        }
        
        // Toggle current password visibility
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.style.display = 'none';
            eyeSlashIcon.style.display = 'block';
            currentlyVisible = passwordInput;
        } else {
            passwordInput.type = 'password';
            eyeIcon.style.display = 'block';
            eyeSlashIcon.style.display = 'none';
            currentlyVisible = null;
        }
    });
}

/**
 * Add the necessary CSS styles for password toggle
 * Only adds styles once to prevent duplication
 */
function addPasswordToggleStyles() {
    // Check if styles already exist
    if (document.getElementById('password-toggle-styles')) return;
    
    // Create style element
    const styleElement = document.createElement('style');
    styleElement.id = 'password-toggle-styles';
    styleElement.textContent = `
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-input-container input {
            flex: 1;
            padding-right: 40px; /* Space for the eye button */
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-dark, #333);
            opacity: 0.7;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        
        .password-toggle:hover {
            opacity: 1;
        }
        
        .password-toggle svg {
            width: 18px;
            height: 18px;
        }
    `;
    
    // Append to document head
    document.head.appendChild(styleElement);
}