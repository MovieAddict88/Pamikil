# Pamikil - Car Management System

A comprehensive car dealership management system built with pure PHP and MySQL, designed specifically for the Philippine market. Optimized for shared hosting platforms including InfinityFree.

## Features

### Core Functionality
- **Vehicle Inventory Management** - Add, edit, delete, and view vehicles with detailed specifications
- **Customer Management** - Track customer information with Philippine-specific fields
- **Sales Transaction Tracking** - Complete sales workflow with Philippine Peso (₱) currency
- **Service/Maintenance Scheduling** - Track vehicle service history and schedule maintenance
- **Financial Reporting** - Dashboard with analytics and comprehensive reports
- **User Authentication** - Role-based access control (Admin, Manager, Staff)

### Technical Features
- **Pure PHP** - No frameworks required, easy to deploy
- **MySQL Database** - Relational database with proper normalization
- **Responsive Design** - Works on mobile and desktop devices
- **Secure Coding** - SQL injection prevention, CSRF protection, input validation
- **Session Management** - Secure authentication with remember-me functionality
- **File Upload** - Support for vehicle images
- **Export Functionality** - CSV export for reports

### Philippine-Specific Features
- **Currency Formatting** - Philippine Peso (₱) with proper formatting
- **Date Format** - MM/DD/YYYY Philippine format
- **Mobile Validation** - Philippine mobile number format (09XX XXX XXXX)
- **Filipino Names** - Support for common Filipino naming conventions (e.g., De La Cruz)

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3 or higher
- Web server (Apache/Nginx)
- 50 MB minimum disk space (InfinityFree compatible)

### Step 1: Upload Files
1. Upload all files to your web server's public directory
2. Ensure proper permissions (755 for directories, 644 for files)

### Step 2: Create Database
1. Log in to your hosting control panel (e.g., cPanel, InfinityFree VistaPanel)
2. Go to MySQL Databases
3. Create a new database
4. Create a database user with password
5. Grant all privileges to the user

### Step 3: Import Schema
1. Open phpMyAdmin or your database management tool
2. Select your database
3. Import the `database/schema.sql` file
4. Verify all tables were created successfully

### Step 4: Configure Database
1. Open `config/database.php`
2. Update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_database_username');
   define('DB_PASS', 'your_database_password');
   define('DB_NAME', 'your_database_name');
   ```

### Step 5: Configure Site Settings (Optional)
1. Open `config/config.php`
2. Update SITE_URL to match your domain:
   ```php
   define('SITE_URL', 'https://your-domain.com');
   ```

### Step 6: Set Permissions
Ensure the following directories are writable:
```
public/uploads/    (755)
public/reports/    (755)
logs/              (755)
```

### Step 7: Login
1. Visit your website URL
2. Login with default credentials:
   - Username: `admin`
   - Password: `admin123`

⚠️ **Important:** Change the default admin password immediately after first login!

## Directory Structure

```
pamikil/
├── config/
│   ├── config.php           # Main configuration
│   └── database.php         # Database connection
├── includes/
│   ├── auth.php             # Authentication functions
│   ├── footer.php           # Footer template
│   ├── functions.php        # Helper functions
│   └── header.php           # Header template
├── public/
│   ├── css/
│   │   └── style.css        # Main stylesheet
│   ├── uploads/             # File uploads
│   └── reports/             # Generated reports
├── database/
│   └── schema.sql           # Database schema
├── auth/
│   ├── login.php            # Login page
│   └── logout.php           # Logout handler
├── vehicles/
│   ├── index.php            # Vehicle list
│   ├── create.php           # Add vehicle
│   ├── edit.php             # Edit vehicle
│   ├── view.php             # View vehicle details
│   ├── delete.php           # Delete vehicle
│   └── export.php           # Export vehicles
├── customers/
│   ├── index.php            # Customer list
│   ├── create.php           # Add customer
│   ├── view.php             # View customer
│   └── edit.php             # Edit customer
├── sales/
│   ├── index.php            # Sales list
│   ├── create.php           # Create sale
│   ├── view.php             # View sale
│   └── invoice.php          # Print invoice
├── services/
│   ├── index.php            # Service list
│   ├── create.php           # Add service
│   ├── view.php             # View service
│   ├── edit.php             # Edit service
│   └── delete.php           # Delete service
├── users/
│   ├── index.php            # User list
│   ├── create.php           # Add user
│   ├── edit.php             # Edit user
│   └── reset-password.php   # Reset password
├── reports/
│   └── index.php            # Reports dashboard
├── dashboard.php            # Main dashboard
├── index.php                # Entry point
├── README.md                # This file
└── .gitignore               # Git ignore file
```

## Database Schema

### Tables
- **users** - System users with role-based access
- **locations** - Multi-location support
- **customers** - Customer information
- **vehicles** - Vehicle inventory
- **services** - Service and maintenance records
- **sales** - Sales transactions
- **transactions** - Payment tracking
- **customer_communications** - Customer communication logs
- **activity_logs** - User activity tracking (optional)

## User Roles

### Admin
- Full access to all features
- Manage users and locations
- Delete records
- View all reports

### Manager
- Manage vehicles, customers, sales, and services
- View reports
- Cannot manage users or delete records

### Staff
- View and manage vehicles
- View customers
- View records
- Limited editing capabilities

## Usage Guide

### Adding a Vehicle
1. Go to Vehicles → Add Vehicle
2. Fill in vehicle details (make, model, year, etc.)
3. Set pricing (purchase and selling price)
4. Upload vehicle images
5. Set status (Available, Reserved, etc.)
6. Save

### Creating a Sale
1. Go to Sales → New Sale
2. Select vehicle from available inventory
3. Select customer
4. Set sale price and financing details
5. Review and submit
6. Print invoice

### Scheduling Service
1. Go to Services → Add Service
2. Select vehicle
3. Enter service details
4. Set service date and cost
5. Save

### Viewing Reports
1. Go to Reports
2. Select date range
3. View sales summary, top vehicles, top customers
4. Export data as needed

## Deployment Notes

### InfinityFree
- Works perfectly with InfinityFree's free hosting
- No special requirements
- Ensure PHP version is 7.4 or higher in VistaPanel
- File uploads limited to 5MB per file
- Database size limited to free tier (usually 50MB+)

### Paid Hosting
- Recommended for production use
- Better performance with more resources
- Support for SSL certificates
- Regular backups recommended

### Security Recommendations
1. Change default admin password immediately
2. Use strong passwords for all users
3. Enable SSL/HTTPS
4. Regular database backups
5. Keep PHP version updated
6. Restrict directory access with .htaccess

## Troubleshooting

### Database Connection Error
- Verify database credentials in `config/database.php`
- Ensure database user has proper permissions
- Check MySQL server status

### File Upload Not Working
- Check directory permissions (755)
- Verify upload size limit in php.ini
- Ensure disk space is available

### Session Issues
- Check PHP session configuration
- Clear browser cookies
- Verify session directory is writable

### Page Not Found
- Verify SITE_URL in `config/config.php`
- Check web server rewrite rules
- Ensure .htaccess is configured (if using Apache)

## API Reference

### Helper Functions

```php
// Format currency
formatCurrency($amount)  // Returns ₱ 1,234.56

// Validate Philippine mobile
validatePHMobileNumber('09171234567')  // Returns true/false

// Format date
formatDate('2024-01-01')  // Returns 01/01/2024

// Check user role
hasRole('admin')  // Returns true/false

// Require login
requireLogin()  // Redirects if not logged in
```

## Support

For issues and questions:
- Check the troubleshooting section above
- Review error logs in the `logs/` directory
- Ensure all requirements are met

## License

This software is provided as-is for commercial and personal use in the Philippines.

## Credits

Developed for the Philippine automotive industry with focus on usability and reliability.
