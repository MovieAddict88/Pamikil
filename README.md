# CineCraze - Professional Streaming Platform

A modern, responsive streaming platform built with PHP, MySQL, and PWA technology. Features movies, TV series, live TV, and a comprehensive admin dashboard.

## Features

### 🎬 Main Application (index.php)
- **Progressive Web App (PWA)** - Installable on mobile and desktop
- **Responsive Design** - Works seamlessly from smartphones to smart TVs
- **Bottom Navigation** - Home, Movies, Series, Live TV, Watch Later
- **Hero Carousel** - Featured content with smooth transitions
- **Advanced Search** - Real-time search with autocomplete
- **Professional Filters** - Filter by type, genre, year, and more
- **Server Selection** - Multiple video source options
- **Watch Later** - Save content for later viewing with IndexedDB caching
- **Offline Support** - Works offline with cached content
- **Auto Updates** - Detects new content automatically
- **Theme Toggle** - Dark/light mode support
- **IndexedDB Integration** - Client-side caching for better performance

### 🛠️ Admin Dashboard (admin.php)
- **Content Management** - Add, edit, delete movies, series, and live TV
- **TMDB Integration** - Import metadata from The Movie Database
- **Auto Embed** - Automatically generate video sources
- **Bulk Operations** - Update multiple items at once
- **Data Export/Import** - Backup and restore functionality
- **Statistics Dashboard** - View platform analytics
- **Settings Management** - Configure API keys and preferences

### 🚀 Installation
1. **Run the installer** - Navigate to `install.php` in your browser
2. **Database Setup** - Provide MySQL credentials (host, username, password, database name)
3. **Automatic Installation** - The script will create all necessary tables and configuration
4. **Access the Platform** - Visit `index.php` for the main app or `admin-login.php` for admin access

### 📱 PWA Installation
- **Desktop**: Look for the install icon in the address bar
- **Mobile**: Use "Add to Home Screen" from the browser menu
- **Automatic Updates** - The app updates automatically when new versions are available

### 🔑 Default Admin Credentials
- **Username**: `admin`
- **Password**: `admin123`

### 🗄️ Database Structure
- **categories** - Content categories and subcategories
- **content** - Main content table (movies, series, live TV)
- **series** - Season information for TV series
- **episodes** - Individual episode data
- **servers** - Video streaming sources
- **watch_later** - User's saved content
- **settings** - Application configuration

### 🎨 Responsive Design
- **Mobile First** - Optimized for mobile devices
- **Clamp() Functions** - Fluid typography and spacing
- **Media Queries** - Tailored for different screen sizes
- **Flexible Grid** - Adapts to any screen size
- **Touch Friendly** - Optimized for touch interactions

### 📊 Technology Stack
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Vanilla JavaScript, CSS3, HTML5
- **PWA**: Service Workers, Web App Manifest
- **Storage**: IndexedDB for client-side caching
- **Icons**: Font Awesome 6.4.0
- **Video Player**: Plyr.js 3.7.8
- **Fonts**: Inter (Google Fonts)

### 🎯 Key Features Implementation

#### Progressive Web App (PWA)
- **Service Worker** - Handles caching and offline functionality
- **Web App Manifest** - Configures app appearance and behavior
- **Background Sync** - Syncs data when connection is restored
- **Push Notifications** - Ready for future notification features

#### Watch Later System
- **Client-Side Storage** - Uses IndexedDB for offline access
- **Server Sync** - Automatically syncs with server database
- **Cross-Device** - Works across all devices with the same user account
- **Real-Time Updates** - Instant addition/removal from the list

#### Content Management
- **TMDB Integration** - Fetch metadata automatically
- **Auto Embed Sources** - Generate video sources from TMDB IDs
- **Bulk Operations** - Update multiple items efficiently
- **Data Validation** - Ensures data integrity

#### Responsive Design
- **CSS Clamp()** - Fluid typography and spacing
- **Mobile-First** - Optimized for mobile, enhanced for desktop
- **Flexible Layouts** - CSS Grid and Flexbox
- **Touch Gestures** - Swipe navigation and touch interactions

### 🔧 Configuration

#### TMDB API Setup
1. Get API key from [The Movie Database](https://www.themoviedb.org/settings/api)
2. Go to Admin Dashboard → Settings
3. Enter your TMDB API key
4. Save settings

#### GitHub Integration (Optional)
1. Create Personal Access Token in GitHub
2. Set repository in format: `username/repository`
3. Configure in Admin Dashboard → Settings

### 📱 Supported Devices
- **Smartphones** - iOS and Android
- **Tablets** - iPad, Android tablets
- **Desktop** - Windows, macOS, Linux
- **Smart TVs** - WebOS, Tizen, Android TV
- **Gaming Consoles** - PlayStation, Xbox (browser support)

### 🚀 Performance Optimizations
- **Lazy Loading** - Images and content load on demand
- **Caching Strategy** - Smart caching for offline usage
- **Database Indexing** - Optimized queries
- **CDN Integration** - External resources loaded from CDN
- **Minified Assets** - Optimized file sizes

### 🔒 Security Features
- **Input Validation** - All inputs sanitized and validated
- **SQL Injection Protection** - Prepared statements used throughout
- **XSS Prevention** - Output properly escaped
- **CSRF Protection** - Form tokens for state-changing operations
- **Admin Authentication** - Session-based admin login

### 📈 Analytics Ready
- **View Tracking** - Track content views
- **User Behavior** - Monitor search patterns
- **Performance Metrics** - Load times and errors
- **Content Popularity** - Most watched content

### 🎨 Customization
- **Theme Colors** - Easily change primary/secondary colors
- **Logo** - Replace with your own branding
- **Content Types** - Add new content categories
- **Server Sources** - Add new video hosting providers

### 📝 File Structure
```
/
├── install.php              # Auto-installation script
├── index.php               # Main streaming application
├── admin.php               # Admin dashboard
├── admin-login.php         # Admin login page
├── admin-settings.php      # Settings API endpoint
├── api.php                 # Main API endpoint
├── config.php              # Database configuration
├── manifest.json           # PWA manifest
├── sw.js                   # Service worker
├── icons/                  # PWA icons (create these)
└── README.md               # This file
```

### 🎯 Usage Examples

#### Adding Content via Admin
1. Login to admin dashboard
2. Go to Content Management
3. Click "Add Content"
4. Fill in details or import from TMDB
5. Add video sources
6. Save content

#### Using Watch Later
1. Browse content on main app
2. Click "Watch Later" on any content
3. Access saved content via Watch Later tab
4. Remove items when done

#### Installing as PWA
1. Visit the site in a supported browser
2. Look for install prompt or use browser menu
3. Install to home screen or desktop
4. Launch like a native app

### 🆘 Troubleshooting

#### Installation Issues
- Ensure MySQL server is running
- Check database credentials
- Verify PHP extensions (PDO, JSON)
- Check file permissions

#### PWA Issues
- Clear browser cache
- Check service worker registration
- Verify HTTPS connection (required for PWA)
- Update browser to latest version

#### Performance Issues
- Check database indexes
- Optimize image sizes
- Enable browser caching
- Monitor server resources

### 🔮 Future Enhancements
- **User Accounts** - Multiple user profiles
- **Social Features** - Comments, ratings, sharing
- **Advanced Search** - AI-powered recommendations
- **Multi-Language** - Internationalization support
- **Live Streaming** - Real-time broadcast support
- **Payment Integration** - Subscription model
- **Advanced Analytics** - Detailed usage statistics

### 📞 Support
For technical support or feature requests, please refer to the documentation or create an issue in the project repository.

---

**CineCraze** - Your ultimate streaming destination! 🎬✨