document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if(loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const studentId = document.getElementById('student_id').value.trim();
            const password = document.getElementById('password').value;
            
            if(!studentId || !password) {
                e.preventDefault();
                showAlert('Please fill in all fields', 'error');
                return false;
            }
        });
    }
});

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
        ${message}
    `;
    
    const loginBox = document.querySelector('.login-box');
    const existingAlert = loginBox.querySelector('.alert');
    
    if(existingAlert) {
        existingAlert.remove();
    }
    
    loginBox.insertBefore(alertDiv, loginBox.querySelector('form'));
}
