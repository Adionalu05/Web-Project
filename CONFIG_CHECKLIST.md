# Configuration Checklist

## Pre-Installation Checks

- [ ] PHP 7.4 or higher installed
- [ ] SQLite 3 support enabled in PHP
- [ ] Web server (Apache, Nginx, or PHP built-in) available
- [ ] Write permissions on project directory

## After Installation Steps

### 1. Create Required Directories
- [ ] `/data/` directory exists
- [ ] `/uploads/` directory exists
- [ ] Both directories are writable (chmod 755)

### 2. Verify PHP Configuration
- [ ] SQLite extension is enabled: `php -m | grep -i sqlite`
- [ ] File upload is enabled: `php -i | grep upload`
- [ ] Session support is enabled: `php -i | grep session`

### 3. First Run
- [ ] Start PHP server: `php -S localhost:8000`
- [ ] Navigate to `http://localhost:8000`
- [ ] Verify landing page loads
- [ ] Check that database is created in `/data/documents.db`

### 4. User Registration
- [ ] Successfully register a new account
- [ ] Verify email validation works
- [ ] Verify username uniqueness check works
- [ ] Verify password hashing (passwords stored securely)

### 5. User Authentication
- [ ] Successfully login with credentials
- [ ] Session token is created in database
- [ ] Logout function works properly
- [ ] Can't access dashboard without login

### 6. File Upload
- [ ] Can select a valid file
- [ ] Can add title, category, and tags
- [ ] File uploads successfully
- [ ] File is stored in `/uploads/` directory
- [ ] Metadata is saved in database
- [ ] File size validation works (>10MB rejected)
- [ ] File type validation works (invalid types rejected)

### 7. File Organization
- [ ] Categories dropdown populated
- [ ] Tags appear after tagging documents
- [ ] Multiple tags can be added to one document
- [ ] Documents appear in dashboard table

### 8. File Search & Filter
- [ ] Search by title works
- [ ] Filter by category works
- [ ] Filter by tag works
- [ ] Combined filters work together
- [ ] Clear filters button resets all

### 9. File Management
- [ ] Can download files
- [ ] Delete confirmation appears
- [ ] Files are deleted from server and database
- [ ] Deleted files no longer appear in list

### 10. Security Verification
- [ ] .htaccess is in place and working
- [ ] Sensitive files are protected
- [ ] Cannot directly access `/config/`, `/auth/`, `/data/` directories
- [ ] Config files are not accessible via web
- [ ] Users can only access their own documents

## Performance Checks

- [ ] Page load time is acceptable
- [ ] Search results appear quickly
- [ ] File upload progress visible
- [ ] No console errors in browser

## Database Verification

Check database tables with SQLite:
```bash
sqlite3 data/documents.db ".tables"
```

Should show:
- users
- documents
- categories
- tags
- document_tags
- sessions

## Common Issues & Fixes

### Database Connection Error
```
Fix: Ensure /data/ directory exists and is writable
chmod 755 data/
```

### File Upload Failed
```
Fix: Ensure /uploads/ directory exists and is writable
chmod 755 uploads/
```

### Session Not Persisting
```
Fix: Check PHP session configuration in php.ini
Ensure session.save_path is writable
```

### File Download Not Working
```
Fix: Verify file path is correct in database
Check file permissions in uploads directory
```

### SQLite Not Found
```
Fix: Enable SQLite in PHP
Ubuntu/Debian: apt-get install php-sqlite3
Edit php.ini: extension=sqlite3
```

## Command Line Tests

### Test Database
```bash
sqlite3 data/documents.db "SELECT COUNT(*) as user_count FROM users;"
```

### Test File Permissions
```bash
ls -la | grep -E "config|data|uploads|css|js"
```

### Test PHP Configuration
```bash
php -i | grep -E "sqlite|upload|session"
```

## Deployment Checklist (for Production)

- [ ] Change default session timeout if needed
- [ ] Enable HTTPS/SSL
- [ ] Update file size limits if needed
- [ ] Review allowed file types
- [ ] Set up regular backups of /data/ directory
- [ ] Configure access controls
- [ ] Test with multiple users
- [ ] Monitor file storage space
- [ ] Set up error logging
- [ ] Test on target server environment

## Support & Troubleshooting

If something doesn't work:
1. Check error logs: Search browser console (F12)
2. Verify directory permissions: `ls -la`
3. Test PHP configuration: `php -i`
4. Verify SQLite database: `sqlite3 data/documents.db ".databases"`
5. Review .htaccess rules: Ensure properly formatted

## Version Information

- PHP Minimum: 7.4
- SQLite Minimum: 3.0
- Session Timeout: 24 hours
- Max File Size: 10 MB
- Supported Browsers: All modern browsers (IE11+)

---
**Last Updated**: 2024
**Status**: ✅ Production Ready
