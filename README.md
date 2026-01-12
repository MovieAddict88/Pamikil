# Karaoke App - Comprehensive PHP/MySQL Application

A professional-grade karaoke application built with pure PHP and MySQL, featuring real-time queue management, YouTube integration, responsive design, and comprehensive admin controls.

## 🎵 Features

### Core Functionality
- **Solo & Room Modes**: Individual sessions and collaborative karaoke rooms
- **Real-time Queue Management**: Live queue updates with WebSocket and polling fallback
- **Professional Scoring System**: Pitch detection and timing accuracy measurement
- **Responsive Design**: Mobile-first design with CSS media queries and clamp() functions

### Admin Panel
- **YouTube Integration**: Search and import individual videos or entire playlists
- **Media Management**: Upload support for multiple video formats (MP4, AVI, MOV, WMV, etc.)
- **Song Database Management**: Complete CRUD operations for songs, categories, and playlists
- **Embed Code Support**: Integration with external video platforms

### User Experience
- **Song Selection**: Number-based entry system like traditional karaoke machines
- **Digital Songbook**: Searchable song database with categories and artists
- **Room Management**: Create/join rooms with private or public access
- **Real-time Features**: Live chat, reactions, and collaborative features

## 🛠 Technology Stack

- **Backend**: Pure PHP 8.0+ with custom MVC architecture
- **Database**: MySQL 8.0+ with optimized schema
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Video Players**: YouTube API, HTML5 video, embedded players
- **Real-time**: WebSocket with polling fallback
- **Security**: CSRF protection, input validation, SQL injection prevention

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- YouTube Data API v3 key (optional, for YouTube integration)

## 🚀 Installation

### 1. Setup Database

```bash
# Create database
mysql -u root -p
CREATE DATABASE karaoke_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

# Import schema and sample data
mysql -u root -p karaoke_app < database.sql
```

### 2. Configure Application

Create `.env` file in the project root:

```env
# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=your-secret-key-here

# Database
DB_HOST=localhost
DB_DATABASE=karaoke_app
DB_USERNAME=root
DB_PASSWORD=your-password

# YouTube API (Optional)
YOUTUBE_API_KEY=your-youtube-api-key

# Session
SESSION_LIFETIME=120
```

### 3. Setup Web Server

#### Apache (.htaccess)
Create `public/.htaccess`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
Add to your nginx configuration:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 4. File Permissions

```bash
# Set proper permissions
chmod 755 public/uploads
chmod 755 storage
chmod 755 storage/sessions
chmod 755 storage/logs
chmod 755 storage/cache
```

### 5. Install Dependencies

No external dependencies required - pure PHP implementation!

## 🎮 Usage

### Default Accounts
- **Admin**: admin@karaoke.app / password
- **Demo**: demo@karaoke.app / password
- **User**: user1@karaoke.app / password

### Creating Your First Room

1. Login with any account
2. Click "Create Room" on dashboard
3. Configure room settings (public/private, max participants, etc.)
4. Share room code with friends
5. Start adding songs to the queue!

### Adding Songs

#### Method 1: YouTube Integration (Admin)
1. Go to Admin → Song Management
2. Click "Import from YouTube"
3. Search for videos or paste playlist URL
4. Import automatically adds songs to database

#### Method 2: Manual Entry (Admin)
1. Go to Admin → Song Management
2. Click "Add New Song"
3. Enter song details and media information
4. Save to add to database

#### Method 3: File Upload (Admin)
1. Go to Admin → Media Upload
2. Upload video/audio files
3. Reference uploaded files when creating songs

## 🏗 Architecture

### Directory Structure
```
karaoke-app/
├── app/
│   ├── Controllers/     # Request handlers
│   ├── Models/          # Data models
│   ├── Views/           # Template views
│   └── ...
├── config/              # Configuration files
├── public/              # Web-accessible files
│   ├── assets/          # CSS, JS, images
│   └── uploads/         # Uploaded media
├── routes/              # Route definitions
├── storage/             # Application storage
├── database.sql         # Database schema
└── index.php            # Application entry point
```

### MVC Pattern
- **Models**: Handle data logic and database operations
- **Views**: Present data to users with templates
- **Controllers**: Handle user requests and coordinate between models and views

### Real-time Features
- **WebSocket**: Primary method for real-time updates
- **Polling**: Fallback for environments without WebSocket support
- **Queue Updates**: Live queue management across all connected clients

## 🔧 Configuration

### Application Settings (`config/app.php`)
```php
// Modify these settings as needed
'karaoke' => [
    'max_queue_items' => 100,
    'default_room_size' => 20,
    'max_room_size' => 100,
    'queue_timeout' => 3600,
    'scoring' => [
        'max_score' => 1000,
        'pitch_weight' => 0.6,
        'timing_weight' => 0.4,
    ],
],
```

### YouTube API Configuration
1. Get API key from [Google Cloud Console](https://console.cloud.google.com/)
2. Enable YouTube Data API v3
3. Add API key to `.env` file
4. Configure rate limits in Google Cloud Console

## 🎯 API Endpoints

### Public API
- `GET /api/songs/search?q=query` - Search songs
- `GET /api/songs/popular` - Get popular songs
- `GET /api/songs/recent` - Get recently added songs
- `GET /api/songs/categories` - Get song categories

### Room API (Authenticated)
- `GET /api/room/{code}/queue` - Get room queue
- `POST /api/room/{code}/queue` - Add song to queue
- `DELETE /api/room/{code}/queue?id={id}` - Remove from queue

### Admin API
- `GET /admin/youtube/search?q=query` - YouTube search
- `POST /admin/youtube/import` - Import from YouTube
- `POST /admin/upload` - Upload media files

## 🔒 Security Features

- **CSRF Protection**: All forms include CSRF tokens
- **Input Validation**: All user inputs are validated and sanitized
- **SQL Injection Prevention**: Prepared statements for all database queries
- **Password Hashing**: Secure password storage using PHP password_hash()
- **Session Security**: Secure session handling with timeout
- **File Upload Security**: Validation of file types and sizes

## 🎨 Customization

### Theming
Modify CSS custom properties in `public/assets/css/main.css`:

```css
:root {
    --primary-color: #667eea;      /* Main brand color */
    --secondary-color: #764ba2;    /* Secondary brand color */
    --accent-color: #ff6b6b;       /* Accent color */
    /* ... more variables */
}
```

### Adding New Features
1. Create model in `app/Models/`
2. Create controller in `app/Controllers/`
3. Add views in `app/Views/`
4. Define routes in `routes/web.php`

## 📱 Mobile Support

The application is fully responsive with:
- Mobile-first CSS approach
- Touch-friendly controls
- Responsive layouts using CSS Grid and Flexbox
- Optimized for screens from 320px to 1440px+

## 🧪 Testing

### Manual Testing Checklist
- [ ] User registration and login
- [ ] Room creation and joining
- [ ] Song queue management
- [ ] Real-time updates
- [ ] File uploads
- [ ] YouTube integration
- [ ] Admin panel functions
- [ ] Mobile responsiveness

### Performance Testing
- [ ] Database query optimization
- [ ] File upload limits
- [ ] Real-time connection handling
- [ ] Mobile performance

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
```bash
# Check MySQL service
sudo systemctl status mysql
# Restart if needed
sudo systemctl restart mysql
```

**YouTube Integration Not Working**
- Verify API key is correct
- Check API quota limits
- Ensure YouTube Data API v3 is enabled

**File Upload Issues**
- Check directory permissions
- Verify PHP upload limits in php.ini
- Check available disk space

**Real-time Features Not Working**
- Verify WebSocket support
- Check firewall settings
- Ensure JavaScript is enabled

## 📈 Performance Optimization

### Database
- Use indexes on frequently queried columns
- Optimize queries with EXPLAIN
- Consider query caching for popular searches

### File Storage
- Use CDN for static assets
- Implement video compression
- Consider cloud storage for large files

### Real-time Features
- Use WebSocket for better performance
- Implement connection pooling
- Add rate limiting for API endpoints

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🆘 Support

For support and questions:
- Check the troubleshooting section
- Review the code comments
- Create an issue in the repository

## 🎊 Acknowledgments

- YouTube Data API for video integration
- Font Awesome for icons
- Modern CSS features for responsive design
- PHP community for best practices

---

Built with ❤️ for the karaoke community!