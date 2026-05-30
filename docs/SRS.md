# Software Requirements Specification (SRS)
## Document Management System (DMS)

**Version:** 1.0  
**Date:** 2026-04-12  
**Team:** Elguga (Lead), Arsela Sokolaj, Dejsi Omari, Adiona, Xhensila  
**Branch:** dev  

---

## 1. Introduction

### 1.1 Purpose
This document specifies the functional and non-functional requirements for the Document Management System (DMS) — a web application that allows authenticated users to upload, organise, share, and search documents.

### 1.2 Scope
The DMS is a PHP + SQLite web application served via PHP's built-in server. It covers:
- User account management (registration, login, password reset)
- Document lifecycle (upload, list, edit metadata, download, delete)
- Folder organisation with recursive nesting
- Document sharing between users
- AI-enhanced search via the Anthropic Claude API
- A responsive dark/light-mode UI

### 1.3 Definitions
| Term | Meaning |
|------|---------|
| Document | An uploaded file with associated metadata (title, category, tags, folder) |
| Folder | A named container that may nest inside another folder |
| Share | A permission record granting one user access to another user's document |
| Session token | A `bin2hex(random_bytes(32))` string stored in DB and validated on every request |

---

## 2. Overall Description

### 2.1 System Context
```
Browser ──AJAX──► api/handle.php ──► Auth / DocumentHandler ──► SQLite DB
                                                              ──► uploads/ (disk)
                                  ──► PHPMailer ──► Gmail SMTP
                                  ──► cURL ──► Anthropic Claude API
```

### 2.2 User Classes
| Class | Description |
|-------|-------------|
| Registered User | Can upload, manage, share documents; access folders |
| Unauthenticated | Can only access login, register, and password-reset pages |

### 2.3 Assumptions
- PHP 7.4+ installed with `pdo_sqlite`, `openssl`, `curl`, `sockets` extensions enabled
- Gmail App Password available for SMTP
- `CLAUDE_API_KEY` environment variable set for AI search (optional — degrades gracefully)

---

## 3. Functional Requirements

### FR-01: User Registration
- The system shall allow a new user to register with username, email, and password.
- Password must be at least 6 characters.
- Email must be a valid format.
- Username must be at least 3 characters and unique.
- Password stored as bcrypt hash; email stored AES-256-CBC encrypted with SHA-256 hash for lookups.

### FR-02: User Login
- The system shall authenticate users with username and password.
- On successful login, a `bin2hex(random_bytes(32))` session token is inserted into the `sessions` table with a 24-hour expiry.
- The token is stored in `$_SESSION['session_token']`.

### FR-03: User Logout
- The system shall delete the server-side session token on logout.
- The client-side PHP session is destroyed.

### FR-04: Password Reset via Email
- An unauthenticated user can request a password reset by email.
- A single-use, 1-hour-expiry token is generated and emailed via PHPMailer/Gmail SMTP.
- The reset link validates the token before allowing a new password to be set.
- After use, the token is marked `used = 1`.

### FR-05: Document Upload
- Authenticated users can upload files with: title, category, tags (comma-separated), and optional folder assignment.
- Allowed extensions: pdf, doc, docx, xls, xlsx, ppt, pptx, txt, jpg, jpeg, png, gif, zip.
- Maximum file size: 10 MB.
- Stored filename on disk is `uniqid()` to prevent enumeration.

### FR-06: Document Listing and Filtering
- The dashboard lists all documents owned by the current user.
- Filters: category, tag, search query.
- Table re-renders via AJAX without page reload.

### FR-07: Secure File Download
- Download requests go through `download.php`.
- The file is served only if the requesting user owns the document **or** has a share record.
- The real file path is never exposed to the browser.

### FR-08: Document Edit
- Users can edit title, category, tags, and description via an inline modal.
- Tags are replaced (old tags deleted, new tags inserted) on each edit.

### FR-09: Document Delete
- Users can delete their own documents.
- Deleting removes the file from disk and the DB row (cascades to tags).

### FR-10: Folder System
- Users can create folders with an optional parent folder.
- Folders support unlimited nesting via `parent_id` adjacency list.
- The sidebar renders the folder tree recursively with depth-based indentation.
- Parent folders show a ▶/▼ toggle arrow; clicking it expands/collapses child folders without triggering a document load.
- Clicking the folder name loads documents (see FR-11).

### FR-11: Recursive Folder Document Retrieval
- Clicking a folder loads all documents in that folder **and all descendant folders** at any depth.
- Implemented via SQLite `WITH RECURSIVE` CTE to collect all descendant folder IDs before fetching documents.

### FR-12: Document Sharing
- A user can share any of their documents with another registered user by username.
- A `(document_id, shared_with_user_id)` UNIQUE constraint prevents duplicate shares.
- The recipient sees shared documents in the **Shared with Me** tab.
- Download permission is granted for shared documents.

### FR-13: AI-Enhanced Search
- The search action performs a SQL `LIKE` query across title, description, and tags.
- If `CLAUDE_API_KEY` is set, results are passed to the Anthropic Claude API for semantic reranking.
- If the API key is absent or the call fails, the original SQL result order is returned unchanged.

### FR-14: Dark / Light Mode
- Users can toggle between dark and light themes.
- Preference is persisted in `localStorage` and applied on page load.

---

## 4. Non-Functional Requirements

### NFR-01: Security
- Passwords: bcrypt via `password_hash()`.
- Email at rest: AES-256-CBC; only SHA-256 hash used for lookups.
- Session tokens: 64-char random hex, DB-backed, 24-hour expiry.
- Downloads: ownership/share check before serving any file.
- Upload: extension whitelist + 10 MB size cap + `uniqid()` filename.
- CSRF protection: `isSameOriginRequest()` checks referer on all POST actions.
- Directory protection: `.htaccess` blocks direct browser access to `auth/`, `config/`, `data/`, `uploads/`.
- API keys: `config/mail.php` gitignored; Claude key via `getenv()`.
- Reset tokens: single-use, 1-hour expiry.

### NFR-02: Performance
- All dashboard interactions (upload, search, filter, folder load, share) use AJAX — no full page reload.
- SQLite `PRAGMA busy_timeout = 3000` prevents read failures under concurrent multi-device access.

### NFR-03: Usability
- Responsive layout supporting desktop and tablet viewports.
- Dark and light mode with smooth toggle.
- Loading states shown during AJAX operations.
- File type icons displayed per document extension.

### NFR-04: Maintainability
- Single AJAX entry point (`api/handle.php`) routes all actions via `?action=X`.
- Auth logic encapsulated in `Auth` class; document logic in `DocumentHandler` class.
- Database schema auto-initialised on first run via `initializeDatabase()`.

### NFR-05: Portability
- Runs on any OS with PHP 7.4+ and SQLite3.
- LAN multi-device access via `php -S 0.0.0.0:8000` with a Windows Firewall rule for port 8000.

---

## 5. Constraints
- SQLite — not suitable for high-concurrency production; adequate for course demo.
- PHP built-in server — single-threaded; adequate for demo/testing.
- Gmail SMTP requires an App Password (2FA must be enabled on the Gmail account).
