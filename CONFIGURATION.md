# Configuration Guide

## Database Configuration

### For InfinityFree Hosting

```
Database Host: sqlXXX.infinityfreeapp.com (provided by InfinityFree)
Database Name: epiz_XXXXXXX_moviestream (with your account prefix)
Database User: epiz_XXXXXXX (your account username)
Database Password: (set during database creation)
```

### For Paid Hosting (cPanel/DirectAdmin)

```
Database Host: localhost
Database Name: username_moviestream
Database User: username_moviestream
Database Password: (your secure password)
```

### For Local Development (XAMPP/WAMP)

```
Database Host: localhost
Database Name: moviestream
Database User: root
Database Password: (usually empty for XAMPP)
```

## API Configuration

### TMDB API Setup

1. **Register Account**
   - Visit: https://www.themoviedb.org/signup
   - Complete registration and verify email

2. **Request API Key**
   - Login to TMDB
   - Go to: Settings → API
   - Click "Request an API Key"
   - Choose "Developer" option
   - Fill in application details:
     - Application Name: Your site name
     - Application URL: Your website URL
     - Application Summary: Brief description

3. **Get API Key**
   - Copy your API Key (v3 auth)
   - Add to Admin → Settings → TMDB API Key

### YouTube Data API Setup

1. **Create Google Cloud Project**
   - Visit: https://console.cloud.google.com/
   - Click "Create Project"
   - Enter project name
   - Click "Create"

2. **Enable YouTube Data API**
   - Go to "APIs & Services" → "Library"
   - Search for "YouTube Data API v3"
   - Click "Enable"

3. **Create API Key**
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "API Key"
   - Copy the generated API key
   - (Optional) Restrict key to YouTube Data API

4. **Add to Application**
   - Admin → Settings → YouTube API Key
   - Paste your API key

## Server Configuration

### Apache (.htaccess)

The application includes a pre-configured `.htaccess` file. If you need to customize:

```apache
# Enable rewrite engine
RewriteEngine On
RewriteBase /

# Pretty URLs for movies
RewriteRule ^movie/([a-zA-Z0-9-]+)$ watch.php?slug=$1 [L,QSA]

# Increase upload limits (if allowed by host)
php_value upload_max_filesize 2048M
php_value post_max_size 2048M
php_value max_execution_time 3600
```

### Nginx Configuration

If using Nginx, add to your site configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/moviestream;
    index index.php;

    # Pretty URLs
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/movie/([a-zA-Z0-9-]+)$ {
        try_files $uri /watch.php?slug=$1;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Protect sensitive directories
    location ~ ^/(config|includes) {
        deny all;
    }

    # Increase upload limits
    client_max_body_size 2048M;
}
```

## PHP Configuration

### Recommended php.ini Settings

```ini
; Upload settings
upload_max_filesize = 2048M
post_max_size = 2048M
max_execution_time = 3600
max_input_time = 3600
memory_limit = 512M

; Security settings
display_errors = Off
log_errors = On
error_log = /path/to/error.log

; Session settings
session.cookie_httponly = 1
session.cookie_secure = 1  ; Enable if using HTTPS
session.use_strict_mode = 1

; Required extensions
extension=pdo_mysql
extension=curl
extension=json
extension=mbstring
extension=fileinfo
```

### For Shared Hosting

If you can't edit php.ini, create `.user.ini` in your root directory:

```ini
upload_max_filesize = 2048M
post_max_size = 2048M
max_execution_time = 3600
memory_limit = 512M
```

## File Permissions

### Linux/Unix Hosting

```bash
# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make uploads writable
chmod 755 uploads/
chmod 755 uploads/movies/
chmod 755 uploads/posters/
chmod 755 uploads/temp/

# Protect config directory (after installation)
chmod 644 config/database.php
```

### Windows Hosting (IIS)

- Ensure IUSR has read access to all files
- Grant write permissions to uploads directory
- Configure URL Rewrite module for pretty URLs

## Security Hardening

### 1. Remove Installation File

After installation:
```bash
rm install.php
# or rename it
mv install.php install.php.disabled
```

### 2. Protect Config Directory

Add to `.htaccess`:
```apache
<Files "database.php">
    Order allow,deny
    Deny from all
</Files>
```

### 3. Enable HTTPS

If available on your hosting:
```apache
# Redirect to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 4. Database User Permissions

Only grant necessary permissions:
```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON moviestream.* TO 'user'@'localhost';
FLUSH PRIVILEGES;
```

## Performance Optimization

### 1. Enable OPcache (if available)

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  ; Production only
```

### 2. Enable Gzip Compression

Add to `.htaccess`:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

### 3. Browser Caching

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### 4. MySQL Optimization

```sql
-- Add indexes for better performance
CREATE INDEX idx_movie_search ON movies(title, year, is_published);
CREATE INDEX idx_movie_featured ON movies(is_featured, is_published);
CREATE INDEX idx_views_movie ON views_log(movie_id, viewed_at);
```

## CDN Configuration

### Cloudflare Setup

1. Add your domain to Cloudflare
2. Update nameservers
3. Enable:
   - Auto Minify (CSS, JS, HTML)
   - Brotli compression
   - Rocket Loader
4. Configure Page Rules:
   - Cache Everything for static assets
   - Bypass cache for admin panel

### Using External CDN for Videos

Example with DigitalOcean Spaces:

```php
// In edit-movie.php, add video URL like:
https://your-space.nyc3.digitaloceanspaces.com/movies/movie-name.mp4
```

## Backup Configuration

### Automated Backup Script

Create `backup.sh`:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/path/to/backups"

# Backup database
mysqldump -u username -p password moviestream > "$BACKUP_DIR/db_$DATE.sql"

# Backup uploads
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" uploads/

# Remove backups older than 30 days
find "$BACKUP_DIR" -type f -mtime +30 -delete
```

### Cron Job for Automated Backups

```bash
# Run daily at 2 AM
0 2 * * * /path/to/backup.sh
```

## Troubleshooting Common Issues

### Issue: "Call to undefined function curl_init()"
**Solution:** Enable cURL extension in php.ini

### Issue: Database connection failed
**Solution:** 
- Verify database credentials
- Check if MySQL service is running
- Ensure database exists
- Test connection with phpMyAdmin

### Issue: Videos won't upload
**Solution:**
- Check upload_max_filesize in PHP settings
- Verify folder permissions (755)
- Ensure adequate disk space

### Issue: TMDB API not working
**Solution:**
- Verify API key is correct
- Check if cURL is enabled
- Test API key at: https://api.themoviedb.org/3/movie/550?api_key=YOUR_KEY

### Issue: Pretty URLs not working
**Solution:**
- Ensure mod_rewrite is enabled
- Check .htaccess file exists
- Verify RewriteBase is correct

## Environment-Specific Notes

### InfinityFree
- Upload limit: 10MB
- Use external hosting for large videos
- YouTube embedding recommended
- Database prefix required

### 000webhost
- Similar to InfinityFree
- May have hourly limits
- Use CDN for videos

### Paid Shared Hosting (Hostinger, Bluehost)
- Higher upload limits
- Better performance
- Full .htaccess support
- SSH access (usually)

### VPS/Dedicated Server
- Full control
- Can configure PHP, MySQL optimally
- Set up video transcoding
- Configure Redis/Memcached

## Support & Updates

For latest configuration tips:
- Check README.md
- Review server logs
- Test with sample TMDB IDs
- Monitor performance metrics
