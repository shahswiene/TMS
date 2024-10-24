// password_strength.js

document.addEventListener('DOMContentLoaded', function() {
    initializePasswordValidation();
});

function initializePasswordValidation() {
    // Support both first time login and user management forms
    const forms = {
        firstTimeLogin: {
            form: document.getElementById('firstTimeLoginForm'),
            password: document.getElementById('new_password'),
            confirm: document.getElementById('confirm_password'),
            indicator: document.getElementById('strengthIndicator'),
            submit: document.getElementById('submitButton')
        },
        addUser: {
            form: document.getElementById('addUserForm'),
            password: document.getElementById('addPassword'),
            confirm: document.getElementById('addConfirmPassword'),
            indicator: document.getElementById('addStrengthIndicator'),
            submit: document.querySelector('#addUserModal .btn-primary')
        },
        editUser: {
            form: document.getElementById('editUserForm'),
            password: document.getElementById('editPassword'),
            confirm: document.getElementById('editConfirmPassword'),
            indicator: document.getElementById('editStrengthIndicator'),
            submit: document.querySelector('#editUserModal .btn-primary')
        }
    };

    // Initialize each form's password validation
    Object.values(forms).forEach(formConfig => {
        if (formConfig.form && formConfig.password && formConfig.confirm) {
            initializeFormPasswordValidation(formConfig);
        }
    });
}

function initializeFormPasswordValidation(config) {
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 12) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[$@#&!]+/)) strength++;

        if (config.indicator) {
            updateStrengthIndicator(config.indicator, strength);
        }

        return strength;
    }

    function updateStrengthIndicator(indicator, strength) {
        const colors = ['red', 'red', 'orange', 'yellow', 'lightgreen', 'green'];
        const widths = ['20%', '20%', '40%', '60%', '80%', '100%'];

        indicator.style.width = widths[strength];
        indicator.style.backgroundColor = colors[strength];

        // Add aria-label for accessibility
        indicator.setAttribute('aria-label', `Password strength: ${getStrengthLabel(strength)}`);
    }

    function getStrengthLabel(strength) {
        const labels = ['Very Weak', 'Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong'];
        return labels[strength];
    }

    function validatePasswordMatch() {
        const passwordsMatch = !config.password.value || 
                             config.password.value === config.confirm.value;
        
        if (!passwordsMatch) {
            config.confirm.setCustomValidity('Passwords do not match');
        } else {
            config.confirm.setCustomValidity('');
        }

        if (config.submit) {
            const strength = config.password.value ? checkPasswordStrength(config.password.value) : 5;
            config.submit.disabled = strength < 5 || !passwordsMatch;
        }
    }

    // Add event listeners
    config.password.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        validatePasswordMatch();

        // Show requirements if password is weak
        if (strength < 5) {
            this.setCustomValidity('Password must be at least 12 characters long and contain lowercase, uppercase, numbers, and special characters');
        } else {
            this.setCustomValidity('');
        }
    });

    config.confirm.addEventListener('input', validatePasswordMatch);

    config.form.addEventListener('submit', function(e) {
        if (config.password.value !== config.confirm.value) {
            e.preventDefault();
            alert('Passwords do not match!');
            return;
        }

        if (config.password.value && checkPasswordStrength(config.password.value) < 5) {
            e.preventDefault();
            alert('Password does not meet strength requirements!');
            return;
        }
    });
}