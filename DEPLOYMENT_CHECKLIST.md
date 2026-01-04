# Pamikil Car Management System

## Quick Deployment Checklist

### Before Deployment
- [ ] Review all configuration files
- [ ] Check database schema requirements
- [ ] Verify PHP version (7.4+ required)
- [ ] Confirm MySQL version (5.7+ required)

### Deployment Steps
1. [ ] Upload all files to web server
2. [ ] Create MySQL database
3. [ ] Import database/schema.sql
4. [ ] Update config/database.php with credentials
5. [ ] Update config/config.php SITE_URL
6. [ ] Set proper file permissions (755)
7. [ ] Run setup.php to initialize admin password
8. [ ] Test login functionality
9. [ ] Delete setup.php for security
10. [ ] Change default admin password

### Post-Deployment Testing
- [ ] Login works with admin/admin123
- [ ] Can view dashboard
- [ ] Can add a vehicle
- [ ] Can add a customer
- [ ] Can create a sale
- [ ] Can generate invoice
- [ ] Can view reports
- [ ] Can logout

### Security Checklist
- [ ] Delete setup.php
- [ ] Change admin password
- [ ] Enable HTTPS/SSL (production)
- [ ] Set up regular backups
- [ ] Configure firewall rules
- [ ] Update .htaccess with domain
- [ ] Restrict directory access

### Default Admin Credentials
```
Username: admin
Password: admin123
```

### Important Notes
1. Always use HTTPS in production
2. Regular backups are essential
3. Monitor logs directory for errors
4. Keep PHP and MySQL updated
5. Test in staging environment first

### Support Files
- README.md - Complete documentation
- INSTALLATION.md - Detailed installation guide
- QUICKSTART.md - InfinityFree quick start
- PROJECT_SUMMARY.md - Project overview
- database/schema.sql - Database structure

### Common Issues

**Database Connection Error**
→ Verify DB_HOST, DB_USER, DB_PASS, DB_NAME in config/database.php

**White Screen**
→ Check PHP error logs in logs/error.log
→ Enable error reporting temporarily

**Login Fails**
→ Run setup.php again
→ Check users table in phpMyAdmin

**Upload Not Working**
→ Check directory permissions (755)
→ Verify upload size limit in php.ini
→ Ensure disk space available

**Session Issues**
→ Clear browser cookies
→ Check session.save_path in php.ini
→ Verify session directory is writable

### File Permissions
```bash
chmod 755 public/uploads/
chmod 755 public/reports/
chmod 755 logs/
chmod 644 config/database.php
```

### Recommended Backup Schedule
- Daily: Database backup
- Weekly: File backup
- Monthly: Full system backup

### Performance Tips
1. Enable OPcache
2. Use proper indexing
3. Optimize images before upload
4. Clean old logs regularly
5. Use CDN for static assets (if available)

---

For detailed instructions, see INSTALLATION.md
For quick start, see QUICKSTART.md
