# Quick Start Guide

Get your movie streaming site up and running in 5 minutes!

## Step 1: Upload Files (2 minutes)

### Using FTP (FileZilla)
1. Connect to your hosting
2. Navigate to `public_html` or `htdocs`
3. Upload all files
4. Done!

### Using cPanel File Manager
1. Login to cPanel
2. Open File Manager
3. Navigate to `public_html`
4. Upload ZIP file
5. Extract files
6. Done!

## Step 2: Run Installer (2 minutes)

1. **Visit Installation Page**
   ```
   http://yourdomain.com/install.php
   ```

2. **Enter Database Info** (Step 1)
   - Get from your hosting control panel
   - Usually: localhost, db_name, db_user, db_pass
   - Click "Continue"

3. **Create Admin Account** (Step 2)
   - Choose username and password
   - Enter email
   - Set site name
   - Click "Continue"

4. **Add API Keys** (Step 3) - Optional
   - TMDB API key (for auto-import)
   - YouTube API key (optional)
   - Or skip and add later
   - Click "Complete Installation"

5. **Done!**
   - Installation complete
   - Delete or rename `install.php`

## Step 3: Add Your First Movie (1 minute)

### Quick Method - Using TMDB

1. **Get TMDB ID**
   - Visit themoviedb.org
   - Search for a movie (e.g., "Inception")
   - Copy ID from URL: `themoviedb.org/movie/27205` → ID is `27205`

2. **Add Movie**
   - Login to admin panel: `yourdomain.com/admin/`
   - Go to "Add Movie"
   - Enter TMDB ID: `27205`
   - Click "Search TMDB"
   - Review auto-filled data
   - Click "Add Movie"

3. **Add Video Source**
   - Click "Edit Movie"
   - Scroll to "Video Servers"
   - Select server or create new one
   - Enter video URL (YouTube or direct link)
   - Click "Add Server"

4. **View Movie**
   - Click "View Movie"
   - Your first movie is live!

## Common First Movies to Test

Just enter these TMDB IDs:

- **Inception**: 27205
- **The Dark Knight**: 155
- **Interstellar**: 157336
- **The Matrix**: 603

## Quick Server Setup

### For YouTube Videos

1. **Admin → Servers**
2. Add server:
   - Name: `YouTube`
   - URL: `https://www.youtube.com/`
   - Type: `YouTube`
3. **Add to movie**: Just paste full YouTube URL

### For Direct Video Links

1. **Admin → Servers**
2. Add server:
   - Name: `Server 1`
   - URL: `https://yourcdn.com/videos/`
   - Type: `Direct`
3. **Add to movie**: Enter full video URL

## Bulk Add Movies (Under 1 minute)

Want to add 10 movies at once?

1. **Admin → Bulk Add**
2. Enter TMDB IDs (one per line):
   ```
   278
   238
   155
   680
   550
   27205
   603
   157336
   13
   129
   ```
3. Click "Add Movies"
4. Wait 30 seconds
5. All movies imported with posters!

## Troubleshooting

### Can't access install.php?
- Check file was uploaded
- Try: `http://yourdomain.com/install.php` (not https if no SSL)

### Database connection error?
- Double-check credentials
- Ensure database exists
- Try using phpMyAdmin to test

### TMDB not importing?
- Check API key is correct
- Test at: `https://api.themoviedb.org/3/movie/550?api_key=YOUR_KEY`
- Use manual entry if needed

### Videos not playing?
- Check video URL is accessible
- Try YouTube links first (easiest)
- Verify video format is supported

## Next Steps

### Customize Your Site

1. **Change Theme Colors**
   - Edit `assets/css/style.css`
   - Look for `:root` variables

2. **Update Site Name**
   - Admin → Settings
   - Change "Site Name"

3. **Add More Movies**
   - Use Bulk Add for speed
   - Or add manually with TMDB IDs

### Optimize Performance

1. **Enable Cloudflare** (free)
   - Adds CDN
   - Speeds up site
   - SSL certificate

2. **Use External Video Hosting**
   - YouTube (free)
   - Vimeo
   - Cloud storage

3. **Add Featured Movies**
   - Edit movie
   - Check "Featured Movie"
   - Shows on homepage

## Popular TMDB IDs to Get Started

Copy-paste into Bulk Add:

```
278
238
155
680
550
27205
603
157336
13
129
424
389
120
122
424
278
19404
299536
99861
1726
```

This adds 20 popular movies instantly!

## Getting Help

### Check These First:
1. README.md - Full documentation
2. CONFIGURATION.md - Detailed setup
3. Server error logs
4. Browser console (F12)

### Common Solutions:
- **White screen**: Check PHP errors in logs
- **404 errors**: Verify .htaccess exists
- **Slow loading**: Use image CDN
- **Upload fails**: Check PHP upload limits

## Pro Tips

### Faster Setup
- Use Bulk Add for multiple movies
- YouTube links work immediately
- Feature 1-2 movies on homepage

### Better Performance
- Optimize images before upload
- Use YouTube for easy video hosting
- Enable caching in .htaccess

### Security
- Delete install.php after setup
- Use strong admin password
- Keep backups

## That's It!

You now have a fully functional movie streaming site!

**Time to complete**: ~5 minutes
**Movies you can add**: Unlimited
**Hosting**: Works on free and paid hosting

---

**Enjoy your movie streaming platform! 🎬🍿**
