# DriveHub Installation Guide

## Quick Start

### Step 1: Import Database

Using MySQL command line:
```bash
mysql -u root -p < database.sql
```

Or using phpMyAdmin:
1. Open phpMyAdmin
2. Create a new database named `car_management`
3. Import the `database.sql` file

### Step 2: Configure Database Connection

Edit the `config.php` file with your database credentials:

```php
define('DB_HOST', 'localhost');      // Your MySQL host
define('DB_USER', 'root');           // Your MySQL username
define('DB_PASS', 'your_password');  // Your MySQL password
define('DB_NAME', 'car_management'); // Database name
```

### Step 3: Access the Application

#### Public Website
```
http://localhost/home.php
```

#### Admin Panel
```
http://localhost/login.php
```

**Default Admin Login:**
- Username: `admin`
- Password: `admin123`

### Step 4: Change Default Password

After logging in:
1. Go to the database
2. Run this SQL to update password:

```sql
UPDATE admins 
SET password = '$2y$10$NEW_HASH_HERE' 
WHERE username = 'admin';
```

Or use this PHP script to generate a new password hash:
```php
<?php
echo password_hash('your_new_password', PASSWORD_DEFAULT);
?>
```

## Server Requirements

### Minimum Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache or Nginx
- **PHP Extensions**:
  - mysqli
  - session
  - json

### Recommended Requirements
- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **Memory**: 256MB+
- **SSL Certificate**: For HTTPS (PWA requirement)

## Apache Configuration

### Enable .htaccess (Optional)

Create `.htaccess` in the root directory:

```apache
RewriteEngine On

# Force HTTPS (recommended for PWA)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove .php extension
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^(.*)$ $1.php [L]

# Deny access to sensitive files
<FilesMatch "^(config|database)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### Virtual Host Configuration

```apache
<VirtualHost *:80>
    ServerName drivehub.local
    DocumentRoot /path/to/drivehub
    
    <Directory /path/to/drivehub>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/drivehub-error.log
    CustomLog ${APACHE_LOG_DIR}/drivehub-access.log combined
</VirtualHost>
```

## Nginx Configuration

```nginx
server {
    listen 80;
    server_name drivehub.local;
    root /path/to/drivehub;
    index home.php index.php;

    # Remove .php extension
    location / {
        try_files $uri $uri/ $uri.php?$query_string;
    }

    # PHP processing
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

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

## Database Backup

### Backup Command
```bash
mysqldump -u root -p car_management > backup_$(date +%Y%m%d).sql
```

### Restore Command
```bash
mysql -u root -p car_management < backup_20240109.sql
```

## File Permissions

Set appropriate permissions:

```bash
# Make files readable
chmod 644 *.php
chmod 644 assets/css/*.css
chmod 644 assets/js/*.js

# Make directories executable
chmod 755 admin/
chmod 755 assets/
chmod 755 assets/images/

# Secure config file
chmod 600 config.php
```

## Troubleshooting

### Database Connection Error

**Error**: "Connection failed: Access denied"

**Solution**:
1. Check MySQL credentials in `config.php`
2. Ensure MySQL is running: `sudo service mysql status`
3. Create database: `CREATE DATABASE car_management;`
4. Grant permissions:
```sql
GRANT ALL PRIVILEGES ON car_management.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

### Session Errors

**Error**: "Cannot modify header information"

**Solution**:
1. Check for whitespace before `<?php` in files
2. Ensure `session_start()` is called before any output
3. Check PHP session directory permissions

### Service Worker Not Registering

**Error**: PWA not installing

**Solution**:
1. Ensure HTTPS is enabled (required for PWA)
2. Check `sw.js` file is accessible
3. Check browser console for errors
4. Clear browser cache and reload

### Missing Icons

**Solution**:
Icons are simple placeholders. For production:
1. Use [RealFaviconGenerator](https://realfavicongenerator.net/)
2. Replace files in `assets/images/`
3. Ensure all sizes are present (72, 96, 128, 144, 152, 192, 384, 512)

## Development vs Production

### Development Setup
```php
// config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Production Setup
```php
// config.php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php-error.log');
```

## Updating the System

1. **Backup database**
2. **Backup files**
3. **Pull updates**
4. **Run database migrations** (if any)
5. **Clear cache**

## Performance Optimization

### Enable OPcache

In `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
```

### MySQL Optimization

```sql
-- Add indexes for frequently queried columns
ALTER TABLE cars ADD INDEX idx_status (status);
ALTER TABLE cars ADD INDEX idx_make_model (make, model);
ALTER TABLE sales ADD INDEX idx_sale_date (sale_date);
```

### Enable Compression

In `.htaccess`:
```apache
# Compress text files
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript application/javascript
</IfModule>
```

## Security Hardening

### 1. Change Default Credentials
```sql
UPDATE admins SET 
    username = 'new_username',
    password = PASSWORD_HASH_HERE
WHERE username = 'admin';
```

### 2. Restrict File Access
```apache
<Files "config.php">
    Order Allow,Deny
    Deny from all
</Files>
```

### 3. Use Environment Variables

Create `.env` file:
```
DB_HOST=localhost
DB_USER=myuser
DB_PASS=securepassword
DB_NAME=car_management
```

Update `config.php`:
```php
define('DB_HOST', getenv('DB_HOST'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME'));
```

### 4. Enable HTTPS

Using Let's Encrypt:
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

### 5. Implement Rate Limiting

Add to your code or use web server level rate limiting.

## Support

For additional help:
- Check the main README_CAR_SYSTEM.md
- Review PHP error logs
- Check MySQL error logs
- Inspect browser console for JavaScript errors

---

**Installation complete! Access your Car Management System and start managing your inventory.**
