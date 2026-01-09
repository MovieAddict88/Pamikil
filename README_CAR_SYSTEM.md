# DriveHub - Car Management System

A complete, fullstack, and fully responsive Car Management System built with PHP and MySQL, featuring a modern admin dashboard and PWA (Progressive Web App) support.

## Features

### Admin Dashboard
- **Dashboard Overview**: Real-time statistics and recent activities
- **Car Management**: Full CRUD operations for vehicle inventory
  - Add, edit, delete cars
  - Track VIN, license plates, specifications
  - Manage car status (Available, Sold, Reserved, Maintenance)
  - Upload images and detailed descriptions
- **Customer Management**: Maintain customer database
  - Contact information
  - License details
  - Purchase history
- **Sales Management**: Record and track vehicle sales
  - Multiple payment methods
  - Payment status tracking
  - Sales reports
- **Maintenance Records**: Track vehicle maintenance
  - Service history
  - Costs and providers
  - Next maintenance scheduling
- **Inquiries**: Manage customer inquiries
  - Email and phone contacts
  - Status tracking (New, Contacted, Closed)
- **Test Drives**: Schedule and manage test drive appointments

### Public Website
- **Responsive Design**: Fully optimized for mobile, tablet, and desktop
- **Advanced Search & Filters**: Search by make, model, year, price range, body type
- **Car Listings**: Browse available vehicles with detailed specifications
- **Car Details Page**: Complete information about each vehicle
- **Test Drive Booking**: Schedule test drives online
- **Contact Forms**: General inquiries and car-specific inquiries
- **PWA Support**: Install as mobile/desktop app

### Technical Features
- **Progressive Web App (PWA)**
  - Installable on mobile and desktop devices
  - Offline support with service worker
  - App-like experience
  - Custom icons and splash screens
- **Responsive Design**
  - CSS Grid and Flexbox
  - CSS clamp() for fluid typography
  - Mobile-first approach
  - Hamburger menu for mobile devices
- **Philippine Currency**: All prices in PHP (₱)
- **Modern UI/UX**
  - Clean, professional design
  - Smooth animations and transitions
  - Font Awesome icons
  - Color-coded status badges

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

### Setup Instructions

1. **Clone or download the repository**
   ```bash
   cd /path/to/your/webserver/root
   ```

2. **Import the database**
   ```bash
   mysql -u root -p < database.sql
   ```
   Or import via phpMyAdmin

3. **Configure database connection**
   Edit `config.php` and update these constants:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'car_management');
   ```

4. **Set permissions**
   ```bash
   chmod 755 assets/
   chmod 644 config.php
   ```

5. **Access the application**
   - Public Website: `http://localhost/home.php`
   - Admin Login: `http://localhost/login.php`

### Default Admin Credentials
- **Username**: `admin`
- **Password**: `admin123`

**Important**: Change the default password after first login!

## File Structure

```
├── admin/                      # Admin panel
│   ├── cars.php               # Vehicle management
│   ├── customers.php          # Customer management
│   ├── sales.php              # Sales management
│   ├── maintenance.php        # Maintenance records
│   ├── inquiries.php          # Customer inquiries
│   ├── test-drives.php        # Test drive appointments
│   ├── dashboard.php          # Admin dashboard
│   ├── logout.php             # Logout handler
│   └── includes/              # Header and footer
│       ├── header.php
│       └── footer.php
├── assets/                    # Static assets
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   ├── js/
│   │   ├── app.js            # Public website JS
│   │   └── admin.js          # Admin panel JS
│   └── images/               # PWA icons and images
│       ├── icon-*.png        # App icons (various sizes)
│       └── screenshot1.png   # PWA screenshot
├── config.php                # Database configuration
├── database.sql              # Database schema and sample data
├── home.php                  # Public homepage
├── car-details.php           # Car details page
├── test-drive.php            # Test drive booking
├── submit-inquiry.php        # Inquiry submission handler
├── login.php                 # Admin login
├── manifest.json             # PWA manifest
├── sw.js                     # Service worker
└── README_CAR_SYSTEM.md      # This file
```

## Database Schema

### Tables
- **admins**: Admin user accounts
- **cars**: Vehicle inventory
- **customers**: Customer information
- **sales**: Sales transactions
- **maintenance**: Maintenance records
- **inquiries**: Customer inquiries
- **test_drives**: Test drive appointments

## Currency

All monetary values are displayed in **Philippine Peso (₱)**.

The system uses the `formatCurrency()` function defined in `config.php`:
```php
function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}
```

## Responsive Design

The system uses modern CSS techniques for responsiveness:

- **CSS Clamp**: Fluid typography that scales with viewport
  ```css
  font-size: clamp(14px, 2vw, 16px);
  ```

- **CSS Grid**: Auto-fit columns that adapt to screen size
  ```css
  grid-template-columns: repeat(auto-fit, minmax(clamp(250px, 30vw, 280px), 1fr));
  ```

- **Media Queries**: Breakpoints for different devices
  - Desktop: > 968px
  - Tablet: 641px - 968px
  - Mobile: ≤ 640px

## PWA Installation

### Desktop (Chrome, Edge)
1. Visit the website
2. Click the install icon in the address bar
3. Or click the "Install App" button on the homepage

### Mobile (Android)
1. Visit the website in Chrome
2. Tap the menu (three dots)
3. Select "Add to Home Screen" or "Install App"

### iOS
1. Visit the website in Safari
2. Tap the Share button
3. Select "Add to Home Screen"

## Customization

### Changing Site Name
Edit `config.php`:
```php
define('SITE_NAME', 'YourSiteName');
```

### Changing Colors
Edit `assets/css/style.css` in the `:root` section:
```css
:root {
    --primary: #2563eb;        /* Primary color */
    --primary-dark: #1e40af;   /* Darker shade */
    /* ... other colors ... */
}
```

### Changing PWA Icons
Replace icon files in `assets/images/` with your custom icons:
- icon-72.png (72x72)
- icon-96.png (96x96)
- icon-128.png (128x128)
- icon-144.png (144x144)
- icon-152.png (152x152)
- icon-192.png (192x192)
- icon-384.png (384x384)
- icon-512.png (512x512)

Use tools like [RealFaviconGenerator](https://realfavicongenerator.net/) for professional icons.

## Security Considerations

1. **Change Default Password**: Immediately change the admin password
2. **Use HTTPS**: Deploy with SSL certificate for secure connections
3. **Database Security**: Use strong passwords and restrict access
4. **Input Validation**: All user inputs are sanitized
5. **Session Security**: Session-based authentication
6. **SQL Injection Prevention**: Using mysqli_real_escape_string()

### Recommended Improvements for Production
- Implement password hashing with stronger algorithms
- Add CSRF token protection
- Implement rate limiting
- Add file upload validation if allowing image uploads
- Use prepared statements instead of string concatenation
- Add admin user management
- Implement role-based access control

## Browser Support

- Chrome/Edge: Full support including PWA
- Firefox: Full support (PWA limited)
- Safari: Full support with iOS PWA
- Mobile browsers: Full responsive support

## Performance

- Lazy loading for images
- Minified CSS and JS (can be added)
- Service worker caching for offline support
- Optimized database queries with indexes

## License

This project is open source and available for educational and commercial use.

## Support

For issues, questions, or contributions, please refer to the project repository or contact the administrator.

## Credits

- Font Awesome for icons
- Google Fonts for typography
- Unsplash for sample car images

---

**Built with ❤️ for the Philippine automotive industry**
