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