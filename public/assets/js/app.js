class KaraokeApp {
    constructor() {
        this.baseUrl = window.location.origin;
        this.apiUrl = this.baseUrl + '/api';
        this.user = null;
        this.room = null;
        this.queue = [];
        this.ws = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initWebSocket();
        this.setupFlashMessages();
    }

    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupModalHandlers();
            this.setupFormHandlers();
            this.setupClickHandlers();
        });
    }

    setupModalHandlers() {
        const createRoomModal = document.getElementById('createRoomModal');
        const createRoomBtn = document.querySelector('.create-room-btn');
        
        if (createRoomBtn && createRoomModal) {
            createRoomBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showModal(createRoomModal);
            });
        }

        document.querySelectorAll('.modal-close').forEach(closeBtn => {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.hideAllModals();
            });
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.hideAllModals();
            }
        });
    }

    setupFormHandlers() {
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                this.handleAjaxSubmit(e.target);
            }
        });
    }

    setupClickHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.join-room-btn')) {
                e.preventDefault();
                const roomCode = e.target.dataset.roomCode;
                this.joinRoom(roomCode);
            }

            if (e.target.matches('.add-to-queue-btn')) {
                e.preventDefault();
                const songId = e.target.dataset.songId;
                const roomId = e.target.dataset.roomId;
                this.addToQueue(songId, roomId);
            }
        });
    }

    showModal(modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    hideAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }

    async handleAjaxSubmit(form) {
        const formData = new FormData(form);
        const url = form.action;
        const method = form.method || 'POST';

        try {
            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();
            
            if (result.success) {
                this.showSuccessMessage(result.message || 'Success!');
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                }
            } else {
                this.showErrorMessage(result.error || 'An error occurred');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showErrorMessage('Network error. Please try again.');
        }
    }

    showSuccessMessage(message) {
        this.flashMessage(message, 'success');
    }

    showErrorMessage(message) {
        this.flashMessage(message, 'error');
    }

    showInfoMessage(message) {
        this.flashMessage(message, 'info');
    }

    flashMessage(message, type = 'info') {
        const flashContainer = document.getElementById('flash-messages') || this.createFlashContainer();
        const flash = document.createElement('div');
        flash.className = `alert alert-${type}`;
        flash.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        flashContainer.appendChild(flash);
        
        setTimeout(() => {
            if (flash.parentElement) {
                flash.remove();
            }
        }, 5000);
    }

    createFlashContainer() {
        const container = document.createElement('div');
        container.id = 'flash-messages';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '10000';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        document.body.appendChild(container);
        return container;
    }

    setupFlashMessages() {
        const flashMessages = document.querySelectorAll('.alert');
        flashMessages.forEach(flash => {
            setTimeout(() => {
                if (flash.parentElement) {
                    flash.style.opacity = '0';
                    setTimeout(() => flash.remove(), 300);
                }
            }, 5000);
        });
    }

    async apiCall(endpoint, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const mergedOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(`${this.apiUrl}${endpoint}`, mergedOptions);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'API request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API call error:', error);
            throw error;
        }
    }

    async createRoom(data) {
        try {
            const response = await this.apiCall('/room/create', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            if (response.success) {
                this.showSuccessMessage('Room created successfully!');
                setTimeout(() => {
                    window.location.href = `/room/${response.room_code}`;
                }, 1000);
            }
            
            return response;
        } catch (error) {
            this.showErrorMessage(error.message);
            throw error;
        }
    }

    async joinRoom(roomCode) {
        window.location.href = `/room/join/${roomCode}`;
    }

    async addToQueue(songId, roomId) {
        const room = roomId || this.room?.id;
        if (!room) {
            this.showErrorMessage('Please join a room first');
            return;
        }

        try {
            const response = await this.apiCall(`/queue/${room}/add`, {
                method: 'POST',
                body: JSON.stringify({ song_id: songId })
            });
            
            if (response.success) {
                this.showSuccessMessage('Song added to queue!');
                this.broadcastQueueUpdate(room);
            }
            
            return response;
        } catch (error) {
            this.showErrorMessage(error.message);
            throw error;
        }
    }

    async searchSongs(query, type = 'all') {
        try {
            const response = await this.apiCall(`/songs/search?q=${encodeURIComponent(query)}&type=${type}`);
            return response;
        } catch (error) {
            this.showErrorMessage('Search failed: ' + error.message);
            throw error;
        }
    }

    initWebSocket() {
        const wsUrl = `ws://localhost:8080`;
        
        try {
            this.ws = new WebSocket(wsUrl);
            
            this.ws.onopen = () => {
                console.log('WebSocket connected');
                if (this.room) {
                    this.joinWebSocketRoom();
                }
            };
            
            this.ws.onmessage = (event) => {
                const message = JSON.parse(event.data);
                this.handleWebSocketMessage(message);
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
            
            this.ws.onclose = () => {
                console.log('WebSocket disconnected');
                setTimeout(() => {
                    this.initWebSocket();
                }, 5000);
            };
        } catch (error) {
            console.error('WebSocket initialization failed:', error);
        }
    }

    handleWebSocketMessage(message) {
        switch (message.type) {
            case 'queue_updated':
                this.updateQueueUI(message.data);
                break;
            case 'user_joined':
                this.handleUserJoined(message);
                break;
            case 'user_left':
                this.handleUserLeft(message);
                break;
            case 'chat_message':
                this.handleChatMessage(message);
                break;
            case 'scoring_update':
                this.handleScoringUpdate(message);
                break;
            default:
                console.log('Unknown message type:', message.type);
        }
    }

    joinWebSocketRoom() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN && this.room) {
            this.ws.send(JSON.stringify({
                type: 'join_room',
                room_id: this.room.id
            }));
        }
    }

    leaveWebSocketRoom() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type: 'leave_room'
            }));
        }
    }

    broadcastQueueUpdate(roomId) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type: 'queue_update',
                room_id: roomId,
                data: { timestamp: Date.now() }
            }));
        }
    }

    updateQueueUI(queueData) {
        const queueContainer = document.querySelector('.queue-container');
        if (queueContainer && queueData.queue) {
            this.renderQueue(queueData.queue);
        }
    }

    renderQueue(queueItems) {
        // Implementation would render the queue items
        console.log('Rendering queue:', queueItems);
    }

    handleUserJoined(message) {
        this.showInfoMessage('A user joined the room');
        // Update participants list
    }

    handleUserLeft(message) {
        this.showInfoMessage('A user left the room');
        // Update participants list
    }

    handleChatMessage(message) {
        this.addChatMessage(message);
    }

    handleScoringUpdate(message) {
        this.updateScoreDisplay(message);
    }

    addChatMessage(message) {
        const chatContainer = document.querySelector('.chat-messages');
        if (chatContainer) {
            const messageEl = document.createElement('div');
            messageEl.className = 'chat-message';
            messageEl.innerHTML = `
                <strong>User ${message.user_id}:</strong> ${message.message}
                <small>${new Date(message.timestamp * 1000).toLocaleTimeString()}</small>
            `;
            chatContainer.appendChild(messageEl);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }

    updateScoreDisplay(scoreData) {
        const scoreElement = document.getElementById('current-score');
        if (scoreElement) {
            scoreElement.textContent = Math.round(scoreData.score);
        }
    }

    // Scoring functions
    startScoring(songId, roomId) {
        return this.apiCall('/scoring/start', {
            method: 'POST',
            body: JSON.stringify({ song_id: songId, room_id: roomId })
        });
    }

    updateScore(scoringId, pitchAccuracy, timingAccuracy, volumeStability, noteCompletion) {
        return this.apiCall('/scoring/update', {
            method: 'POST',
            body: JSON.stringify({
                scoring_id: scoringId,
                pitch_accuracy: pitchAccuracy,
                timing_accuracy: timingAccuracy,
                volume_stability: volumeStability,
                note_completion: noteCompletion
            })
        });
    }

    endScoring(scoringId) {
        return this.apiCall('/scoring/end', {
            method: 'POST',
            body: JSON.stringify({ scoring_id: scoringId })
        });
    }

    // Utility functions
    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    formatDuration(duration) {
        if (typeof duration === 'string') return duration;
        return this.formatTime(duration);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showLoading(element) {
        const loadingEl = document.createElement('div');
        loadingEl.className = 'loading-spinner';
        loadingEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        element.appendChild(loadingEl);
    }

    hideLoading(element) {
        const loadingEl = element.querySelector('.loading-spinner');
        if (loadingEl) loadingEl.remove();
    }
}

// Initialize app
const karaokeApp = new KaraokeApp();

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = KaraokeApp;
}