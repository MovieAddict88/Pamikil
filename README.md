# 🎬 MovieStream - PHP Movie Streaming Application

A comprehensive, feature-rich movie streaming platform built with pure PHP and MySQL. Perfect for hosting on InfinityFree or any shared/dedicated hosting service.

## ✨ Features

### Admin Dashboard
- **TMDB API Integration** - Automatic movie metadata, posters, ratings, and cast information
- **YouTube Integration** - Add movies directly from YouTube
- **Bulk Movie Addition** - Add multiple movies at once using TMDB IDs
- **Multi-Server Management** - Host videos on multiple servers with quality options
- **Video Upload System** - Support for MP4, MKV, AVI, WebM, MPD/DASH formats
- **User Management** - View and manage registered users
- **Analytics Dashboard** - Track views, popular movies, and user activity

### Public Website
- **Responsive Design** - Mobile-friendly interface
- **Advanced Search** - Search by title, cast, director, or genre
- **Featured Movies** - Showcase selected movies on homepage
- **User Registration** - Optional user accounts
- **View Tracking** - Monitor movie popularity

### Video Player
- **Plyr.io Integration** - Beautiful, accessible video player
- **Shaka Player Support** - For MPD/DASH adaptive streaming
- **Multiple Server Support** - Switch between video sources
- **YouTube Embed** - Native YouTube video support
- **Quality Selection** - Multiple quality options (480p, 720p, 1080p, 4K)

## 🚀 Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PHP Extensions: PDO, cURL, JSON
- Mod_rewrite enabled (for pretty URLs)

### Quick Install (3 Steps)

1. **Upload Files**
   - Download and extract all files to your web hosting directory
   - Or clone: `git clone <repository-url>`

2. **Run Installation Wizard**
   - Visit: `http://yourdomain.com/install.php`
   - Follow the step-by-step wizard
   - Enter database credentials
   - Create admin account
   - Configure API keys (optional)

3. **Access Dashboard**
   - Admin panel: `http://yourdomain.com/admin/`
   - Public site: `http://yourdomain.com/`

### InfinityFree Hosting Setup

1. **Database Configuration**
   - Use InfinityFree's cPanel MySQL Database Wizard
   - Database host: Usually `sqlXXX.infinityfreeapp.com`
   - Note: Database names include your account prefix

2. **Upload Files**
   - Use FTP client (FileZilla recommended)
   - Upload to `htdocs` directory
   - Ensure file permissions are set correctly (755 for folders, 644 for files)

3. **Important Notes for InfinityFree**
   - Maximum file upload: 10MB (for free plan)
   - Use external video hosting or upgrade for larger files
   - YouTube embedding works perfectly on free plans
   - TMDB API integration works without issues

### Manual Installation

If you prefer manual setup:

1. **Create Database**
```sql
CREATE DATABASE moviestream CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Configure Database**
   - Copy `config/database.example.php` to `config/database.php`
   - Edit with your database credentials

3. **Import Database**
   - Run `install.php` or manually import SQL schema

4. **Set Permissions**
```bash
chmod 755 uploads/
chmod 755 uploads/movies/
chmod 755 uploads/posters/
chmod 755 uploads/temp/
```

## 🔑 API Keys Setup

### TMDB API Key (Required for auto-import)

1. Create account at [themoviedb.org](https://www.themoviedb.org/)
2. Go to Settings → API
3. Request API key (free)
4. Add to Settings in admin panel

### YouTube API Key (Optional)

1. Go to [Google Cloud Console](https://console.developers.google.com/)
2. Create new project
3. Enable YouTube Data API v3
4. Create credentials (API key)
5. Add to Settings in admin panel

## 📖 Usage Guide

### Adding Movies

#### Method 1: TMDB Auto-Import (Recommended)
1. Go to Admin → Add Movie
2. Enter TMDB ID (find on themoviedb.org)
3. Click "Search TMDB"
4. Review auto-filled data
5. Click "Add Movie"

#### Method 2: Bulk Import
1. Go to Admin → Bulk Add
2. Enter multiple TMDB IDs (one per line)
3. Click "Add Movies"
4. All movies will be imported automatically

#### Method 3: Manual Entry
1. Go to Admin → Add Movie
2. Fill in all fields manually
3. Upload poster and backdrop images
4. Click "Add Movie"

### Adding Video Sources

1. Edit any movie
2. Scroll to "Video Servers" section
3. Select or create a server
4. Add video URL
5. Choose quality and video type
6. Set as primary (optional)
7. Click "Add Server"

**Supported Video Types:**
- Direct links: MP4, MKV, WebM, AVI
- Streaming: MPD/DASH (with DRM support)
- YouTube: Full video URL or ID
- Embed: Any iFrame source

### Server Management

1. Go to Admin → Servers
2. Add server with:
   - **Direct Server**: For self-hosted videos
   - **YouTube Server**: For YouTube links
   - **Embed Server**: For third-party players

Example server configurations:
```
Name: Server 1
URL: https://cdn.example.com/videos/
Type: Direct

Name: YouTube
URL: https://www.youtube.com/
Type: YouTube

Name: External CDN
URL: https://player.example.com/embed/
Type: Embed
```

## 🎨 Customization

### Theme Colors
Edit `assets/css/style.css`:
```css
:root {
    --primary-color: #e50914;  /* Netflix red */
    --dark-bg: #141414;
    --light-bg: #1f1f1f;
}
```

### Site Settings
Admin → Settings:
- Site name and URL
- Items per page
- Enable/disable registration
- Maintenance mode

## 📱 Mobile Optimization

The application is fully responsive:
- Touch-friendly navigation
- Optimized video player
- Mobile menu
- Adaptive layouts

## 🔒 Security Features

- Password hashing (bcrypt)
- SQL injection prevention (PDO prepared statements)
- XSS protection
- CSRF token validation
- Secure file uploads
- Input sanitization
- Session management

## 🎯 Performance Tips

### For Shared Hosting
- Enable caching in `.htaccess`
- Optimize images before upload
- Use external CDN for videos
- Enable gzip compression

### For Dedicated Hosting
- Enable OPcache
- Use Redis/Memcached for sessions
- Configure MySQL query cache
- Set up video transcoding

## 🆘 Troubleshooting

### Videos Won't Play
- Check file format compatibility
- Verify server URL is accessible
- Enable CORS headers if using external CDN
- Try different video type setting

### TMDB Import Not Working
- Verify API key is correct
- Check internet connectivity
- Ensure cURL is enabled
- Try manual entry as fallback

### Upload Fails
- Check PHP upload limits
- Verify folder permissions
- Ensure adequate disk space
- Check `.htaccess` configuration

### Database Connection Error
- Verify credentials in `config/database.php`
- Check MySQL service is running
- Ensure database exists
- Test with phpMyAdmin

## 📝 File Structure

```
moviestream/
├── admin/              # Admin dashboard
│   ├── login.php
│   ├── index.php
│   ├── movies.php
│   ├── add-movie.php
│   ├── bulk-add.php
│   ├── servers.php
│   └── settings.php
├── assets/            # CSS, JS, images
│   ├── css/
│   └── js/
├── config/            # Configuration files
│   └── database.php
├── includes/          # Core functions
│   ├── db.php
│   ├── functions.php
│   ├── tmdb.php
│   └── youtube.php
├── uploads/           # User uploads
│   ├── movies/
│   ├── posters/
│   └── temp/
├── index.php          # Homepage
├── watch.php          # Video player
├── search.php         # Search page
├── login.php          # User login
├── register.php       # User registration
├── install.php        # Installation wizard
└── .htaccess          # Apache configuration
```

## 🔧 Advanced Configuration

### Custom Video Player
Edit `watch.php` to customize Plyr options:
```javascript
new Plyr('#player', {
    controls: ['play', 'progress', 'volume', 'fullscreen'],
    settings: ['quality', 'speed'],
    quality: { default: 720 }
});
```

### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_search ON movies(title, year, rating);
CREATE INDEX idx_views ON views_log(movie_id, viewed_at);
```

### Backup Strategy
```bash
# Backup database
mysqldump -u username -p database_name > backup.sql

# Backup uploads
tar -czf uploads_backup.tar.gz uploads/
```

## 📄 License

This project is open-source and available for personal and commercial use.

## 🤝 Support

For issues and questions:
- Check troubleshooting section
- Review server requirements
- Verify API configurations

## 🎬 Popular TMDB IDs for Testing

- The Shawshank Redemption: 278
- The Godfather: 238
- The Dark Knight: 155
- Pulp Fiction: 680
- Fight Club: 550
- Inception: 27205
- The Matrix: 603
- Interstellar: 157336

## 🚀 Deployment Checklist

- [ ] Database created and configured
- [ ] All files uploaded
- [ ] Folder permissions set (755/644)
- [ ] .htaccess configured
- [ ] Admin account created
- [ ] TMDB API key added
- [ ] First movie added
- [ ] Video server configured
- [ ] Test video playback
- [ ] Security headers enabled
- [ ] Backup strategy in place

## 🌟 Features Coming Soon

- User watchlists and favorites
- Movie ratings and reviews
- Categories and genres pages
- Advanced filtering
- Continue watching feature
- Email notifications
- Social sharing
- Multi-language support

---

**Made with ❤️ for movie enthusiasts**
