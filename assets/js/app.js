/**
 * ARIA HOTEL - MAIN JAVASCRIPT
 * ============================================
 */

const AriaApp = {
    // State
    isLoggedIn: false,
    user: null,
    
    /**
     * Initialize the application
     */
    init() {
        this.setupNavigation();
        this.setupDropdowns();
        this.setupModals();
        this.setupFormValidation();
        this.setupLazyLoading();
        this.setupSmoothScroll();
        this.setupMobileMenu();
        
        console.log('Aria Hotel App initialized');
    },
    
    /**
     * Setup navigation active states
     */
    setupNavigation() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href.replace('.php', ''))) {
                link.classList.add('active');
            }
        });
    },
    
    /**
     * Setup dropdown menus
     */
    setupDropdowns() {
        const userMenus = document.querySelectorAll('.user-menu');
        
        userMenus.forEach(menu => {
            const trigger = menu.querySelector('.user-trigger');
            
            if (trigger) {
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    
                    // Close other dropdowns
                    userMenus.forEach(m => {
                        if (m !== menu) {
                            m.classList.remove('active');
                        }
                    });
                    
                    menu.classList.toggle('active');
                });
            }
        });
        
        // Close on outside click
        document.addEventListener('click', () => {
            userMenus.forEach(menu => {
                menu.classList.remove('active');
            });
        });
        
        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                userMenus.forEach(menu => {
                    menu.classList.remove('active');
                });
            }
        });
    },
    
    /**
     * Setup modal functionality
     */
    setupModals() {
        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
                e.target.style.display = 'none';
            }
        });
    },
    
    /**
     * Open a modal
     */
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('active');
            
            // Focus first input if exists
            const firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    },
    
    /**
     * Close a modal
     */
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('active');
        }
    },
    
    /**
     * Close all modals
     */
    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
            modal.classList.remove('active');
        });
    },
    
    /**
     * Setup form validation
     */
    setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                } else {
                    this.showFormLoading(form);
                }
            });
            
            // Real-time validation on blur
            form.querySelectorAll('input, select, textarea').forEach(field => {
                field.addEventListener('blur', () => {
                    this.validateField(field);
                });
                
                field.addEventListener('input', () => {
                    field.classList.remove('error');
                    const errorEl = field.parentElement.querySelector('.field-error');
                    if (errorEl) errorEl.remove();
                });
            });
        });
    },
    
    /**
     * Validate entire form
     */
    validateForm(form) {
        let isValid = true;
        const fields = form.querySelectorAll('[required], [data-validate]');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    },
    
    /**
     * Validate a single field
     */
    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        // Remove existing error
        field.classList.remove('error');
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) existingError.remove();
        
        // Required validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        
        // Email validation
        if (isValid && field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }
        
        // Phone validation
        if (isValid && field.type === 'tel' && value) {
            const phoneRegex = /^[0-9]{10,11}$/;
            if (!phoneRegex.test(value.replace(/\s/g, ''))) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number (10-11 digits)';
            }
        }
        
        // Password validation
        if (isValid && field.type === 'password' && value) {
            const minLength = field.dataset.minLength || 6;
            if (value.length < minLength) {
                isValid = false;
                errorMessage = `Password must be at least ${minLength} characters`;
            }
        }
        
        // Password confirmation
        if (isValid && field.dataset.confirmFor && value) {
            const targetField = form.querySelector(`[name="${field.dataset.confirmFor}"]`);
            if (targetField && value !== targetField.value) {
                isValid = false;
                errorMessage = 'Passwords do not match';
            }
        }
        
        // Custom pattern validation
        if (isValid && field.dataset.pattern && value) {
            const regex = new RegExp(field.dataset.pattern);
            if (!regex.test(value)) {
                isValid = false;
                errorMessage = field.dataset.message || 'Invalid format';
            }
        }
        
        if (!isValid) {
            field.classList.add('error');
            this.showFieldError(field, errorMessage);
        }
        
        return isValid;
    },
    
    /**
     * Show field error message
     */
    showFieldError(field, message) {
        const errorEl = document.createElement('span');
        errorEl.className = 'field-error';
        errorEl.textContent = message;
        field.parentElement.appendChild(errorEl);
    },
    
    /**
     * Show form loading state
     */
    showFormLoading(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.textContent = submitBtn.dataset.loadingText || 'Processing...';
        }
    },
    
    /**
     * Reset form loading state
     */
    resetFormLoading(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
            if (submitBtn.dataset.originalText) {
                submitBtn.textContent = submitBtn.dataset.originalText;
            }
        }
    },
    
    /**
     * Setup lazy loading for images
     */
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.add('loaded');
                        }
                        imageObserver.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for older browsers
            document.querySelectorAll('img[data-src]').forEach(img => {
                img.src = img.dataset.src;
                img.classList.add('loaded');
            });
        }
    },
    
    /**
     * Setup smooth scrolling
     */
    setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                const targetId = anchor.getAttribute('href');
                const target = document.querySelector(targetId);
                
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Update URL without jumping
                    history.pushState(null, null, targetId);
                }
            });
        });
    },
    
    /**
     * Setup mobile menu
     */
    setupMobileMenu() {
        const mobileBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');
        
        if (mobileBtn && navLinks) {
            mobileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                navLinks.classList.toggle('show');
            });
            
            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!navLinks.contains(e.target) && !mobileBtn.contains(e.target)) {
                    navLinks.classList.remove('show');
                }
            });
            
            // Close on link click
            navLinks.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    navLinks.classList.remove('show');
                });
            });
        }
        
        // Reset on window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && navLinks) {
                navLinks.classList.remove('show');
            }
        });
    },
    
    /**
     * Show toast notification
     */
    showToast(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        // Style the toast
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            min-width: 300px;
            max-width: 400px;
            background: ${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#2563eb'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.3s ease;
        `;
        
        const toastContent = toast.querySelector('.toast-content');
        toastContent.style.cssText = `
            display: flex;
            align-items: center;
            gap: 0.75rem;
        `;
        
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.style.cssText = `
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            opacity: 0.8;
        `;
        
        document.body.appendChild(toast);
        
        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },
    
    /**
     * Format currency
     */
    formatCurrency(amount, currency = 'VND') {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },
    
    /**
     * Format date
     */
    formatDate(date, format = 'dd/MM/yyyy') {
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        
        return format
            .replace('dd', day)
            .replace('MM', month)
            .replace('yyyy', year);
    },
    
    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Make AJAX request
     */
    async request(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };
        
        const mergedOptions = { ...defaultOptions, ...options };
        
        if (mergedOptions.body && typeof mergedOptions.body === 'object') {
            mergedOptions.body = JSON.stringify(mergedOptions.body);
        }
        
        try {
            const response = await fetch(url, mergedOptions);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return await response.text();
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    AriaApp.init();
});

// Export for global use
window.AriaApp = AriaApp;

// Add slideOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
`;
document.head.appendChild(style);