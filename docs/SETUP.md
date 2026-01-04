# SETUP INSTRUCTIONS

## Pamikil Learning Platform - Complete Setup Guide

This guide will help you set up the Pamikil Learning Platform on both free and paid hosting environments.

---

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Local Development Setup](#local-development-setup)
3. [Free Hosting Setup (InfinityFree)](#free-hosting-setup-infinityfree)
4. [Paid Hosting Setup (cPanel)](#paid-hosting-setup-cpanel)
5. [Post-Installation Configuration](#post-installation-configuration)
6. [Testing Checklist](#testing-checklist)
7. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.2 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Disk Space**: 100 MB minimum (500 MB recommended)
- **Memory**: 128 MB PHP memory limit

### Required PHP Extensions
```bash
php -m | grep -E "(pdo|pdo_mysql|mbstring|json|fileinfo|session|openssl)"
```

All must be enabled.

---

## Local Development Setup

### Option 1: XAMPP (Windows)

1. **Download and Install XAMPP**
   - Visit: https://www.apachefriends.org/
   - Download and install XAMPP

2. **Start Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

3. **Setup Database**
   - Go to http://localhost/phpmyadmin
   - Create database: `pamikil_learning`
   - Import `database/schema.sql`
   - Import `database/sample_data.sql`

4. **Install Platform**
   - Copy files to: `C:\xampp\htdocs\pamikil`
   - Edit `config/config.php` with database credentials

5. **Access**
   - Open: http://localhost/pamikil

### Option 2: MAMP (Mac)

1. **Download and Install MAMP**
   - Visit: https://www.mamp.info/
   - Download and install MAMP

2. **Configure**
   - Open MAMP and start servers
   - Go to http://localhost:8888/phpMyAdmin
   - Follow steps 3-5 from XAMPP setup

### Option 3: WAMP (Windows)

Similar to XAMPP, use http://localhost/phpmyadmin

---

## Free Hosting Setup (InfinityFree)

### Step 1: Create Account

1. Visit https://www.infinityfree.net/
2. Click "Sign Up"
3. Complete registration
4. Confirm email address

### Step 2: Create Account

1. Log in to InfinityFree Client Area
2. Click "Create Account"
3. Fill in:
   - **Domain**: Choose a subdomain (e.g., `mylearning.rf.gd`)
   - **Category**: Select appropriate category
4. Click "Create Account"
5. Wait for account activation (usually instant)

### Step 3: Access Control Panel

1. Click "Control Panel" for your account
2. Log in with VistaPanel credentials

### Step 4: Create Database

1. In VistaPanel, click "MySQL Databases"
2. Under "Create New Database":
   - **Database Name**: Enter name (e.g., `pamikil_db`)
   - **Username**: Enter username
   - **Password**: Enter strong password
3. Click "Create Database"
4. **Important**: Copy database details:
   - MySQL Hostname (usually `sql123.infinityfree.com`)
   - Database Name (e.g., `epiz_12345678_pamikil_db`)
   - Username (e.g., `epiz_12345678`)
   - Password

### Step 5: Import Database

1. Click "phpMyAdmin" under "MySQL Databases"
2. Select your database from the left panel
3. Click "Import" tab
4. Click "Choose File"
5. Select `database/schema.sql` from your computer
6. Click "Go" at bottom
7. Repeat for `database/sample_data.sql`

### Step 6: Upload Files

#### Method A: Online File Manager

1. In VistaPanel, click "Online File Manager"
2. Navigate to `htdocs`
3. Delete all default files
4. Click "Upload Files"
5. Upload all files from the platform

#### Method B: FTP (Recommended)

1. **Get FTP Credentials**:
   - Go to "FTP Accounts" in VistaPanel
   - Note: FTP Host, FTP Username, FTP Password

2. **Use FileZilla**:
   - Download FileZilla
   - Host: FTP Host from VistaPanel
   - Username: FTP Username
   - Password: FTP Password
   - Port: 21
   - Connect

3. **Upload**:
   - Navigate to `htdocs` on remote
   - Delete all files
   - Upload all platform files

### Step 7: Configure Database

1. In File Manager, go to `htdocs/config/`
2. Edit `config.php`
3. Update database settings:
   ```php
   define('DB_HOST', 'sql123.infinityfree.com');  // Your MySQL hostname
   define('DB_NAME', 'epiz_12345678_pamikil_db'); // Your full database name
   define('DB_USER', 'epiz_12345678');         // Your database username
   define('DB_PASS', 'your_password');          // Your database password
   define('SITE_URL', 'https://yoursite.rf.gd'); // Your site URL
   ```
4. Save file

### Step 8: Set Permissions

1. In Online File Manager, select:
   - `logs` folder → Set to 777
   - `public/uploads` folder → Set to 777

### Step 9: Test

1. Visit your subdomain (e.g., `https://yoursite.rf.gd`)
2. Test login with:
   - Username: `admin`
   - Password: `password`

### InfinityFree Limitations

- **File Uploads**: Max 2MB per file
- **Execution Time**: 30 seconds max
- **Concurrent Connections**: Limited
- **CPU Usage**: Fair usage policy applies

**Tips for InfinityFree**:
- Keep images optimized
- Minimize large file uploads
- Use efficient queries
- Enable caching in browser

---

## Paid Hosting Setup (cPanel)

### Step 1: Purchase Hosting

Choose any cPanel-based hosting provider:
- Bluehost
- SiteGround
- HostGator
- A2 Hosting
- Namecheap
- Many others

### Step 2: Log in to cPanel

1. You'll receive cPanel login details via email
2. Access: `https://yourdomain.com:2083`
3. Log in with credentials

### Step 3: Create Database

1. Go to "MySQL Database Wizard"
2. **Step 1: Create Database**
   - New Database: `pamikil_learning`
   - Click "Create Database"
3. **Step 2: Create Database User**
   - Username: `pamikil_user`
   - Password: Generate strong password
   - Click "Create User"
4. **Step 3: Add User to Database**
   - Select user and database
   - Check "ALL PRIVILEGES"
   - Click "Make Changes"
5. **Important**: Save credentials

### Step 4: Import Database

1. Go to "phpMyAdmin"
2. Select your database from left panel
3. Click "Import" tab
4. Choose file: `database/schema.sql`
5. Click "Go"
6. Repeat for `database/sample_data.sql`

### Step 5: Upload Files

#### Method A: File Manager

1. Go to "File Manager"
2. Navigate to `public_html`
3. Delete all files
4. Click "Upload"
5. Upload all platform files (as .zip for faster upload)
6. Extract if uploaded as .zip

#### Method B: FTP

1. Get FTP details from cPanel > FTP Accounts
2. Use FileZilla to connect
3. Upload files to `public_html`

### Step 6: Configure

1. Go to File Manager
2. Edit `config/config.php`
3. Update:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_db_name');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_password');
   define('SITE_URL', 'https://yourdomain.com');
   ```
4. Save

### Step 7: Set Permissions

1. Right-click `logs` folder → Change Permissions → 777
2. Right-click `public/uploads` folder → Change Permissions → 777

### Step 8: Install SSL

1. Go to "Let's Encrypt SSL" (or "SSL/TLS Status")
2. Select your domain
3. Click "Issue"
4. Wait for installation
5. Enable force HTTPS in `.htaccess` if desired

### Step 9: Configure Email (Optional)

1. Go to "Email Accounts"
2. Create admin email (e.g., `admin@yourdomain.com`)
3. Update config with new admin email

---

## Post-Installation Configuration

### 1. Change Admin Password

1. Log in as admin
2. Go to Profile
3. Change password
4. Save

### 2. Create Users

**For Parents**:
1. Log in as admin
2. Go to Users > Add User
3. Create parent accounts

**For Students**:
1. Parents can register student accounts
2. Or admin can create them

### 3. Customize Site

1. Go to Admin > Settings
2. Update:
   - Site Name
   - Site Description
   - Email Address
   - Other settings

### 4. Test Activities

1. Log in as student
2. Try different activity types
3. Verify rewards are awarded
4. Test progress tracking

### 5. Configure Parent Controls

1. Log in as parent
2. Go to child's profile
3. Set:
   - Time limits
   - Allowed subjects
   - Blocked hours
   - Notifications

---

## Testing Checklist

### Functionality Tests

- [ ] User can register (parent)
- [ ] User can register (student)
- [ ] Login works for all roles
- [ ] Logout works correctly
- [ ] Password reset works
- [ ] Student can view activities
- [ ] Quiz activity works correctly
- [ ] Flashcard activity works
- [ ] Drag-and-drop works
- [ ] Rewards are awarded
- [ ] XP increases correctly
- [ ] Badges are earned
- [ ] Leaderboard updates
- [ ] Avatar customization works
- [ ] Parent can view progress
- [ ] Parent can set time limits
- [ ] Admin can manage users
- [ ] Admin can manage activities
- [ ] Admin can view reports
- [ ] Settings can be updated

### Security Tests

- [ ] SQL injection attempts are blocked
- [ ] XSS attacks are prevented
- [ ] CSRF tokens work
- [ ] Passwords are hashed
- [ ] Sessions expire correctly
- [ ] Sensitive directories are protected

### Performance Tests

- [ ] Page load time < 3 seconds
- [ ] Activities load smoothly
- [ ] No memory errors
- [ ] Database queries are efficient

### Mobile Tests

- [ ] Works on iPhone
- [ ] Works on Android
- [ ] Works on iPad
- [ ] Works on tablets
- [ ] Touch controls work
- [ ] Responsive layout works

---

## Troubleshooting

### Common Issues

#### 1. "Database Connection Failed"

**Symptoms**: Error message about database connection

**Solutions**:
- Verify database credentials in config.php
- Check MySQL service is running
- Verify database exists
- Check user has proper privileges
- Test connection via phpMyAdmin

#### 2. "500 Internal Server Error"

**Symptoms**: Generic server error

**Solutions**:
- Check .htaccess syntax
- Verify PHP version compatibility
- Check error logs: `logs/error.log`
- Ensure file permissions are correct
- Disable .htaccess temporarily to test

#### 3. "Session Expired Too Often"

**Symptoms**: Frequent logouts

**Solutions**:
- Increase SESSION_TIMEOUT in config
- Check session.save_path permissions
- Verify cookies are enabled
- Check time zone settings
- Ensure domain is consistent

#### 4. "File Upload Failed"

**Symptoms**: Can't upload images/files

**Solutions**:
- Check upload_max_filesize in php.ini
- Verify post_max_size in php.ini
- Check directory permissions (777)
- Verify fileinfo extension is enabled
- Check available disk space

#### 5. "White Screen of Death"

**Symptoms**: Blank page, no errors

**Solutions**:
- Enable DEBUG_MODE in config
- Check PHP error log
- Verify all PHP files are present
- Check for syntax errors
- Increase memory_limit in php.ini

### Getting Help

1. Check error logs: `logs/error.log`
2. Enable DEBUG_MODE in config
3. Review this documentation
4. Search online for specific errors
5. Contact hosting support

---

## Next Steps

1. **Customize Design**: Edit CSS in `public/css/style.css`
2. **Add Content**: Use admin panel to create activities
3. **Configure Email**: Set up SMTP for password resets
4. **Set Up Backups**: Automate database backups
5. **Monitor Analytics**: Use Google Analytics or similar
6. **Scale**: Upgrade hosting as user base grows

---

## Support

For additional help:
- Check README.md for overview
- Review code comments
- Check logs directory
- Enable debug mode for detailed errors
