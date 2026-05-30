# External Services
## Document Management System

The project uses APIs in two directions:
- **Inward** — browser calls `api/handle.php` via AJAX (our own internal API)
- **Outward** — PHP calls two external services: Anthropic Claude and Gmail SMTP

---

## Internal API — `api/handle.php`

Single entry point for all AJAX requests. Routes by `?action=X`. All endpoints require an active session (returns `401 JSON` if not authenticated).

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
| `get_folder_documents` | GET | Return documents inside a specific folder (recursive) |
| `share_document` | POST | Share a document with another user by username |
| `get_shared_documents` | GET | Return documents shared with current user |

---

## External API 1 — Anthropic Claude (AI Search)

**Purpose:** After a SQL keyword search, Claude reranks results by semantic relevance so the most relevant document appears first — not just the closest keyword match.

**File:** `auth/document_handler.php` → `aiRerank($query, $documents)`  
**Endpoint:** `https://api.anthropic.com/v1/messages`  
**Model:** `claude-haiku-4-5-20251001`

**Flow:**
```php
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
// Claude returns "3,1,2" → documents reordered accordingly
```

**Graceful fallback:** if `CLAUDE_API_KEY` is empty or the request fails, `aiRerank()` returns the original results unchanged. Search keeps working.

---

## External API 2 — Gmail SMTP (Password Reset)

**Purpose:** Deliver password reset links to users' inboxes securely.

**File:** `auth/email.php` → `sendEmail($to, $subject, $body)`  
**Library:** PHPMailer (`lib/PHPMailer/`)  
**Credentials:** `config/mail.php` (gitignored)

**Configuration:**
```php
$mail->isSMTP();
$mail->Host       = MAIL_HOST;       // smtp.gmail.com
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = MAIL_PORT;       // 587
$mail->Username   = MAIL_USERNAME;   // Gmail address
$mail->Password   = MAIL_PASSWORD;   // App password — no spaces
$mail->send();
```

PHPMailer opens a TLS connection to `smtp.gmail.com:587`, authenticates, and delivers the email. Returns `true` on success; failures are logged to `data/mail.log`.

---

## Credential Security

Keys and passwords live in config files, never in application code.

| Credential | Location | In git? |
|------------|----------|---------|
| Gmail address + app password | `config/mail.php` | No — gitignored |
| Anthropic API key | Environment variable `CLAUDE_API_KEY` | No — never on disk |

The Claude key is read from the environment at runtime:
```php
// config/database.php
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');
```

Setting the key before starting the server:
```powershell
# Windows PowerShell
$env:CLAUDE_API_KEY = "sk-ant-..."
php -S localhost:8000
```

---

## Required PHP Extensions

Both external services require extensions enabled in `php.ini`:

| Extension | Required by |
|-----------|------------|
| `curl` | Claude API (outbound HTTP) |
| `openssl` | PHPMailer TLS, email encryption |
| `sockets` | PHPMailer SMTP transport |

If any are missing, uncomment them in `php.ini` (remove the `;` prefix) and restart the server.
