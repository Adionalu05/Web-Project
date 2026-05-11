# Document Management System

A multi-user web application for secure document upload, organisation, and retrieval — built with PHP, SQLite, jQuery, and the Anthropic Claude API. Developed as a university Web Development project by a team of five.

---

## Features

| Feature | Details |
|---------|---------|
| **Authentication** | Registration, login, logout with DB-backed session tokens (24h expiry) |
| **Secure upload** | Extension whitelist, 10 MB limit, `uniqid()` filename on disk, metadata stored in SQLite |
| **Secure download** | Auth-gated `download.php` — ownership or share permission verified before any file is served; raw filesystem paths never exposed |
| **Document management** | Inline edit of title, category, tags, description via AJAX modal; delete with ownership check |
| **Folder system** | Nested folders with recursive document retrieval — clicking a parent folder surfaces documents from all descendant subfolders using a SQLite `WITH RECURSIVE` CTE |
| **File sharing** | Share any document with another registered user by username; shared documents appear in a separate "Shared with Me" tab |
| **AI-enhanced search** | Keyword results reranked by semantic relevance via the Anthropic Claude API (`claude-haiku-4-5-20251001`); falls back to SQL order gracefully if no API key is set |
| **Password reset** | Email-based flow via PHPMailer + Gmail SMTP (STARTTLS); single-use tokens with 1-hour expiry stored in DB |
| **Email encryption** | Email addresses stored AES-256-CBC encrypted at rest; SHA-256 hash used for all lookups so the plaintext is never queried |
| **File icons** | Document table renders an icon per file extension (PDF, DOCX, image, etc.) |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Server | PHP 7.4+ (built-in server or Apache) |
| Database | SQLite 3 via PDO |
| Frontend | HTML/CSS, jQuery (local), AJAX |
| Email | PHPMailer 6 — Gmail SMTP, STARTTLS, port 587 |
| AI | Anthropic Claude API — `claude-haiku-4-5-20251001` via PHP cURL |
| Security | bcrypt, AES-256-CBC, SHA-256, session tokens, `.htaccess` directory protection |

---

## Architecture

All browser→server communication goes through a single AJAX endpoint (`api/handle.php?action=X`). This keeps routing in one place and makes every operation easy to audit for authentication — all 12 actions require a valid session token.

```
Browser (jQuery AJAX)
    │
    ▼
api/handle.php               ← single entry point, routes by ?action=
    │
    ├── Auth                 auth/auth.php              ← session check on every request
    ├── DocumentHandler      auth/document_handler.php  ← all SQL queries
    └── sendEmail()          auth/email.php             ← PHPMailer wrapper
            │
            ├── SQLite       data/documents.db
            ├── Uploads      uploads/                   ← gitignored
            ├── Claude       api.anthropic.com/v1/messages
            └── Gmail        smtp.gmail.com:587
```

---

## Database Schema

Nine tables, designed to 3NF with foreign keys and cascade deletes throughout.

```
users               — credentials + AES-encrypted email + SHA-256 hash
sessions            — DB-backed tokens (not PHP session alone)
documents           — file metadata + folder_id FK
categories          — Tickets, Contracts, Reports, Other
tags                — free-form labels
document_tags       — many-to-many join
folders             — self-referential (parent_id) for nesting
shares              — document_id + shared_with_user_id, UNIQUE constraint
password_resets     — single-use tokens, 1h expiry, used flag
```

Schema initialises automatically on first run via `config/database.php → initializeDatabase()`. All `CREATE TABLE` statements use `IF NOT EXISTS`; the `folder_id` column is added via a try/catch-wrapped `ALTER TABLE` so the app is safe to deploy against an existing database without wiping data.

---

## Security Highlights

- **Passwords** — `password_hash()` bcrypt, never stored plain
- **Sessions** — `bin2hex(random_bytes(32))` token stored in DB and validated on every request; logout deletes the server-side record, not just the cookie
- **Email at rest** — AES-256-CBC encrypted via OpenSSL; only a SHA-256 hash is used for lookups so the plaintext is never queried against the DB
- **Downloads** — served through `download.php` which checks ownership OR share permission before reading any file; raw file paths never appear in the browser
- **Uploads** — extension whitelist, 10 MB cap, randomised filename on disk so the original name cannot be enumerated
- **CSRF mitigation** — `isSameOriginRequest()` validates referer host against server host on all POST actions
- **Directory hardening** — `.htaccess` blocks direct browser access to `auth/`, `config/`, `data/`, `uploads/`
- **Credentials** — `config/mail.php` gitignored; Claude API key loaded via `getenv()` so it never touches the filesystem

---

## AI Search — How It Works

```
GET api/handle.php?action=search&q=financial

  1. SQL LIKE query → raw keyword matches returned
  2. aiRerank("financial", $docs) called if CLAUDE_API_KEY is set
       → prompt lists matched documents with id, title, category, tags
       → Claude returns comma-separated IDs in relevance order
       → PHP reorders $docs by that sequence
  3. Reranked list returned to browser

  Fallback: if API key absent or cURL fails → original SQL order returned unchanged
```

---

## Recursive Folder Search

Folders are stored as an adjacency list (`parent_id` FK on the `folders` table). Clicking a parent folder triggers a query using a recursive CTE to collect every descendant folder ID before fetching documents:

```sql
WITH RECURSIVE subfolder_ids AS (
    SELECT id FROM folders WHERE id = :folder_id
    UNION ALL
    SELECT f.id FROM folders f
    INNER JOIN subfolder_ids s ON f.parent_id = s.id
)
SELECT d.* FROM documents d
WHERE d.folder_id IN (SELECT id FROM subfolder_ids)
  AND (d.user_id = :uid OR d.id IN (
      SELECT document_id FROM shares WHERE shared_with_user_id = :uid2
  ))
```

---

## Getting Started

### Requirements

PHP 7.4+ with extensions enabled in `php.ini`:

```ini
extension_dir = "C:\php\ext"   ; Windows — must be absolute path
extension=curl
extension=openssl
extension=sockets
extension=pdo_sqlite
extension=sqlite3
upload_max_filesize = 10M
post_max_size = 12M
```

### Setup

```bash
git clone https://github.com/Katalizatori/Document-Management-System.git
cd Document-Management-System
```

Create `config/mail.php` (not in repo — see `.docs/_installation.md`):
```php
<?php
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',  'your@gmail.com');
define('MAIL_PASSWORD',  'your16charapppassword'); // no spaces
define('MAIL_FROM',      'your@gmail.com');
define('MAIL_FROM_NAME', 'File Management System');
```

Set Claude API key (optional — search works without it):
```bash
set CLAUDE_API_KEY=sk-ant-...
```

### Run

```bash
php -S localhost:8000
```

Open `http://localhost:8000`, register an account, and start uploading.

To load a pre-built demo with nested folders and test documents:
```
http://localhost:8000/seed_demo.php
```

---

## Project Structure

```
├── index.php                  Landing page
├── login.php / register.php   Auth pages
├── dashboard.php              Main application view
├── download.php               Auth-gated file download
├── forgot_password.php        Password reset request
├── reset_password.php         Token validation + new password
│
├── api/handle.php             Single AJAX router (12 actions)
├── auth/
│   ├── auth.php               Auth class — session, register, login
│   ├── document_handler.php   DocumentHandler — all SQL queries
│   └── email.php              PHPMailer wrapper
├── config/
│   ├── database.php           PDO setup, schema init, AES helpers
│   └── mail.php               SMTP credentials (gitignored)
├── lib/PHPMailer/             PHPMailer library
├── js/
│   ├── dashboard.js           All AJAX calls + modal handlers
│   └── jquery.min.js          Local jQuery copy
├── css/style.css              All styles
│
├── data/                      gitignored — SQLite DB + mail log
├── uploads/                   gitignored — uploaded files
└── .docs/                     Full project documentation
```

---

## Documentation

All documentation lives in `.docs/`:

| File | Contents |
|------|---------|
| `_project-explanation.md` | Stack, feature checklist, full DB schema, API reference, auth flows |
| `_installation.md` | Step-by-step setup guide — PHP, php.ini, credentials, server commands |
| `feature_external-services.md` | Claude API + Gmail SMTP — how they work, how keys are stored securely, debug log |
| `feature-recursive-folder-search.md` | Recursive CTE implementation — before/after SQL, sidebar rendering, seed demo |
| `presentation_summary.md` | Full project reference + live demo walkthrough |
| `presentation_team.md` | Team roles and file ownership |
| `_debugging.md` | Every bug encountered during development — root cause and fix |
| `diagrams.md` | Mermaid architecture diagram, ERD, Gantt chart |
| `report-2026-04-06.md` / `report-2026-04-07.md` | Professor meeting notes and agreed requirements |

---

## Team

Five members, each owning a distinct vertical slice of the project:

| Role | Responsibility |
|------|---------------|
| Frontend & UI | `dashboard.php`, all auth pages, `css/style.css` — layout, modals, tab bar, file icons |
| API Integration | `api/handle.php`, `js/dashboard.js`, Claude + Gmail integrations |
| Database Design | `config/database.php` schema, `auth/document_handler.php` — all SQL, AES encryption |
| Auth & Security | `auth/auth.php`, `download.php`, `.htaccess`, password reset flow |
| Infrastructure & Docs | Server setup, `.gitignore` policy, git branching strategy, all `.docs/` files |

Development followed a `main` / `dev` branch strategy — all feature work done on `dev`, `main` kept stable throughout.

---

## Sensitive Files — Never Committed

```
config/mail.php      SMTP credentials
data/documents.db    SQLite database
data/mail.log        PHPMailer error log
uploads/             User-uploaded files
seed_demo.php        Development-only seeder script
demo-files/          Local demo upload files
.claude/             AI assistant working files
```
