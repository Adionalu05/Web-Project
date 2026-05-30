# Software Design Document (SDD)
## Document Management System (DMS)

**Version:** 1.0  
**Date:** 2026-04-20  
**Team:** Elguga (Lead), Arsela Sokolaj, Dejsi Omari, Adiona, Xhensila  

---

## 1. Architecture Overview

The system follows a layered MVC-like architecture with a single AJAX entry point.

```
┌─────────────────────────────────────────┐
│              Browser (Client)           │
│  Pages: dashboard.php, login.php, ...   │
│  JS: js/dashboard.js (jQuery AJAX)      │
│  CSS: css/style.css (dark/light mode)   │
└──────────────┬──────────────────────────┘
               │ HTTP GET / POST ?action=X
               ▼
┌─────────────────────────────────────────┐
│         api/handle.php (Router)         │
│  - Validates same-origin               │
│  - Checks session auth                 │
│  - Routes to handler functions         │
└──────────┬──────────────┬──────────────┘
           │              │
           ▼              ▼
┌──────────────┐  ┌──────────────────────┐
│  Auth class  │  │ DocumentHandler class │
│ auth/auth.php│  │ auth/document_handler │
│              │  │                      │
│ register()   │  │ uploadFile()         │
│ login()      │  │ getDocuments()       │
│ logout()     │  │ searchDocuments()    │
│ isAuth()     │  │ aiRerank()           │
│ resetPwd()   │  │ getFolderDocuments() │
└──────┬───────┘  │ shareDocument()      │
       │          └──────────┬───────────┘
       │                     │
       ▼                     ▼
┌─────────────────────────────────────────┐
│           config/database.php           │
│  PDO SQLite connection                 │
│  Schema initialisation                 │
│  AES-256-CBC encrypt/decrypt helpers   │
│  SHA-256 hash helper                   │
└──────────────────┬──────────────────────┘
                   │
        ┌──────────┴──────────┐
        ▼                     ▼
┌──────────────┐     ┌────────────────┐
│  data/       │     │  uploads/      │
│  documents.db│     │  (files disk)  │
└──────────────┘     └────────────────┘
```

### External Services
| Service | Usage | File |
|---------|-------|------|
| Gmail SMTP | Password reset email | `auth/email.php` via PHPMailer |
| Anthropic Claude API | AI search reranking | `auth/document_handler.php → aiRerank()` |

---

## 2. File Structure

```
Web-Project/
├── index.php               → redirect to login or dashboard
├── login.php               → login form
├── register.php            → registration form
├── logout.php              → session destruction
├── dashboard.php           → main app (table, sidebar, modals)
├── download.php            → auth-gated file delivery
├── forgot_password.php     → request reset link
├── reset_password.php      → set new password with token
├── seed_demo.php           → demo data seeder (gitignored, local only)
│
├── api/
│   └── handle.php          → single AJAX entry point (12 actions)
│
├── auth/
│   ├── auth.php            → Auth class + page protection guard
│   ├── document_handler.php → DocumentHandler class
│   └── email.php           → PHPMailer SMTP wrapper
│
├── config/
│   ├── database.php        → PDO, schema init, crypto helpers
│   └── mail.php            → SMTP credentials (gitignored)
│
├── lib/
│   └── PHPMailer/          → PHPMailer, SMTP, Exception classes
│
├── js/
│   ├── jquery.min.js       → local jQuery (CDN unreachable on LAN)
│   ├── dashboard.js        → all AJAX, modal, table, tree logic
│   └── theme.js            → dark/light mode toggle + localStorage
│
├── css/
│   └── style.css           → layout, modals, sidebar tree, icons
│
├── data/                   → gitignored
│   └── documents.db        → SQLite database
│
├── uploads/                → gitignored, actual uploaded files
├── docs/                   → project documentation
├── .EN_Docs/               → English technical documentation
├── .SQ_Docs/               → Albanian translations of all docs
└── .htaccess               → blocks direct access to sensitive dirs
```

---

## 3. Database Design

See [db-schema.md](db-schema.md) for full SQL schema.

### Entity Relationship Summary

```
users ──< sessions           (one user → many sessions)
users ──< documents          (one user → many documents)
users ──< folders            (one user → many folders)
documents ──< document_tags  (one document → many tags)
tags ──< document_tags       (one tag → many documents)
folders ──< folders          (self-referential: parent_id)
documents >── folders        (many documents → one folder)
documents ──< shares         (one document → shared with many users)
users ──< shares             (one user → receives many shares)
users ──< password_resets    (one user → many reset tokens)
```

---

## 4. API Design

All requests go to `api/handle.php`. Authentication required for all actions (returns HTTP 401 JSON on failure).

| Action | Method | Input | Output |
|--------|--------|-------|--------|
| `upload` | POST | title, category_id, tags, file, folder_id | `{success, message}` |
| `get_documents` | GET | category, tag, search (optional) | `{success, documents[]}` |
| `search` | GET | q | `{success, documents[]}` (AI-reranked) |
| `delete` | POST | document_id | `{success, message}` |
| `get_tags` | GET | — | `{success, tags[]}` |
| `get_categories` | GET | — | `{success, categories[]}` |
| `edit_document` | POST | id, title, category_id, description, tags | `{success, message}` |
| `create_folder` | POST | name, parent_id | `{success, folder}` |
| `get_folders` | GET | — | `{success, folders[]}` |
| `get_folder_documents` | GET | folder_id | `{success, documents[]}` |
| `share_document` | POST | document_id, username | `{success, message}` |
| `get_shared_documents` | GET | — | `{success, documents[]}` |

---

## 5. Key Design Decisions

### 5.1 Single AJAX Entry Point
All browser→server communication routes through `api/handle.php?action=X`. Benefits: single auth check location, consistent JSON responses, easy to extend.

### 5.2 DB-Backed Session Tokens
PHP `$_SESSION` alone is not invalidatable server-side. We pair it with a `sessions` table row: logout deletes the DB record, making the cookie useless even if stolen.

### 5.3 Recursive Folder CTE
`getFolderDocuments()` uses a SQLite `WITH RECURSIVE` CTE to traverse the folder adjacency list to any depth before fetching documents. This replaces the original `WHERE folder_id = :id` (exact match only).

```sql
WITH RECURSIVE subfolder_ids(id) AS (
  SELECT :folder_id
  UNION ALL
  SELECT f.id FROM folders f
  INNER JOIN subfolder_ids s ON f.parent_id = s.id
  WHERE f.user_id = :user_id
)
SELECT d.* FROM documents d
WHERE d.folder_id IN (SELECT id FROM subfolder_ids)
  AND (d.user_id = :uid OR d.id IN (SELECT document_id FROM shares WHERE shared_with_user_id = :uid2))
```

### 5.4 Email Encryption
Emails stored AES-256-CBC encrypted to protect the DB file if exposed. Lookups use SHA-256 hash to avoid decrypting on every query. `decryptValue()` uses strict base64 decoding — if the stored value is plain text (pre-encryption era), it returns it unchanged.

### 5.5 AI Search Graceful Fallback
`aiRerank()` is only called when `CLAUDE_API_KEY` is set. Any cURL failure or API error returns the original SQL result order. Search is never broken by the AI layer.

### 5.6 Collapsible Folder Tree (UI)
The sidebar tree uses split click targets: the ▶/▼ arrow calls `toggleFolder(id, e)` with `e.stopPropagation()` to expand/collapse, while the folder name calls `loadFolder(id)` to fetch documents. `folderData` is injected from PHP as an inline `<script>` variable so the pane tree re-renders without extra API calls.

---

## 6. Security Design

| Threat | Mitigation |
|--------|-----------|
| Brute-force login | bcrypt (slow hash) + no account enumeration on login error |
| Session hijacking | DB token — logout physically deletes it server-side |
| CSRF | `isSameOriginRequest()` checks Referer host on all POST actions |
| Path traversal / file exposure | `download.php` serves file via `readfile()` after auth check; real path never sent to client |
| Injection | PDO prepared statements throughout; extension whitelist on upload |
| Email leak | AES-256-CBC at rest; SHA-256 hash for lookups |
| Secret exposure | `config/mail.php` gitignored; Claude key via `getenv()` never written to disk |
| Directory listing | `.htaccess` denies access to `auth/`, `config/`, `data/`, `uploads/` |
