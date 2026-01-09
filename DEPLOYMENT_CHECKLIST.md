# DriveHub Deployment Checklist

## Pre-Deployment Checklist

### ✅ Files Verification
- [x] All PHP files present (17 files)
- [x] CSS file present (assets/css/style.css)
- [x] JavaScript files present (app.js, admin.js, sw.js)
- [x] Database schema file (database.sql)
- [x] PWA manifest (manifest.json)
- [x] Service worker (sw.js)
- [x] PWA icons (9 icons)
- [x] Documentation files (4 .md files)
- [x] .gitignore file

### 📋 Required Actions Before Going Live

#### 1. Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE car_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Import schema and sample data
mysql -u root -p car_management < database.sql

# Verify tables
mysql -u root -p car_management -e "SHOW TABLES;"
```
**Expected tables**: admins, cars, customers, sales, maintenance, inquiries, test_drives

#### 2. Configure Database Connection
Edit `config.php`:
```php
define('DB_HOST', 'localhost');           // Your MySQL host
define('DB_USER', 'your_username');       // Change this
define('DB_PASS', 'your_password');       // Change this  
define('DB_NAME', 'car_management');      // Database name
```

#### 3. Change Default Admin Password
```sql
-- Option 1: Using SQL directly
UPDATE admins 
SET password = '$2y$10$YOUR_NEW_PASSWORD_HASH_HERE'
WHERE username = 'admin';

-- Option 2: Generate hash using PHP
-- Run: php -r "echo password_hash('your_new_password', PASSWORD_DEFAULT);"
-- Then update database with the hash
```

Or change username too:
```sql
UPDATE admins 
SET username = 'newusername',
    password = '$2y$10$NEW_HASH_HERE',
    email = 'newemail@domain.com',
    full_name = 'Your Name'
WHERE id = 1;
```

#### 4. Update Site Configuration
Edit `config.php`:
```php
define('SITE_NAME', 'Your Dealership Name');
define('CURRENCY', '₱');           // Keep for Philippine Peso
define('CURRENCY_CODE', 'PHP');    // Keep for Philippine Peso
```

#### 5. Customize PWA Manifest
Edit `manifest.json`:
```json
{
  "name": "Your Dealership Name",
  "short_name": "YourName",
  "description": "Your custom description",
  "start_url": "/home.php",
  "theme_color": "#2563eb"
}
```

#### 6. Replace PWA Icons (Recommended)
Current icons are basic placeholders. For professional appearance:

1. Design your logo
2. Use [RealFaviconGenerator](https://realfavicongenerator.net/)
3. Generate all required sizes:
   - 72x72, 96x96, 128x128, 144x144
   - 152x152, 192x192, 384x384, 512x512
4. Replace files in `assets/images/`
5. Add your screenshot (540x720) for app stores

#### 7. Update Contact Information
Edit `home.php` footer section:
```php
<p><i class="fas fa-phone"></i> +63 YOUR PHONE</p>
<p><i class="fas fa-envelope"></i> your-email@domain.com</p>
<p><i class="fas fa-map-marker-alt"></i> Your Address</p>
```

#### 8. Set File Permissions
```bash
# Set proper permissions
chmod 755 admin/
chmod 755 assets/
chmod 644 *.php
chmod 644 assets/css/*.css
chmod 644 assets/js/*.js
chmod 600 config.php  # Secure the config file
```

#### 9. Configure Web Server

**For Apache**, create/edit `.htaccess`:
```apache
# Enable rewrite engine
RewriteEngine On

# Force HTTPS (required for PWA)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove .php extension (optional)
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^(.*)$ $1.php [L]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"

# Deny access to sensitive files
<FilesMatch "^(config|database|\.git)">
    Order allow,deny
    Deny from all
</FilesMatch>
```

**For Nginx**, add to server block:
```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    root /var/www/drivehub;
    index home.php;
    
    # SSL configuration
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        try_files $uri $uri/ $uri.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    # Deny access to sensitive files
    location ~ ^/(config|database)\.php$ {
        deny all;
        return 404;
    }
    
    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

#### 10. Enable HTTPS/SSL
**Required for PWA functionality!**

Using Let's Encrypt:
```bash
# Install certbot
sudo apt update
sudo apt install certbot python3-certbot-apache

# Or for Nginx
sudo apt install certbot python3-certbot-nginx

# Generate certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Or for Nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

#### 11. Update Service Worker Cache
Edit `sw.js` and update version:
```javascript
const CACHE_NAME = 'drivehub-v2'; // Increment version
```

#### 12. Clear Sample Data (Optional)
If you don't want the sample cars:
```sql
-- Keep admin, remove sample cars
DELETE FROM cars WHERE id > 0;
-- Reset auto-increment
ALTER TABLE cars AUTO_INCREMENT = 1;
```

#### 13. Set Up Email (Future Enhancement)
For inquiry notifications, configure PHP mail or SMTP:
```php
// In config.php (future enhancement)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

#### 14. Error Logging
Edit `config.php` for production:
```php
// Development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Production (use this)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php/drivehub-error.log');
```

Create log directory:
```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
```

#### 15. Backup Strategy
Set up automatic backups:
```bash
# Create backup script
cat > /usr/local/bin/backup-drivehub.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/drivehub"
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u root -p'PASSWORD' car_management > $BACKUP_DIR/db_$DATE.sql

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/drivehub

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
EOF

chmod +x /usr/local/bin/backup-drivehub.sh

# Add to crontab (daily at 2 AM)
echo "0 2 * * * /usr/local/bin/backup-drivehub.sh" | sudo crontab -
```

### 🔒 Security Hardening

#### Essential Security Steps
- [x] Change default admin password
- [ ] Enable HTTPS (required for PWA)
- [ ] Restrict config.php access
- [ ] Add security headers
- [ ] Enable SQL injection prevention (already in code)
- [ ] Sanitize all outputs (already in code)
- [ ] Set proper file permissions
- [ ] Hide PHP version
- [ ] Disable directory listing
- [ ] Set up firewall rules
- [ ] Enable fail2ban for login attempts (recommended)

#### Recommended `.htaccess` additions:
```apache
# Hide PHP version
Header unset X-Powered-By
ServerSignature Off

# Disable directory browsing
Options -Indexes

# Prevent access to hidden files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 📊 Performance Optimization

#### Database Optimization
```sql
-- Add indexes for better performance
ALTER TABLE cars ADD INDEX idx_status (status);
ALTER TABLE cars ADD INDEX idx_make_model (make, model);
ALTER TABLE cars ADD INDEX idx_price (price);
ALTER TABLE sales ADD INDEX idx_sale_date (sale_date);
ALTER TABLE inquiries ADD INDEX idx_status (status);
```

#### Enable PHP OPcache
Add to `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

#### Enable Compression
Add to `.htaccess`:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript application/javascript application/json
</IfModule>
```

### ✅ Testing Checklist

Before going live, test:
- [ ] Database connection works
- [ ] Admin login works
- [ ] All CRUD operations work (Create, Read, Update, Delete)
- [ ] Search and filters work
- [ ] Forms submit correctly
- [ ] Inquiries are saved
- [ ] Test drive bookings work
- [ ] PWA installs correctly (requires HTTPS)
- [ ] Service worker registers
- [ ] Mobile responsive design
- [ ] All pages load without errors
- [ ] PHP errors are logged (not displayed)
- [ ] SSL certificate is valid
- [ ] All links work

### 🚀 Go-Live Steps

1. [ ] Complete all checklist items above
2. [ ] Upload files to production server
3. [ ] Import database
4. [ ] Update config.php with production credentials
5. [ ] Set file permissions
6. [ ] Test admin login
7. [ ] Test public pages
8. [ ] Test PWA installation
9. [ ] Monitor error logs
10. [ ] Create first backup

### 📱 Post-Deployment

#### Monitor These
- PHP error logs: `/var/log/php/drivehub-error.log`
- Web server logs: `/var/log/apache2/` or `/var/log/nginx/`
- Database performance
- Disk space
- Backup success

#### Regular Maintenance
- Daily: Check error logs
- Weekly: Review inquiries and test drives
- Monthly: Database backup verification
- Quarterly: Security updates
- Yearly: SSL certificate renewal (automated with Let's Encrypt)

### 🆘 Troubleshooting

**Can't login?**
- Check database credentials in config.php
- Verify admin user exists in database
- Check session configuration

**PWA not installing?**
- Requires HTTPS (except localhost)
- Check manifest.json is accessible
- Check sw.js is accessible
- Check browser console for errors

**Database errors?**
- Check MySQL is running
- Verify credentials
- Check database exists
- Check user has permissions

**500 Internal Server Error?**
- Check PHP error logs
- Check .htaccess syntax
- Verify file permissions
- Check PHP extensions are installed

### 📞 Support Resources

- Main Documentation: `README_CAR_SYSTEM.md`
- Installation Guide: `INSTALLATION.md`
- Features List: `FEATURES.md`
- This Checklist: `DEPLOYMENT_CHECKLIST.md`

---

## Quick Deployment Summary

```bash
# 1. Import database
mysql -u root -p < database.sql

# 2. Update config.php credentials

# 3. Change default password

# 4. Set permissions
chmod 600 config.php
chmod 755 admin/ assets/
chmod 644 *.php

# 5. Enable HTTPS
sudo certbot --apache -d yourdomain.com

# 6. Test everything

# 7. Go live!
```

**Remember**: HTTPS is required for PWA functionality!

---

**Deployment checklist complete! Your Car Management System is ready for production.**
