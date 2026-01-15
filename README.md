# Car Management System

A comprehensive car dealership management system built with pure PHP and MySQL, designed for professional use in the Philippines and optimized for deployment on InfinityFree and shared hosting platforms.

## Features

### Core Functionality
- **Vehicle Inventory Management** - Add, edit, view, and manage vehicle inventory with detailed specifications
- **Customer Management** - Comprehensive customer database with contact details and history
- **Sales Transaction Tracking** - Record and manage sales with Philippine Peso (₱) currency support
- **Service/Maintenance Scheduling** - Schedule and track vehicle service history
- **Financial Reporting** - Generate sales reports and analytics
- **User Authentication** - Secure login with role-based access control (Admin, Manager, Staff)

### Philippine-Specific Features
- Currency formatting in Philippine Peso (₱)
- Local date format (MM/DD/YYYY)
- Philippine mobile number format validation (+63)
- Support for Filipino naming conventions (First, Middle, Last name)

### Professional Features
- Invoice generation system
- Inventory status tracking (Available, Sold, Reserved, In Service)
- Low inventory alerts
- Customer communication logs
- Vehicle history tracking
- Multi-location support
- Comprehensive audit trail
- Export functionality (CSV)
- Print-friendly reports
- Mobile-responsive design

### Technical Features
- Pure PHP (no frameworks) - Works on any PHP hosting
- MySQL database backend
- Secure coding practices (SQL injection prevention, XSS protection)
- Session-based authentication
- File upload capability for vehicle images
- Pagination for large datasets
- Search and filter functionality
- Role-based access control

## System Requirements

- PHP 7.2 or higher
- MySQL 5.6 or higher
- Apache/Nginx web server
- Minimum 50MB disk space
- Modern web browser (Chrome, Firefox, Safari, Edge)

## Quick Start

1. **Upload Files** - Upload all files to your web server
2. **Create Database** - Create a MySQL database
3. **Import Schema** - Import `database/schema.sql`
4. **Configure** - Edit `config/database.php` with your credentials
5. **Login** - Access the system and login with default credentials

**Default Login:**
- Username: `admin`
- Password: `admin123`

**IMPORTANT:** Change the default password immediately after first login!

For detailed installation instructions, see [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)

## Directory Structure

```
car-management/
├── assets/              # Static files
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   ├── images/         # System images
│   └── uploads/        # Uploaded vehicle images
├── config/             # Configuration files
│   ├── config.php     # Application settings
│   └── database.php   # Database connection
├── database/           # Database files
│   └── schema.sql     # Database schema
├── includes/           # Shared includes
│   ├── functions.php  # Helper functions
│   ├── header.php     # Common header
│   └── footer.php     # Common footer
├── modules/            # Application modules
│   ├── auth/          # Authentication
│   ├── dashboard/     # Main dashboard
│   ├── vehicles/      # Vehicle management
│   ├── customers/     # Customer management
│   ├── sales/         # Sales transactions
│   ├── services/      # Service scheduling
│   ├── reports/       # Reports and analytics
│   └── users/         # User management
└── index.php          # Main entry point
```

## User Roles

### Admin
- Full system access
- User management
- Delete records
- View all reports
- System configuration

### Manager
- Vehicle management
- Customer management
- Sales and service management
- View reports
- Limited user access

### Staff
- Add/edit vehicles and customers
- Record sales and services
- View basic reports
- No user management access

## Key Modules

### Dashboard
- Overview statistics
- Sales summary
- Inventory status
- Quick actions
- Recent transactions

### Vehicle Inventory
- Add new vehicles with full specifications
- Upload multiple images per vehicle
- Track vehicle status
- View vehicle history
- Search and filter vehicles

### Customer Management
- Complete customer profiles
- Contact information
- Purchase history
- Service history
- Communication logs

### Sales Transactions
- Create sales invoices
- Track payment status
- Multiple payment methods
- Down payment and balance tracking
- Link to vehicles and customers

### Service Management
- Schedule service appointments
- Track service history
- Record costs and payments
- Service status tracking
- Technician assignment

### Reports
- Sales reports (daily, monthly, yearly)
- Inventory reports
- Financial summaries
- Customer reports
- Export to CSV
- Print functionality

### User Management (Admin only)
- Create and manage user accounts
- Assign roles
- Activate/deactivate users
- View user activity logs

## Database Schema

### Main Tables
- `users` - System users and authentication
- `locations` - Business locations/branches
- `vehicles` - Vehicle inventory
- `customers` - Customer information
- `sales` - Sales transactions
- `service_history` - Service and maintenance records
- `communication_logs` - Customer communications
- `transaction_logs` - System audit trail

## Security Features

- Password hashing with bcrypt
- SQL injection prevention using prepared statements
- XSS protection with output escaping
- Session management with HTTP-only cookies
- Role-based access control
- Audit logging for all actions
- Input validation and sanitization

## Deployment

### InfinityFree Hosting
This system is optimized for InfinityFree's free hosting:
- No special PHP extensions required
- Works within free plan limitations
- Compatible with shared MySQL database
- File upload within size limits

### Other Shared Hosting
Compatible with most shared hosting providers:
- cPanel hosting
- Plesk hosting
- DirectAdmin hosting
- Any PHP/MySQL hosting

### Local Development
Can be run locally using:
- XAMPP
- WAMP
- MAMP
- LAMP
- Docker

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Responsive Design

The system is fully responsive and works on:
- Desktop computers
- Laptops
- Tablets
- Smartphones

## Philippine Format Support

### Currency
- Philippine Peso symbol (₱)
- Format: ₱ 1,234,567.89

### Date Format
- MM/DD/YYYY format
- 12-hour time format with AM/PM

### Phone Numbers
- Supports +63 country code
- Formats: +63 917 123 4567
- Validates Philippine mobile numbers (09XX XXX XXXX)

### Names
- First Name, Middle Name, Last Name format
- Common Filipino naming conventions

## Backup and Maintenance

### Regular Backups
- Export database weekly
- Download uploaded images
- Store backups securely offsite

### Database Maintenance
```sql
-- Optimize tables monthly
OPTIMIZE TABLE vehicles, customers, sales, service_history;

-- Clean old transaction logs
DELETE FROM transaction_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Image Management
- Regularly review uploaded images
- Compress large images
- Remove images of sold/deleted vehicles

## Troubleshooting

### Common Issues

**Cannot Login**
- Verify database connection
- Check username and password
- Clear browser cache

**Images Not Uploading**
- Check folder permissions (755)
- Verify PHP upload limits
- Ensure uploads folder exists

**Blank Page**
- Check PHP error logs
- Verify all files uploaded
- Check database connection

**Slow Performance**
- Optimize database tables
- Compress images
- Check hosting resources

For more troubleshooting help, see [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)

## Development

### Built With
- PHP (Pure, no frameworks)
- MySQL
- HTML5
- CSS3 (Custom styles)
- JavaScript (Vanilla JS)
- Font Awesome Icons

### Design Principles
- Simple and intuitive interface
- Fast loading times
- Mobile-first responsive design
- Accessibility considerations
- Clean and maintainable code

## Version History

### Version 1.0.0 (Current)
- Initial release
- Full CRUD operations for all modules
- Role-based access control
- Philippine format support
- Responsive design
- CSV export functionality
- Audit logging
- Multi-location support

## Future Enhancements

Potential features for future versions:
- PDF report generation
- Email notifications
- SMS integration
- Advanced analytics dashboard
- Inventory forecasting
- Customer portal
- Online payment integration
- Multi-language support
- API for mobile app integration

## Support

For installation help, see [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)

For bug reports or feature requests, contact your system administrator.

## License

Copyright © 2024 Car Management System
All rights reserved.

This software is provided for professional use.

## Credits

Developed with consideration for:
- Philippine business practices
- Local formatting requirements
- Shared hosting limitations
- Small to medium dealership needs

## Screenshots

### Login Page
Professional login interface with demo credentials displayed

### Dashboard
Comprehensive overview with statistics, recent activities, and quick actions

### Vehicle Inventory
Complete vehicle management with images, specifications, and status tracking

### Sales Management
Full sales transaction recording with payment tracking

### Reports
Detailed reports with export and print functionality

---

**Car Management System** - Efficient Vehicle Dealership Management for the Philippines

For questions or support, contact your system administrator.
