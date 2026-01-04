            </div>
        </main>
    </div>
    
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }
        
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this item?');
        }
    </script>
</body>
</html>
