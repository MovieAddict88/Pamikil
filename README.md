# CineCraze - PHP MySQL Streaming Platform

A professional, fully responsive movie and series streaming platform built with PHP and MySQL. Features include PWA support, TMDB integration, and a powerful admin dashboard.

## Features

### Frontend (index.php)
- **Progressive Web App (PWA)** - Install as a native app on any device
- **Fully Responsive** - Optimized for smartphones, tablets, laptops, desktops, and smart TVs (320px to 4K+)
- **Modern UI/UX** - Netflix-inspired design with smooth animations
- **Hero Carousel** - Showcase featured content
- **Advanced Filters** - Filter by category, type, genre, and year
- **Live Search** - Real-time search with instant results
- **Grid/List View Toggle** - Switch between viewing modes
- **Video Player** - Embedded player with multiple servers and quality options
- **Related Content** - Discover similar movies and series
- **Like/Dislike System** - User engagement features
- **View Counter** - Track content popularity
- **Lazy Loading** - Optimized image loading for better performance
- **Theme Toggle** - Light/Dark mode support
- **Offline Support** - Service worker caching

### Admin Dashboard (admin/)
- **Comprehensive Dashboard** - Statistics and quick actions
- **Content Management** - Full CRUD operations for movies and series
- **TMDB Integration** - Import content directly from The Movie Database
- **Category Management** - Organize content into categories
- **Media Management** - Upload and manage posters, backdrops
- **Source Management** - Multiple streaming sources per content
- **Series Support** - Seasons and episodes management
- **Subtitle Support** - Multiple language subtitles
- **Settings Panel** - Configure site settings
- **Responsive Admin UI** - Works on all devices

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PHP Extensions:
  - mysqli
  - gd (optional, for icon generation)
  - json
  - mbstring

## Installation

1. **Upload Files**
   ```bash
   # Upload all files to your web server
   ```

2. **Set Permissions**
   ```bash
   chmod 755 assets/
   chmod 755 uploads/
   ```

3. **Run Installation**
   - Navigate to: `http://yourdomain.com/install.php`
   - Follow the installation wizard:
     - Step 1: Database Configuration
     - Step 2: Create Admin Account
     - Step 3: Complete!

4. **Generate PWA Icons** (Optional)
   ```bash
   php generate-icons.php
   ```

5. **Security**
   - Delete `install.php` after installation
   - Delete `generate-icons.php` after generating icons

## Configuration

### TMDB API (Optional)
To enable TMDB import functionality:
1. Get your API key from [TMDB](https://www.themoviedb.org/settings/api)
2. Go to Admin Panel → Settings
3. Enter your TMDB API key

### Database Configuration
All database settings are stored in `config.php` (auto-generated during installation)

## Usage

### Adding Content

#### Method 1: Manual Entry
1. Go to Admin Panel → Add Content
2. Fill in the content details
3. Add video source URL
4. Save

#### Method 2: TMDB Import
1. Go to Admin Panel → Import from TMDB
2. Search for movie or TV series
3. Click to import
4. Add video sources manually

### Managing Categories
1. Go to Admin Panel → Categories
2. Add, edit, or delete categories
3. Assign content to categories

### Managing Users
- Default admin account created during installation
- Login at: `http://yourdomain.com/admin/`

## Responsive Breakpoints

- **Smartphones**: 320px - 480px (2 columns)
- **Tablets**: 481px - 768px (3 columns)
- **Laptops**: 769px - 1024px (4 columns)
- **Desktops**: 1025px - 1440px (5 columns)
- **Large Desktops**: 1441px - 1920px (6 columns)
- **4K TVs**: 2560px+ (8 columns)

All text uses `clamp()` for fluid typography and elements use media queries for optimal display.

## PWA Features

- **Install Prompt** - Automatic installation prompt
- **Offline Support** - Browse cached content offline
- **App Icons** - Multiple sizes for all devices
- **Standalone Mode** - Full-screen app experience
- **Push Notifications** - (Ready for implementation)

## API Endpoints

### Public API
- `GET /api/content.php?action=list` - List all content
- `GET /api/content.php?action=get&id={id}` - Get single content
- `GET /api/content.php?action=featured` - Get featured content
- `GET /api/content.php?action=related&id={id}` - Get related content
- `GET /api/categories.php?action=list` - List categories

### Admin API (Requires authentication)
- `POST /admin/api.php?action=add_content` - Add content
- `POST /admin/api.php?action=update_content` - Update content
- `POST /admin/api.php?action=delete_content` - Delete content
- `GET /admin/api.php?action=tmdb_search` - Search TMDB
- `POST /admin/api.php?action=tmdb_import` - Import from TMDB

## Database Schema

### Tables
- `content` - Movies, series, and live content
- `categories` - Content categories
- `sources` - Video streaming sources
- `seasons` - TV series seasons
- `episodes` - Season episodes
- `episode_sources` - Episode streaming sources
- `subtitles` - Subtitle files
- `users` - Admin users
- `settings` - Site settings

## Security Features

- Password hashing (bcrypt)
- SQL injection protection (prepared statements)
- XSS protection (output escaping)
- Session management
- Admin authentication required
- CSRF protection ready

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Opera
- Mobile browsers (iOS Safari, Chrome Mobile, etc.)

## Performance Optimizations

- Lazy loading images
- Service worker caching
- Optimized database queries with indexes
- CDN for external libraries
- Minification ready
- Image optimization recommendations

## Customization

### Themes
- Modify CSS variables in `index.php` and `admin/index.php`
- Colors, spacing, and typography all customizable

### Layout
- Grid columns configured via CSS Grid
- Responsive breakpoints in media queries
- Easy to adjust for your needs

## Troubleshooting

### Installation Issues
- Check database credentials
- Ensure PHP mysqli extension is enabled
- Check folder permissions

### PWA Not Working
- Requires HTTPS (except localhost)
- Check service worker registration
- Clear browser cache

### Images Not Loading
- Check file permissions
- Verify image URLs are accessible
- Run `generate-icons.php` for PWA icons

## Support

For issues and feature requests, please check the documentation or contact support.

## License

Copyright © 2024 CineCraze. All rights reserved.

## Credits

- Font Awesome for icons
- Plyr for video player
- TMDB for movie database
- Google Fonts for typography

---

**Note**: This is a content management platform. Ensure you have rights to stream any content you add.
