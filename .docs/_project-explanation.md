# Document Managment System for Web Dev Class

Stack:
* PHP
* 


Features:
- [x] Authentication: 
    - [x] Login / Logout
    - [x] Session
- [x] Secure file upload `auth/document_handler.php`, `api/handle.php`
    - [x] Categories & Tags
    - [x] List, Filter, Delete Files
- [x] Dashboard + AJAX `dashboard.php`, `js/dashboard.js`
- [ ] Secure file download `download.php`
- [ ] Edit doc metadate inline
- [ ] Folder system with Icons
- [ ] File Sharing
- [ ] Claude API integration for Searching
- [ ] Password reset via email
- [ ]


## Explanation of basic notions used

### API
An **API** (Application Programming Interface) is a contract between two programs that defines how they can talk to each other. One side exposes a set of URLs (endpoints) that accept requests in a defined format and return structured responses (usually JSON). The other side calls those URLs to get data or trigger actions without needing to know anything about the internal code behind them.

This project connects to two external APIs:

| External Service | Purpose | Protocol |
|-----------------|---------|----------|
| **Anthropic Claude API** | Reranks search results by relevance using AI | HTTPS + JSON |
| **Gmail SMTP** (via PHPMailer) | Sends password reset emails | SMTP + TLS |

#### Files that connect to external services

**`auth/document_handler.php` → Claude API**
The method `aiRerank($query, $documents)` is called after every search. It sends the search term and the list of matching documents to the Claude API via `curl`, and asks it to return the document IDs sorted by relevance. The results are then reordered before being returned to the dashboard.

```php
// Simplified flow inside aiRerank()
$payload = json_encode([
    'model'    => 'claude-haiku-4-5-20251001',
    'messages' => [['role' => 'user', 'content' => "Rank these by relevance to: $query ..."]]
]);
$ch = curl_init('https://api.anthropic.com/v1/messages');
// → sends request, parses comma-separated IDs from response, reorders $documents
```

**`auth/email.php` → Gmail SMTP**
Wraps PHPMailer into a single `sendEmail($to, $subject, $body)` function. PHPMailer opens a TLS connection to `smtp.gmail.com:587`, authenticates with the stored credentials, and delivers the email.

```php
$mail->isSMTP();
$mail->Host       = MAIL_HOST;       // smtp.gmail.com
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = MAIL_PORT;       // 587
$mail->Username   = MAIL_USERNAME;   // Gmail address
$mail->Password   = MAIL_PASSWORD;   // App password
```

---

### How API keys are stored securely

API keys and passwords are credentials — if they leak, anyone can use them (and run up your bill). Two rules are followed:

**1. Keys live in config files, not in the main code**

| File | What it holds | Tracked by git? |
|------|--------------|----------------|
| `config/mail.php` | Gmail address + app password | ❌ No |
| Environment variable `CLAUDE_API_KEY` | Anthropic API key | ❌ No |

`config/database.php` reads the Claude key from the environment so it never touches the filesystem:
```php
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');
```

**2. Sensitive files are listed in `.gitignore`**

`.gitignore` tells Git to pretend these files don't exist:
```
config/mail.php
data/documents.db
data/mail.log
uploads/
```

This means even if you run `git add .` by accident, Git will skip them and they will never appear in a commit or on GitHub. If a key were pushed to a public repo, it would need to be revoked immediately and regenerated — `.gitignore` prevents that entirely.

---

### SESSION

A **session** is a way for the server to remember who you are between page loads. When you log in, the server generates a random token (`bin2hex(random_bytes(32))`), stores it in the `sessions` database table, and puts it in your browser's session cookie. On every subsequent request, PHP reads the cookie, looks the token up in the database, and confirms it hasn't expired. This project uses a 24-hour timeout defined in `config/database.php`.

---

### HANDLER

A **handler** in this project refers to a PHP class or file that is responsible for one domain of logic. For example, `auth/document_handler.php` contains the `DocumentHandler` class which handles everything to do with documents (upload, delete, edit, share, search). `auth/auth.php` contains the `Auth` class which handles everything to do with users (register, login, logout, session validation). This separation keeps the code organised and each file focused on a single responsibility.

---

### cURL

**cURL** is a library (available in PHP via the `curl_*` functions) that lets your server make outgoing HTTP requests to other servers. In this project it is used inside `aiRerank()` to call the Claude API. The flow is: open a connection (`curl_init`), configure it (URL, headers, POST body), execute it (`curl_exec`), and parse the response.

---

### GET / POST

**GET** requests ask the server for data — parameters are passed in the URL (e.g. `api/handle.php?action=get_documents&category_id=2`). They are used for reading data and should never change server state.

**POST** requests send data to the server inside the request body, not the URL — used for actions that change state (upload a file, delete a document, log in). File uploads always use POST with `enctype="multipart/form-data"` so binary data can be included in the body.

---

## Demonstrations

### Demo 1 — Password Reset via Email

**Steps:**
1. Log out and go to `login.php`
2. Click **"Forgot your password?"**
3. Enter the email address used when registering
4. Click **Send Reset Link**
5. Open your Gmail inbox — you will receive an email from the system with a reset link
6. Click the link → enter a new password → confirm it
7. You are redirected to `login.php` with a success message
8. Log in with the new password

**What happens behind the scenes:**
```
forgot_password.php (POST)
  → hash email with SHA-256
  → look up user in database by email_hash
  → generate 64-char hex token (bin2hex(random_bytes(32)))
  → insert into password_resets with expires_at = NOW + 1 hour
  → call sendEmail() → PHPMailer → smtp.gmail.com:587 → inbox

reset_password.php (GET ?token=...)
  → validate token exists, used=0, expires_at > NOW
  → show new password form

reset_password.php (POST)
  → password_hash() → UPDATE users SET password = ?
  → UPDATE password_resets SET used = 1
  → redirect to login.php?reset=1
```

---

### Demo 2 — AI-Enhanced Search (Claude API)

**Steps:**
1. Upload a few documents with different titles and tags (e.g. "Q4 Sales Report", "Server Contract 2025", "Bug Ticket #42")
2. In the search box, type a vague or conceptual query (e.g. `financial` or `infrastructure issue`)
3. The results are returned sorted by relevance — not just keyword match

**What happens behind the scenes:**
```
dashboard.php search form (GET ?search=financial)
  → searchDocuments() runs SQL LIKE query → returns raw matches
  → aiRerank("financial", $documents) is called
      → builds prompt listing all document IDs, titles, categories, tags
      → curl POST → api.anthropic.com/v1/messages
      → Claude returns: "3,1,2" (IDs in relevance order)
      → $documents reordered accordingly
  → reranked list returned to dashboard
```

**If no API key is set**, `aiRerank()` returns the original SQL results unchanged — the feature degrades gracefully and search still works normally.


## Explanation of Project

File structure

```
```


### Database


```
schema



```


Update for features requested by prof:


in `config/database.php`
```
ALTER TABLE documents ADD COLUMN folder_id INTEGER DEFAULT NULL

-- Multiple Folder
CREATE TABLE IF NOT EXISTS folders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  user_id INTEGER NOT NULL,
  parent_id INTEGER DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (parent_id) REFERENCES folders(id)
);

-- File sharing
CREATE TABLE IF NOT EXISTS shares (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  document_id INTEGER NOT NULL,
  owner_id INTEGER NOT NULL,
  shared_with_user_id INTEGER NOT NULL,
  permission TEXT DEFAULT 'read',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (document_id) REFERENCES documents(id),
  FOREIGN KEY (owner_id) REFERENCES users(id),
  FOREIGN KEY (shared_with_user_id) REFERENCES users(id)
);

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  email TEXT NOT NULL,
  token TEXT UNIQUE NOT NULL,
  expires_at DATETIME NOT NULL,
  used INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

```

`download.php`
* Starts session, checks `Auth::isAuthenticated()`, redirects if false
* Get `?id=` parameter and query database for both files and shares tables
* `readfile()` with `Content-Disposition` headers

`forgot_password.php`
* Form with email address
* POST: 
    * query for email, 
    * generate `bin2hex(random_bytes(32))` token, and set it to expire `expires_at = +1 hour` 
    * insert into `password_resets`
    * call `mail($email, "Password Resret", "Reset link: http://localhost:8000/reset_password.php?token=$token")`
    * "Check email" pop-up

`reset_password.php`
* GET: 
    * Validates token
* POST:
    * Shows password change forms
    * Validates: 
        * Matching
        * +6 chars
        * call `password_hash()`
        * update `USERS` table
        * mark token as used `used=1`
        * redirect `login.php`


`api/handle.php`
* Add queries:
    * `edit_document`
    * `create_folder`
    * `get_folder`
    * `get_folder_documents`
    * `share_documents`
    * `get_shared_documents`
    * `search` using claude API

`auth/document_handler.php`
* Add methods:
    * `editDocuments($id, $title, $categoryId, $description, $tags)`
    * `createFolder($name, $parentId)`
    * `getFolders()`
    * `getFolderDocuments($folderId)` owned & shared
    * `shareDocuments($documentId, $targetUsername)`
    * `getSharedDocuments()`
    * `aiRerank($query, $documents)`

`dashboard.php`
* Add UI elemnts:
    * Folders panel in sidebar: "New Folder" button + folder tree list; clicking folder triggers AJAX get_folder_documents
    * Edit modal: hidden <div id="editModal"> with form fields; "Edit" button on each row populates and shows it
    * Share button on each document row: opens a small modal to enter a username
    * File icons: JS map of extension → icon (PDF=📄, DOCX=📝, IMG=🖼, etc.), default=📎; rendered in document table
    * Shared documents tab: toggle between "My Documents" and "Shared with Me"
    * "Forgot password?" link on login.php pointing to forgot_password.php

`js/dashboard.js`

* openEditModal(id, title, categoryId, description) — populate + show modal
* submitEdit() — AJAX POST to api/handle.php?action=edit_document
* openShareModal(id) — show share dialog
* submitShare() — AJAX POST to api/handle.php?action=share_document
* loadFolder(folderId) — AJAX GET get_folder_documents, re-render table
* File icon helper: getFileIcon(extension)