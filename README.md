# CineCraze - Premium Movie Streaming Platform

A comprehensive PHP/MySQL-powered Progressive Web App (PWA) for streaming movies, TV series, and live TV content with admin dashboard and advanced features.

## 🚀 Features

### Frontend (index.php)
- **Progressive Web App (PWA)** - Installable, works offline
- **Responsive Design** - Optimized for smartphones to smart TVs using clamp() and media queries
- **Bottom Tab Navigation** - Home, Movies, Series, Live TV, Watch Later
- **Watch Later Functionality** - Save content for later with IndexedDB storage
- **Professional Search** - Real-time search with professional styling
- **Server Selection** - Multiple video server options
- **Content Caching** - IndexedDB for offline viewing and auto-sync
- **Auto-detect Changes** - Syncs with database when content updates
- **YouTube Integration** - Watch YouTube content within the app

### Backend & Database
- **Auto-Installation Script** - One-click PHP/MySQL setup
- **Admin Dashboard** - Complete content management system
- **YouTube API Integration** - Import content directly from YouTube
- **TMDB Integration** - Movie database import functionality
- **Bulk Import** - JSON-based bulk content upload
- **Data Export** - Export data in JSON, CSV, or SQL formats
- **User Management** - Role-based access control
- **Content Analytics** - View counts and engagement metrics

### Database Structure
- Categories, Movies, Series, Live TV content
- Multiple video servers per content
- Season and episode management
- User authentication and profiles
- Watch later functionality
- View history tracking
- Settings and configuration

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- YouTube API Key (optional, for YouTube import)
- TMDB API Key (optional, for TMDB import)

## 🛠️ Installation

### 1. Quick Installation
1. Upload all files to your web server
2. Navigate to `http://your-domain.com/install.php`
3. Follow the installation wizard
4. Configure your database settings
5. Set admin credentials
6. Complete installation

### 2. Manual Installation
1. Create a MySQL database
2. Import the SQL structure (auto-created by installer)
3. Configure `config/database.php` manually
4. Set proper file permissions

### 3. Configuration
Update `config/database.php` with your settings:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cinecraze_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

## 🎯 Usage

### Admin Dashboard
1. Visit `/login.php`
2. Use credentials: `admin` / `admin123` (or your custom credentials)
3. Access admin features:
   - Add/Edit/Delete content
   - Import from YouTube/TMDB
   - Bulk import via JSON
   - Export data
   - Manage users

### Adding Content
1. **Manual Addition**: Use the admin form to add individual content
2. **YouTube Import**: Search and import YouTube videos
3. **TMDB Import**: Search The Movie Database for content
4. **Bulk Import**: Upload JSON file with multiple content items

### User Features
- Browse content by category
- Search functionality
- Watch later saved content
- Offline viewing (PWA)
- Multiple server options
- Responsive viewing on all devices

## 📱 Progressive Web App

### Installation
- Visit the site on mobile/desktop
- Look for "Install App" prompt or use browser menu
- App will be installed like a native app

### Offline Features
- Cached content for offline viewing
- Service worker for background sync
- Watch later sync when online
- Push notifications for new content

## 🔧 API Endpoints

### Content API
- `GET /api/sync.php?action=movies` - Get all movies
- `GET /api/sync.php?action=categories` - Get categories
- `GET /api/sync.php?action=search&q=query` - Search content
- `GET /api/sync.php?action=sync` - Sync all data

### Watch Later API
- `POST /api/sync.php` - Add/remove from watch later
- `GET /api/sync.php?action=watchlater` - Get user's watch later

### Import API
- `POST /api/sync.php` - YouTube search and import
- `POST /api/sync.php` - TMDB search and import

### Export API
- `GET /api/export.php?format=json` - Export as JSON
- `GET /api/export.php?format=csv` - Export as CSV
- `GET /api/export.php?format=backup` - Full database backup

## 🎨 Customization

### Styling
- CSS variables in `:root` for easy theme customization
- Responsive design using clamp() and media queries
- Professional color scheme and typography
- Mobile-first approach

### Configuration
- API keys in database settings table
- Site name and description
- Content categories
- Server configurations

## 🔒 Security

- SQL injection protection with prepared statements
- XSS protection with proper escaping
- CSRF protection for forms
- Secure session management
- Input validation and sanitization

## 📊 Performance

- IndexedDB caching for offline performance
- Lazy loading for images
- Service worker for background sync
- Optimized database queries
- Compressed responses
- CDN-ready assets

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection**: Check credentials in `config/database.php`
2. **Installation Fails**: Ensure MySQL user has CREATE privileges
3. **PWA Not Installing**: Check HTTPS requirement and manifest.json
4. **YouTube Import**: Verify YouTube API key configuration
5. **TMDB Import**: Check TMDB API key in admin settings

### Debug Mode
Enable error reporting by setting:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📄 License

This project is open source and available under the MIT License.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For support and questions:
- Check the documentation
- Review the code comments
- Check common troubleshooting steps

## 🔄 Updates

### Version 1.0.0
- Initial release with full PWA functionality
- Admin dashboard with content management
- YouTube and TMDB integration
- Watch later functionality
- Responsive design for all devices
- Auto-installation script
- IndexedDB caching and offline support

---

**Built with ❤️ using PHP, MySQL, HTML5, CSS3, and JavaScript**