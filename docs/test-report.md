# Test Plan & Report
## Document Management System

**Version:** 1.0  
**Date:** 2026-05-11  
**Tester:** Xhensila, Adiona  
**Environment:** Windows 11, PHP 8.2, SQLite 3, Chrome 124, Firefox 125  

---

## 1. Test Scope

Manual end-to-end testing of all functional requirements defined in `SRS.md`. Each test case is run twice: once with a fresh database and once with existing data.

---

## 2. Test Cases

### Module 1: Authentication

| ID | Test Case | Steps | Expected | Result |
|----|-----------|-------|----------|--------|
| T-01 | Register with valid data | Fill username, email, password ≥ 6 chars → submit | Redirect to login page, no error | ✅ PASS |
| T-02 | Register with duplicate username | Reuse existing username | Error: "Username already taken" | ✅ PASS |
| T-03 | Register with short password | Password = "abc" (3 chars) | Error: "Password must be at least 6 characters" | ✅ PASS |
| T-04 | Register with invalid email | Email = "notanemail" | Error: "Invalid email address" | ✅ PASS |
| T-05 | Login with correct credentials | Enter valid username + password | Redirect to dashboard | ✅ PASS |
| T-06 | Login with wrong password | Correct username, wrong password | Error: "Invalid username or password" | ✅ PASS |
| T-07 | Access dashboard while logged out | Navigate to dashboard.php directly | Redirect to login.php | ✅ PASS |
| T-08 | Logout | Click Logout | Session destroyed, redirect to login | ✅ PASS |
| T-09 | Session expiry | Manually expire token in DB | Next request redirects to login | ✅ PASS |

### Module 2: Password Reset

| ID | Test Case | Steps | Expected | Result |
|----|-----------|-------|----------|--------|
| T-10 | Request reset with registered email | Submit registered email | Success message + email received | ✅ PASS |
| T-11 | Request reset with unknown email | Submit unregistered email | Generic success message (no leak) | ✅ PASS |
| T-12 | Use valid reset link | Click link within 1 hour | New password form shown | ✅ PASS |
| T-13 | Use expired reset link | Wait >1 hour or manually expire | Error: token invalid/expired | ✅ PASS |
| T-14 | Reuse reset link | Click same link twice | Error: token already used | ✅ PASS |
| T-15 | Set new password | Enter matching passwords ≥ 6 chars | Password updated, redirect to login | ✅ PASS |
| T-16 | Passwords do not match | Enter different passwords | Error: passwords do not match | ✅ PASS |

### Module 3: Document Upload

| ID | Test Case | Steps | Expected | Result |
|----|-----------|-------|----------|--------|
| T-17 | Upload valid PDF | Select .pdf, fill title → submit | Document appears in table (AJAX, no reload) | ✅ PASS |
| T-18 | Upload file > 10 MB | Select file > 10 MB | Error: file too large | ✅ PASS |
| T-19 | Upload disallowed extension | Select .exe file | Error: file type not allowed | ✅ PASS |
| T-20 | Upload with tags | Enter comma-separated tags | Tags appear in document row | ✅ PASS |
| T-21 | Upload to folder | Select existing folder | Document appears under folder | ✅ PASS |
| T-22 | Upload without title | Leave title blank | Error: title required | ✅ PASS |

### Module 4: Document Management

| ID | Test Case | Steps | Expected | Result |
|----|-----------|-------|----------|--------|
| T-23 | List all documents | Open dashboard | All uploaded documents shown | ✅ PASS |
| T-24 | Filter by category | Select category filter | Only matching documents shown | ✅ PASS |
| T-25 | Filter by tag | Enter tag in search | Only documents with that tag shown | ✅ PASS |
| T-26 | Download own document | Click Download | File downloads, correct content | ✅ PASS |
| T-27 | Download another user's document | Manually construct URL | 403 / redirect to login | ✅ PASS |
| T-28 | Edit document title | Open modal, change title → save | Title updated in table (AJAX) | ✅ PASS |
| T-29 | Edit tags | Open modal, change tags → save | Tags updated | ✅ PASS |
| T-30 | Delete document | Click Delete → confirm | Row removed, file deleted from disk | ✅ PASS |

### Module 5: Folder System

| ID | Test Case | Steps | Expected | Result |
|----|-----------|-------|----------|--------|
| T-31 | Create root folder | Enter folder name, no parent → create | Folder appears in sidebar | ✅ PASS |
| T-32 | Create nested folder | Select parent, enter name → create | Folder appears under parent | ✅ PASS |
| T-33 | Click leaf folder | Click folder with no children | Documents in that folder shown | ✅ PASS |
| T-34 | Click parent folder | Click folder with subfolders | Documents from all descendant folders shown | ✅ PASS |
| T-35 | Toggle expand arrow | Click ▶ on parent folder | Child folders revealed, arrow → ▼ | ✅ PASS |
| T-36 | Toggle collapse arrow | Click ▼ on expanded folder | Child folders hidden, arrow → ▶ | ✅ PASS |
| T-37 | 3-level deep recursion | Root → Child → Grandchild with docs | Clicking root returns all docs at all 3 levels | ✅ PASS |

### Module 6: File Sharing

| ID | Test Case | Steps | Expected | Result |
|----|-----------|-------|----------|--------|
| T-38 | Share with existing user | Click Share, enter valid username | Success message | ✅ PASS |
| T-39 | Share with non-existent user | Enter username that doesn't exist | Error: user not found | ✅ PASS |
| T-40 | Share with yourself | Enter own username | Error: cannot share with yourself | ✅ PASS |
| T-41 | Duplicate share | Share same doc with same user twice | Second attempt silently deduped (INSERT OR IGNORE) | ✅ PASS |
| T-42 | Recipient sees shared doc | Switch to recipient user, Shared tab | Document appears in Shared with Me | ✅ PASS |
| T-43 | Recipient downloads shared doc | Click Download on shared doc | File downloads successfully | ✅ PASS |

### Module 7: AI Search

| ID | Test Case | Steps | Expected | Result |
|----|-----------|-------|----------|--------|
| T-44 | Search with CLAUDE_API_KEY set | Enter query → search | Results returned in semantic relevance order | ✅ PASS |
| T-45 | Search without CLAUDE_API_KEY | Unset key, enter query | Results returned in SQL order (no crash) | ✅ PASS |
| T-46 | Search with no results | Enter nonsense query | Empty state message shown | ✅ PASS |

### Module 8: Multi-Device Access

| ID | Test Case | Steps | Expected | Result |
|----|-----------|-------|----------|--------|
| T-47 | Access from LAN device | Start `php -S 0.0.0.0:8000`, open IP on second device | Full app loads | ✅ PASS |
| T-48 | Register on second device | Complete registration from second device | User appears in DB, can login | ✅ PASS |
| T-49 | Share across devices | User A (host) shares doc → User B (LAN device) sees it | Document in Shared tab on B | ✅ PASS |

---

## 3. Bugs Found During Testing

| Bug ID | Description | Status | Reference |
|--------|-------------|--------|-----------|
| B-01 | Login "Access Denied" — port mismatch in `isSameOriginRequest()` | Fixed | `_debugging.md` entry 1 |
| B-02 | Upload form submitting as GET — jQuery CDN unreachable on LAN | Fixed | `_debugging.md` entry 2 |
| B-03 | PHP upload limit 2 MB silently rejecting files | Fixed | `_debugging.md` entry 3 |
| B-04 | PHPMailer TLS failing — openssl/curl/sockets disabled in php.ini | Fixed | `_debugging.md` entry 4 |
| B-05 | Parent folder click returns no documents | Fixed | `_debugging.md` entry 5 |
| B-06 | `openssl_decrypt` IV length warning on plain-text emails | Fixed | `_debugging.md` entry 6 |
| B-07 | File sharing returns 404 — relative login redirect + SQLite busy timeout | Fixed | `_debugging.md` entry 7 |

---

## 4. Test Summary

| Module | Total | Pass | Fail | Pass Rate |
|--------|-------|------|------|-----------|
| Authentication | 9 | 9 | 0 | 100% |
| Password Reset | 7 | 7 | 0 | 100% |
| Document Upload | 6 | 6 | 0 | 100% |
| Document Management | 8 | 8 | 0 | 100% |
| Folder System | 7 | 7 | 0 | 100% |
| File Sharing | 6 | 6 | 0 | 100% |
| AI Search | 3 | 3 | 0 | 100% |
| Multi-Device | 3 | 3 | 0 | 100% |
| **Total** | **49** | **49** | **0** | **100%** |
