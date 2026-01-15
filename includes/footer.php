        </div>
    </main>
    
    <?php if (is_logged_in()): ?>
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved. | Version <?php echo APP_VERSION; ?></p>
        </div>
    </footer>
    <?php endif; ?>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
