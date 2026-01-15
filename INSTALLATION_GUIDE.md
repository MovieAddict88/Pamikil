# Car Management System - Installation Guide

## System Requirements

### Minimum Requirements
- PHP 7.2 or higher
- MySQL 5.6 or higher
- Apache/Nginx web server
- Minimum 50MB disk space
- 256MB RAM (recommended 512MB+)

### Recommended for InfinityFree Hosting
- Works with InfinityFree's free hosting plan
- Compatible with shared hosting environments
- No special PHP extensions required
- Standard MySQL database access

## Installation Instructions

### Step 1: Download and Extract Files

1. Download the complete car management system files
2. Extract the ZIP file to your local computer
3. You should see the following folder structure:
```
car-management/
├── assets/
├── config/
├── database/
├── includes/
├── modules/
└── index.php
```

### Step 2: Upload Files to Hosting

#### For InfinityFree Hosting:
1. Login to your InfinityFree control panel (https://panel.infinityfree.com/)
2. Go to "File Manager" or use FTP client (like FileZilla)
3. Navigate to `htdocs` folder (public_html on some hosts)
4. Upload all files and folders to the `htdocs` directory
5. Ensure folder permissions are set correctly (755 for folders, 644 for files)

#### For Other Shared Hosting:
1. Connect to your hosting via FTP or File Manager
2. Upload files to your `public_html` or `www` directory
3. Ensure proper permissions are set

### Step 3: Create MySQL Database

#### On InfinityFree:
1. Go to MySQL Databases in the control panel
2. Click "Create Database"
3. Note down the database details:
   - Database name
   - Database username
   - Database password
   - Database hostname (usually `sqlXXX.infinityfree.com`)

#### On cPanel/Other Hosting:
1. Access cPanel or hosting control panel
2. Go to MySQL Databases
3. Create a new database
4. Create a database user
5. Add the user to the database with ALL PRIVILEGES
6. Note down all credentials

### Step 4: Import Database Schema

#### Method 1: Using phpMyAdmin (Recommended)
1. Access phpMyAdmin from your hosting control panel
2. Select the database you created
3. Click on "Import" tab
4. Choose the `database/schema.sql` file
5. Click "Go" to import
6. Wait for the success message

#### Method 2: Using MySQL Command Line
```bash
mysql -u your_username -p your_database_name < database/schema.sql
```

### Step 5: Configure Database Connection

1. Open `config/database.php` in a text editor
2. Update the database credentials:

```php
define('DB_HOST', 'localhost');  // or sqlXXX.infinityfree.com for InfinityFree
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

3. Save the file
4. Re-upload to your hosting if editing locally

### Step 6: Configure Application Settings

1. Open `config/config.php`
2. Update the APP_URL:

```php
define('APP_URL', 'http://yourdomain.com');  // or http://yourdomain.infinityfreeapp.com
```

3. For production, disable error display:
```php
ini_set('display_errors', 0);
```

4. Save and re-upload

### Step 7: Set Folder Permissions

Ensure the following folders have write permissions (755 or 777):
- `assets/uploads/vehicles/` - For vehicle image uploads
- `logs/` - For error logs (will be created automatically)

On Linux/Unix hosting:
```bash
chmod 755 assets/uploads/vehicles/
chmod 755 logs/
```

### Step 8: Test Installation

1. Open your browser
2. Navigate to your domain (e.g., `http://yourdomain.com`)
3. You should be redirected to the login page
4. Use the default admin credentials:
   - Username: `admin`
   - Password: `admin123`

### Step 9: Change Default Password (IMPORTANT!)

1. Login with admin credentials
2. Click on your profile dropdown
3. Select "Profile"
4. Change your password immediately
5. Update your email and other details

### Step 10: Setup Complete!

You can now:
- Add vehicles to your inventory
- Manage customers
- Record sales transactions
- Schedule services
- Generate reports

## Default User Accounts

The system comes with three demo accounts:

1. **Admin Account**
   - Username: `admin`
   - Password: `admin123`
   - Full access to all features

2. **Manager Account**
   - Username: `manager1`
   - Password: `admin123`
   - Access to most features, limited user management

3. **Staff Account**
   - Username: `staff1`
   - Password: `admin123`
   - Basic access for daily operations

**IMPORTANT:** Change all passwords immediately after installation!

## Troubleshooting

### Database Connection Error
- Check that database credentials in `config/database.php` are correct
- Verify database server is running
- Check if database user has proper permissions
- For InfinityFree, ensure you're using the correct SQL hostname

### Images Not Uploading
- Check folder permissions for `assets/uploads/vehicles/`
- Verify PHP upload_max_filesize (should be at least 5MB)
- Check disk space availability
- Ensure folder exists and is writable

### Blank Page or White Screen
- Enable error display temporarily in `config/config.php`:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Check PHP error logs
- Verify all files were uploaded correctly

### Cannot Login
- Verify database was imported correctly
- Check if users table has data: `SELECT * FROM users;`
- Clear browser cache and cookies
- Try password reset (contact admin)

### PHP Version Error
- This system requires PHP 7.2 or higher
- Check PHP version: create a file `phpinfo.php` with `<?php phpinfo(); ?>`
- Contact hosting provider to upgrade PHP if needed

### Session Errors
- Check that session directory is writable
- Verify `session_start()` is working
- Clear browser cookies

### InfinityFree Specific Issues

#### Slow Performance
- InfinityFree free hosting has resource limitations
- Optimize images before uploading
- Consider upgrading to paid hosting for better performance

#### Database Limitations
- Free accounts have database size limits
- Regularly export and backup your data
- Clean up old logs periodically

#### FTP Issues
- Use FTP hostname provided by InfinityFree
- Port: 21 (standard FTP)
- Use passive mode
- FileZilla is recommended FTP client

## Backup Procedures

### Database Backup
1. Access phpMyAdmin
2. Select your database
3. Click "Export"
4. Choose "Quick" export method
5. Format: SQL
6. Download the backup file

### Files Backup
1. Connect via FTP
2. Download entire application folder
3. Store backups securely offsite
4. Schedule regular backups (weekly recommended)

## Upgrading

When upgrading to newer versions:
1. Backup database and files first
2. Read upgrade notes carefully
3. Upload new files (overwrite old ones)
4. Run any database migration scripts
5. Clear cache and test thoroughly

## Security Recommendations

1. **Change Default Passwords**
   - Never use default passwords in production
   - Use strong, unique passwords

2. **Use HTTPS**
   - Install SSL certificate (free with Let's Encrypt)
   - Force HTTPS in configuration

3. **Regular Updates**
   - Keep PHP and MySQL updated
   - Monitor for security patches

4. **File Permissions**
   - Never set 777 permissions on files
   - Use 755 for directories, 644 for files

5. **Disable Error Display**
   - Don't show errors in production
   - Log errors to file instead

6. **Regular Backups**
   - Backup database daily
   - Store backups securely offsite

7. **User Access Control**
   - Only create accounts for authorized users
   - Remove inactive accounts
   - Use role-based access appropriately

## Support and Resources

### Documentation
- User manual: See USER_MANUAL.md
- API documentation: Contact developer

### Common Tasks
- Adding vehicles: Dashboard → Add Vehicle
- Recording sales: Sales → New Sale
- Generating reports: Reports → Select report type
- Managing users: Users → Add/Edit users (Admin only)

### Getting Help
- Check troubleshooting section first
- Review PHP error logs
- Check MySQL error logs
- Contact your hosting provider for server issues

## Performance Optimization

### For Shared Hosting
1. Optimize images before uploading (compress, resize)
2. Use appropriate image formats (JPEG for photos)
3. Limit database queries where possible
4. Regular database maintenance

### Database Optimization
```sql
-- Run periodically to optimize tables
OPTIMIZE TABLE vehicles, customers, sales, service_history;
```

### Clear Old Logs
```sql
-- Clear transaction logs older than 90 days
DELETE FROM transaction_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

## Uninstallation

If you need to remove the system:
1. Backup any important data first
2. Delete all application files from hosting
3. Drop the database or delete all tables
4. Remove database user if no longer needed

## License and Credits

Car Management System v1.0.0
Developed for professional use in the Philippines
Supports Philippine Peso currency and local formats

---

**Installation Complete!** You're now ready to manage your car dealership efficiently.

For additional support or customization requests, contact your system administrator.
