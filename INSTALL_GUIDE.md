# CineCraze Installation Guide

## Quick Start

### 1. Upload Files
Upload all project files to your web server (Apache/Nginx with PHP support).

### 2. Set Permissions
```bash
chmod 755 assets/
chmod 755 includes/
chmod 755 admin/
chmod 755 api/
```

### 3. Access Installation Wizard
Navigate to: `http://yourdomain.com/install.php`

### 4. Complete Installation Steps

#### Step 1: Database Configuration
- **Database Host**: Usually `localhost`
- **Database Username**: Your MySQL username
- **Database Password**: Your MySQL password
- **Database Name**: e.g., `cinecraze` (will be created automatically)

Click "Continue" - The installer will:
- Create the database if it doesn't exist
- Create all necessary tables with indexes
- Set up default settings
- Generate config.php file

#### Step 2: Create Admin Account
- **Username**: Minimum 4 characters
- **Email**: Your admin email address
- **Password**: Minimum 6 characters (will be encrypted)

Click "Complete Installation"

#### Step 3: Done!
- Visit your site
- Access admin panel
- **IMPORTANT**: Delete `install.php` for security

## Post-Installation

### 1. Login to Admin Panel
- URL: `http://yourdomain.com/admin/`
- Use the credentials you created

### 2. Configure TMDB API (Optional but Recommended)
1. Get free API key from [TMDB](https://www.themoviedb.org/settings/api)
2. Go to Admin Panel → Settings
3. Enter your TMDB API key
4. Save Settings

### 3. Add Categories (Optional)
1. Go to Admin Panel → Categories
2. Click "Add Category"
3. Enter category names like: "Action", "Drama", "Comedy", etc.

### 4. Add Content

#### Option A: Import from TMDB (Recommended)
1. Go to Admin Panel → Import from TMDB
2. Search for a movie or TV series
3. Click on the result to import
4. The system will auto-fill:
   - Title
   - Description
   - Poster image
   - Backdrop image
   - Rating
   - Genres
   - Year
5. Click "Edit" to add video sources (embed URLs)
6. Save

#### Option B: Manual Entry
1. Go to Admin Panel → Add Content
2. Fill in all details manually
3. Add video source URL (embed link)
4. Save

### 5. Test Your Site
1. Visit: `http://yourdomain.com/`
2. Browse content
3. Click to play videos
4. Test search functionality
5. Try PWA installation (Install App button)

## PWA Installation

Your site is now a Progressive Web App! Users can:
- Click the "Install" button in the header
- Or use browser's "Install App" option
- App works offline with cached content
- Runs in standalone mode (like a native app)

## Troubleshooting

### Can't access install.php
- Check file permissions
- Ensure PHP is running
- Check error logs

### Database connection failed
- Verify MySQL credentials
- Check if MySQL is running
- Ensure database user has CREATE privileges

### Images not showing
- Run: `php generate-icons.php` or `python3 generate-icons.py`
- Check assets/images/ folder permissions

### TMDB import not working
- Verify API key in Settings
- Check internet connection
- Ensure PHP has allow_url_fopen enabled

### Admin login not working
- Clear browser cache
- Check browser console for errors
- Verify username/password

### Videos not playing
- Check embed URL format
- Test embed URL directly in browser
- Ensure video source is accessible

## Security Checklist

- [ ] Deleted install.php
- [ ] Deleted generate-icons.php and generate-icons.py
- [ ] Changed default admin password
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Enable HTTPS (required for PWA)
- [ ] Keep PHP and MySQL updated

## System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache or Nginx
- **PHP Extensions**:
  - mysqli (required)
  - json (required)
  - mbstring (recommended)
  - gd (optional, for icon generation)

## Features Overview

### Frontend Features
✅ Progressive Web App (PWA)
✅ Fully Responsive (320px to 4K+)
✅ Hero Carousel
✅ Advanced Filters (Category, Type, Genre, Year)
✅ Live Search
✅ Grid/List View Toggle
✅ Multi-server Video Player
✅ Related Content
✅ Theme Toggle (Light/Dark)
✅ Lazy Loading
✅ Offline Support

### Admin Features
✅ Dashboard with Statistics
✅ Content Management (CRUD)
✅ TMDB Integration
✅ Category Management
✅ Multiple Video Sources
✅ Series/Seasons/Episodes Support
✅ Subtitle Management
✅ Settings Panel
✅ Responsive Admin UI

## Support

For issues, refer to README.md or contact your hosting provider for server-specific issues.

## Quick Reference

### Important URLs
- Main Site: `http://yourdomain.com/`
- Admin Login: `http://yourdomain.com/admin/`
- Installation: `http://yourdomain.com/install.php` (delete after use)

### Default Structure
```
/
├── index.php (Main site)
├── install.php (Delete after installation)
├── manifest.json (PWA manifest)
├── sw.js (Service worker)
├── .gitignore
├── README.md
├── INSTALL_GUIDE.md
├── /admin/ (Admin panel)
├── /api/ (REST API endpoints)
├── /includes/ (Database connection)
├── /assets/ (JavaScript, CSS, Images)
└── config.php (Auto-generated, contains DB credentials)
```

### Next Steps
1. ✅ Install the application
2. ✅ Configure TMDB API
3. ✅ Add categories
4. ✅ Import or add content
5. ✅ Test everything
6. ✅ Share your site!

---

**Congratulations!** Your CineCraze streaming platform is ready. Start adding content and enjoy! 🎬🍿
