function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    const sidebar = document.getElementById('sidebar');
    if (sidebar && window.innerWidth <= 968) {
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !e.target.closest('.mobile-menu-toggle')) {
                sidebar.classList.remove('active');
            }
        });
    }
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registered:', registration.scope);
            })
            .catch(err => {
                console.log('ServiceWorker registration failed:', err);
            });
    });
}

function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

function printReport() {
    window.print();
}

window.addEventListener('resize', () => {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && window.innerWidth > 968) {
        sidebar.classList.remove('active');
    }
});
