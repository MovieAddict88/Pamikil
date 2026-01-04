# Deployment Guide

Complete checklist for deploying MovieStream to production.

## Pre-Deployment Checklist

### 1. File Preparation
- [ ] All files uploaded to hosting
- [ ] Folder permissions set (755 for dirs, 644 for files)
- [ ] Uploads directory writable (755)
- [ ] .htaccess file present (created by installer)

### 2. Database Setup
- [ ] MySQL database created
- [ ] Database user created with proper permissions
- [ ] Database credentials noted
- [ ] Character set: utf8mb4_unicode_ci

### 3. Server Requirements Verified
- [ ] PHP 7.4+ installed
- [ ] PDO MySQL extension enabled
- [ ] cURL extension enabled
- [ ] JSON extension enabled
- [ ] mod_rewrite enabled (Apache) or URL rewrite configured (Nginx)

## Installation Steps

### Step 1: Run Installer
```
Visit: http://yourdomain.com/install.php
```

1. **Database Configuration**
   - Enter host (usually 'localhost')
   - Database name
   - Database username
   - Database password
   - Click Continue

2. **Admin Account**
   - Choose admin username
   - Set strong password
   - Enter admin email
   - Set site name
   - Enter site URL (with https://)
   - Click Continue

3. **API Configuration** (Optional but recommended)
   - TMDB API key (for movie import)
   - YouTube API key (optional)
   - Click Complete Installation

4. **Verify Success**
   - Installation complete message appears
   - Admin login and website links shown

### Step 2: Secure Installation
```bash
# Delete or rename install.php
rm install.php
# OR
mv install.php install.php.disabled
```

### Step 3: First Login
1. Visit: `http://yourdomain.com/admin/`
2. Login with admin credentials
3. Verify dashboard loads correctly

## Post-Installation Configuration

### 1. Server Setup

**Add Default Server:**
1. Go to Admin → Servers
2. Click "Add New Server"
3. For YouTube:
   - Name: YouTube
   - URL: https://www.youtube.com/
   - Type: YouTube
   - Active: Yes

4. For Direct Videos:
   - Name: Server 1
   - URL: https://yourcdn.com/videos/
   - Type: Direct
   - Active: Yes

### 2. Settings Configuration

**Admin → Settings:**
- [ ] Verify site name
- [ ] Verify site URL
- [ ] Set items per page (default: 20)
- [ ] Configure user registration (enable/disable)
- [ ] Maintenance mode (keep off)
- [ ] Add TMDB API key if not done
- [ ] Add YouTube API key if needed

### 3. Add Initial Content

**Quick Start - Bulk Import:**
1. Admin → Bulk Add
2. Paste these TMDB IDs:
```
278
238
155
680
550
```
3. Click "Add Movies"
4. Wait 30 seconds
5. 5 popular movies imported!

**Or Manual Add:**
1. Admin → Add Movie
2. Enter TMDB ID (e.g., 550)
3. Click "Search TMDB"
4. Review data
5. Click "Add Movie"

### 4. Add Video Sources

For each movie:
1. Admin → Movies → Edit Movie
2. Scroll to "Video Servers"
3. Select server
4. Enter video URL
5. Choose quality and type
6. Click "Add Server"

**Example URLs:**
- YouTube: `https://www.youtube.com/watch?v=VIDEO_ID`
- Direct MP4: `https://cdn.example.com/movie.mp4`
- MPD/DASH: `https://cdn.example.com/movie.mpd`

### 5. Feature Movies

1. Edit 1-2 movies
2. Check "Featured Movie"
3. Save
4. Featured movie appears on homepage

## Security Hardening

### 1. File Permissions
```bash
# Secure config directory
chmod 644 config/database.php

# Uploads should be writable but not executable
chmod 755 uploads/
find uploads/ -type f -exec chmod 644 {} \;
```

### 2. Protect Sensitive Directories

Add to `.htaccess` (if not already present):
```apache
# Block direct access to config
<FilesMatch "database\.php">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block access to includes
RewriteRule ^includes/ - [F,L]
```

### 3. Enable HTTPS

**If hosting supports SSL:**
```apache
# Add to .htaccess
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 4. Strong Passwords
- Admin password: 12+ characters, mixed case, numbers, symbols
- Database user: Different from admin password
- Change default passwords immediately

### 5. Regular Backups

**Database Backup:**
```bash
# Run weekly
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

**File Backup:**
```bash
# Backup uploads directory
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

## Performance Optimization

### 1. Cloudflare Setup (Recommended - Free)

1. **Add Site to Cloudflare**
   - Visit cloudflare.com
   - Add your domain
   - Update nameservers

2. **Configure Settings**
   - SSL: Full or Flexible
   - Auto Minify: CSS, JS, HTML
   - Brotli: Enabled
   - Rocket Loader: Enabled

3. **Page Rules**
   ```
   yourdomain.com/uploads/*
   - Cache Level: Cache Everything
   - Edge Cache TTL: 1 month
   
   yourdomain.com/admin/*
   - Security Level: High
   - Cache Level: Bypass
   ```

### 2. Enable Caching

Add to `.htaccess`:
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType video/mp4 "access plus 1 month"
</IfModule>
```

### 3. Enable Gzip Compression

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

### 4. Optimize Database

Run in phpMyAdmin or MySQL client:
```sql
-- Optimize tables
OPTIMIZE TABLE movies, users, servers, movie_servers, views_log;

-- Add additional indexes if needed
CREATE INDEX idx_movie_published_rating ON movies(is_published, rating DESC);
CREATE INDEX idx_movie_published_views ON movies(is_published, views DESC);
```

### 5. External Video Hosting

For better performance:
- Use YouTube for hosting (free, unlimited)
- Use cloud storage (AWS S3, DigitalOcean Spaces)
- Use video CDN (Bunny.net, Cloudflare Stream)

## Monitoring & Maintenance

### 1. Regular Checks

**Weekly:**
- [ ] Check error logs
- [ ] Verify backups completed
- [ ] Review new user registrations
- [ ] Check disk space usage

**Monthly:**
- [ ] Update popular movies
- [ ] Clean old log entries
- [ ] Review analytics
- [ ] Test video playback
- [ ] Security audit

### 2. Log Monitoring

**Check PHP Errors:**
```bash
tail -f /path/to/error_log
```

**Common Issues:**
- Database connection failures
- Upload size exceeded
- API rate limits

### 3. Analytics

**Built-in Analytics:**
- Admin Dashboard shows:
  - Total movies
  - Total users
  - Total views
  - Top movies
  - Recent activity

**External Analytics:**
- Add Google Analytics to includes/header.php
- Monitor traffic patterns
- Track popular content

## Troubleshooting Deployment

### Issue: White Screen After Upload

**Solution:**
1. Check PHP error logs
2. Verify all files uploaded
3. Check file permissions
4. Ensure PHP version 7.4+

### Issue: Database Connection Failed

**Solution:**
1. Verify credentials in config/database.php
2. Check database exists
3. Test with phpMyAdmin
4. Ensure MySQL service running

### Issue: Pretty URLs Not Working

**Solution:**
1. Verify .htaccess exists
2. Check mod_rewrite enabled
3. Ensure AllowOverride enabled in Apache config
4. Test without pretty URLs first

### Issue: Videos Won't Play

**Solution:**
1. Check video URL is accessible
2. Verify CORS headers if external
3. Test with YouTube URL first
4. Check browser console for errors

### Issue: TMDB Import Failing

**Solution:**
1. Verify API key correct
2. Test API key: `curl "https://api.themoviedb.org/3/movie/550?api_key=YOUR_KEY"`
3. Check cURL enabled
4. Try manual entry as fallback

### Issue: Upload Size Limit

**Solution:**
1. Check hosting upload limits
2. Modify .htaccess PHP values
3. Use external hosting for large files
4. Consider YouTube embedding

## Hosting-Specific Notes

### InfinityFree
- Maximum upload: 10MB
- Use YouTube for videos
- External image CDN recommended
- Database names have prefix

### 000webhost
- Similar to InfinityFree
- May have hourly hit limits
- Upgrade for better performance

### Shared Hosting (Hostinger, Bluehost, etc.)
- Higher upload limits (100MB-500MB)
- Better performance
- Full .htaccess support
- SSH access usually available

### VPS/Dedicated Server
- Full control
- Install ffmpeg for transcoding
- Configure Nginx for better performance
- Set up Redis for caching
- Configure fail2ban for security

## Going Live Checklist

- [ ] All movies added and tested
- [ ] Video playback working
- [ ] User registration tested
- [ ] Search functionality working
- [ ] Mobile responsiveness verified
- [ ] SSL certificate active
- [ ] Backups configured
- [ ] Monitoring set up
- [ ] Error logging enabled
- [ ] Social sharing tested (if applicable)
- [ ] SEO meta tags added (if needed)
- [ ] Privacy policy page created (if needed)
- [ ] Terms of service added (if needed)
- [ ] Contact information updated
- [ ] Admin email notifications working

## Post-Launch

### Week 1
- Monitor for errors
- Check user feedback
- Verify all features working
- Fix any issues immediately

### Week 2-4
- Add more content
- Optimize performance
- Gather user feedback
- Implement improvements

### Ongoing
- Regular backups
- Security updates
- Content updates
- Performance monitoring
- User support

## Support Resources

- **Documentation**: README.md, CONFIGURATION.md, API_REFERENCE.md
- **TMDB Help**: https://www.themoviedb.org/talk
- **YouTube API**: https://developers.google.com/youtube/v3
- **PHP Manual**: https://www.php.net/manual/
- **MySQL Docs**: https://dev.mysql.com/doc/

---

**Deployment Complete! 🚀**

Your movie streaming platform is now live and ready for users!
