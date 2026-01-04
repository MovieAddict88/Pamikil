# API Integration Reference

## TMDB API Integration

### Overview
The MovieDB (TMDB) API provides comprehensive movie metadata including:
- Movie titles and descriptions
- Posters and backdrop images
- Cast and crew information
- Ratings and popularity
- Release dates and runtime
- Genres and languages

### Getting Started

1. **Register for API Key**
   - Website: https://www.themoviedb.org/
   - Free tier available
   - No credit card required
   - Generous rate limits

2. **API Key Usage**
   - Used for automatic movie import
   - Fetches high-quality images
   - Updates movie metadata
   - No cost for basic usage

### Endpoints Used

#### Get Movie by ID
```
GET https://api.themoviedb.org/3/movie/{movie_id}
Parameters:
  - api_key: Your API key
  - append_to_response: credits,videos,images
```

**Response includes:**
- Movie title and overview
- Poster and backdrop URLs
- Release date and runtime
- Ratings and vote count
- Genres
- Production companies and countries
- Cast and crew (via credits)
- Videos/trailers (via videos)

#### Search Movies
```
GET https://api.themoviedb.org/3/search/movie
Parameters:
  - api_key: Your API key
  - query: Search term
  - page: Results page number
```

#### Get Popular Movies
```
GET https://api.themoviedb.org/3/movie/popular
Parameters:
  - api_key: Your API key
  - page: Results page number
```

### Image URLs

TMDB provides images in various sizes:

**Poster Sizes:**
- w92, w154, w185, w342, w500, w780, original

**Backdrop Sizes:**
- w300, w780, w1280, original

**Base URL Structure:**
```
https://image.tmdb.org/t/p/{size}/{path}

Example:
https://image.tmdb.org/t/p/w500/poster_path.jpg
```

### Finding TMDB IDs

**Method 1: From URL**
- Visit: https://www.themoviedb.org/
- Search for a movie
- Look at URL: `themoviedb.org/movie/550-fight-club`
- ID is: `550`

**Method 2: From Search Results**
- Use TMDB website search
- Movie ID shown in results

**Method 3: From API**
- Use search endpoint
- Get ID from results

### Rate Limits

**Free Tier:**
- 40 requests per 10 seconds
- More than enough for typical usage

**Best Practices:**
- Cache results locally (done automatically)
- Use bulk import for multiple movies
- Download images to your server

### Example Integration

The application uses TMDB in these files:

**includes/tmdb.php:**
```php
$tmdb = new TMDB();
$result = $tmdb->getMovieById(550);

if ($result['success']) {
    $movie = $result['data'];
    // Use movie data
}
```

**Admin pages:**
- `add-movie.php` - Single movie import
- `bulk-add.php` - Multiple movie import

### Error Handling

Common errors:
- `401`: Invalid API key
- `404`: Movie not found
- `429`: Rate limit exceeded

The application handles these gracefully.

---

## YouTube Data API Integration

### Overview
YouTube Data API v3 allows:
- Video search
- Video metadata retrieval
- Embed URL generation
- Thumbnail access

### Getting Started

1. **Create Google Cloud Project**
   - Visit: https://console.cloud.google.com/
   - Create new project
   - Enable YouTube Data API v3

2. **Create API Key**
   - Go to Credentials
   - Create API Key
   - (Optional) Restrict to YouTube Data API
   - (Optional) Add HTTP referrer restrictions

3. **Add to Application**
   - Admin → Settings
   - Enter YouTube API Key
   - Save

### Endpoints Used

#### Search Videos
```
GET https://www.googleapis.com/youtube/v3/search
Parameters:
  - key: Your API key
  - part: snippet
  - q: Search query
  - type: video
  - maxResults: Number of results
```

#### Get Video Details
```
GET https://www.googleapis.com/youtube/v3/videos
Parameters:
  - key: Your API key
  - part: snippet,contentDetails,statistics
  - id: Video ID
```

### Video ID Extraction

The application automatically extracts YouTube IDs from:
- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- `https://www.youtube.com/embed/VIDEO_ID`

### Embed URLs

Generated format:
```
https://www.youtube.com/embed/VIDEO_ID
```

### Thumbnail URLs

Available qualities:
```
default.jpg    - 120x90
mqdefault.jpg  - 320x180
hqdefault.jpg  - 480x360
sddefault.jpg  - 640x480
maxresdefault.jpg - 1280x720 (if available)
```

URL format:
```
https://img.youtube.com/vi/VIDEO_ID/maxresdefault.jpg
```

### Rate Limits

**Free Tier:**
- 10,000 units per day
- Search costs: 100 units
- Video details: 1 unit

**Best Practices:**
- Use direct URLs when possible
- Cache video metadata
- Only use API for search features

### Example Integration

**includes/youtube.php:**
```php
$youtube = new YouTube();
$videoId = $youtube->extractVideoId($url);
$embedUrl = $youtube->getEmbedUrl($videoId);
```

**Usage in admin:**
- Add YouTube URL to movie
- Automatically extracted and embedded
- No manual configuration needed

---

## Video Player APIs

### Plyr.io

**Purpose:** Primary video player for standard formats

**Features:**
- Beautiful, accessible UI
- Keyboard shortcuts
- Fullscreen support
- Picture-in-picture
- Quality selection
- Playback speed control

**CDN Links:**
```html
<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
<script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
```

**Initialization:**
```javascript
const player = new Plyr('#player', {
    controls: ['play-large', 'play', 'progress', 'current-time', 
               'mute', 'volume', 'settings', 'pip', 'airplay', 'fullscreen'],
    settings: ['quality', 'speed'],
});
```

**Supported Formats:**
- MP4, WebM
- YouTube (native)
- Vimeo (native)
- HLS (with hls.js)

**Documentation:** https://github.com/sampotts/plyr

---

### Shaka Player

**Purpose:** DASH/MPD adaptive streaming

**Features:**
- DASH support
- DRM support (Widevine, PlayReady)
- Adaptive bitrate streaming
- Live streaming
- Offline playback

**CDN Link:**
```html
<script src="https://cdn.jsdelivr.net/npm/shaka-player@4.3.0/dist/shaka-player.compiled.js"></script>
```

**Initialization:**
```javascript
const player = new shaka.Player(videoElement);
player.load('video.mpd').then(() => {
    // Video loaded
});
```

**Use Cases:**
- Premium content with DRM
- Adaptive bitrate streaming
- Professional streaming services

**Documentation:** https://github.com/shaka-project/shaka-player

---

## Integration Examples

### Adding TMDB Movie

```php
// includes/tmdb.php
$tmdb = new TMDB('your-api-key');
$result = $tmdb->getMovieById(550);

if ($result['success']) {
    $movieData = $result['data'];
    
    // Save to database
    $stmt = $db->prepare("INSERT INTO movies (...) VALUES (...)");
    $stmt->execute([...]);
}
```

### Adding YouTube Video

```php
// includes/youtube.php
$youtube = new YouTube('your-api-key');
$videoId = $youtube->extractVideoId('https://www.youtube.com/watch?v=abc123');
$embedUrl = $youtube->getEmbedUrl($videoId);

// Save to movie_servers
$stmt = $db->prepare("INSERT INTO movie_servers (video_url, video_type) VALUES (?, 'youtube')");
$stmt->execute([$embedUrl]);
```

### Custom Player Integration

```javascript
// watch.php
function initPlayer() {
    const videoElement = document.getElementById('player');
    
    // For MPD/DASH
    if (videoType === 'mpd') {
        const player = new shaka.Player(videoElement);
        player.load(videoUrl).then(() => {
            new Plyr(videoElement); // Add Plyr controls
        });
    } else {
        // For regular videos
        new Plyr(videoElement);
    }
}
```

---

## Security Considerations

### API Keys
- Never commit API keys to git
- Store in config/database.php
- Use environment variables for production
- Restrict API keys by domain/IP

### CORS Headers
For external video sources:
```apache
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "GET, OPTIONS"
```

### Content Security Policy
```apache
Header set Content-Security-Policy "default-src 'self'; script-src 'self' cdn.plyr.io cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.plyr.io;"
```

---

## Troubleshooting

### TMDB Issues

**Problem:** 401 Unauthorized
**Solution:** Check API key is correct

**Problem:** No images loading
**Solution:** Check image URLs, ensure HTTPS

**Problem:** Rate limit exceeded
**Solution:** Reduce request frequency, use caching

### YouTube Issues

**Problem:** Video won't embed
**Solution:** 
- Check video allows embedding
- Verify video is public
- Check for copyright restrictions

**Problem:** API quota exceeded
**Solution:**
- Request quota increase
- Use direct URLs instead of API

### Player Issues

**Problem:** Video format not supported
**Solution:**
- Check browser compatibility
- Use MP4 for best compatibility
- Consider video transcoding

**Problem:** CORS errors
**Solution:**
- Enable CORS on video server
- Use proxy if needed

---

## Best Practices

### API Usage
1. Cache responses locally
2. Handle errors gracefully
3. Respect rate limits
4. Use bulk operations when possible

### Video Hosting
1. Use CDN for better performance
2. Provide multiple qualities
3. Optimize video encoding
4. Consider adaptive streaming

### Security
1. Validate all inputs
2. Sanitize API responses
3. Use HTTPS everywhere
4. Implement rate limiting

---

## Additional Resources

### TMDB
- API Docs: https://developers.themoviedb.org/3
- Forum: https://www.themoviedb.org/talk
- Status: https://status.themoviedb.org/

### YouTube
- API Docs: https://developers.google.com/youtube/v3
- Console: https://console.cloud.google.com/
- Quotas: Check in console

### Players
- Plyr: https://plyr.io/
- Shaka: https://shaka-player-demo.appspot.com/
- Video.js: https://videojs.com/ (alternative)

---

**Need help? Check the main README.md or CONFIGURATION.md**
