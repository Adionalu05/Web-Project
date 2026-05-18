# Document Management System — Web Dev Class

Stack:
* PHP 7.4+ (built-in server: `php -S localhost:8000`)
* SQLite 3 via PDO (`pdo_sqlite`, `sqlite3` extensions)
* jQuery (local copy at `js/jquery.min.js` — CDN unreachable on local network)
* PHPMailer (`lib/PHPMailer/`) for Gmail SMTP
* Anthropic Claude API (`claude-haiku-4-5-20251001`) for AI search reranking

Required `php.ini` extensions:
```ini
extension=curl
extension=openssl
extension=sockets
extension=pdo_sqlite
extension=sqlite3
extension_dir = "C:\php\ext"   ; must be absolute path on Windows
upload_max_filesize = 10M
post_max_size = 12M
```

---

## Architecture

### System Overview

```mermaid
flowchart LR
    subgraph CLIENT["🌐 Browser"]
        UI["Pages\ndashboard · login · register\nforgot_password · reset_password"]
        AJAX["jQuery AJAX\njs/dashboard.js"]
    end

    subgraph SERVER["⚙️ PHP Server"]
        API["api/handle.php\n─────────────────\nSingle AJAX entry point\n12 actions · all auth-gated"]

        subgraph CORE["Core Classes"]
            AUTH["Auth\nauth/auth.php\n──────────────\nRegister · Login · Logout\nSession token validation\nbcrypt · same-origin check"]
            DH["DocumentHandler\nauth/document_handler.php\n──────────────\nUpload · Search · Edit · Delete\nFolders · Share · aiRerank()"]
        end

        MAIL["PHPMailer wrapper\nauth/email.php"]
        CFG["config/database.php\n──────────────\nSchema init\nAES-256-CBC helpers\nClaude API key"]
    end

    subgraph STORAGE["💾 Storage"]
        DB[("SQLite\ndocuments.db\n──────────────\n9 tables\nFKs + cascade deletes")]
        FILES["uploads/\nFiles on disk\ngitignored"]
    end

    subgraph EXTERNAL["☁️ External Services"]
        CLAUDE["Anthropic Claude API\nclaude-haiku-4-5-20251001\n──────────────\nAI search reranking\nGraceful fallback if no key"]
        GMAIL["Gmail SMTP\nsmtp.gmail.com:587\n──────────────\nPassword reset email\nSTARTTLS · App password"]
    end

    UI --> AJAX
    AJAX -->|"GET / POST ?action=X"| API
    API --> AUTH
    API --> DH
    API -->|"password reset"| MAIL
    AUTH <-->|"session tokens"| DB
    DH <-->|"all queries"| DB
    DH -->|"read/write files"| FILES
    DH -->|"cURL · aiRerank()"| CLAUDE
    MAIL -->|"STARTTLS · port 587"| GMAIL
    CFG -.->|"schema init"| DB
```

---

### Request Lifecycle

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant api/handle.php
    participant Auth
    participant DocumentHandler
    participant SQLite
    participant Claude API

    User->>Browser: clicks Search "financial"
    Browser->>api/handle.php: GET ?action=search&q=financial
    api/handle.php->>Auth: isAuthenticated()
    Auth->>SQLite: SELECT sessions WHERE token=? AND expires_at > NOW
    SQLite-->>Auth: ✅ valid session
    Auth-->>api/handle.php: user_id = 3

    api/handle.php->>DocumentHandler: searchDocuments("financial")
    DocumentHandler->>SQLite: SELECT ... WHERE title LIKE '%financial%'
    SQLite-->>DocumentHandler: [doc3, doc1, doc2]

    DocumentHandler->>Claude API: POST /v1/messages (prompt + doc list)
    Claude API-->>DocumentHandler: "3,1,2" (reranked IDs)
    DocumentHandler-->>api/handle.php: [doc3, doc1, doc2] reordered

    api/handle.php-->>Browser: JSON response
    Browser-->>User: renders reranked results
```

---

## Features

- [x] Authentication
    - [x] Register / Login / Logout  `auth/auth.php`
    - [x] Session tokens (stored in DB, 24h expiry)  `sessions` table
- [x] Secure file upload  `auth/document_handler.php`, `api/handle.php`
    - [x] Categories & Tags
    - [x] List, Filter, Delete files
- [x] Dashboard + AJAX  `dashboard.php`, `js/dashboard.js`
- [x] Secure file download  `download.php`  (auth-gated, no raw path exposed)
- [x] Edit document metadata inline  (modal in dashboard)
- [x] Folder system with file icons  `folders` table, sidebar in dashboard
- [x] File sharing between users  `shares` table
- [x] Claude API integration for AI-ranked search  `auth/document_handler.php → aiRerank()`
- [x] Password reset via email  `forgot_password.php`, `reset_password.php`, PHPMailer

---

## Explanation of Basic Notions Used

**API**
A contract between two programs: one exposes URLs (endpoints) that accept requests in a defined format and return structured responses (usually JSON). This project uses APIs in two directions:
- *Inward*: the browser calls our own `api/handle.php` via AJAX
- *Outward*: our PHP calls the Claude API and Gmail SMTP

**SESSION**
PHP's `$_SESSION` is just a server-side key-value store keyed to a cookie in the browser. We extend it with a DB-backed token: on login a `bin2hex(random_bytes(32))` token is written to the `sessions` table and also put in `$_SESSION['token']`. On every request `Auth::isAuthenticated()` checks that the token exists in the DB and hasn't expired.

**HANDLER**
A class that owns all the logic for one domain. `Auth` handles users and sessions. `DocumentHandler` handles everything about files — upload, list, search, edit, share, folders. `api/handle.php` is the HTTP-level handler: a single entry point that reads `?action=X` and routes to the right method.

**cURL**
PHP's library for making outbound HTTP requests. We use it in `aiRerank()` to call the Anthropic API endpoint. `curl_init()` → `curl_setopt()` (headers, body, method) → `curl_exec()` → `curl_close()`.

**GET / POST**
- `GET`: reads data, params in the URL (`?search=invoices`). Used for: list documents, get folders, search.
- `POST`: sends data in the request body (not the URL). Used for: upload, login, delete, edit, share. Anything that mutates state must be POST.

---

## File Structure

```
Web-Project/
│
├── index.php                  → redirects to login or dashboard
├── login.php                  → login form + "Forgot password?" link
├── register.php               → registration form
├── logout.php                 → destroys session, redirects
├── dashboard.php              → main app view (table, sidebar, modals)
├── download.php               → auth-gated file download handler
├── forgot_password.php        → email input form → generates reset token
├── reset_password.php         → validates token → change password form
├── edit_document.php          → (legacy standalone page, superseded by modal)
│
├── api/
│   └── handle.php             → single AJAX entry point, routes by ?action=
│
├── auth/
│   ├── auth.php               → Auth class: register, login, logout, session check
│   ├── document_handler.php   → DocumentHandler class: all file + folder + share logic
│   └── email.php              → sendEmail() wrapper using PHPMailer
│
├── config/
│   ├── database.php           → PDO connection, schema init, encryption helpers
│   └── mail.php               → Gmail SMTP credentials (gitignored)
│
├── lib/
│   └── PHPMailer/             → PHPMailer.php, SMTP.php, Exception.php
│
├── js/
│   ├── jquery.min.js          → local jQuery copy
│   └── dashboard.js           → all AJAX calls, modal handlers, table rendering
│
├── css/
│   └── style.css              → layout, modals, tab bar, folder tree, icons
│
├── data/                      → gitignored
│   ├── documents.db           → SQLite database file
│   └── mail.log               → PHPMailer error log
│
├── uploads/                   → gitignored — actual uploaded files
│
├── .htaccess                  → blocks direct browser access to auth/, config/, data/, uploads/
├── .gitignore
└── .docs/                     → all project documentation
```

---

## Database

### Full Schema

```sql
-- Users: stores credentials + encrypted email
CREATE TABLE IF NOT EXISTS users (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  username    TEXT UNIQUE NOT NULL,
  email       TEXT NOT NULL,          -- AES-256-CBC encrypted, base64-encoded
  email_hash  TEXT UNIQUE NOT NULL,   -- SHA-256 of lowercase email, used for lookups
  password    TEXT NOT NULL,          -- bcrypt via password_hash()
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sessions: DB-backed tokens (more secure than PHP session alone)
CREATE TABLE IF NOT EXISTS sessions (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id       INTEGER NOT NULL,
  session_token TEXT UNIQUE NOT NULL, -- bin2hex(random_bytes(32))
  expires_at    DATETIME NOT NULL,    -- 24 hours from creation
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Categories: Tickets, Contracts, Reports, Other (seeded)
CREATE TABLE IF NOT EXISTS categories (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  name        TEXT UNIQUE NOT NULL,
  description TEXT,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Documents: one row per uploaded file
CREATE TABLE IF NOT EXISTS documents (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id     INTEGER NOT NULL,
  title       TEXT NOT NULL,
  category_id INTEGER,
  file_path   TEXT NOT NULL,          -- server path (never exposed to client)
  file_name   TEXT NOT NULL,          -- original filename
  file_size   INTEGER NOT NULL,       -- bytes
  file_format TEXT NOT NULL,          -- extension (pdf, docx, jpg…)
  description TEXT,
  folder_id   INTEGER DEFAULT NULL,   -- FK to folders (added via ALTER TABLE)
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Tags: free-form labels
CREATE TABLE IF NOT EXISTS tags (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  name       TEXT UNIQUE NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- document_tags: many-to-many join
CREATE TABLE IF NOT EXISTS document_tags (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  document_id INTEGER NOT NULL,
  tag_id      INTEGER NOT NULL,
  UNIQUE(document_id, tag_id),
  FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id)      REFERENCES tags(id)      ON DELETE CASCADE
);

-- Folders: supports nesting via parent_id (NULL = root)
CREATE TABLE IF NOT EXISTS folders (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  name       TEXT NOT NULL,
  user_id    INTEGER NOT NULL,
  parent_id  INTEGER DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE SET NULL
);

-- Shares: document_id + shared_with_user_id must be unique (no duplicate shares)
CREATE TABLE IF NOT EXISTS shares (
  id                   INTEGER PRIMARY KEY AUTOINCREMENT,
  document_id          INTEGER NOT NULL,
  owner_id             INTEGER NOT NULL,
  shared_with_user_id  INTEGER NOT NULL,
  permission           TEXT DEFAULT 'read',
  created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(document_id, shared_with_user_id),
  FOREIGN KEY (document_id)         REFERENCES documents(id) ON DELETE CASCADE,
  FOREIGN KEY (owner_id)            REFERENCES users(id)     ON DELETE CASCADE,
  FOREIGN KEY (shared_with_user_id) REFERENCES users(id)     ON DELETE CASCADE
);

-- Password resets: single-use tokens with 1h expiry
CREATE TABLE IF NOT EXISTS password_resets (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER NOT NULL,
  email_hash TEXT NOT NULL,           -- SHA-256, used to look up user
  token      TEXT UNIQUE NOT NULL,    -- bin2hex(random_bytes(32))
  expires_at DATETIME NOT NULL,       -- NOW + 1 hour
  used       INTEGER DEFAULT 0,       -- 1 after redemption
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Email Encryption

Emails are stored encrypted (AES-256-CBC) so the DB file doesn't leak addresses. Lookups use a SHA-256 hash instead of the plaintext.

```php
// config/database.php
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'change_me_to_a_random_secret');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

// Encrypt before INSERT
$encryptedEmail = encryptValue($email);  // → base64(IV + ciphertext)
$emailHash      = hash('sha256', strtolower(trim($email)));

// Lookup without decrypting
SELECT * FROM users WHERE email_hash = :hash

// Decrypt when displaying
$plainEmail = decryptValue($row['email']);
```

`decryptValue()` uses `base64_decode($value, true)` (strict mode) — if the stored value is not valid base64 (e.g. plain-text email from before openssl was enabled), it returns the raw value instead of crashing.

---

## API Endpoints — `api/handle.php`

All calls go to a single file. `?action=X` routes to the right logic. All require an active session (401 if not).

| Action | Method | What it does |
|--------|--------|-------------|
| `upload` | POST | Validate + store file, save metadata to DB |
| `get_documents` | GET | Return all documents for current user (with filters) |
| `search` | GET | Keyword search + AI reranking via Claude |
| `delete` | POST | Delete file and DB record (ownership check) |
| `get_tags` | GET | Return all tags |
| `get_categories` | GET | Return all categories |
| `edit_document` | POST | Update title, category, tags, description |
| `create_folder` | POST | Insert new folder for current user |
| `get_folders` | GET | Return all folders for current user |
| `get_folder_documents` | GET | Return documents inside a specific folder |
| `share_document` | POST | Share a document with another user by username |
| `get_shared_documents` | GET | Return documents shared with current user |

---

## DocumentHandler Methods — `auth/document_handler.php`

| Method | Description |
|--------|-------------|
| `uploadFile($title, $category_id, $tags, $file, $folder_id)` | Validate, move file, INSERT document + tags |
| `getDocuments($filters)` | SELECT with optional category/tag/search filters |
| `searchDocuments($query)` | SQL LIKE search → passes results to `aiRerank()` |
| `deleteDocument($id)` | Ownership check → DELETE file + DB row |
| `editDocument($id, $title, $categoryId, $desc, $tags)` | UPDATE + delete old tags + re-add tags |
| `createFolder($name, $parentId)` | INSERT into folders |
| `getFolders()` | SELECT WHERE user_id = current |
| `getFolderDocuments($folderId)` | SELECT WHERE folder_id = ? AND (owned OR shared) |
| `shareDocument($docId, $targetUsername)` | Verify ownership, resolve username → id, INSERT share |
| `getSharedDocuments()` | SELECT via shares WHERE shared_with_user_id = current |
| `aiRerank($query, $docs)` | cURL to Claude API, returns docs reordered by relevance |

---

## Auth Flow

```
Register:
  POST /register.php
    → validate fields
    → password_hash()
    → encryptValue(email) + hash('sha256', email)
    → INSERT users
    → redirect login.php

Login:
  POST /login.php
    → hash('sha256', email) → SELECT user
    → password_verify()
    → bin2hex(random_bytes(32)) → INSERT sessions
    → $_SESSION['token'] = token
    → redirect dashboard.php

Every request:
  Auth::isAuthenticated()
    → SELECT sessions WHERE token = $_SESSION['token'] AND expires_at > NOW
    → if not found → redirect login.php

Logout:
  DELETE sessions WHERE token = current
  → session_destroy()
  → redirect login.php
```

---

## Password Reset Flow

```
forgot_password.php (GET)  →  show email form

forgot_password.php (POST)
  → hash('sha256', email) → SELECT user by email_hash
  → bin2hex(random_bytes(32)) token
  → INSERT password_resets (expires_at = NOW + 1h)
  → sendEmail() → PHPMailer → smtp.gmail.com:587 → inbox
  → show "Check your email" confirmation

reset_password.php (GET ?token=...)
  → SELECT password_resets WHERE token = ? AND used = 0 AND expires_at > NOW
  → if invalid → show error
  → if valid  → show new password form

reset_password.php (POST)
  → validate: passwords match, min 6 chars
  → password_hash() → UPDATE users SET password
  → UPDATE password_resets SET used = 1
  → redirect login.php?reset=1
```

---

## AI Search Flow

```
GET api/handle.php?action=search&q=financial

  → searchDocuments("financial")
      → SQL: SELECT ... WHERE title LIKE '%financial%' OR tags LIKE ...
      → raw result: [{id:3, title:"Q4 Report"}, {id:1, title:"Invoice"}, ...]

  → aiRerank("financial", $docs)           ← only if CLAUDE_API_KEY is set
      → build prompt:
          "Search query: "financial"
           Documents:
           3: Q4 Report [Reports] finance quarterly
           1: Invoice Jan [Contracts] billing
           ...
           Return IDs in order of relevance, comma-separated."
      → cURL POST https://api.anthropic.com/v1/messages
          headers: x-api-key, anthropic-version: 2023-06-01
          body: {model: "claude-haiku-4-5-20251001", messages: [...]}
      → Claude responds: "3,1,2"
      → docs reordered by that ID sequence

  → JSON response to browser
```

If `CLAUDE_API_KEY` is empty or the cURL call fails → original SQL order returned unchanged. Search keeps working.

---

## Security Notes

| Concern | Implementation |
|---------|---------------|
| Passwords | `password_hash()` bcrypt, never stored plain |
| Sessions | DB token (`bin2hex(random_bytes(32))`), 24h expiry |
| Email at rest | AES-256-CBC encrypted, only hash used for lookups |
| Download | `download.php` checks ownership/share before serving any file |
| Uploads | Extension whitelist, size limit (10 MB), `uniqid()` filename on disk |
| CSRF | `isSameOriginRequest()` checks referer host on all POST actions |
| Directory access | `.htaccess` denies browser access to `auth/`, `config/`, `data/`, `uploads/` |
| API keys | `config/mail.php` gitignored; Claude key via `getenv()` (never on disk) |
| Reset tokens | Single-use, 1h expiry, `bin2hex(random_bytes(32))` |

---

## Known Bugs Fixed

| Bug | Root cause | Fix |
|-----|------------|-----|
| Login "Access Denied" | `isSameOriginRequest()` compared `localhost` vs `localhost:8000` (port mismatch) | Wrap `HTTP_HOST` in `parse_url()` so port is stripped on both sides |
| Upload submits as GET | jQuery CDN unreachable locally → `e.preventDefault()` never ran | Download jQuery to `js/jquery.min.js`; add `method="POST"` to form |
| Files over 2 MB silently rejected | `upload_max_filesize = 2M` default in `php.ini` | Raise to `10M`, `post_max_size` to `12M` |
| PHPMailer TLS fails | `openssl`, `curl`, `sockets` commented out in `php.ini` | Uncomment all three; absolute `extension_dir` on Windows |
| Gmail SMTP auth fails | App password copied with spaces (`abcd efgh…`) | Remove all spaces from `MAIL_PASSWORD` in `config/mail.php` |
| `openssl_decrypt IV 12 bytes` warning | Plain-text emails stored before openssl was enabled — `base64_decode` of a plain email is shorter than 16-byte IV | Use `base64_decode($v, true)` strict; return value as-is if false or too short |

---

## Presentation Walkthrough

Demo files for upload are in `demo-files/` (gitignored):
- `invoice_january.txt` — invoice with billing keywords
- `contract_services_2025.txt` — service contract with financial terms
- `q1_financial_report.txt` — quarterly report with revenue/expense breakdown

Run the seed first if showing the folder demo: `http://localhost:8000/seed_demo.php`

---

### Step 1 — Authentication System

**Show:** `http://localhost:8000/register.php`

1. Fill in username, email, password → click **Register**
2. Gets redirected to login — point out that the system does not log you in automatically (intentional: separation of registration and authentication)
3. Log in with the new credentials → lands on dashboard
4. **Talk about:** session tokens — every request after login is validated against the `sessions` table in the DB, not just a cookie. Show the logout button and explain the token is deleted server-side, not just cleared client-side.

---

### Step 2 — Password Reset via Email

**Show:** Log out first → `http://localhost:8000/login.php` → click **"Forgot your password?"**

1. Enter the registered email address → click **Send Reset Link**
2. Open the inbox — show the email arriving from the system
3. Click the reset link → lands on the new password form
4. Enter a new password → redirected to login with a success message
5. Log in with the new password
6. **Talk about:** the token is a `bin2hex(random_bytes(32))` 64-character string stored in `password_resets` with a 1-hour expiry and a `used` flag — once clicked it cannot be used again. The email address in the DB is AES-256-CBC encrypted; lookup uses a SHA-256 hash instead of the plaintext.

---

### Step 3 — Uploading Files on the Dashboard

**Show:** Dashboard → upload sidebar on the left

Upload the three demo files one by one from `demo-files/`:

| File | Title to enter | Category | Tags |
|------|---------------|----------|------|
| `invoice_january.txt` | January Invoice | Contracts | invoice, billing, 2025 |
| `contract_services_2025.txt` | Service Contract 2025 | Contracts | contract, services, legal |
| `q1_financial_report.txt` | Q1 Financial Report | Reports | finance, quarterly, revenue |

After each upload, the table updates via AJAX without a page reload — point this out.

6. **Talk about:** files are stored with a `uniqid()` generated filename on disk so the original name cannot be guessed. Download goes through `download.php` which checks ownership before serving — the raw file path is never exposed in the browser.

---

### Step 4 — AI-Enhanced Search

**Show:** Search box at the top of the dashboard

1. Type `financial` → click Search
2. Results appear — all three documents match to some degree
3. **Point out:** the order is not purely alphabetical or by upload date — Claude has reranked them by semantic relevance. The Q1 Financial Report should rank first because its content is most directly about finance, even though the word appears in all three.
4. Try a second search: `legal agreement` — the contract should surface at the top despite "legal agreement" not appearing verbatim in any title.
5. **Talk about:** the search query and document list are sent to `api.anthropic.com/v1/messages` via cURL. Claude returns a comma-separated list of IDs in relevance order. If the API key is not set or the call fails, the original SQL order is returned unchanged — the feature degrades gracefully.

---

### Step 5 — Recursive Folder Structure

**Show:** Folder sidebar (run `http://localhost:8000/seed_demo.php` first if not already done)

1. The sidebar shows the indented tree — **Demo Project** at root, **Demo 1 / Demo 2 / Demo 3** as children, **Demo 1.1 / Demo 2.1** as grandchildren
2. Click **Demo 1.1** (leaf folder) → shows 2 documents (Demo 1.1.1, Demo 1.1.2)
3. Click **Demo 1** (parent) → shows 4 documents — the 2 from Demo 1.1 appear too
4. Click **Demo Project** (root) → shows all 8 documents across all three levels
5. **Talk about:** the original implementation used `WHERE folder_id = :id` — exact match only. A file in a subfolder was invisible from the parent. The fix uses a SQLite recursive CTE (`WITH RECURSIVE`) that walks the entire folder subtree before fetching documents. This was a deliberate post-spec addition — the original professor spec said no recursive search, but three levels of nesting made the limitation obvious. See `feature-recursive-folder-search.md`.
