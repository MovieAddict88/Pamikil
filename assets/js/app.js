// PWA Installation
let deferredPrompt;
const installBtn = document.getElementById('installBtn');
const installPrompt = document.getElementById('installPrompt');
const installPromptBtn = document.getElementById('installPromptBtn');
const closePrompt = document.getElementById('closePrompt');

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registered:', registration.scope);
            })
            .catch(error => {
                console.log('ServiceWorker registration failed:', error);
            });
    });
}

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    installBtn.style.display = 'block';
    
    setTimeout(() => {
        if (!localStorage.getItem('installPromptClosed')) {
            installPrompt.style.display = 'flex';
        }
    }, 5000);
});

installBtn.addEventListener('click', () => {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted the install prompt');
                installPrompt.style.display = 'none';
            }
            deferredPrompt = null;
        });
    }
});

installPromptBtn.addEventListener('click', () => {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            installPrompt.style.display = 'none';
            deferredPrompt = null;
        });
    }
});

closePrompt.addEventListener('click', () => {
    installPrompt.style.display = 'none';
    localStorage.setItem('installPromptClosed', 'true');
});

window.addEventListener('appinstalled', () => {
    console.log('PWA was installed');
    installBtn.style.display = 'none';
    installPrompt.style.display = 'none';
});

// App State
const appState = {
    content: [],
    categories: [],
    genres: [],
    years: [],
    currentView: 'grid',
    filters: {
        category: '',
        type: '',
        genre: '',
        year: '',
        search: ''
    },
    currentPage: 1,
    carousel: {
        current: 0,
        items: [],
        autoplayInterval: null
    },
    currentContent: null
};

// Theme Toggle
const themeToggle = document.getElementById('themeToggle');
const savedTheme = localStorage.getItem('theme') || 'dark';

if (savedTheme === 'light') {
    document.body.classList.add('light-theme');
    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
}

themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('light-theme');
    const isLight = document.body.classList.contains('light-theme');
    themeToggle.innerHTML = isLight ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    localStorage.setItem('theme', isLight ? 'light' : 'dark');
});

// Header Scroll Effect
const header = document.getElementById('header');
window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Initialize App
async function init() {
    await loadCategories();
    await loadGenres();
    await loadYears();
    await loadFeaturedContent();
    await loadContent();
}

// Load Categories
async function loadCategories() {
    try {
        const response = await fetch('api/categories.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            appState.categories = data.data;
            populateCategoryFilter();
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

function populateCategoryFilter() {
    const categoryFilter = document.getElementById('categoryFilter');
    appState.categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        categoryFilter.appendChild(option);
    });
}

// Load Genres
async function loadGenres() {
    try {
        const response = await fetch('api/content.php?action=genres');
        const data = await response.json();
        
        if (data.success) {
            appState.genres = data.data;
            populateGenreFilter();
        }
    } catch (error) {
        console.error('Error loading genres:', error);
    }
}

function populateGenreFilter() {
    const genreFilter = document.getElementById('genreFilter');
    appState.genres.forEach(genre => {
        const option = document.createElement('option');
        option.value = genre;
        option.textContent = genre;
        genreFilter.appendChild(option);
    });
}

// Load Years
async function loadYears() {
    try {
        const response = await fetch('api/content.php?action=years');
        const data = await response.json();
        
        if (data.success) {
            appState.years = data.data;
            populateYearFilter();
        }
    } catch (error) {
        console.error('Error loading years:', error);
    }
}

function populateYearFilter() {
    const yearFilter = document.getElementById('yearFilter');
    appState.years.forEach(year => {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        yearFilter.appendChild(option);
    });
}

// Load Featured Content for Carousel
async function loadFeaturedContent() {
    try {
        const response = await fetch('api/content.php?action=featured');
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            appState.carousel.items = data.data;
            renderCarousel();
            startCarouselAutoplay();
        }
    } catch (error) {
        console.error('Error loading featured content:', error);
    }
}

function renderCarousel() {
    const carouselInner = document.getElementById('carouselInner');
    const carouselIndicators = document.getElementById('carouselIndicators');
    
    carouselInner.innerHTML = '';
    carouselIndicators.innerHTML = '';
    
    appState.carousel.items.forEach((item, index) => {
        const carouselItem = document.createElement('div');
        carouselItem.className = 'carousel-item';
        carouselItem.innerHTML = `
            <img src="${item.backdrop_url || item.poster_url || 'assets/images/placeholder.jpg'}" alt="${escapeHtml(item.title)}">
            <div class="carousel-content">
                <h2>${escapeHtml(item.title)}</h2>
                <p>${escapeHtml(item.description || '')}</p>
                <button class="btn btn-primary" onclick="openPlayer(${item.id})">
                    <i class="fas fa-play"></i> Watch Now
                </button>
            </div>
        `;
        carouselInner.appendChild(carouselItem);
        
        const indicator = document.createElement('div');
        indicator.className = 'indicator' + (index === 0 ? ' active' : '');
        indicator.addEventListener('click', () => goToSlide(index));
        carouselIndicators.appendChild(indicator);
    });
}

function goToSlide(index) {
    const carouselInner = document.getElementById('carouselInner');
    const indicators = document.querySelectorAll('.indicator');
    
    appState.carousel.current = index;
    carouselInner.style.transform = `translateX(-${index * 100}%)`;
    
    indicators.forEach((indicator, i) => {
        indicator.classList.toggle('active', i === index);
    });
    
    resetCarouselAutoplay();
}

document.getElementById('prevBtn').addEventListener('click', () => {
    const prev = (appState.carousel.current - 1 + appState.carousel.items.length) % appState.carousel.items.length;
    goToSlide(prev);
});

document.getElementById('nextBtn').addEventListener('click', () => {
    const next = (appState.carousel.current + 1) % appState.carousel.items.length;
    goToSlide(next);
});

function startCarouselAutoplay() {
    appState.carousel.autoplayInterval = setInterval(() => {
        const next = (appState.carousel.current + 1) % appState.carousel.items.length;
        goToSlide(next);
    }, 5000);
}

function resetCarouselAutoplay() {
    if (appState.carousel.autoplayInterval) {
        clearInterval(appState.carousel.autoplayInterval);
        startCarouselAutoplay();
    }
}

// Load Content
async function loadContent() {
    const contentGrid = document.getElementById('contentGrid');
    contentGrid.innerHTML = '<div class="loading"><i class="fas fa-spinner"></i><p>Loading content...</p></div>';
    
    try {
        const params = new URLSearchParams({
            action: 'list',
            page: appState.currentPage,
            limit: 20
        });
        
        if (appState.filters.category) params.append('category_id', appState.filters.category);
        if (appState.filters.type) params.append('type', appState.filters.type);
        if (appState.filters.genre) params.append('genre', appState.filters.genre);
        if (appState.filters.year) params.append('year', appState.filters.year);
        if (appState.filters.search) params.append('search', appState.filters.search);
        
        const response = await fetch(`api/content.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            appState.content = data.data;
            renderContent();
        } else {
            contentGrid.innerHTML = '<div class="loading"><p>No content found</p></div>';
        }
    } catch (error) {
        console.error('Error loading content:', error);
        contentGrid.innerHTML = '<div class="loading"><p>Error loading content</p></div>';
    }
}

function renderContent() {
    const contentGrid = document.getElementById('contentGrid');
    contentGrid.innerHTML = '';
    
    if (appState.content.length === 0) {
        contentGrid.innerHTML = '<div class="loading"><p>No content found</p></div>';
        return;
    }
    
    appState.content.forEach(item => {
        const card = createContentCard(item);
        contentGrid.appendChild(card);
    });
    
    observeImages();
}

function createContentCard(item) {
    const card = document.createElement('div');
    card.className = `content-card ${appState.currentView}`;
    card.onclick = () => openPlayer(item.id);
    
    const badgeClass = item.type === 'movie' ? 'badge-movie' : item.type === 'series' ? 'badge-series' : 'badge-live';
    
    card.innerHTML = `
        <div class="card-img">
            <img data-src="${item.poster_url || 'assets/images/placeholder.jpg'}" alt="${escapeHtml(item.title)}">
            <div class="card-badge ${badgeClass}">${item.type.toUpperCase()}</div>
        </div>
        <div class="card-info">
            <h3 class="card-title">${escapeHtml(item.title)}</h3>
            <div class="card-meta">
                ${item.year ? `<span><i class="fas fa-calendar"></i> ${item.year}</span>` : ''}
                ${item.rating ? `<span><i class="fas fa-star"></i> ${item.rating}</span>` : ''}
                ${item.duration ? `<span><i class="fas fa-clock"></i> ${item.duration}</span>` : ''}
            </div>
        </div>
    `;
    
    return card;
}

// Lazy Loading Images
function observeImages() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px'
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// View Toggle
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const view = btn.dataset.view;
        appState.currentView = view;
        
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const contentGrid = document.getElementById('contentGrid');
        if (view === 'list') {
            contentGrid.className = 'content-list';
            contentGrid.style.display = 'flex';
        } else {
            contentGrid.className = 'content-grid';
            contentGrid.style.display = 'grid';
        }
        
        renderContent();
    });
});

// Filters
document.getElementById('categoryFilter').addEventListener('change', (e) => {
    appState.filters.category = e.target.value;
    appState.currentPage = 1;
    loadContent();
});

document.getElementById('typeFilter').addEventListener('change', (e) => {
    appState.filters.type = e.target.value;
    appState.currentPage = 1;
    loadContent();
});

document.getElementById('genreFilter').addEventListener('change', (e) => {
    appState.filters.genre = e.target.value;
    appState.currentPage = 1;
    loadContent();
});

document.getElementById('yearFilter').addEventListener('change', (e) => {
    appState.filters.year = e.target.value;
    appState.currentPage = 1;
    loadContent();
});

// Search
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const closeSearchBtn = document.getElementById('closeSearchBtn');
let searchTimeout;

searchInput.addEventListener('input', (e) => {
    const query = e.target.value.trim();
    
    if (query.length > 0) {
        closeSearchBtn.style.display = 'block';
    } else {
        closeSearchBtn.style.display = 'none';
        searchResults.style.display = 'none';
        return;
    }
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchContent(query);
    }, 300);
});

closeSearchBtn.addEventListener('click', () => {
    searchInput.value = '';
    searchResults.style.display = 'none';
    closeSearchBtn.style.display = 'none';
});

async function searchContent(query) {
    try {
        const response = await fetch(`api/content.php?action=list&search=${encodeURIComponent(query)}&limit=10`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            renderSearchResults(data.data);
        } else {
            searchResults.innerHTML = '<div class="search-result-item"><p>No results found</p></div>';
            searchResults.style.display = 'block';
        }
    } catch (error) {
        console.error('Error searching:', error);
    }
}

function renderSearchResults(results) {
    searchResults.innerHTML = '';
    
    results.forEach(item => {
        const resultItem = document.createElement('div');
        resultItem.className = 'search-result-item';
        resultItem.onclick = () => {
            openPlayer(item.id);
            searchResults.style.display = 'none';
            searchInput.value = '';
            closeSearchBtn.style.display = 'none';
        };
        
        resultItem.innerHTML = `
            <img src="${item.poster_url || 'assets/images/placeholder.jpg'}" alt="${escapeHtml(item.title)}">
            <div class="search-result-info">
                <h4>${escapeHtml(item.title)}</h4>
                <p>${item.year || ''} • ${item.type.toUpperCase()}</p>
            </div>
        `;
        
        searchResults.appendChild(resultItem);
    });
    
    searchResults.style.display = 'block';
}

// Video Player
async function openPlayer(contentId) {
    try {
        const response = await fetch(`api/content.php?action=get&id=${contentId}`);
        const data = await response.json();
        
        if (data.success) {
            appState.currentContent = data.data;
            renderPlayer();
            loadRelatedContent(contentId);
        }
    } catch (error) {
        console.error('Error loading content:', error);
    }
}

function renderPlayer() {
    const playerModal = document.getElementById('playerModal');
    const playerTitle = document.getElementById('playerTitle');
    const serversContent = document.getElementById('serversContent');
    const infoContent = document.getElementById('infoContent');
    
    playerTitle.textContent = appState.currentContent.title;
    
    serversContent.innerHTML = '';
    if (appState.currentContent.sources && appState.currentContent.sources.length > 0) {
        appState.currentContent.sources.forEach((source, index) => {
            const serverBtn = document.createElement('button');
            serverBtn.className = 'server-btn' + (index === 0 ? ' active' : '');
            serverBtn.textContent = `${source.server_name}${source.quality ? ' ' + source.quality : ''}`;
            serverBtn.onclick = () => loadVideo(source.url);
            serversContent.appendChild(serverBtn);
        });
        
        if (appState.currentContent.sources[0]) {
            loadVideo(appState.currentContent.sources[0].url);
        }
    } else {
        serversContent.innerHTML = '<p>No sources available</p>';
    }
    
    infoContent.innerHTML = `
        <h3>${escapeHtml(appState.currentContent.title)}</h3>
        <div class="card-meta" style="margin-bottom: 15px;">
            ${appState.currentContent.year ? `<span><i class="fas fa-calendar"></i> ${appState.currentContent.year}</span>` : ''}
            ${appState.currentContent.rating ? `<span><i class="fas fa-star"></i> ${appState.currentContent.rating}</span>` : ''}
            ${appState.currentContent.duration ? `<span><i class="fas fa-clock"></i> ${appState.currentContent.duration}</span>` : ''}
            <span><i class="fas fa-eye"></i> ${appState.currentContent.views || 0} views</span>
        </div>
        <p style="color: var(--gray); margin-bottom: 10px;">${escapeHtml(appState.currentContent.description || '')}</p>
        ${appState.currentContent.genre ? `<p><strong>Genre:</strong> ${escapeHtml(appState.currentContent.genre)}</p>` : ''}
        ${appState.currentContent.country ? `<p><strong>Country:</strong> ${escapeHtml(appState.currentContent.country)}</p>` : ''}
        ${appState.currentContent.language ? `<p><strong>Language:</strong> ${escapeHtml(appState.currentContent.language)}</p>` : ''}
    `;
    
    playerModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function loadVideo(url) {
    const videoFrame = document.getElementById('videoFrame');
    videoFrame.src = url;
    
    document.querySelectorAll('.server-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
}

async function loadRelatedContent(contentId) {
    try {
        const response = await fetch(`api/content.php?action=related&id=${contentId}`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            const relatedContent = document.getElementById('relatedContent');
            relatedContent.innerHTML = '';
            
            data.data.forEach(item => {
                const card = createContentCard(item);
                relatedContent.appendChild(card);
            });
            
            observeImages();
        }
    } catch (error) {
        console.error('Error loading related content:', error);
    }
}

document.getElementById('closePlayer').addEventListener('click', () => {
    const playerModal = document.getElementById('playerModal');
    const videoFrame = document.getElementById('videoFrame');
    
    playerModal.classList.remove('active');
    videoFrame.src = '';
    document.body.style.overflow = '';
});

// Player Tabs
document.querySelectorAll('.player-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const tabName = tab.dataset.tab;
        
        document.querySelectorAll('.player-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        
        document.getElementById('serversContent').style.display = tabName === 'servers' ? 'grid' : 'none';
        document.getElementById('infoContent').style.display = tabName === 'info' ? 'block' : 'none';
        document.getElementById('relatedContent').style.display = tabName === 'related' ? 'grid' : 'none';
    });
});

// Utility Functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
init();
