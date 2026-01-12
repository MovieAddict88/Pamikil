/**
 * Pamikil Learning Platform - Main JavaScript
 * Handles interactive elements, activities, and UI interactions
 */

// ============================================================
// Application State
// ============================================================
const App = {
    currentUser: null,
    currentActivity: null,
    activitySession: null,
    settings: {
        fontSize: 100,
        highContrast: false,
        audioFeedback: false,
        autoSave: true,
        autoSaveInterval: 30000 // 30 seconds
    },
    
    /**
     * Initialize application
     */
    init() {
        this.loadSettings();
        this.initNavigation();
        this.initAccessibility();
        this.initForms();
        this.initTooltips();
        
        // Start auto-save if on activity page
        if (document.querySelector('.activity-container')) {
            this.initAutoSave();
        }
    },
    
    /**
     * Load user settings from localStorage
     */
    loadSettings() {
        const saved = localStorage.getItem('pamikil_settings');
        if (saved) {
            try {
                this.settings = { ...this.settings, ...JSON.parse(saved) };
                this.applySettings();
            } catch (e) {
                console.error('Failed to load settings:', e);
            }
        }
    },
    
    /**
     * Save settings to localStorage
     */
    saveSettings() {
        localStorage.setItem('pamikil_settings', JSON.stringify(this.settings));
    },
    
    /**
     * Apply settings to DOM
     */
    applySettings() {
        // Font size
        document.documentElement.style.fontSize = `${this.settings.fontSize}%`;
        
        // High contrast
        if (this.settings.highContrast) {
            document.body.classList.add('high-contrast');
        } else {
            document.body.classList.remove('high-contrast');
        }
        
        // Audio feedback
        document.getElementById('audioFeedback').checked = this.settings.audioFeedback;
        document.getElementById('highContrast').checked = this.settings.highContrast;
        document.getElementById('fontSizeValue').textContent = `${this.settings.fontSize}%`;
    },
    
    /**
     * Initialize navigation
     */
    initNavigation() {
        // Mobile menu toggle
        const navbarToggle = document.getElementById('navbarToggle');
        if (navbarToggle) {
            navbarToggle.addEventListener('click', () => {
                document.querySelector('.navbar-collapse').classList.toggle('active');
            });
        }
        
        // User dropdown
        const userMenuToggle = document.getElementById('userMenuToggle');
        if (userMenuToggle) {
            userMenuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelector('.user-dropdown').classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.user-dropdown')) {
                    document.querySelector('.user-dropdown')?.classList.remove('active');
                }
            });
        }
        
        // Accessibility menu toggle
        const a11yToggle = document.getElementById('a11yToggle');
        if (a11yToggle) {
            a11yToggle.addEventListener('click', () => {
                document.getElementById('a11yControls').classList.toggle('active');
            });
        }
    },
    
    /**
     * Initialize accessibility controls
     */
    initAccessibility() {
        // Font size controls
        document.querySelectorAll('.a11y-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const action = btn.dataset.action;
                if (action === 'font-up' && this.settings.fontSize < 150) {
                    this.settings.fontSize += 10;
                } else if (action === 'font-down' && this.settings.fontSize > 80) {
                    this.settings.fontSize -= 10;
                }
                this.applySettings();
                this.saveSettings();
            });
        });
        
        // High contrast toggle
        const highContrastToggle = document.getElementById('highContrast');
        if (highContrastToggle) {
            highContrastToggle.addEventListener('change', () => {
                this.settings.highContrast = highContrastToggle.checked;
                this.applySettings();
                this.saveSettings();
            });
        }
        
        // Audio feedback toggle
        const audioFeedbackToggle = document.getElementById('audioFeedback');
        if (audioFeedbackToggle) {
            audioFeedbackToggle.addEventListener('change', () => {
                this.settings.audioFeedback = audioFeedbackToggle.checked;
                this.saveSettings();
            });
        }
    },
    
    /**
     * Initialize forms
     */
    initForms() {
        // Form validation
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e, form));
        });
        
        // Password confirmation
        const passwordInputs = document.querySelectorAll('input[type="password"][data-confirm]');
        passwordInputs.forEach(input => {
            const confirmId = input.dataset.confirm;
            const confirmInput = document.getElementById(confirmId);
            
            if (confirmInput) {
                confirmInput.addEventListener('input', () => {
                    if (confirmInput.value !== input.value) {
                        confirmInput.setCustomValidity('Passwords do not match');
                    } else {
                        confirmInput.setCustomValidity('');
                    }
                });
            }
        });
    },
    
    /**
     * Handle form submission
     */
    async handleFormSubmit(e, form) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const url = form.action || form.dataset.url;
        const method = form.method || 'POST';
        
        try {
            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (form.dataset.redirect) {
                    window.location.href = form.dataset.redirect;
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    this.showToast(data.message || 'Success!', 'success');
                    if (data.reset) form.reset();
                }
            } else {
                this.showToast(data.error || 'Something went wrong', 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showToast('An error occurred. Please try again.', 'error');
        }
    },
    
    /**
     * Initialize tooltips
     */
    initTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(el => {
            el.addEventListener('mouseenter', (e) => {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = el.dataset.tooltip;
                document.body.appendChild(tooltip);
                
                const rect = el.getBoundingClientRect();
                tooltip.style.top = `${rect.bottom + 8}px`;
                tooltip.style.left = `${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px`;
            });
            
            el.addEventListener('mouseleave', () => {
                document.querySelector('.tooltip')?.remove();
            });
        });
    },
    
    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-message">${message}</span>
            <button class="toast-close">&times;</button>
        `;
        
        document.body.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.add('toast-hide');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
        
        // Manual close
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.classList.add('toast-hide');
            setTimeout(() => toast.remove(), 300);
        });
    },
    
    /**
     * Initialize auto-save for activities
     */
    initAutoSave() {
        if (!this.settings.autoSave) return;
        
        setInterval(() => {
            if (this.activitySession) {
                this.saveProgress();
            }
        }, this.settings.autoSaveInterval);
        
        // Save on page unload
        window.addEventListener('beforeunload', () => {
            if (this.activitySession) {
                this.saveProgress();
            }
        });
    },
    
    /**
     * Save activity progress
     */
    async saveProgress() {
        if (!this.activitySession || !this.activitySession.id) return;
        
        try {
            await fetch('/api/save-progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    session_id: this.activitySession.id,
                    session_data: this.activitySession
                })
            });
        } catch (error) {
            console.error('Auto-save error:', error);
        }
    },
    
    /**
     * Play sound effect
     */
    playSound(type) {
        if (!this.settings.audioFeedback) return;
        
        const sounds = {
            correct: '/assets/sounds/correct.mp3',
            incorrect: '/assets/sounds/incorrect.mp3',
            complete: '/assets/sounds/complete.mp3',
            click: '/assets/sounds/click.mp3',
            achievement: '/assets/sounds/achievement.mp3'
        };
        
        const audio = new Audio(sounds[type]);
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Audio play failed:', e));
    }
};

// ============================================================
// Quiz Activity Handler
// ============================================================
const QuizActivity = {
    questions: [],
    currentQuestion: 0,
    answers: [],
    score: 0,
    timer: null,
    timeSpent: 0,
    timeLimit: null,
    startTime: null,
    
    init(data) {
        this.questions = data.questions;
        this.timeLimit = data.time_limit;
        this.startTime = Date.now();
        
        this.loadQuestion(0);
        this.startTimer();
    },
    
    loadQuestion(index) {
        const question = this.questions[index];
        const container = document.getElementById('quizContainer');
        
        container.innerHTML = `
            <div class="quiz-question" data-question="${question.id}">
                <div class="question-header">
                    <span class="question-number">Question ${index + 1} of ${this.questions.length}</span>
                    <span class="question-timer" id="questionTimer">${this.formatTime(this.timeSpent)}</span>
                </div>
                <h3 class="question-text">${question.question}</h3>
                ${question.image ? `<div class="question-image">${question.image}</div>` : ''}
                <div class="quiz-options">
                    ${question.options.map((opt, i) => `
                        <button class="quiz-option" data-index="${i}">
                            ${opt}
                        </button>
                    `).join('')}
                </div>
                <div class="question-feedback" id="questionFeedback"></div>
                <button class="btn btn-primary" id="nextQuestion" style="display: none;">Next</button>
            </div>
        `;
        
        // Add event listeners
        container.querySelectorAll('.quiz-option').forEach(btn => {
            btn.addEventListener('click', () => this.selectAnswer(btn, question));
        });
        
        document.getElementById('nextQuestion').addEventListener('click', () => {
            this.nextQuestion();
        });
    },
    
    selectAnswer(btn, question) {
        const selectedIndex = parseInt(btn.dataset.index);
        const isCorrect = selectedIndex === question.correct;
        
        // Disable all options
        document.querySelectorAll('.quiz-option').forEach(opt => {
            opt.disabled = true;
            opt.classList.remove('selected');
            
            const optIndex = parseInt(opt.dataset.index);
            if (optIndex === question.correct) {
                opt.classList.add('correct');
            } else if (optIndex === selectedIndex && !isCorrect) {
                opt.classList.add('incorrect');
            }
        });
        
        // Record answer
        this.answers.push({
            question_id: question.id,
            selected: selectedIndex,
            correct: isCorrect
        });
        
        if (isCorrect) {
            this.score++;
            App.playSound('correct');
            this.showFeedback(true, question.explanation);
        } else {
            App.playSound('incorrect');
            this.showFeedback(false, question.explanation);
        }
        
        // Show next button
        document.getElementById('nextQuestion').style.display = 'block';
    },
    
    showFeedback(isCorrect, explanation) {
        const feedback = document.getElementById('questionFeedback');
        feedback.innerHTML = `
            <div class="alert alert-${isCorrect ? 'success' : 'danger'}">
                <strong>${isCorrect ? 'Correct!' : 'Not quite!'}</strong>
                ${explanation ? `<p>${explanation}</p>` : ''}
            </div>
        `;
    },
    
    nextQuestion() {
        this.currentQuestion++;
        
        if (this.currentQuestion < this.questions.length) {
            this.loadQuestion(this.currentQuestion);
        } else {
            this.complete();
        }
    },
    
    startTimer() {
        if (this.timeLimit) {
            const totalTime = this.timeLimit;
            let remaining = totalTime;
            
            this.timer = setInterval(() => {
                remaining--;
                this.timeSpent++;
                
                const timerEl = document.getElementById('questionTimer');
                if (timerEl) {
                    timerEl.textContent = this.formatTime(this.timeSpent);
                    
                    if (remaining <= 30) {
                        timerEl.classList.add('timer-warning');
                    }
                }
                
                if (remaining <= 0) {
                    this.complete();
                }
            }, 1000);
        } else {
            this.timer = setInterval(() => {
                this.timeSpent++;
                const timerEl = document.getElementById('questionTimer');
                if (timerEl) {
                    timerEl.textContent = this.formatTime(this.timeSpent);
                }
            }, 1000);
        }
    },
    
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    },
    
    complete() {
        clearInterval(this.timer);
        
        const finalScore = Math.round((this.score / this.questions.length) * 100);
        const timeSpent = Math.floor((Date.now() - this.startTime) / 1000);
        
        // Submit results
        this.submitResults(this.score, timeSpent);
    },
    
    async submitResults(score, timeSpent) {
        try {
            const response = await fetch('/api/complete-activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    session_id: App.activitySession.id,
                    score: score,
                    time_spent: timeSpent
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showResults(data.results);
            } else {
                App.showToast('Failed to save results', 'error');
            }
        } catch (error) {
            console.error('Submit error:', error);
            App.showToast('An error occurred', 'error');
        }
    },
    
    showResults(results) {
        const container = document.getElementById('quizContainer');
        
        container.innerHTML = `
            <div class="activity-results">
                <div class="results-header">
                    <i class="fa ${results.passed ? 'fa-trophy' : 'fa-info-circle'}"></i>
                    <h2>${results.passed ? 'Great Job!' : 'Keep Practicing!'}</h2>
                </div>
                
                <div class="results-score">
                    <div class="score-circle">
                        <span class="score-number">${results.score_percentage}%</span>
                        <span class="score-label">Score</span>
                    </div>
                </div>
                
                <div class="results-details">
                    <div class="result-stat">
                        <i class="fa fa-star"></i>
                        <div>
                            <strong>${starRating(results.star_rating)}</strong>
                            <span>Rating</span>
                        </div>
                    </div>
                    <div class="result-stat">
                        <i class="fa fa-coins"></i>
                        <div>
                            <strong>+${results.coins_earned}</strong>
                            <span>Coins</span>
                        </div>
                    </div>
                    <div class="result-stat">
                        <i class="fa fa-bolt"></i>
                        <div>
                            <strong>+${results.xp_earned}</strong>
                            <span>XP</span>
                        </div>
                    </div>
                </div>
                
                <div class="results-actions">
                    <a href="/activities" class="btn btn-secondary">Back to Activities</a>
                    <a href="/progress" class="btn btn-primary">View Progress</a>
                </div>
            </div>
        `;
        
        App.playSound(results.passed ? 'achievement' : 'complete');
    }
};

// ============================================================
// Flashcard Activity Handler
// ============================================================
const FlashcardActivity = {
    cards: [],
    currentIndex: 0,
    viewed: [],
    
    init(data) {
        this.cards = data.cards;
        
        if (data.shuffle) {
            this.shuffleCards();
        }
        
        this.renderCard();
    },
    
    shuffleCards() {
        for (let i = this.cards.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [this.cards[i], this.cards[j]] = [this.cards[j], this.cards[i]];
        }
    },
    
    renderCard() {
        const card = this.cards[this.currentIndex];
        const container = document.getElementById('flashcardContainer');
        
        container.innerHTML = `
            <div class="flashcard-progress">
                <span>Card ${this.currentIndex + 1} of ${this.cards.length}</span>
            </div>
            
            <div class="flashcard-container">
                <div class="flashcard" id="currentFlashcard">
                    <div class="flashcard-front">
                        ${card.front}
                    </div>
                    <div class="flashcard-back">
                        ${card.back}
                    </div>
                </div>
            </div>
            
            <div class="flashcard-controls">
                <button class="btn btn-outline" id="prevCard" ${this.currentIndex === 0 ? 'disabled' : ''}>
                    <i class="fa fa-arrow-left"></i> Previous
                </button>
                <button class="btn btn-primary" id="flipCard">
                    <i class="fa fa-sync-alt"></i> Flip
                </button>
                <button class="btn btn-outline" id="nextCard" ${this.currentIndex === this.cards.length - 1 ? 'disabled' : ''}>
                    Next <i class="fa fa-arrow-right"></i>
                </button>
            </div>
        `;
        
        // Event listeners
        const flashcard = document.getElementById('currentFlashcard');
        flashcard.addEventListener('click', () => {
            flashcard.classList.toggle('flipped');
            if (!this.viewed.includes(this.currentIndex)) {
                this.viewed.push(this.currentIndex);
            }
        });
        
        document.getElementById('flipCard').addEventListener('click', () => {
            flashcard.classList.toggle('flipped');
            if (!this.viewed.includes(this.currentIndex)) {
                this.viewed.push(this.currentIndex);
            }
        });
        
        document.getElementById('prevCard').addEventListener('click', () => {
            this.navigateCard(-1);
        });
        
        document.getElementById('nextCard').addEventListener('click', () => {
            this.navigateCard(1);
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.navigateCard(-1);
            if (e.key === 'ArrowRight') this.navigateCard(1);
            if (e.key === ' ' || e.key === 'Enter') {
                flashcard.classList.toggle('flipped');
                if (!this.viewed.includes(this.currentIndex)) {
                    this.viewed.push(this.currentIndex);
                }
            }
        });
    },
    
    navigateCard(direction) {
        const newIndex = this.currentIndex + direction;
        
        if (newIndex >= 0 && newIndex < this.cards.length) {
            this.currentIndex = newIndex;
            this.renderCard();
        }
    }
};

// ============================================================
// Star Rating Helper
// ============================================================
function starRating(rating) {
    let html = '<div class="star-rating">';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            html += '<span class="star filled">★</span>';
        } else {
            html += '<span class="star">☆</span>';
        }
    }
    html += '</div>';
    return html;
}

// ============================================================
// Initialize App on DOM Ready
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// ============================================================
// Export for external use
// ============================================================
window.App = App;
window.QuizActivity = QuizActivity;
window.FlashcardActivity = FlashcardActivity;
