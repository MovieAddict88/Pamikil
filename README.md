# CineCraze - Free Netflix Clone with Live TV

A modern, fully responsive streaming platform built with vanilla HTML, CSS, and JavaScript. Features include movies, series, live TV, and a comprehensive admin panel.

## 🚀 Features

### Frontend (index.html)
- **Modern UI/UX**: Netflix-inspired design with dark theme and smooth animations
- **Live TV**: Watch live streaming channels (not available in Netflix)
- **Responsive Design**: Built with CSS clamps, media queries for all devices
- **Multi-Server Support**: Automatic fallback between different streaming servers
- **Search & Discovery**: Real-time search with suggestions
- **My List**: Personal watchlist with localStorage persistence
- **Video Player**: Full-featured player with server switching
- **Progressive Loading**: Lazy loading and infinite scroll

### Admin Panel (cinecraze.html)
- **Dashboard**: Real-time statistics and activity monitoring
- **TMDB Integration**: Search and auto-generate content with metadata
- **YouTube API**: Search and import trailers with full integration
- **Upload System**: Drag & drop file uploads with progress tracking
- **Embed Management**: Add content via external embed links
- **Server Manager**: Configure multiple streaming servers (VidSrc+, MultiEmbed, etc.)
- **Settings Panel**: Manage API keys for TMDB and YouTube
- **Database Management**: Import/export functionality for backup

## 🛠 Technology Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Fonts**: Inter (Google Fonts)
- **Icons**: Font Awesome 6.4.0
- **Video Player**: Plyr.js
- **Storage**: localStorage for data persistence
- **APIs**: TMDB API, YouTube Data API v3

## 🎯 Key Differentiators from Netflix

1. **Live TV**: Unlike Netflix, includes live streaming channels
2. **Free**: No subscription fees or hidden costs
3. **Open Source**: Full access to source code
4. **Multi-Server**: Built-in redundancy for better uptime
5. **Admin Control**: Complete content management system
6. **API Integration**: TMDB and YouTube for rich metadata

## 📱 Responsive Design

The platform uses modern CSS techniques for optimal viewing on all devices:

- **CSS Clamps**: Dynamic sizing for typography and spacing
- **Media Queries**: Tailored layouts for mobile, tablet, and desktop
- **Flexbox & Grid**: Modern layout techniques
- **Touch-Friendly**: Optimized for mobile interaction

## 🔧 Setup Instructions

### 1. Basic Setup
```bash
# Clone or download the project
# Open index.html in a modern web browser
# For admin panel, open cinecraze.html
```

### 2. API Configuration
1. Get TMDB API key from [themoviedb.org](https://www.themoviedb.org/settings/api)
2. Get YouTube API key from [Google Console](https://console.developers.google.com/)
3. In admin panel, go to Settings tab
4. Enter your API keys and save

### 3. Adding Content
- **Via TMDB**: Use TMDB Search tab, find content, click "Generate Content"
- **Via YouTube**: Use YouTube Search tab, find trailers, click "Add as Trailer"
- **Manual Upload**: Use Upload Content tab for custom content
- **Embed Links**: Use Embed Content tab for external streams

## 🎮 How to Use

### For Viewers
1. Visit `index.html` to access the main streaming interface
2. Browse Movies, Series, or Live TV sections
3. Use search to find specific content
4. Click on any content to start watching
5. Switch between servers if one doesn't work
6. Add content to "My List" for later viewing

### For Administrators
1. Visit `cinecraze.html` to access the admin panel
2. Configure API keys in Settings
3. Use TMDB Search to import movies and series
4. Use YouTube Search to add trailers
5. Manage servers in Server Manager tab
6. Export/import database for backup

## 🌟 Live TV Channels

The platform includes sample live TV channels:
- News Network (24/7 news)
- Sports HD (live sports coverage)
- Music Channel (music videos and concerts)
- Kids TV (educational content)
- Documentary (nature and science)
- Movie Channel (24/7 movies)

## 🔌 Server Integration

### Default Servers
- **VidSrc**: Primary streaming server (vidsrc.me)
- **MultiEmbed**: Secondary server (multiembed.moe)
- **Videasy**: Additional backup server

### Server Management
- Add custom servers through admin panel
- Configure server priority and fallback
- Monitor server status and performance

## 💾 Data Storage

All data is stored locally using browser localStorage:
- Content database
- User preferences
- My List and watch history
- API configurations
- Admin settings

## 🚀 Future Enhancements

Potential improvements for the platform:
- User authentication system
- Comment and rating system
- Download for offline viewing
- Social features (sharing, reviews)
- Advanced search filters
- Content recommendations
- Mobile app development

## 📄 License

This project is open source and available under the MIT License.

## 🤝 Contributing

Contributions are welcome! Areas for improvement:
- Additional streaming server integrations
- Enhanced search functionality
- Mobile app development
- Backend API development
- Performance optimizations

## 📞 Support

For technical support or feature requests, please open an issue in the project repository.

---

**CineCraze** - Bringing free streaming entertainment to everyone! 🎬📺