# DriveHub - Car Management System

A complete, fullstack, and fully responsive **Car Management System** built with **PHP** and **MySQL**, featuring a modern admin dashboard and **Progressive Web App (PWA)** support.

## 🚗 Features

### Admin Dashboard
- **Dashboard Overview** with real-time statistics
- **Complete Car Inventory Management** (CRUD operations)
- **Customer Database** management
- **Sales Tracking** with Philippine Peso (₱) currency
- **Maintenance Records** tracking
- **Customer Inquiries** management
- **Test Drive Scheduling** system

### Public Website
- Fully responsive design (mobile, tablet, desktop)
- Advanced search and filtering
- Car details with specifications
- Test drive booking
- Contact forms
- PWA installable app

### Technical Features
- ✅ **Fully Responsive** using CSS Grid, Flexbox, and `clamp()`
- ✅ **Progressive Web App (PWA)** with service worker
- ✅ **Philippine Currency** (₱) throughout
- ✅ **Modern UI/UX** with Font Awesome icons
- ✅ **Session-based Authentication**
- ✅ **MySQL Database** with relational structure

## 🚀 Quick Start

### 1. Import Database
```bash
mysql -u root -p < database.sql
```

### 2. Configure Database
Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'car_management');
```

### 3. Access Application
- **Public Website**: `http://localhost/home.php`
- **Admin Panel**: `http://localhost/login.php`

### 4. Login Credentials
- **Username**: `admin`
- **Password**: `admin123`

⚠️ **Change the default password after first login!**

## 📂 Project Structure

```
├── admin/                  # Admin panel pages
│   ├── dashboard.php      # Admin dashboard
│   ├── cars.php           # Car management
│   ├── customers.php      # Customer management
│   ├── sales.php          # Sales management
│   ├── maintenance.php    # Maintenance records
│   ├── inquiries.php      # Customer inquiries
│   └── test-drives.php    # Test drive appointments
├── assets/
│   ├── css/
│   │   └── style.css      # Main responsive stylesheet
│   ├── js/
│   │   ├── app.js         # Public website JavaScript
│   │   └── admin.js       # Admin panel JavaScript
│   └── images/            # PWA icons
├── config.php             # Database configuration
├── database.sql           # Database schema & sample data
├── home.php               # Public homepage
├── car-details.php        # Car details page
├── test-drive.php         # Test drive booking
├── login.php              # Admin login
├── manifest.json          # PWA manifest
├── sw.js                  # Service worker
└── README_CAR_SYSTEM.md   # Detailed documentation
```

## 💾 Database Schema

- **admins** - Admin user accounts
- **cars** - Vehicle inventory (make, model, year, price, status, etc.)
- **customers** - Customer information
- **sales** - Sales transactions
- **maintenance** - Maintenance records
- **inquiries** - Customer inquiries
- **test_drives** - Test drive appointments

## 🎨 Key Technologies

- **Backend**: PHP 7.4+ with MySQLi
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Database**: MySQL 5.7+
- **Icons**: Font Awesome 6.4
- **PWA**: Service Worker, Web Manifest
- **Responsive**: CSS Grid, Flexbox, CSS `clamp()`

## 📱 PWA Installation

### Desktop (Chrome/Edge)
1. Visit the website
2. Click install icon in address bar
3. Or click "Install App" button

### Mobile (Android)
1. Open in Chrome
2. Tap menu → "Add to Home Screen"

### iOS
1. Open in Safari
2. Share → "Add to Home Screen"

## 🎯 Currency

All prices are displayed in **Philippine Peso (₱)**. The system uses:
```php
function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}
```

## 📱 Responsive Design

Using modern CSS techniques:
- **CSS Clamp**: `font-size: clamp(14px, 2vw, 16px);`
- **CSS Grid**: `grid-template-columns: repeat(auto-fit, minmax(clamp(250px, 30vw, 280px), 1fr));`
- **Media Queries**: Breakpoints at 968px, 640px

## 🔒 Security Notes

1. ✅ Session-based authentication
2. ✅ SQL injection prevention with `mysqli_real_escape_string()`
3. ✅ Password hashing with `password_hash()`
4. ✅ Input sanitization
5. ⚠️ **Recommended**: Implement CSRF tokens, use prepared statements, enable HTTPS

## 📚 Documentation

- **Complete Guide**: See `README_CAR_SYSTEM.md`
- **Installation**: See `INSTALLATION.md`
- **Sample Data**: Included in `database.sql`

## 🛠️ Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- Modern web browser

## 🎨 Customization

### Change Site Name
Edit `config.php`:
```php
define('SITE_NAME', 'YourSiteName');
```

### Change Colors
Edit `assets/css/style.css`:
```css
:root {
    --primary: #2563eb;
    --primary-dark: #1e40af;
    /* ... */
}
```

### Replace PWA Icons
Replace images in `assets/images/`:
- icon-72.png through icon-512.png
- Use [RealFaviconGenerator](https://realfavicongenerator.net/)

## 🐛 Troubleshooting

**Database Connection Error**
- Check credentials in `config.php`
- Ensure MySQL is running
- Verify database exists

**Session Errors**
- Check for whitespace before `<?php`
- Verify session directory permissions

**PWA Not Installing**
- Requires HTTPS (except localhost)
- Check browser console for errors

## 📄 License

Open source - available for educational and commercial use.

## 🙏 Credits

- Font Awesome for icons
- Sample car images from Unsplash
- Built for the Philippine automotive industry

---

**Built with ❤️ using PHP, MySQL, and modern web technologies**

For complete documentation, see `README_CAR_SYSTEM.md` and `INSTALLATION.md`
