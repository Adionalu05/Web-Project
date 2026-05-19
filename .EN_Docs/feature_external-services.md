# External Services Integration

---

## What is an API?

An **API** (Application Programming Interface) is a contract between two programs that defines how they can talk to each other. One side exposes a set of URLs (endpoints) that accept requests in a defined format and return structured responses (usually JSON). The other side calls those URLs to get data or trigger actions without needing to know anything about the internal code behind them.

This project uses APIs in two directions:
- **Inward** — our own `api/handle.php` exposes endpoints that the browser calls via AJAX
- **Outward** — our PHP code calls two external services (Claude and Gmail)

---

## Our Internal API — `api/handle.php`

Every AJAX request from the dashboard goes to this single file. The `action` parameter routes it to the right function:

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

All endpoints require an active session — unauthenticated requests get a `401` response.

---

## External API 1 — Anthropic Claude (AI Search)

**Purpose:** After a search query returns results from SQLite, Claude reranks them by semantic relevance so the most relevant document appears first — not just the one with the closest keyword match.

**File:** `auth/document_handler.php` → method `aiRerank($query, $documents)`

**Endpoint:** `https://api.anthropic.com/v1/messages`

**How it works:**
```php
// Simplified flow inside aiRerank()
$payload = json_encode([
    'model'    => 'claude-haiku-4-5-20251001',
    'messages' => [[
        'role'    => 'user',
        'content' => "Search query: \"$query\"\nDocuments:\n$docList\nReturn IDs in order of relevance, comma-separated."
    ]]
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-api-key: ' . CLAUDE_API_KEY,
    'anthropic-version: 2023-06-01',
    'Content-Type: application/json'
]);
// → Claude returns "3,1,2" → documents reordered accordingly
```

**Graceful fallback:** If `CLAUDE_API_KEY` is empty or the request fails, `aiRerank()` returns the original results unchanged. Search continues working normally.

---

## External API 2 — Gmail SMTP (Password Reset Email)

**Purpose:** Deliver the password reset link to the user's inbox securely.

**File:** `auth/email.php` → function `sendEmail($to, $subject, $body)`  
**Library:** PHPMailer (`lib/PHPMailer/`)  
**Credentials:** `config/mail.php`

**How it works:**
```php
$mail->isSMTP();
$mail->Host       = MAIL_HOST;       // smtp.gmail.com
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = MAIL_PORT;       // 587
$mail->Username   = MAIL_USERNAME;   // Gmail address
$mail->Password   = MAIL_PASSWORD;   // App password (not your real password)
$mail->send();
```

PHPMailer opens a TLS connection to `smtp.gmail.com:587`, authenticates, and delivers the email. Returns `true` on success, `false` on failure (failure is logged to `data/mail.log`).

---

## How Keys Are Stored Securely

API keys and passwords are credentials — if they leak, anyone can impersonate the app, send emails from your account, or run up your API bill.

**Rule 1 — Keys live in config files, never in application code**

| Credential | Where it lives | Committed to git? |
|------------|---------------|------------------|
| Gmail address + app password | `config/mail.php` | ❌ No |
| Anthropic API key | Environment variable `CLAUDE_API_KEY` | ❌ No |

The Claude key is read from the environment at runtime so it never touches the filesystem at all:
```php
// config/database.php
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');
```

**Rule 2 — Sensitive files are listed in `.gitignore`**

`.gitignore` tells Git to ignore these files entirely, even on `git add .`:
```
config/mail.php
data/documents.db
data/mail.log
uploads/
```

If a key were accidentally pushed to a public GitHub repo it would need to be revoked and regenerated immediately. `.gitignore` makes that impossible by design.

---

## Demonstrations

### Demo 1 — Password Reset via Email

1. Log out → go to `login.php` → click **"Forgot your password?"**
2. Enter the email address used at registration → click **Send Reset Link**
3. Check inbox — email arrives from the system with a reset link
4. Click the link → set a new password → redirected to login with success message
5. Log in with the new password ✅

**Behind the scenes:**
```
forgot_password.php (POST)
  → SHA-256 hash of email → lookup user by email_hash
  → bin2hex(random_bytes(32)) → 64-char token
  → INSERT into password_resets (expires in 1 hour)
  → sendEmail() → PHPMailer → smtp.gmail.com:587 → inbox

reset_password.php (GET ?token=...)
  → validate: token exists, used=0, expires_at > NOW

reset_password.php (POST)
  → password_hash() → UPDATE users SET password
  → UPDATE password_resets SET used = 1
  → redirect login.php?reset=1
```

---

### Demo 2 — AI-Enhanced Search

1. Upload several documents with varied titles (e.g. "Q4 Sales Report", "Server Contract 2025", "Bug Ticket #42")
2. Search for a vague/conceptual term like `financial` or `infrastructure problem`
3. Results appear sorted by relevance — not just keyword proximity ✅

**Behind the scenes:**
```
Search form (GET ?search=financial)
  → searchDocuments() → SQL LIKE query → raw matches returned
  → aiRerank("financial", $documents)
      → prompt sent to api.anthropic.com/v1/messages
      → Claude returns: "3,1,2" (IDs ranked by relevance)
      → documents reordered
  → reranked list sent to dashboard
```

If no API key is configured, `aiRerank()` skips the Claude call and returns the SQL results as-is.

---

## Debug Log — External Services

### ✅ PHP extensions missing (openssl, curl, sockets)
**Date:** 2026-04-14
**Symptom:** PHPMailer could not establish a TLS connection to Gmail. Claude API cURL calls also silently failing. `data/mail.log` was empty — email was never even attempted.
**Root cause:** `openssl`, `curl`, and `sockets` were all commented out in `C:\php\php.ini`. PHPMailer requires `openssl` for TLS and `sockets` as a transport. The Claude integration requires `curl` for outbound HTTP requests.
**Fix:** Uncommented all three lines in `php.ini` and restarted the server:
```
extension=curl
extension=openssl
extension=sockets
```

---

### ✅ Gmail app password entered with spaces
**Date:** 2026-04-14
**Symptom:** Authentication to `smtp.gmail.com:587` would fail even with a valid app password.
**Root cause:** Gmail displays the 16-character app password in groups of 4 (e.g. `abcd efgh ijkl mnop`). If copied with spaces into `config/mail.php`, SMTP authentication fails.
**Fix:** Remove all spaces from the value in `config/mail.php`:
```php
define('MAIL_PASSWORD', 'abcdefghijklmnop'); // no spaces
```

---

### ✅ Gmail SMTP — confirmed working
**Date:** 2026-04-14
**Status:** Password reset email delivered successfully end-to-end. PHPMailer → smtp.gmail.com:587 → inbox confirmed.

---

### 🔄 Claude AI Search — pending API key
**Date:** 2026-04-14
**Status:** Code is implemented and falls back gracefully. Awaiting Anthropic API key to test live reranking.
