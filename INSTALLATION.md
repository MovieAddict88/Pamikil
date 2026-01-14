# Installation Guide - Pamikil Car Management System

This guide provides detailed step-by-step instructions for installing Pamikil on various hosting platforms.

## Prerequisites

### System Requirements
- **PHP Version**: 7.4 or higher (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Disk Space**: Minimum 50 MB (100 MB+ recommended)
- **Memory**: 64 MB PHP memory limit

### PHP Extensions Required
- PDO
- PDO_MySQL
- mbstring
- json
- fileinfo
- GD (for image processing)

## Installation Methods

### Method 1: InfinityFree (Free Hosting)

#### Step 1: Create Account
1. Visit [infinityfree.com](https://infinityfree.com)
2. Sign up for a free account
3. Create a new account/website

#### Step 2: Upload Files
1. Go to File Manager in VistaPanel
2. Navigate to `htdocs` directory
3. Delete all existing files
4. Upload all Pamikil files:
   - Option A: Use File Manager to upload each file/folder
   - Option B: Use FTP client (FileZilla, WinSCP)
   - Option C: Compress to .zip, upload, then extract

#### Step 3: Create Database
1. In VistaPanel, go to "MySQL Databases"
2. Create a new database:
   - Database name: `pamikil_db` (or your preferred name)
   - Click "Create Database"
3. Create a database user:
   - Username: `pamikil_user`
   - Password: Generate a strong password
   - Click "Create User"
4. Link user to database:
   - Check "All Privileges"
   - Click "Modify User"

#### Step 4: Import Database Schema
1. In VistaPanel, go to "phpMyAdmin"
2. Select your database from the left sidebar
3. Click "Import" tab
4. Click "Choose File" and select `database/schema.sql`
5. Click "Go" at the bottom
6. Verify tables were created (you should see them in the left sidebar)

#### Step 5: Configure Database
1. Go to File Manager
2. Open `config/database.php`
3. Update with your database credentials:
   ```php
   define('DB_HOST', 'sqlXXX.infinityfree.com');  // Check in VistaPanel
   define('DB_USER', 'pamikil_user');
   define('DB_PASS', 'your_password_here');
   define('DB_NAME', 'pamikil_db');
   ```
4. Save the file

#### Step 6: Set File Permissions
1. In File Manager, right-click on directories
2. Change permissions to `755`:
   - `public/uploads/`
   - `public/reports/`
   - `logs/`

#### Step 7: Configure Site URL
1. Open `config/config.php`
2. Update SITE_URL:
   ```php
   define('SITE_URL', 'https://your-site.infinityfreeapp.com');
   ```
3. Save the file

#### Step 8: Login
1. Visit your site URL
2. Login with default credentials:
   - Username: `admin`
   - Password: `admin123`
3. Change password immediately!

---

### Method 2: cPanel Shared Hosting

#### Step 1: Upload Files
1. Log in to cPanel
2. Open File Manager
3. Navigate to `public_html`
4. Upload all Pamikil files

#### Step 2: Create Database
1. In cPanel, go to "MySQL Database Wizard"
2. Follow the wizard to:
   - Create a database (e.g., `youruser_pamikil`)
   - Create a database user
   - Grant all privileges

#### Step 3: Import Schema
1. In cPanel, go to "phpMyAdmin"
2. Select your database
3. Import `database/schema.sql`

#### Step 4: Configure
Update `config/database.php` with your credentials:
```php
define('DB_HOST', 'localhost');  // Usually localhost on cPanel
define('DB_USER', 'youruser_pamikil');
define('DB_PASS', 'your_password');
define('DB_NAME', 'youruser_pamikil');
```

#### Step 5: Set Permissions
Using File Manager or FTP, set permissions:
```
chmod 755 public/uploads/
chmod 755 public/reports/
chmod 755 logs/
```

#### Step 6: Test
Visit your domain and login with admin/admin123

---

### Method 3: VPS/Dedicated Server (Ubuntu)

#### Step 1: Install LAMP Stack
```bash
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring php-json
sudo a2enmod php8.2
sudo systemctl restart apache2
```

#### Step 2: Create Virtual Host
```bash
sudo nano /etc/apache2/sites-available/pamikil.conf
```

Add:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/pamikil/public

    <Directory /var/www/pamikil/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/pamikil_error.log
    CustomLog ${APACHE_LOG_DIR}/pamikil_access.log combined
</VirtualHost>
```

Enable:
```bash
sudo a2ensite pamikil.conf
sudo systemctl reload apache2
```

#### Step 3: Create Database
```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE pamikil CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pamikil'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON pamikil.* TO 'pamikil'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Step 4: Import Schema
```bash
mysql -u pamikil -p pamikil < database/schema.sql
```

#### Step 5: Deploy Files
```bash
sudo mkdir -p /var/www/pamikil
sudo cp -r . /var/www/pamikil/
sudo chown -R www-data:www-data /var/www/pamikil
sudo chmod -R 755 /var/www/pamikil
```

#### Step 6: Configure
Update `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'pamikil');
define('DB_PASS', 'strong_password');
define('DB_NAME', 'pamikil');
```

---

## Post-Installation Tasks

### 1. Security Checklist
- [ ] Change default admin password
- [ ] Create additional user accounts
- [ ] Enable SSL/HTTPS
- [ ] Set up regular backups
- [ ] Update SITE_URL in config
- [ ] Remove any sample/test data

### 2. Configure Email (Optional)
For email notifications, edit `config/config.php`:
```php
define('ENABLE_EMAIL_NOTIFICATIONS', true);
```

Then configure PHP's mail settings or use SMTP library.

### 3. Set Cron Jobs (Optional)
For automated tasks:
```bash
# Daily backup
0 2 * * * /usr/bin/mysqldump -u user -p'password' dbname > /backups/db_$(date +\%Y\%m\%d).sql

# Clean old logs
0 3 * * * find /path/to/logs/ -name "*.log" -mtime +30 -delete
```

### 4. Testing Checklist
- [ ] Login works
- [ ] Dashboard loads
- [ ] Can add vehicle
- [ ] Can add customer
- [ ] Can create sale
- [ ] Can view reports
- [ ] File uploads work
- [ ] Export functionality works
- [ ] Logout works

---

## Troubleshooting

### Blank Page / White Screen
1. Check PHP error logs
2. Enable error display in `config/config.php` temporarily
3. Verify all files uploaded correctly

### Database Connection Failed
1. Verify database credentials
2. Check database server is running
3. Verify user has proper privileges
4. Check firewall settings

### Permission Denied Errors
```bash
# Fix permissions
chmod 755 public/uploads/
chmod 755 public/reports/
chmod 755 logs/
```

### Images Not Uploading
1. Check upload_max_filesize in php.ini
2. Verify directory permissions
3. Ensure disk space is available

### Session Not Working
1. Verify session.save_path in php.ini
2. Check browser cookies are enabled
3. Ensure session directory is writable

---

## Updating

To update to a newer version:

1. **Backup everything:**
   - Database backup
   - File backup

2. **Download new version**

3. **Upload new files** (overwrite existing)

4. **Run any database migrations** (if provided)

5. **Test thoroughly**

---

## Support

For additional help:
- Check the README.md
- Review error logs in `logs/` directory
- Verify all requirements are met

---

## Security Best Practices

1. **Strong Passwords**
   - Use minimum 12 characters
   - Mix letters, numbers, and symbols
   - Never reuse passwords

2. **File Access**
   - Keep config files outside public access
   - Use proper file permissions (644 for files, 755 for directories)

3. **SSL Certificate**
   - Always use HTTPS in production
   - Use Let's Encrypt for free SSL

4. **Regular Backups**
   - Daily database backups
   - Weekly file backups
   - Test restore procedures

5. **Keep Updated**
   - Update PHP regularly
   - Update MySQL/MariaDB
   - Apply security patches

---

## Performance Optimization

### For High Traffic:
1. Enable OPcache
2. Use Redis for session storage
3. Implement database query caching
4. Use CDN for static assets
5. Enable Gzip compression

### For Shared Hosting:
1. Optimize images before upload
2. Use lazy loading
3. Minimize external dependencies
4. Clean old logs regularly

---

## License Notes

This software is provided for use in the Philippines. Modify and adapt as needed for your specific requirements.
