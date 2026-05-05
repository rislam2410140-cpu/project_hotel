// ========================================
// Modern Hotel Management - Enhanced JS
// ========================================

// ========== MODAL MANAGEMENT ==========
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        modal.focus();
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Close modal on outside click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        });
    }
});

// ========== CONFIRMATION DIALOGS ==========
function confirmDelete(msg = 'Are you sure?') {
    return confirm(msg || 'Are you sure you want to delete this?');
}

function confirmAction(msg = 'Are you sure?') {
    return confirm(msg);
}

// ========== CURRENCY FORMATTING ==========
function formatCurrency(input) {
    let value = input.value.replace(/[^\d.]/g, '');
    input.value = value ? '$' + parseFloat(value).toFixed(2) : '';
}

// ========== DATE VALIDATION ==========
function validateDates(checkInElem, checkOutElem) {
    const checkIn = new Date(checkInElem.value);
    const checkOut = new Date(checkOutElem.value);
    
    if (checkIn >= checkOut) {
        showNotification('Check-out date must be after check-in date', 'error');
        return false;
    }
    return true;
}

// ========== NIGHT CALCULATION ==========
function calculateNights(checkInElem, checkOutElem, nightsDisplay) {
    const checkIn = new Date(checkInElem.value);
    const checkOut = new Date(checkOutElem.value);
    
    if (checkIn < checkOut) {
        const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
        if (nightsDisplay) {
            nightsDisplay.textContent = nights;
        }
        return nights;
    }
    return 0;
}

// ========== NOTIFICATION SYSTEM ==========
function showNotification(message, type = 'info') {
    const notificationContainer = document.getElementById('notification-container') || 
                                  createNotificationContainer();
    
    const notification = document.createElement('div');
    notification.className = `flash-message flash-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button class="flash-close" onclick="this.parentElement.remove();">&times;</button>
    `;
    
    notificationContainer.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'notification-container';
    container.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 999;
        width: 90%;
        max-width: 400px;
    `;
    document.body.appendChild(container);
    return container;
}

// ========== SMOOTH SCROLL ==========
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus to first form error
    const errorMsg = document.querySelector('.error-msg');
    if (errorMsg) {
        const input = errorMsg.parentElement.querySelector('input, select, textarea');
        if (input) {
            input.focus();
            input.style.borderColor = 'var(--danger)';
        }
    }

    // Add ripple effect to buttons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            // Ripple effect already in CSS, just add visual feedback
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Enhanced form inputs with visual feedback
    document.querySelectorAll('input, textarea, select').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });

    // Fade in elements on load
    observeElements();
});

// ========== INTERSECTION OBSERVER FOR ANIMATIONS ==========
function observeElements() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    });

    document.querySelectorAll('.card, .stat-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

// ========== FORM VALIDATION ==========
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = 'var(--danger)';
            isValid = false;
        } else {
            input.style.borderColor = '';
        }
    });

    return isValid;
}

// ========== AUTO-SAVE FORM ==========
let saveTimeout;
function autoSaveForm(formId) {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        const form = document.getElementById(formId);
        if (form) {
            const formData = new FormData(form);
            localStorage.setItem(`form-${formId}`, JSON.stringify(Object.fromEntries(formData)));
        }
    }, 1000);
}

function restoreForm(formId) {
    const saved = localStorage.getItem(`form-${formId}`);
    if (saved) {
        const data = JSON.parse(saved);
        const form = document.getElementById(formId);
        if (form) {
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) input.value = data[key];
            });
        }
    }
}

// ========== LOADING STATE ==========
function setLoadingState(elementId, isLoading = true) {
    const element = document.getElementById(elementId);
    if (!element) return;

    if (isLoading) {
        element.disabled = true;
        element.innerHTML = '<span style="opacity: 0.7;">Loading...</span>';
        element.style.pointerEvents = 'none';
    } else {
        element.disabled = false;
        element.innerHTML = element.getAttribute('data-original-text') || 'Submit';
        element.style.pointerEvents = '';
    }
}

// ========== TABLE ENHANCEMENTS ==========
document.addEventListener('DOMContentLoaded', function() {
    // Make table rows clickable if they have a data-href attribute
    document.querySelectorAll('tbody tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function() {
            window.location.href = this.getAttribute('data-href');
        });
    });

    // Add sorting to tables with class 'sortable'
    document.querySelectorAll('table.sortable thead th').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            sortTable(this);
        });
    });
});

function sortTable(th) {
    const table = th.closest('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(th.parentElement.children).indexOf(th);
    const isAsc = !th.classList.contains('sort-asc');

    rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();

        if (isAsc) {
            return aValue.localeCompare(bValue, undefined, {numeric: true});
        } else {
            return bValue.localeCompare(aValue, undefined, {numeric: true});
        }
    });

    // Update sort indicator
    table.querySelectorAll('th').forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
    th.classList.add(isAsc ? 'sort-asc' : 'sort-desc');

    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}
