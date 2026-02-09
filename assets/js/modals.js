// Global Modal System for WSU Booking System
// Replaces alert() and confirm() with styled modals

// Confirmation modal variables
let confirmCallback = null;

/**
 * Show confirmation modal
 * @param {string} title - Modal title
 * @param {string} message - Confirmation message
 * @param {function} callback - Function to execute on confirm
 */
function showConfirmModal(title, message, callback) {
    document.getElementById('confirmTitle').innerHTML = '<i class="fas fa-question-circle"></i> ' + title;
    document.getElementById('confirmMessage').textContent = message;
    confirmCallback = callback;
    document.getElementById('confirmModal').classList.add('active');
}

/**
 * Close confirmation modal
 */
function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
    confirmCallback = null;
}

/**
 * Execute confirmed action
 */
function confirmAction() {
    if(confirmCallback) {
        confirmCallback();
    }
    closeConfirmModal();
}

/**
 * Show message modal (replaces alert)
 * @param {string} title - Modal title
 * @param {string} message - Message content
 * @param {string} type - Type: 'success', 'error', 'info', 'warning'
 */
function showMessageModal(title, message, type = 'info') {
    const icons = {
        'success': '<i class="fas fa-check-circle" style="color: #10b981;"></i>',
        'error': '<i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>',
        'info': '<i class="fas fa-info-circle" style="color: #2563eb;"></i>',
        'warning': '<i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>'
    };
    
    document.getElementById('messageTitle').innerHTML = icons[type] + ' ' + title;
    document.getElementById('messageContent').textContent = message;
    document.getElementById('messageModal').classList.add('active');
}

/**
 * Close message modal
 */
function closeMessageModal() {
    document.getElementById('messageModal').classList.remove('active');
}

/**
 * Confirm logout action
 */
function confirmLogout() {
    // Determine the correct path to logout.php based on current location
    let logoutPath = '../auth/logout.php';
    
    // If we're already in auth folder or root
    if (window.location.pathname.includes('/auth/')) {
        logoutPath = 'logout.php';
    } else if (!window.location.pathname.includes('/student/') && 
               !window.location.pathname.includes('/staff/') && 
               !window.location.pathname.includes('/admin/')) {
        logoutPath = 'auth/logout.php';
    }
    
    showConfirmModal(
        'Confirm Logout',
        'Are you sure you want to logout? Any unsaved changes will be lost.',
        function() {
            window.location.href = logoutPath;
        }
    );
}

// Initialize modal event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if(e.target === this) {
                const modalId = this.id;
                if(modalId === 'confirmModal') {
                    closeConfirmModal();
                } else if(modalId === 'messageModal') {
                    closeMessageModal();
                }
            }
        });
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // ESC key closes modals
        if(e.key === 'Escape') {
            if(document.getElementById('confirmModal')?.classList.contains('active')) {
                closeConfirmModal();
            } else if(document.getElementById('messageModal')?.classList.contains('active')) {
                closeMessageModal();
            }
        }
    });
});
