// Warehouse Management System - Main JavaScript File

class WarehouseSystem {
    constructor() {
        this.init();
    }
    
    init() {
        this.initSidebar();
        this.initDatePickers();
        this.initFormValidation();
        this.initNotifications();
        this.initResponsive();
        this.initTooltips();
        this.initModals();
        this.initTabs();
        this.initDataTables();
        this.initTheme();
    }
    
    initSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                sidebarToggle.innerHTML = sidebar.classList.contains('active') ? '✕' : '☰';
                localStorage.setItem('sidebarState', sidebar.classList.contains('active') ? 'open' : 'closed');
            });
        }
        
        // Restore sidebar state
        const savedState = localStorage.getItem('sidebarState');
        if (savedState === 'open' && sidebarToggle) {
            sidebar.classList.add('active');
            sidebarToggle.innerHTML = '✕';
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                e.target !== sidebarToggle) {
                sidebar.classList.remove('active');
                if (sidebarToggle) sidebarToggle.innerHTML = '☰';
            }
        });
    }
    
    initDatePickers() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        const today = new Date().toISOString().split('T')[0];
        
        dateInputs.forEach(input => {
            if (!input.max) input.max = today;
            
            // Add date picker enhancement
            const wrapper = document.createElement('div');
            wrapper.style.position = 'relative';
            wrapper.style.width = '100%';
            
            const icon = document.createElement('span');
            icon.className = 'date-picker-icon';
            icon.innerHTML = '📅';
            icon.style.cssText = 'position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; opacity: 0.5; z-index: 2;';
            icon.addEventListener('click', () => input.showPicker());
            
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            wrapper.appendChild(icon);
            
            // Auto format on blur
            input.addEventListener('blur', () => {
                if (input.value) {
                    const date = new Date(input.value);
                    input.value = date.toISOString().split('T')[0];
                }
            });
        });
    }
    
    initFormValidation() {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    this.showFieldError(field, 'Field ini wajib diisi');
                    isValid = false;
                } else {
                    this.clearFieldError(field);
                    
                    // Email validation
                    if (field.type === 'email' && field.value) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(field.value)) {
                            this.showFieldError(field, 'Format email tidak valid');
                            isValid = false;
                        }
                    }
                    
                    // Number validation
                    if (field.type === 'number' && field.value) {
                        const value = parseFloat(field.value);
                        
                        if (field.min && value < parseFloat(field.min)) {
                            this.showFieldError(field, `Nilai minimum adalah ${field.min}`);
                            isValid = false;
                        }
                        
                        if (field.max && value > parseFloat(field.max)) {
                            this.showFieldError(field, `Nilai maksimum adalah ${field.max}`);
                            isValid = false;
                        }
                    }
                    
                    // Password confirmation
                    if (field.id === 'confirm_password') {
                        const password = document.getElementById('new_password');
                        if (password && password.value !== field.value) {
                            this.showFieldError(field, 'Password tidak cocok');
                            isValid = false;
                        }
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                this.showNotification('Harap periksa form Anda', 'error');
            }
        });
    }
    
    showFieldError(field, message) {
        this.clearFieldError(field);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.cssText = 'color: #f44336; font-size: 12px; margin-top: 5px;';
        
        field.parentNode.appendChild(errorDiv);
        field.style.borderColor = '#f44336';
        field.focus();
    }
    
    clearFieldError(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) existingError.remove();
        field.style.borderColor = '';
    }
    
    initNotifications() {
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Close alert button
        document.querySelectorAll('.alert .close').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.alert').remove();
            });
        });
    }
    
    initResponsive() {
        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                this.updateResponsiveClasses();
            }, 250);
        });
        
        this.updateResponsiveClasses();
    }
    
    updateResponsiveClasses() {
        const isMobile = window.innerWidth <= 768;
        document.body.classList.toggle('mobile-view', isMobile);
        document.body.classList.toggle('desktop-view', !isMobile);
    }
    
    initTooltips() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        tooltips.forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip-content';
                tooltip.textContent = element.getAttribute('data-tooltip');
                tooltip.style.cssText = `
                    position: absolute;
                    background: #333;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    z-index: 1000;
                    white-space: nowrap;
                    pointer-events: none;
                `;
                
                const rect = element.getBoundingClientRect();
                tooltip.style.left = rect.left + rect.width / 2 + 'px';
                tooltip.style.top = rect.top - 30 + 'px';
                tooltip.style.transform = 'translateX(-50%)';
                
                document.body.appendChild(tooltip);
                element.tooltipElement = tooltip;
            });
            
            element.addEventListener('mouseleave', () => {
                if (element.tooltipElement) {
                    element.tooltipElement.remove();
                }
            });
        });
    }
    
    initModals() {
        document.querySelectorAll('[data-modal]').forEach(trigger => {
            trigger.addEventListener('click', () => {
                const modalId = trigger.getAttribute('data-modal');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                }
            });
        });
        
        document.querySelectorAll('.modal .close').forEach(closeBtn => {
            closeBtn.addEventListener('click', () => {
                const modal = closeBtn.closest('.modal');
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            document.querySelectorAll('.modal').forEach(modal => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });
    }
    
    initTabs() {
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                const tabContent = document.getElementById(tabId);
                
                if (tabContent) {
                    // Hide all tabs
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Remove active class from all buttons
                    document.querySelectorAll('.tab-button').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    // Show selected tab
                    tabContent.classList.add('active');
                    button.classList.add('active');
                    
                    // Save active tab
                    localStorage.setItem('activeTab', tabId);
                }
            });
        });
        
        // Restore active tab
        const savedTab = localStorage.getItem('activeTab');
        if (savedTab) {
            const tabButton = document.querySelector(`[data-tab="${savedTab}"]`);
            if (tabButton) tabButton.click();
        }
    }
    
    initDataTables() {
        // Initialize sortable tables
        document.querySelectorAll('.data-table thead th[data-sort]').forEach(th => {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                const table = th.closest('table');
                const columnIndex = Array.from(th.parentNode.children).indexOf(th);
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                const isNumeric = th.getAttribute('data-sort') === 'numeric';
                
                const direction = th.getAttribute('data-sort-direction') === 'asc' ? 'desc' : 'asc';
                
                // Update all headers
                table.querySelectorAll('thead th').forEach(header => {
                    header.removeAttribute('data-sort-direction');
                });
                th.setAttribute('data-sort-direction', direction);
                
                // Sort rows
                rows.sort((a, b) => {
                    const aValue = a.children[columnIndex].textContent;
                    const bValue = b.children[columnIndex].textContent;
                    
                    if (isNumeric) {
                        const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
                        const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));
                        return direction === 'asc' ? aNum - bNum : bNum - aNum;
                    } else {
                        return direction === 'asc' 
                            ? aValue.localeCompare(bValue)
                            : bValue.localeCompare(aValue);
                    }
                });
                
                // Reorder rows
                const tbody = table.querySelector('tbody');
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    }
    
    initTheme() {
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.classList.toggle('dark-mode', savedTheme === 'dark');
        
        // Theme toggle button
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const isDark = document.body.classList.toggle('dark-mode');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                themeToggle.innerHTML = isDark ? '☀️' : '🌙';
                this.showNotification(`Mode ${isDark ? 'gelap' : 'terang'} diaktifkan`, 'info');
            });
            
            themeToggle.innerHTML = savedTheme === 'dark' ? '☀️' : '🌙';
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${icons[type] || icons.info}</span>
                <span class="notification-message">${message}</span>
                <button class="notification-close">✕</button>
            </div>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s;
            min-width: 300px;
            max-width: 400px;
        `;
        
        const colors = {
            success: '#4caf50',
            error: '#f44336',
            warning: '#ff9800',
            info: '#2196f3'
        };
        
        notification.style.backgroundColor = colors[type] || colors.info;
        
        document.body.appendChild(notification);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
        
        // Auto remove
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.5s';
            setTimeout(() => notification.remove(), 500);
        }, 5000);
    }
    
    exportData(tableId, format = 'csv', filename = 'export') {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        if (format === 'csv') {
            this.exportToCSV(table, filename + '.csv');
        } else if (format === 'excel') {
            this.exportToExcel(table, filename + '.xlsx');
        }
    }
    
    exportToCSV(table, filename) {
        const rows = table.querySelectorAll('tr');
        const csv = [];
        
        rows.forEach(row => {
            const rowData = [];
            const cells = row.querySelectorAll('th, td');
            
            cells.forEach(cell => {
                let text = cell.textContent.trim();
                text = text.replace(/"/g, '""');
                rowData.push(`"${text}"`);
            });
            
            csv.push(rowData.join(','));
        });
        
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        
        if (navigator.msSaveBlob) {
            navigator.msSaveBlob(blob, filename);
        } else {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
    
    printElement(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Cetak - Sistem Gudang Wortel</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .print-header { text-align: center; margin-bottom: 30px; }
                    .print-footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h2>Desa Barus Julu</h2>
                    <h3>Sistem Gudang Wortel</h3>
                    <p>Tanggal Cetak: ${new Date().toLocaleDateString('id-ID')}</p>
                </div>
                ${element.outerHTML}
                <div class="print-footer">
                    <p>Dicetak pada: ${new Date().toLocaleString('id-ID')}</p>
                    <p>© ${new Date().getFullYear()} Desa Barus Julu</p>
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(() => window.close(), 500);
                    }
                </script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.warehouseSystem = new WarehouseSystem();
    
    // Initialize password toggles
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    });
    
    // Initialize search functionality
    const searchInput = document.getElementById('globalSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.querySelector('.data-table');
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        }, 300));
    }
    
    // Initialize confirm dialogs
    document.querySelectorAll('[data-confirm]').forEach(link => {
        link.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Apakah Anda yakin?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Initialize auto-refresh for dashboard
    if (window.location.pathname.includes('dashboard/index.php')) {
        setInterval(() => {
            const eventSource = new EventSource('includes/updates.php');
            eventSource.onmessage = function(event) {
                const data = JSON.parse(event.data);
                if (data.update) {
                    location.reload();
                }
            };
        }, 30000); // 30 seconds
    }
});

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}