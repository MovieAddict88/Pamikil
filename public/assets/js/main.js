/**
 * Main JavaScript for Karaoke App
 */

// ===== GLOBAL VARIABLES =====
let currentUser = null;
let currentRoom = null;
let webSocket = null;
let pollingInterval = null;
let youtubePlayer = null;

// ===== UTILITY FUNCTIONS =====
function $(selector) {
    return document.querySelector(selector);
}

function $$(selector) {
    return document.querySelectorAll(selector);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDuration(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

function showToast(message, type = 'success', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${getToastIcon(type)}"></i>
            <span>${escapeHtml(message)}</span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Auto remove
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

function getToastIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        return navigator.clipboard.writeText(text);
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        return Promise.resolve();
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    setupMobileMenu();
    setupTooltips();
    setupFlashMessages();
    setupFormValidation();
    loadCurrentUser();
    
    // Initialize room-specific functionality if we're on a room page
    if (window.location.pathname.startsWith('/room/')) {
        initializeRoom();
    }
}

function loadCurrentUser() {
    const userData = document.querySelector('meta[name="user-data"]');
    if (userData) {
        try {
            currentUser = JSON.parse(userData.content);
        } catch (e) {
            console.error('Failed to parse user data:', e);
        }
    }
}

function initializeRoom() {
    const roomDataElement = document.querySelector('meta[name="room-data"]');
    if (roomDataElement) {
        try {
            currentRoom = JSON.parse(roomDataElement.content);
            setupRealTimeUpdates();
            initializeVideoPlayer();
        } catch (e) {
            console.error('Failed to parse room data:', e);
        }
    }
}

// ===== MOBILE MENU =====
function setupMobileMenu() {
    const mobileMenuToggle = $('#mobileMenuToggle');
    const navMenu = $('.nav-menu');
    
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('mobile-open');
            const icon = this.querySelector('i');
            if (navMenu.classList.contains('mobile-open')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenuToggle.contains(event.target) && !navMenu.contains(event.target)) {
                navMenu.classList.remove('mobile-open');
                const icon = mobileMenuToggle.querySelector('i');
                icon.className = 'fas fa-bars';
            }
        });
    }
}

// ===== TOOLTIPS =====
function setupTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(event) {
    const element = event.target;
    const text = element.getAttribute('title');
    
    if (!text) return;
    
    // Remove existing tooltip
    hideTooltip();
    
    // Create tooltip
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    document.body.appendChild(tooltip);
    
    // Position tooltip
    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltipRect.width / 2) + 'px';
    tooltip.style.top = rect.top - tooltipRect.height - 5 + 'px';
    
    // Show tooltip
    setTimeout(() => tooltip.classList.add('show'), 10);
    
    // Store reference
    element._tooltip = tooltip;
}

function hideTooltip() {
    const tooltips = document.querySelectorAll('.tooltip');
    tooltips.forEach(tooltip => tooltip.remove());
}

// ===== FLASH MESSAGES =====
function setupFlashMessages() {
    const flashContainer = $('.flash-container');
    if (flashContainer) {
        // Auto-hide flash messages after 5 seconds
        setTimeout(() => {
            const messages = flashContainer.querySelectorAll('.flash-message');
            messages.forEach(message => {
                setTimeout(() => {
                    if (message.parentElement) {
                        message.classList.remove('show');
                        setTimeout(() => message.remove(), 300);
                    }
                }, 100);
            });
        }, 5000);
    }
}

// ===== FORM VALIDATION =====
function setupFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!validateForm(this)) {
                event.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', debounce(() => validateField(input), 500));
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    // Required validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
    }
    
    // Min length validation
    if (field.hasAttribute('minlength') && value) {
        const minLength = parseInt(field.getAttribute('minlength'));
        if (value.length < minLength) {
            isValid = false;
            message = `Must be at least ${minLength} characters`;
        }
    }
    
    // Max length validation
    if (field.hasAttribute('maxlength') && value) {
        const maxLength = parseInt(field.getAttribute('maxlength'));
        if (value.length > maxLength) {
            isValid = false;
            message = `Must be no more than ${maxLength} characters`;
        }
    }
    
    // Password confirmation
    if (field.id === 'confirm_password') {
        const password = document.getElementById('password').value;
        if (value && password !== value) {
            isValid = false;
            message = 'Passwords do not match';
        }
    }
    
    // Update field state
    updateFieldValidation(field, isValid, message);
    
    return isValid;
}

function updateFieldValidation(field, isValid, message) {
    // Remove existing validation classes and messages
    field.classList.remove('is-valid', 'is-invalid');
    const existingFeedback = field.parentNode.querySelector('.invalid-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }
    
    // Add validation class
    field.classList.add(isValid ? 'is-valid' : 'is-invalid');
    
    // Add feedback message for invalid fields
    if (!isValid && message) {
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        field.parentNode.appendChild(feedback);
    }
}

// ===== REAL-TIME UPDATES =====
function setupRealTimeUpdates() {
    if (!currentRoom) return;
    
    // Try to establish WebSocket connection first
    if (WebSocket) {
        setupWebSocket();
    }
    
    // Fallback to polling if WebSocket fails
    setupPolling();
}

function setupWebSocket() {
    const wsUrl = getWebSocketUrl();
    
    try {
        webSocket = new WebSocket(wsUrl);
        
        webSocket.onopen = function(event) {
            console.log('WebSocket connected');
            showToast('Connected to real-time updates', 'success', 2000);
            
            // Join room
            sendWebSocketMessage({
                type: 'join_room',
                room_id: currentRoom.id,
                user_id: currentUser.id
            });
        };
        
        webSocket.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                handleWebSocketMessage(data);
            } catch (e) {
                console.error('Failed to parse WebSocket message:', e);
            }
        };
        
        webSocket.onclose = function(event) {
            console.log('WebSocket disconnected');
            showToast('Real-time connection lost', 'warning', 3000);
            
            // Fallback to polling
            setupPolling();
        };
        
        webSocket.onerror = function(error) {
            console.error('WebSocket error:', error);
            showToast('Real-time connection failed', 'error', 3000);
            
            // Fallback to polling
            setupPolling();
        };
        
    } catch (e) {
        console.error('Failed to create WebSocket connection:', e);
        setupPolling();
    }
}

function setupPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    
    pollingInterval = setInterval(() => {
        fetchRoomUpdates();
    }, 5000); // Poll every 5 seconds
}

function fetchRoomUpdates() {
    if (!currentRoom) return;
    
    fetch(`/api/room/${currentRoom.code}/queue`)
        .then(response => response.json())
        .then(data => {
            updateRoomDisplay(data);
        })
        .catch(error => {
            console.error('Failed to fetch room updates:', error);
        });
}

function handleWebSocketMessage(data) {
    switch (data.type) {
        case 'queue_update':
            updateQueue(data.queue);
            break;
        case 'current_song_update':
            updateCurrentSong(data.song);
            break;
        case 'participant_update':
            updateParticipants(data.participants);
            break;
        case 'room_settings_update':
            updateRoomSettings(data.settings);
            break;
        case 'chat_message':
            addChatMessage(data.message);
            break;
        default:
            console.log('Unknown WebSocket message type:', data.type);
    }
}

function sendWebSocketMessage(data) {
    if (webSocket && webSocket.readyState === WebSocket.OPEN) {
        webSocket.send(JSON.stringify(data));
    }
}

function getWebSocketUrl() {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const host = window.location.host;
    return `${protocol}//${host}/ws`;
}

function updateRoomDisplay(data) {
    if (data.queue) {
        updateQueue(data.queue);
    }
    
    if (data.currentSong) {
        updateCurrentSong(data.currentSong);
    }
    
    if (data.participants) {
        updateParticipants(data.participants);
    }
}

// ===== QUEUE MANAGEMENT =====
function updateQueue(queue) {
    const queueList = document.getElementById('queueList');
    if (!queueList) return;
    
    queueList.innerHTML = queue.map(item => `
        <div class="queue-item ${item.status}">
            <div class="queue-number">${item.position}</div>
            <div class="queue-song-info">
                <h4>${escapeHtml(item.title)}</h4>
                <p>${escapeHtml(item.artist)}</p>
                <div class="queue-meta">
                    <img src="${item.avatar_url || '/assets/images/default-avatar.png'}" 
                         alt="Requester" class="requester-avatar">
                    <span>${escapeHtml(item.display_name)}</span>
                    ${item.duration ? `<span class="duration"><i class="fas fa-clock"></i>${formatDuration(item.duration)}</span>` : ''}
                </div>
            </div>
            ${(currentRoom?.isModerator || item.user_id == currentUser?.id) ? `
            <div class="queue-actions">
                <button class="action-btn" onclick="removeFromQueue(${item.id})" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>` : ''}
        </div>
    `).join('');
    
    // Update queue stats
    updateQueueStats();
}

function updateQueueStats() {
    const queueItems = document.querySelectorAll('.queue-item');
    const queuedItems = Array.from(queueItems).filter(item => 
        item.classList.contains('queued')
    );
    
    const statsElement = document.querySelector('.queue-stats');
    if (statsElement) {
        statsElement.textContent = `${queuedItems.length} queued`;
    }
}

function addToQueue(songId, roomCode = null) {
    const targetRoom = roomCode || currentRoom?.code;
    if (!targetRoom) {
        showToast('Room not found', 'error');
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        showToast('Security token missing', 'error');
        return;
    }
    
    fetch(`/api/room/${targetRoom}/queue`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ song_id: songId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Song added to queue!');
            
            // Send WebSocket message for real-time update
            if (webSocket && webSocket.readyState === WebSocket.OPEN) {
                sendWebSocketMessage({
                    type: 'queue_added',
                    song_id: songId,
                    room_id: currentRoom.id
                });
            }
        } else {
            showToast('Failed to add song: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error adding song to queue:', error);
        showToast('Failed to add song to queue', 'error');
    });
}

function removeFromQueue(queueId) {
    if (!confirm('Remove this song from the queue?')) {
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    fetch(`/api/room/${currentRoom.code}/queue?id=${queueId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-Token': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Song removed from queue');
            
            // Send WebSocket message for real-time update
            if (webSocket && webSocket.readyState === WebSocket.OPEN) {
                sendWebSocketMessage({
                    type: 'queue_removed',
                    queue_id: queueId,
                    room_id: currentRoom.id
                });
            }
        } else {
            showToast('Failed to remove song', 'error');
        }
    })
    .catch(error => {
        console.error('Error removing song from queue:', error);
        showToast('Failed to remove song', 'error');
    });
}

// ===== VIDEO PLAYER =====
function initializeVideoPlayer() {
    const videoContainer = document.getElementById('videoPlayer');
    if (!videoContainer || !currentRoom?.currentSong) return;
    
    const song = currentRoom.currentSong;
    
    if (song.youtube_video_id) {
        initializeYouTubePlayer(song.youtube_video_id);
    } else if (song.file_path) {
        initializeHTML5Video(song.file_path);
    } else if (song.embed_code) {
        // Embed code is already in the HTML
        initializeEmbeddedPlayer();
    }
}

function initializeYouTubePlayer(videoId) {
    // Check if YouTube API is loaded
    if (typeof YT === 'undefined' || !YT.Player) {
        console.log('YouTube API not loaded, will retry...');
        setTimeout(() => initializeYouTubePlayer(videoId), 1000);
        return;
    }
    
    youtubePlayer = new YT.Player('youtubePlayer', {
        height: '100%',
        width: '100%',
        videoId: videoId,
        playerVars: {
            autoplay: 1,
            controls: 0,
            disablekb: 1,
            enablejsapi: 1,
            fs: 0,
            iv_load_policy: 3,
            modestbranding: 1,
            playsinline: 1,
            rel: 0,
            start: currentRoom.currentSong.start_time || 0
        },
        events: {
            'onReady': onYouTubePlayerReady,
            'onStateChange': onYouTubePlayerStateChange,
            'onError': onYouTubePlayerError
        }
    });
}

function initializeHTML5Video(videoSrc) {
    const video = document.getElementById('html5Video');
    if (video) {
        video.src = videoSrc;
        video.load();
        
        video.addEventListener('loadedmetadata', function() {
            console.log('Video metadata loaded');
        });
        
        video.addEventListener('play', function() {
            updatePlayPauseButton('pause');
        });
        
        video.addEventListener('pause', function() {
            updatePlayPauseButton('play');
        });
        
        video.addEventListener('ended', function() {
            handleVideoEnd();
        });
    }
}

function initializeEmbeddedPlayer() {
    // Embedded players (like Vimeo or other iframe embeds) 
    // don't need special initialization
    console.log('Embedded player initialized');
}

function onYouTubePlayerReady(event) {
    console.log('YouTube player ready');
    event.target.playVideo();
    updatePlayPauseButton('pause');
}

function onYouTubePlayerStateChange(event) {
    const states = {
        [YT.PlayerState.UNSTARTED]: 'unstarted',
        [YT.PlayerState.ENDED]: 'ended',
        [YT.PlayerState.PLAYING]: 'playing',
        [YT.PlayerState.PAUSED]: 'paused',
        [YT.PlayerState.BUFFERING]: 'buffering',
        [YT.PlayerState.CUED]: 'cued'
    };
    
    console.log('YouTube player state:', states[event.data]);
    
    switch (event.data) {
        case YT.PlayerState.PLAYING:
            updatePlayPauseButton('pause');
            startProgressTracking();
            break;
        case YT.PlayerState.PAUSED:
            updatePlayPauseButton('play');
            stopProgressTracking();
            break;
        case YT.PlayerState.ENDED:
            handleVideoEnd();
            break;
    }
}

function onYouTubePlayerError(event) {
    console.error('YouTube player error:', event.data);
    showToast('Video playback error', 'error');
}

function updatePlayPauseButton(state) {
    const playPauseBtn = document.getElementById('playPauseBtn');
    if (playPauseBtn) {
        const icon = playPauseBtn.querySelector('i');
        icon.className = state === 'pause' ? 'fas fa-pause' : 'fas fa-play';
    }
}

function togglePlayPause() {
    if (youtubePlayer) {
        const state = youtubePlayer.getPlayerState();
        if (state === YT.PlayerState.PLAYING) {
            youtubePlayer.pauseVideo();
        } else {
            youtubePlayer.playVideo();
        }
    } else {
        const video = document.getElementById('html5Video');
        if (video) {
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        }
    }
}

function skipSong() {
    if (!confirm('Skip the current song?')) {
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    fetch(`/api/room/${currentRoom.code}/queue/skip`, {
        method: 'POST',
        headers: {
            'X-CSRF-Token': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            showToast('Failed to skip song', 'error');
        }
    })
    .catch(error => {
        console.error('Error skipping song:', error);
        showToast('Failed to skip song', 'error');
    });
}

let progressInterval = null;

function startProgressTracking() {
    if (progressInterval) {
        clearInterval(progressInterval);
    }
    
    progressInterval = setInterval(() => {
        updateProgress();
    }, 1000);
}

function stopProgressTracking() {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
}

function updateProgress() {
    if (!currentRoom?.currentSong) return;
    
    let currentTime = 0;
    let duration = currentRoom.currentSong.duration || 0;
    
    if (youtubePlayer) {
        currentTime = youtubePlayer.getCurrentTime();
        duration = youtubePlayer.getDuration();
    } else {
        const video = document.getElementById('html5Video');
        if (video) {
            currentTime = video.currentTime;
            duration = video.duration;
        }
    }
    
    // Update progress bar
    const progressFill = document.getElementById('songProgress');
    if (progressFill && duration > 0) {
        const progress = (currentTime / duration) * 100;
        progressFill.style.width = progress + '%';
    }
    
    // Update time display
    const currentTimeElement = document.getElementById('currentTime');
    const totalTimeElement = document.getElementById('totalTime');
    
    if (currentTimeElement) {
        currentTimeElement.textContent = formatDuration(Math.floor(currentTime));
    }
    
    if (totalTimeElement && duration) {
        totalTimeElement.textContent = formatDuration(Math.floor(duration));
    }
}

function handleVideoEnd() {
    showToast('Song finished', 'info', 2000);
    
    // Send WebSocket message for video end
    if (webSocket && webSocket.readyState === WebSocket.OPEN) {
        sendWebSocketMessage({
            type: 'song_ended',
            room_id: currentRoom.id
        });
    }
    
    // Auto-advance to next song if enabled
    if (currentRoom.settings?.autoplay) {
        setTimeout(() => {
            skipSong();
        }, 2000);
    }
}

// ===== SONG BROWSER =====
function openSongBrowser() {
    const modal = document.getElementById('songBrowserModal');
    if (modal) {
        modal.style.display = 'block';
        loadInitialSongs();
    }
}

function closeSongBrowser() {
    const modal = document.getElementById('songBrowserModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function switchSongTab(tab) {
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Clear search input for non-search tabs
    if (tab !== 'search') {
        const searchInput = document.getElementById('songSearch');
        if (searchInput) {
            searchInput.value = '';
            searchInput.type = 'text';
            searchInput.placeholder = 'Search songs...';
        }
    }
    
    // Load appropriate songs
    switch(tab) {
        case 'popular':
            loadPopularSongs();
            break;
        case 'recent':
            loadRecentSongs();
            break;
        case 'by-number':
            showSongNumberInput();
            break;
        default:
            // Search tab - user needs to enter search term
            break;
    }
}

function searchSongs() {
    const query = document.getElementById('songSearch')?.value;
    if (!query || !query.trim()) {
        showToast('Please enter a search term', 'warning');
        return;
    }
    
    fetch(`/api/songs/search?q=${encodeURIComponent(query.trim())}`)
        .then(response => response.json())
        .then(data => {
            if (data.results) {
                displaySongs(data.results);
            } else {
                displaySongs([]);
            }
        })
        .catch(error => {
            console.error('Error searching songs:', error);
            showToast('Failed to search songs', 'error');
        });
}

function loadInitialSongs() {
    loadPopularSongs();
}

function loadPopularSongs() {
    fetch('/api/songs/popular')
        .then(response => response.json())
        .then(data => {
            if (data.songs) {
                displaySongs(data.songs);
            } else {
                displaySongs([]);
            }
        })
        .catch(error => {
            console.error('Error loading popular songs:', error);
            showToast('Failed to load popular songs', 'error');
        });
}

function loadRecentSongs() {
    fetch('/api/songs/recent')
        .then(response => response.json())
        .then(data => {
            if (data.songs) {
                displaySongs(data.songs);
            } else {
                displaySongs([]);
            }
        })
        .catch(error => {
            console.error('Error loading recent songs:', error);
            showToast('Failed to load recent songs', 'error');
        });
}

function showSongNumberInput() {
    const searchInput = document.getElementById('songSearch');
    if (searchInput) {
        searchInput.type = 'number';
        searchInput.placeholder = 'Enter song number...';
        searchInput.min = '1';
        searchInput.focus();
    }
}

function displaySongs(songs) {
    const container = document.getElementById('songResults');
    if (!container) return;
    
    if (songs.length === 0) {
        container.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>No songs found</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = songs.map(song => `
        <div class="song-browser-item">
            <div class="song-info">
                <div class="song-number">#${song.song_number}</div>
                <div class="song-details">
                    <h4>${escapeHtml(song.title)}</h4>
                    <p>${escapeHtml(song.artist)}</p>
                    ${song.duration ? `<small class="duration">${formatDuration(song.duration)}</small>` : ''}
                    ${song.categories ? `<small class="categories">${escapeHtml(song.categories)}</small>` : ''}
                </div>
            </div>
            <button class="btn btn-sm btn-primary" onclick="addSongToQueue(${song.id})">
                <i class="fas fa-plus"></i>
                Add
            </button>
        </div>
    `).join('');
}

// ===== ROOM MANAGEMENT =====
function copyRoomCode() {
    const roomCode = currentRoom?.code;
    if (!roomCode) {
        showToast('Room code not found', 'error');
        return;
    }
    
    copyToClipboard(roomCode)
        .then(() => {
            showToast('Room code copied to clipboard!');
        })
        .catch(() => {
            showToast('Failed to copy room code', 'error');
        });
}

// ===== PARTICIPANT MANAGEMENT =====
function updateParticipants(participants) {
    const participantsList = document.querySelector('.participants-list');
    if (!participantsList) return;
    
    participantsList.innerHTML = participants.map(participant => `
        <div class="participant-item ${participant.role}">
            <img src="${participant.avatar_url || '/assets/images/default-avatar.png'}" 
                 alt="${escapeHtml(participant.display_name)}" 
                 class="participant-avatar">
            <div class="participant-info">
                <span class="participant-name">${escapeHtml(participant.display_name)}</span>
                ${participant.role !== 'participant' ? `
                <span class="participant-role">
                    <i class="fas fa-${participant.role === 'host' ? 'crown' : 'shield-alt'}"></i>
                    ${ucfirst(participant.role)}
                </span>` : ''}
            </div>
        </div>
    `).join('');
}

// ===== UTILITY FUNCTIONS =====
function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// ===== ROOM SETTINGS =====
<?php if (isset($userRole) && $userRole === 'host'): ?>
function openRoomSettings() {
    const modal = document.getElementById('roomSettingsModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeRoomSettings() {
    const modal = document.getElementById('roomSettingsModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function saveRoomSettings() {
    const form = document.getElementById('roomSettingsForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const settings = Object.fromEntries(formData.entries());
    
    // Convert checkboxes to booleans
    settings.autoplay = form.querySelector('input[name="autoplay"]').checked;
    settings.allow_duets = form.querySelector('input[name="allow_duets"]').checked;
    settings.require_approval = form.querySelector('input[name="require_approval"]').checked;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    fetch(`/api/room/${currentRoom.code}/settings`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Settings updated successfully');
            closeRoomSettings();
            
            // Send WebSocket message for settings update
            if (webSocket && webSocket.readyState === WebSocket.OPEN) {
                sendWebSocketMessage({
                    type: 'room_settings_updated',
                    settings: settings,
                    room_id: currentRoom.id
                });
            }
        } else {
            showToast('Failed to update settings', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving room settings:', error);
        showToast('Failed to update settings', 'error');
    });
}
<?php endif; ?>

// ===== GLOBAL ERROR HANDLING =====
window.addEventListener('error', function(event) {
    console.error('Global error:', event.error);
    showToast('An unexpected error occurred', 'error');
});

window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    showToast('An unexpected error occurred', 'error');
});

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', function(event) {
    // ESC key to close modals
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    }
    
    // Spacebar to play/pause (when in room)
    if (event.key === ' ' && currentRoom && event.target.tagName !== 'INPUT' && event.target.tagName !== 'TEXTAREA') {
        event.preventDefault();
        if (document.getElementById('playPauseBtn')) {
            togglePlayPause();
        }
    }
});

// ===== PERFORMANCE MONITORING =====
if ('performance' in window) {
    window.addEventListener('load', function() {
        setTimeout(() => {
            const perfData = performance.getEntriesByType('navigation')[0];
            console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
        }, 0);
    });
}

// Export functions for global use
window.KaraokeApp = {
    addToQueue,
    removeFromQueue,
    skipSong,
    togglePlayPause,
    copyRoomCode,
    openSongBrowser,
    closeSongBrowser,
    searchSongs,
    showToast,
    <?php if (isset($userRole) && $userRole === 'host'): ?>
    openRoomSettings,
    closeRoomSettings,
    saveRoomSettings,
    <?php endif; ?>
};