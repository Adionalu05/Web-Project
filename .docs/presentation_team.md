# Team — Roles & Responsibilities

Five members. Each person owned a vertical slice of the project end-to-end.

---

## Person 1 — Frontend & UI Design

**Concern:** Everything the user sees and interacts with in the browser.

**Files owned:**
- `dashboard.php` — main application view (document table, sidebar, tab bar, all modals)
- `login.php` — login form + "Forgot password?" link + reset success message
- `register.php` — registration form
- `forgot_password.php` — email input form (GET view)
- `reset_password.php` — token validation view + new password form
- `css/style.css` — all styling: layout, modal overlays, tab bar, folder tree, buttons, file icons

**What they built:**
- Responsive two-column layout (sidebar + main content area)
- Document table with per-row Edit, Share, Download, Delete buttons
- Edit modal (pre-populated with current title, category, tags, description)
- Share modal (username input → share with another user)
- New Folder modal
- Tab switcher between "My Documents" and "Shared with Me"
- File type icons mapped by extension (PDF, DOCX, image, etc.)
- Flash messages and form feedback across all auth pages

---

## Person 2 — API Integration

**Concern:** All communication between the browser and the server, and between the server and external services.

**Files owned:**
- `api/handle.php` — single AJAX entry point; routes all browser requests by `action` parameter
- `js/dashboard.js` — all client-side AJAX calls, DOM updates, modal logic
- `auth/email.php` — PHPMailer wrapper for Gmail SMTP
- `lib/PHPMailer/` — PHPMailer library (PHPMailer.php, SMTP.php, Exception.php)
- `config/mail.php` — Gmail SMTP credentials (gitignored)

**What they built:**
- Unified API router (`api/handle.php`) handling 12 actions over GET/POST
- All jQuery AJAX calls in `dashboard.js` (upload, search, edit, delete, share, folder navigation)
- Claude Anthropic API integration — cURL request to `api.anthropic.com/v1/messages`, response parsing, graceful fallback if key is absent
- Gmail SMTP email delivery via PHPMailer (STARTTLS, port 587, app password auth)
- Secure credential storage: Claude key via `getenv()`, mail credentials in gitignored config file

---

## Person 3 — Database Design & Queries

**Concern:** Data model, schema, and all SQL that reads or writes to the database.

**Files owned:**
- `config/database.php` — schema initialisation, all `CREATE TABLE` statements, AES-256-CBC encryption helpers
- `auth/document_handler.php` — all DocumentHandler methods (every SQL query lives here)
- `.docs/sqlite_queries.md` — reference sheet of all queries used in the project

**What they designed:**
- Core tables: `users`, `sessions`, `documents`, `categories`, `tags`, `document_tags`
- Extended schema: `folders` (with `parent_id` for nesting), `shares`, `password_resets`
- `folder_id` column migration on `documents`
- AES-256-CBC at-rest encryption for email addresses (`encryptValue` / `decryptValue`)
- SHA-256 email hashing for indexed lookups without exposing plaintext
- All DocumentHandler methods: upload, list, filter, keyword search, edit, delete, folder CRUD, share, get shared documents

---

## Person 4 — Authentication & Security

**Concern:** User identity, session management, access control, and hardening.

**Files owned:**
- `auth/auth.php` — `Auth` class: register, login, logout, session validation, same-origin check
- `download.php` — secure file download (auth-gated, no raw filesystem paths exposed)
- `.htaccess` — directory access rules (blocks direct access to `auth/`, `config/`, `data/`, `uploads/`)
- `.gitignore` — prevents credentials and user data from being committed

**What they built:**
- Session token system: `bin2hex(random_bytes(32))` token stored in `sessions` table, validated on every request
- Password hashing with `password_hash()` / `password_verify()` (bcrypt)
- Password reset flow: secure `bin2hex(random_bytes(32))` token, 1-hour expiry, single-use (`used = 1` after redemption), stored in `password_resets`
- `download.php`: checks session, verifies ownership OR share permission before serving any file; sets `Content-Disposition: attachment` so no raw path is ever exposed in a link
- `isSameOriginRequest()`: CSRF mitigation — compares referer host against server host
- `.htaccess` rules denying direct browser access to sensitive directories

---

## Person 5 — Infrastructure & Documentation

**Concern:** Project setup, developer environment, version control hygiene, and written documentation.

**Files owned:**
- `QUICK_START.md` — step-by-step server setup guide
- `CONFIG_CHECKLIST.md` — pre-launch checklist (php.ini extensions, credentials, paths)
- `.docs/_project-explanation.md` — project overview, stack, feature checklist, DB schema notes
- `.docs/external-services.md` — API explainer (what an API is, Claude integration, Gmail SMTP, key security, demos, debug log)
- `.docs/debug-log.md` — running log of every bug found and fixed
- `.docs/report-2026-04-06.md`, `report-2026-04-07.md` — professor meeting reports
- `.docs/diagrams.md` — architecture and flow diagrams
- `.docs/cleanup-report.md` — documents all legacy files deleted and why
- Git branching strategy: `main` kept stable, all feature work on `dev`

**What they set up:**
- PHP built-in server configuration (`php -S localhost:8000`)
- `php.ini` extension requirements documented and verified (`openssl`, `curl`, `sockets`, `pdo_sqlite`)
- `.gitignore` policy: `config/mail.php`, `data/documents.db`, `data/mail.log`, `uploads/` all excluded
- All written documentation kept in `.docs/` and updated alongside every feature and bug fix
