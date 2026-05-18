# Document Managment System for Web Dev Class

Stack:
* PHP
* 


Features:
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


## Explanation of basic notions used

API

SESSION

HANDLER

CURL

GET/POST


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