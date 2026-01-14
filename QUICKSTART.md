# Pamikil Car Management System - Quick Start Guide

## Quick Start for InfinityFree

1. **Upload Files**
   - Use File Manager to upload all files to `htdocs`

2. **Create Database**
   - Go to MySQL Databases in VistaPanel
   - Create database (e.g., `pamikil_db`)
   - Create user and assign to database
   - Copy database credentials

3. **Import Schema**
   - Go to phpMyAdmin
   - Select your database
   - Import `database/schema.sql`
   - Click Go

4. **Configure Database**
   - Open `config/database.php`
   - Update DB_HOST, DB_USER, DB_PASS, DB_NAME
   - Save

5. **Run Setup**
   - Visit `yoursite.infinityfreeapp.com/setup.php`
   - Click continue
   - Note the admin credentials shown

6. **Login**
   - Go to `yoursite.infinityfreeapp.com/auth/login.php`
   - Login with credentials from setup
   - **Delete setup.php immediately for security!**

7. **Change Password**
   - Go to Profile
   - Change your password immediately

## Default Admin Password

The default password for admin user is: `admin123`

After running setup.php, you can login with:
- Username: `admin`
- Password: `admin123`

## Troubleshooting

### Can't Login
- Run setup.php again
- Check database credentials
- Verify schema was imported

### Database Error
- Verify credentials in config/database.php
- Check database was created
- Verify user has permissions

## Support

See README.md for complete documentation
