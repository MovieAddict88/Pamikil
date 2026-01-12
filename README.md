# Pamikil Learning Platform

A complete, production-ready interactive learning platform for children built with vanilla PHP and MySQL.

## Features

- **7 Interactive Activity Types**: Quizzes, Stories, Drag-and-Drop, Flashcards, Jigsaw Puzzles, Crosswords, and Matching Games
- **3 User Roles**: Students, Parents, and Administrators with different capabilities
- **Gamification System**: Coins, XP, badges, achievements, and leaderboards
- **Avatar Customization**: Unlockable items for personalization
- **Content Management System**: Organized by subjects, age groups, and difficulty levels
- **Parent Controls**: Time limits, content restrictions, and progress monitoring
- **Admin Dashboard**: Full platform management and analytics
- **Responsive Design**: Mobile-first approach supporting 320px to 1920px+ screens
- **Accessibility**: ARIA labels, keyboard navigation, high contrast mode, font size controls

## Tech Stack

- **Backend**: Pure PHP 7.4+ (no frameworks)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3 (Grid, Flexbox, clamp()), Vanilla JavaScript
- **No External Dependencies**: Completely self-contained

## Requirements

### Server Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Apache 2.4+ or Nginx 1.18+
- mod_rewrite (Apache) or URL rewriting support (Nginx)
- SSL/TLS certificate (recommended for production)

### PHP Extensions

- PDO
- PDO_MySQL
- mbstring
- json
- fileinfo
- session
- openssl

## Quick Start

### 1. Clone or Download

```bash
git clone <repository-url>
cd pamikil
```

### 2. Configure

Edit `config/config.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE pamikil_learning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 4. Import Schema and Sample Data

```bash
mysql -u your_username -p pamikil_learning < database/schema.sql
mysql -u your_username -p pamikil_learning < database/sample_data.sql
```

### 5. Set Permissions

```bash
chmod -R 755 .
chmod -R 777 public/uploads logs
```

### 6. Access Platform

Open your browser and navigate to:
```
http://your-domain.com
```

**Default Admin Credentials:**
- Username: `admin`
- Password: `password`

**Default Parent Account:**
- Username: `parent1`
- Password: `password`

**Default Student Accounts:**
- Username: `student1` (Emma, Age 6, linked to parent1)
- Username: `student2` (Jake, Age 8, linked to parent1)
- Password: `password`

## Deployment

### Free Hosting (InfinityFree)

1. **Upload Files**
   - Use File Manager or FTP to upload all files
   - Upload to `htdocs` or `public_html` directory

2. **Create Database**
   - Go to Control Panel > MySQL Databases
   - Create a new database and user
   - Note the database name, username, and password

3. **Import SQL Files**
   - Go to phpMyAdmin
   - Select your database
   - Import `database/schema.sql`
   - Import `database/sample_data.sql`

4. **Configure**
   - Edit `config/config.php` with your database details

5. **Test**
   - Visit your domain and test the platform

### Paid Hosting (cPanel)

1. **Upload Files**
   - Use File Manager or FTP (FileZilla)
   - Upload to `public_html`

2. **Create Database**
   - cPanel > MySQL Database Wizard
   - Follow the wizard to create a database and user
   - Assign all privileges

3. **Import Database**
   - cPanel > phpMyAdmin
   - Select your database
   - Import SQL files

4. **Configure**
   - Edit `config/config.php`

5. **SSL Certificate**
   - Enable Let's Encrypt SSL in cPanel
   - Force HTTPS redirect in `.htaccess`

### Shared Hosting Considerations

- **Memory Limit**: Ensure PHP memory limit is at least 128MB
- **Upload Size**: Set `upload_max_filesize` and `post_max_size` to 10MB+
- **Execution Time**: Set `max_execution_time` to 300 seconds
- **File Permissions**: Use 755 for directories, 644 for files

## Directory Structure

```
pamikil/
├── admin/                  # Admin panel pages
├── api/                    # API endpoints
│   ├── admin/             # Admin API routes
│   ├── save-progress.php
│   ├── complete-activity.php
│   ├── purchase-avatar.php
│   └── equip-avatar.php
├── auth/                   # Authentication pages
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── forgot-password.php
│   └── reset-password.php
├── config/                 # Configuration files
│   └── config.php         # Main configuration
├── database/              # Database files
│   ├── schema.sql        # Database schema
│   └── sample_data.sql   # Sample data
├── docs/                  # Documentation
│   ├── SETUP.md          # Setup guide
│   ├── PARENT_GUIDE.md   # Parent guide
│   └── README.md         # This file
├── includes/              # PHP classes and functions
│   ├── Activity.php      # Activity management
│   ├── Admin.php         # Admin functions
│   ├── Auth.php          # Authentication
│   ├── Database.php      # Database connection
│   ├── Gamification.php  # Gamification features
│   ├── functions.php     # Utility functions
│   └── Security.php      # Security utilities
├── logs/                  # Application logs
├── public/                # Public files
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript
│   ├── images/          # Images
│   ├── uploads/         # User uploads
│   ├── assets/          # Audio files
│   ├── admin/           # Admin pages
│   └── *.php            # Public pages
├── templates/             # Template files
│   ├── header.php       # Site header
│   └── footer.php       # Site footer
├── .htaccess            # Apache configuration
├── .gitignore           # Git ignore file
├── index.php            # Main entry point
└── README.md            # This file
```

## Security Features

- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Input sanitization and output encoding
- **CSRF Protection**: Token-based verification for forms
- **Password Security**: Bcrypt hashing with cost factor 10
- **Session Management**: Secure cookies with timeout handling
- **Input Validation**: Server-side validation for all user inputs
- **File Upload Security**: MIME type validation, file size limits
- **Security Headers**: X-Frame-Options, X-XSS-Protection, etc.

## Troubleshooting

### Database Connection Errors

**Problem**: Unable to connect to database

**Solutions**:
1. Verify database credentials in `config.php`
2. Check if MySQL service is running
3. Verify database exists and user has privileges
4. Check firewall settings

### Session Issues

**Problem**: Users being logged out frequently

**Solutions**:
1. Check `session.save_path` permissions
2. Increase `session.gc_maxlifetime` in php.ini
3. Ensure cookies are enabled in browser
4. Check time zone settings

### File Upload Issues

**Problem**: Unable to upload files

**Solutions**:
1. Check `upload_max_filesize` and `post_max_size` in php.ini
2. Verify directory permissions (777 for uploads)
3. Check available disk space
4. Ensure `fileinfo` extension is enabled

### Performance Issues

**Problem**: Site is slow

**Solutions**:
1. Enable GZIP compression
2. Add browser caching headers
3. Optimize database indexes
4. Use CDN for static assets
5. Enable PHP OPcache
6. Consider upgrading hosting plan

### .htaccess Not Working

**Problem**: URL routing fails

**Solutions**:
1. Ensure mod_rewrite is enabled
2. Check `AllowOverride` in Apache config
3. Verify .htaccess file permissions
4. Check syntax errors in .htaccess

## Maintenance

### Regular Tasks

1. **Backup Database**
   ```bash
   mysqldump -u username -p database_name > backup.sql
   ```

2. **Clear Logs**
   ```bash
   rm logs/*.log
   ```

3. **Update Content**
   - Use admin panel to add new activities
   - Monitor user activity
   - Review and moderate user-generated content

4. **Check Security**
   - Update PHP version regularly
   - Keep database updated
   - Monitor error logs
   - Review access logs

## Support

For issues, questions, or contributions:

- Check documentation in the `docs/` directory
- Review error logs in the `logs/` directory
- Enable debug mode in `config.php` for detailed errors
- Check `database/schema.sql` for table relationships

## License

MIT License - See LICENSE file for details

## Credits

Developed by Pamikil Learning Team

---

**Note**: This platform is designed for educational purposes. Ensure compliance with local regulations for data privacy and child protection when deploying in production environments.
