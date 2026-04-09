/**
 * ARIA HOTEL - ADMIN JAVASCRIPT v2.0
 * ============================================
 */

const AdminApp = {
    sidebar: null,
    menuToggle: null,
    
    init() {
        this.sidebar = document.getElementById('adminSidebar');
        this.menuToggle = document.getElementById('menuToggle');
        
        this.setupMobileMenu();
        this.setupDropdowns();
        this.setupModals();
        this.setupNotifications();
        this.setupTabs();
        
        console.log('Admin App initialized');
    },
    
    setupMobileMenu() {
        if (this.menuToggle && this.sidebar) {
            this.menuToggle.addEventListener('click', () => {
                this.sidebar.classList.toggle('open');
            });
            
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    if (!this.sidebar.contains(e.target) && !this.menuToggle.contains(e.target)) {
                        this.sidebar.classList.remove('open');
                    }
                }
            });
        }
    },
    
    setupDropdowns() {
        const userMenus = document.querySelectorAll('.user-menu');
        
        userMenus.forEach(menu => {
            const trigger = menu.querySelector('.user-trigger');
            
            if (trigger) {
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    userMenus.forEach(m => {
                        if (m !== menu) m.classList.remove('active');
                    });
                    menu.classList.toggle('active');
                });
            }
        });
        
        document.addEventListener('click', () => {
            userMenus.forEach(menu => menu.classList.remove('active'));
        });
    },
    
    setupModals() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
        
        window.onclick = (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target.id);
            }
        };
    },
    
    setupNotifications() {
        const notificationBtn = document.querySelector('.notification-btn');
        const notificationsMenu = document.getElementById('notificationsMenu');
        
        if (notificationBtn && notificationsMenu) {
            notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationsMenu.classList.toggle('show');
            });
            
            document.addEventListener('click', (e) => {
                if (!notificationsMenu.contains(e.target) && !notificationBtn.contains(e.target)) {
                    notificationsMenu.classList.remove('show');
                }
            });
        }
    },
    
    setupTabs() {
        const tabButtons = document.querySelectorAll('.tab-item');
        
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabName = btn.dataset.tab;
                
                tabButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                document.getElementById(`tab-${tabName}`)?.classList.add('active');
            });
        });
    },
    
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('active');
            
            const firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    },
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('active');
        }
    },
    
    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
            modal.classList.remove('active');
        });
    },
    
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
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },
    
    confirmAction(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },
    
    formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' VND';
    },
    
    formatDate(date) {
        return new Date(date).toLocaleDateString('vi-VN');
    },
    
    async apiRequest(endpoint, data = {}) {
        try {
            const response = await fetch(`ajax/${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API Request failed:', error);
            throw error;
        }
    }
};

// Global functions
window.openModal = (id) => AdminApp.openModal(id);
window.closeModal = (id) => AdminApp.closeModal(id);
window.showToast = (msg, type) => AdminApp.showToast(msg, type);

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    AdminApp.init();
});

// Add slide animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100%); }
    }
    .toast-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .toast-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        opacity: 0.8;
    }
    .toast-close:hover {
        opacity: 1;
    }
`;
document.head.appendChild(style);