// Form Validation and Enhancement

class FormValidator {
    constructor() {
        this.rules = {};
        this.messages = {
            required: 'Field ini wajib diisi',
            email: 'Format email tidak valid',
            min: 'Nilai minimum adalah {0}',
            max: 'Nilai maksimum adalah {0}',
            minLength: 'Minimal {0} karakter',
            maxLength: 'Maksimal {0} karakter',
            numeric: 'Harus berupa angka',
            integer: 'Harus berupa bilangan bulat',
            decimal: 'Harus berupa angka desimal',
            date: 'Format tanggal tidak valid',
            time: 'Format waktu tidak valid',
            equalTo: 'Nilai tidak sama dengan {0}',
            notEqualTo: 'Nilai tidak boleh sama dengan {0}',
            pattern: 'Format tidak valid'
        };
        this.init();
    }
    
    init() {
        this.initForms();
        this.initInputMasks();
        this.initAutoComplete();
        this.initCharacterCounters();
    }
    
    initForms() {
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    this.showFormErrors(form);
                }
            });
            
            // Real-time validation
            form.querySelectorAll('input, select, textarea').forEach(input => {
                input.addEventListener('blur', () => {
                    this.validateField(input);
                });
                
                input.addEventListener('input', () => {
                    this.clearFieldError(input);
                });
            });
        });
    }
    
    initInputMasks() {
        // Date mask
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 8) value = value.substr(0, 8);
                
                if (value.length >= 2) {
                    value = value.substr(0, 2) + '/' + value.substr(2);
                }
                if (value.length >= 5) {
                    value = value.substr(0, 5) + '/' + value.substr(5);
                }
                
                e.target.value = value;
            });
        });
        
        // Number mask
        document.querySelectorAll('input[data-mask="number"]').forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/[^\d]/g, '');
                e.target.value = new Intl.NumberFormat('id-ID').format(value);
            });
        });
        
        // Weight mask (kg with decimal)
        document.querySelectorAll('input[data-mask="weight"]').forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/[^\d.]/g, '');
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                e.target.value = value;
            });
        });
    }
    
    initAutoComplete() {
        // Initialize autocomplete for search fields
        document.querySelectorAll('input[data-autocomplete]').forEach(input => {
            const source = input.getAttribute('data-autocomplete-source');
            const minLength = parseInt(input.getAttribute('data-autocomplete-min') || 2);
            
            input.addEventListener('input', debounce(async (e) => {
                const searchTerm = e.target.value.trim();
                
                if (searchTerm.length < minLength) {
                    this.removeAutocomplete(input);
                    return;
                }
                
                try {
                    const response = await fetch(`${source}?q=${encodeURIComponent(searchTerm)}`);
                    const data = await response.json();
                    this.showAutocomplete(input, data);
                } catch (error) {
                    console.error('Autocomplete error:', error);
                }
            }, 300));
            
            // Hide autocomplete when clicking outside
            document.addEventListener('click', (e) => {
                if (!input.parentNode.contains(e.target)) {
                    this.removeAutocomplete(input);
                }
            });
        });
    }
    
    initCharacterCounters() {
        document.querySelectorAll('textarea[data-maxlength], input[data-maxlength]').forEach(input => {
            const maxLength = parseInt(input.getAttribute('data-maxlength'));
            const counter = document.createElement('div');
            counter.className = 'character-counter';
            counter.style.cssText = 'font-size: 12px; color: #666; text-align: right; margin-top: 5px;';
            
            input.parentNode.appendChild(counter);
            
            const updateCounter = () => {
                const length = input.value.length;
                counter.textContent = `${length}/${maxLength}`;
                counter.style.color = length > maxLength ? '#f44336' : length > maxLength * 0.8 ? '#ff9800' : '#666';
            };
            
            input.addEventListener('input', updateCounter);
            updateCounter();
        });
    }
    
    validateForm(form) {
        let isValid = true;
        const fields = form.querySelectorAll('[required], [data-validate]');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        
        // Required validation
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, this.messages.required);
            return false;
        }
        
        if (!value) return true; // Skip other validations if empty
        
        // Email validation
        if (field.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                this.showFieldError(field, this.messages.email);
                isValid = false;
            }
        }
        
        // Number validation
        if (field.type === 'number' || field.getAttribute('data-validate') === 'numeric') {
            if (isNaN(value)) {
                this.showFieldError(field, this.messages.numeric);
                isValid = false;
            } else {
                const numValue = parseFloat(value);
                
                if (field.min && numValue < parseFloat(field.min)) {
                    this.showFieldError(field, this.messages.min.replace('{0}', field.min));
                    isValid = false;
                }
                
                if (field.max && numValue > parseFloat(field.max)) {
                    this.showFieldError(field, this.messages.max.replace('{0}', field.max));
                    isValid = false;
                }
            }
        }
        
        // Length validation
        if (field.getAttribute('data-minlength')) {
            const minLength = parseInt(field.getAttribute('data-minlength'));
            if (value.length < minLength) {
                this.showFieldError(field, this.messages.minLength.replace('{0}', minLength));
                isValid = false;
            }
        }
        
        if (field.getAttribute('data-maxlength')) {
            const maxLength = parseInt(field.getAttribute('data-maxlength'));
            if (value.length > maxLength) {
                this.showFieldError(field, this.messages.maxLength.replace('{0}', maxLength));
                isValid = false;
            }
        }
        
        // Date validation
        if (field.type === 'date' || field.getAttribute('data-validate') === 'date') {
            const date = new Date(value);
            if (isNaN(date.getTime())) {
                this.showFieldError(field, this.messages.date);
                isValid = false;
            }
        }
        
        // Pattern validation
        if (field.pattern) {
            const regex = new RegExp(field.pattern);
            if (!regex.test(value)) {
                this.showFieldError(field, this.messages.pattern);
                isValid = false;
            }
        }
        
        // Equal to validation
        if (field.getAttribute('data-equal-to')) {
            const otherField = document.querySelector(field.getAttribute('data-equal-to'));
            if (otherField && value !== otherField.value) {
                this.showFieldError(field, this.messages.equalTo.replace('{0}', otherField.getAttribute('name') || 'field'));
                isValid = false;
            }
        }
        
        if (isValid) {
            this.clearFieldError(field);
        }
        
        return isValid;
    }
    
    showFieldError(field, message) {
        this.clearFieldError(field);
        
        const error = document.createElement('div');
        error.className = 'field-error';
        error.textContent = message;
        error.style.cssText = 'color: #f44336; font-size: 12px; margin-top: 5px;';
        
        field.parentNode.appendChild(error);
        field.classList.add('error');
        field.style.borderColor = '#f44336';
        
        // Add error icon
        const icon = document.createElement('span');
        icon.innerHTML = '❌';
        icon.style.cssText = 'position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #f44336;';
        
        if (field.parentNode.querySelector('.toggle-password')) {
            field.parentNode.querySelector('.toggle-password').style.right = '30px';
        }
        
        field.parentNode.appendChild(icon);
        field.errorIcon = icon;
    }
    
    clearFieldError(field) {
        const error = field.parentNode.querySelector('.field-error');
        if (error) error.remove();
        
        field.classList.remove('error');
        field.style.borderColor = '';
        
        if (field.errorIcon) {
            field.errorIcon.remove();
            delete field.errorIcon;
        }
        
        if (field.parentNode.querySelector('.toggle-password')) {
            field.parentNode.querySelector('.toggle-password').style.right = '10px';
        }
    }
    
    showFormErrors(form) {
        const invalidFields = form.querySelectorAll('.error');
        if (invalidFields.length > 0) {
            invalidFields[0].scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            
            warehouseSystem.showNotification(`Terdapat ${invalidFields.length} error dalam form`, 'error');
        }
    }
    
    showAutocomplete(input, items) {
        this.removeAutocomplete(input);
        
        if (items.length === 0) return;
        
        const container = document.createElement('div');
        container.className = 'autocomplete-container';
        container.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
        `;
        
        items.forEach(item => {
            const option = document.createElement('div');
            option.className = 'autocomplete-option';
            option.textContent = item.text || item;
            option.style.cssText = 'padding: 10px; cursor: pointer; border-bottom: 1px solid #eee;';
            
            option.addEventListener('mouseover', () => {
                option.style.backgroundColor = '#f0f7f0';
            });
            
            option.addEventListener('mouseout', () => {
                option.style.backgroundColor = '';
            });
            
            option.addEventListener('click', () => {
                input.value = item.value || item.text || item;
                this.removeAutocomplete(input);
                input.focus();
                
                // Trigger change event
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            container.appendChild(option);
        });
        
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(container);
        input.autocompleteContainer = container;
    }
    
    removeAutocomplete(input) {
        if (input.autocompleteContainer) {
            input.autocompleteContainer.remove();
            delete input.autocompleteContainer;
        }
    }
    
    resetForm(form) {
        form.reset();
        form.querySelectorAll('.field-error').forEach(error => error.remove());
        form.querySelectorAll('.error').forEach(field => {
            field.classList.remove('error');
            field.style.borderColor = '';
        });
    }
}

// Initialize form validator when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.formValidator = new FormValidator();
    
    // Add form reset functionality
    document.querySelectorAll('button[type="reset"]').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('form');
            if (form && window.formValidator) {
                window.formValidator.resetForm(form);
                warehouseSystem.showNotification('Form telah direset', 'info');
            }
        });
    });
    
    // Add form auto-save functionality
    document.querySelectorAll('form[data-autosave]').forEach(form => {
        const saveKey = form.getAttribute('data-autosave-key') || 'form_autosave';
        
        // Load saved data
        const savedData = localStorage.getItem(saveKey);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(key => {
                    const input = form.querySelector(`[name="${key}"]`);
                    if (input) {
                        input.value = data[key];
                    }
                });
                
                const restoreBtn = document.createElement('button');
                restoreBtn.type = 'button';
                restoreBtn.className = 'btn btn-secondary btn-small';
                restoreBtn.textContent = 'Kembalikan data tersimpan';
                restoreBtn.style.marginLeft = '10px';
                restoreBtn.addEventListener('click', () => {
                    form.reset();
                    localStorage.removeItem(saveKey);
                    restoreBtn.remove();
                    warehouseSystem.showNotification('Data tersimpan telah dihapus', 'info');
                });
                
                form.querySelector('.form-actions')?.appendChild(restoreBtn);
            } catch (error) {
                console.error('Error loading saved form data:', error);
            }
        }
        
        // Auto-save on input
        form.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('input', debounce(() => {
                const formData = new FormData(form);
                const data = {};
                formData.forEach((value, key) => {
                    data[key] = value;
                });
                localStorage.setItem(saveKey, JSON.stringify(data));
            }, 1000));
        });
        
        // Clear saved data on submit
        form.addEventListener('submit', () => {
            localStorage.removeItem(saveKey);
        });
    });
});