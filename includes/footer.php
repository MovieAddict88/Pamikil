    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo getSetting('site_name', SITE_NAME); ?></h3>
                    <p>Watch your favorite movies online for free.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="search.php">Search</a></li>
                        <li><a href="admin/login.php">Admin</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Information</h4>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo getSetting('site_name', SITE_NAME); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        function toggleMenu() {
            document.querySelector('.nav-menu').classList.toggle('active');
        }
    </script>
</body>
</html>
