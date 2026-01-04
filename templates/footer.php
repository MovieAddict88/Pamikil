    </main>
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Pamikil Learning</h4>
                    <p>Fun and engaging educational activities for children of all ages.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <?php if ($auth->isLoggedIn() && $auth->isStudent()): ?>
                            <li><a href="<?= SITE_URL ?>/activities">Activities</a></li>
                            <li><a href="<?= SITE_URL ?>/progress">My Progress</a></li>
                            <li><a href="<?= SITE_URL ?>/rewards">Rewards</a></li>
                        <?php else: ?>
                            <li><a href="<?= SITE_URL ?>/">Home</a></li>
                            <li><a href="<?= SITE_URL ?>/login">Log In</a></li>
                            <li><a href="<?= SITE_URL ?>/register">Sign Up</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Resources</h4>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/docs/parent-guide">Parent Guide</a></li>
                        <li><a href="<?= SITE_URL ?>/docs/faq">FAQ</a></li>
                        <li><a href="<?= SITE_URL ?>/docs/privacy">Privacy Policy</a></li>
                        <li><a href="<?= SITE_URL ?>/docs/terms">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Email: <?= htmlspecialchars(ADMIN_EMAIL) ?></p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Pamikil Learning. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?= asset('js/main.js') ?>"></script>
    
    <!-- Accessibility Controls -->
    <div class="a11y-controls" id="a11yControls">
        <button class="a11y-toggle" id="a11yToggle" aria-label="Accessibility Options">
            <i class="fa fa-universal-access"></i>
        </button>
        <div class="a11y-menu" id="a11yMenu">
            <h5>Accessibility Options</h5>
            <div class="a11y-option">
                <label for="fontSize">Font Size</label>
                <button class="a11y-btn" data-action="font-down"><i class="fa fa-minus"></i></button>
                <span id="fontSizeValue">100%</span>
                <button class="a11y-btn" data-action="font-up"><i class="fa fa-plus"></i></button>
            </div>
            <div class="a11y-option">
                <label>
                    <input type="checkbox" id="highContrast"> High Contrast
                </label>
            </div>
            <div class="a11y-option">
                <label>
                    <input type="checkbox" id="audioFeedback"> Audio Feedback
                </label>
            </div>
        </div>
    </div>
</body>
</html>
