            </div>
        </main>
        
        <footer class="app-footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-logo">
                        <i class="fas fa-microphone-alt"></i>
                        <span>Karaoke App</span>
                    </div>
                    <div class="footer-links">
                        <a href="/about">About</a>
                        <a href="/privacy">Privacy</a>
                        <a href="/terms">Terms</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <script src="/assets/js/app.js"></script>
    <?php if (isset($currentPage) && file_exists(__DIR__ . '/../../assets/js/' . $currentPage . '.js')): ?>
        <script src="/assets/js/<?php echo $currentPage; ?>.js"></script>
    <?php endif; ?>
</body>
</html>