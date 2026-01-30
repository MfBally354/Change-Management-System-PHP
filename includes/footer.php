</main>
    
    <footer style="text-align: center; padding: 20px; color: #999; margin-top: 40px;">
        <p>&copy; <?php echo date('Y'); ?> Change Management System. All rights reserved.</p>
    </footer>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
        
        // Confirm delete actions
        function confirmDelete(message) {
            return confirm(message || 'Apakah Anda yakin ingin menghapus data ini?');
        }
        
        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return true;
            
            const required = form.querySelectorAll('[required]');
            let valid = true;
            
            required.forEach(function(field) {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    valid = false;
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!valid) {
                alert('Mohon lengkapi semua field yang wajib diisi.');
            }
            
            return valid;
        }
    </script>
</body>
</html>
