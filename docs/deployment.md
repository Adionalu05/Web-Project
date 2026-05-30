# Deployment Guide
## Document Management System

**Environment:** Local / LAN  
**Date:** 2026-05-18  

> For PHP installation steps on Windows see the **PHP Setup** section below.

---

## Prerequisites

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 7.4+ | Tested on 8.2 |
| SQLite | 3 | Via `pdo_sqlite` extension |
| Git | Any | For cloning |

### Required PHP Extensions
Enable in `php.ini` (remove the `;` prefix):
```ini
extension=pdo_sqlite
extension=sqlite3
extension=openssl
extension=curl
extension=sockets
extension=fileinfo
```

On Windows, also set:
```ini
extension_dir = "C:\php\ext"    ; absolute path required
```

### PHP Upload Limits
In `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 12M
```

---

## Local Deployment

### 1. Clone the repository
```bash
git clone https://github.com/Adionalu05/Web-Project.git
cd Web-Project
```

### 2. Configure Gmail SMTP
Create `config/mail.php`:
```php
<?php
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'yourapppasswordwithoutspaces');
define('MAIL_FROM_NAME', 'DMS System');
```

> Gmail requires a 16-character App Password (Google Account → Security → 2FA → App passwords). Remove all spaces from the password string.

### 3. Start the server
```bash
# Local only (this machine)
php -S localhost:8000

# LAN access (all devices on same network)
php -S 0.0.0.0:8000
```

### 4. Open in browser
```
http://localhost:8000
```

The database (`data/documents.db`) and schema are created automatically on first request.

---

## LAN / Multi-Device Deployment

Allows a second device on the same Wi-Fi to access the app.

### 1. Find host machine IP
```bash
# Windows
ipconfig
# Look for: IPv4 Address under Wi-Fi adapter
# Example: 192.168.1.45
```

### 2. Start server bound to all interfaces
```bash
php -S 0.0.0.0:8000
```

### 3. Allow port 8000 through Windows Firewall
Windows Defender Firewall → Inbound Rules → New Rule:
- Type: Port
- Protocol: TCP, port 8000
- Action: Allow the connection
- Profile: Private
- Name: "PHP Dev Server"

### 4. Connect from second device
```
http://192.168.1.45:8000
```

Both devices read/write the same `data/documents.db` file.

---

## AI Search (Optional)

Set the Claude API key in the environment before starting the server:

```bash
# Windows PowerShell
$env:CLAUDE_API_KEY = "sk-ant-..."
php -S 0.0.0.0:8000

# Windows Command Prompt
set CLAUDE_API_KEY=sk-ant-...
php -S 0.0.0.0:8000
```

If the key is not set, search falls back to SQL ordering — no crash.

---

## Demo Data Seeder

For repeatable demo setup, run the seeder once after starting the server:

```
http://localhost:8000/seed_demo.php
```

This creates a 3-level folder tree with 8 test documents. The script wipes the previous demo state before re-seeding.

> `seed_demo.php` is listed in `.gitignore` — local use only, not part of production code.

---

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| Upload silently fails for files > 2 MB | `upload_max_filesize = 2M` in php.ini | Raise to 10M, `post_max_size` to 12M |
| "Access Denied" on login | Port mismatch in `isSameOriginRequest()` | Already fixed in current version |
| PHPMailer SMTP fails | openssl/curl disabled in php.ini | Uncomment extensions; use absolute `extension_dir` on Windows |
| Gmail auth fails | App password has spaces | Remove all spaces from `MAIL_PASSWORD` |
| Second device cannot connect | Windows Firewall blocking port 8000 | Add inbound rule (see LAN section above) |
| `openssl_decrypt` IV warning | Plain-text emails stored before openssl enabled | Already handled with strict base64 decode |
| Sharing returns 404 | Relative login redirect + SQLite busy timeout | Already fixed in current version |
| `Database connection error: could not find driver` | `pdo_sqlite` or `extension_dir` not configured | Set absolute `extension_dir` in php.ini; remove `;` before `extension=pdo_sqlite` |

---

## PHP Setup on Windows (first-time install)

### 1. Install PHP

- Download **Non Thread Safe** PHP 8.x from https://windows.php.net/download/
- Extract to `C:\php`
- Confirm: `php -v`

### 2. Create php.ini

```powershell
Copy-Item C:\php\php.ini-development C:\php\php.ini
```

### 3. Add PHP to PATH

- Open **System Properties → Environment Variables**
- Under **System Variables**, select `Path` → **Edit**
- Add `C:\php`
- Click OK and restart the terminal
- Confirm: `php -v`

### 4. Enable extensions in php.ini

Open `C:\php\php.ini` and make these changes:

```ini
; Required on Windows — must be absolute path
extension_dir = "C:\php\ext"

; Uncomment all of these (remove the leading semicolon)
extension=pdo_sqlite
extension=sqlite3
extension=openssl
extension=curl
extension=sockets
extension=fileinfo

; Upload limits
upload_max_filesize = 10M
post_max_size = 12M
```

Confirm extensions are active:
```bash
php -m
# Should include: curl, openssl, PDO, pdo_sqlite, sqlite3
```

---

## SQLite CLI — Database Inspection

Inspect the database directly from the terminal:

```bash
sqlite3 data/documents.db
```

Useful commands:
```sql
.tables                          -- list all tables
PRAGMA table_info(users);        -- show columns for a table
SELECT id, username FROM users;  -- query data
.headers on                      -- show column names in output
.mode column                     -- aligned column output
.quit                            -- exit
```
