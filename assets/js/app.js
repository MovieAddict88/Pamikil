let deferredPrompt;
let installButton = document.getElementById('installBtn');
let installBanner = document.getElementById('installBanner');

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

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  
  if (installButton) {
    installButton.style.display = 'inline-flex';
  }
  
  const dismissed = localStorage.getItem('installBannerDismissed');
  if (!dismissed && installBanner) {
    setTimeout(() => {
      installBanner.classList.add('show');
    }, 3000);
  }
});

function showInstallPrompt() {
  if (installButton) {
    installButton.style.display = 'inline-flex';
  }
}

function installPWA() {
  if (!deferredPrompt) {
    alert('App installation is not available. Please use Chrome, Edge, or Safari.');
    return;
  }
  
  deferredPrompt.prompt();
  
  deferredPrompt.userChoice.then((choiceResult) => {
    if (choiceResult.outcome === 'accepted') {
      console.log('User accepted the install prompt');
      if (installBanner) {
        installBanner.classList.remove('show');
      }
    } else {
      console.log('User dismissed the install prompt');
    }
    deferredPrompt = null;
  });
}

function dismissInstallBanner() {
  if (installBanner) {
    installBanner.classList.remove('show');
    localStorage.setItem('installBannerDismissed', 'true');
  }
}

window.addEventListener('appinstalled', () => {
  console.log('PWA was installed');
  if (installButton) {
    installButton.style.display = 'none';
  }
  if (installBanner) {
    installBanner.classList.remove('show');
  }
});

function toggleMobileMenu() {
  const menu = document.getElementById('navbarMenu');
  if (menu) {
    menu.classList.toggle('active');
  }
}

function openInquiryModal(carId, carName) {
  const modal = document.getElementById('inquiryModal');
  if (modal) {
    document.getElementById('inquiry_car_id').value = carId;
    document.getElementById('carName').textContent = carName;
    modal.classList.add('active');
  }
}

function closeInquiryModal() {
  const modal = document.getElementById('inquiryModal');
  if (modal) {
    modal.classList.remove('active');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const inquiryModal = document.getElementById('inquiryModal');
  if (inquiryModal) {
    inquiryModal.addEventListener('click', (e) => {
      if (e.target === inquiryModal) {
        closeInquiryModal();
      }
    });
  }
  
  const successParam = new URLSearchParams(window.location.search).get('success');
  const errorParam = new URLSearchParams(window.location.search).get('error');
  
  if (successParam) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.innerHTML = '<i class="fas fa-check-circle"></i> ' + decodeURIComponent(successParam);
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '10000';
    alert.style.maxWidth = '400px';
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 5000);
  }
  
  if (errorParam) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + decodeURIComponent(errorParam);
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '10000';
    alert.style.maxWidth = '400px';
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 5000);
  }
});

if (window.matchMedia('(display-mode: standalone)').matches) {
  console.log('Running as installed PWA');
}

window.addEventListener('online', () => {
  console.log('Back online');
});

window.addEventListener('offline', () => {
  console.log('Connection lost');
});
