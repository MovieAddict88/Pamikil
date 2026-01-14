<?php
/**
 * Footer Template
 * Car Management System - Pamikil
 */
?>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global JavaScript functions

// Confirm before delete
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Format currency
function formatCurrency(amount) {
    return '<?php echo CURRENCY; ?> ' + parseFloat(amount).toFixed(<?php echo DECIMAL_PLACES; ?>).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Validate mobile number (Philippine format)
function validatePHMobileNumber(phone) {
    const cleanPhone = phone.replace(/\D/g, '');
    if (/^09\d{9}$/.test(cleanPhone)) return true;
    if (/^639\d{9}$/.test(cleanPhone)) return true;
    return false;
}

// Format mobile number
function formatPHMobileNumber(phone) {
    const cleanPhone = phone.replace(/\D/g, '');
    
    if (/^639(\d{2})(\d{3})(\d{4})$/.test(cleanPhone)) {
        return cleanPhone.replace(/^63/, '0').replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
    }
    
    if (/^09(\d{2})(\d{3})(\d{4})$/.test(cleanPhone)) {
        return cleanPhone.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
    }
    
    return phone;
}

// Image preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard!');
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
    });
}

// Print page
function printPage() {
    window.print();
}

// Export to CSV (client-side)
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    
    let csv = [];
    
    for (const row of rows) {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        
        for (const col of cols) {
            // Skip action columns
            if (col.classList.contains('no-export')) continue;
            
            let text = col.textContent.trim();
            // Escape quotes and wrap in quotes
            text = '"' + text.replace(/"/g, '""') + '"';
            rowData.push(text);
        }
        
        csv.push(rowData.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (navigator.msSaveBlob) {
        navigator.msSaveBlob(blob, filename);
    } else {
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide flash messages
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert.classList.contains('alert-dismissible')) {
                const closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) closeBtn.click();
            }
        }, 5000);
    });
    
    // Format phone numbers on input
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (validatePHMobileNumber(this.value)) {
                this.value = formatPHMobileNumber(this.value);
            }
        });
    });
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});
</script>
</body>
</html>
