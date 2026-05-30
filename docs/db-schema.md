# Database Schema
## Document Management System

**Database:** SQLite 3  
**File:** `data/documents.db`  
**Initialised by:** `config/database.php → initializeDatabase()`

---

## Entity Relationship Diagram

```
users ─────────────── sessions
  │                    (user_id FK)
  │
  ├── documents ──── document_tags ──── tags
  │     │ (user_id FK)  (document_id FK) (tag_id FK)
  │     │
  │     ├── folder_id FK ──► folders (self-ref: parent_id)
  │     │
  │     └── shares
  │           ├── owner_id FK ──► users
  │           └── shared_with_user_id FK ──► users
  │
  ├── folders (user_id FK)
  │
  └── password_resets (user_id FK)
```

---

## Tables

### `users`
```sql
CREATE TABLE IF NOT EXISTS users (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  username    TEXT UNIQUE NOT NULL,
  email       TEXT NOT NULL,          -- AES-256-CBC encrypted, base64-encoded
  email_hash  TEXT UNIQUE NOT NULL,   -- SHA-256(lowercase email) for lookups
  password    TEXT NOT NULL,          -- bcrypt via password_hash()
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### `sessions`
```sql
CREATE TABLE IF NOT EXISTS sessions (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id       INTEGER NOT NULL,
  session_token TEXT UNIQUE NOT NULL, -- bin2hex(random_bytes(32))
  expires_at    DATETIME NOT NULL,    -- 24 hours from creation
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### `categories`
Seeded with: Tickets, Contracts, Reports, Other
```sql
CREATE TABLE IF NOT EXISTS categories (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  name        TEXT UNIQUE NOT NULL,
  description TEXT,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### `documents`
```sql
CREATE TABLE IF NOT EXISTS documents (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id     INTEGER NOT NULL,
  title       TEXT NOT NULL,
  category_id INTEGER,
  file_path   TEXT NOT NULL,          -- server path, never exposed to client
  file_name   TEXT NOT NULL,          -- original filename
  file_size   INTEGER NOT NULL,       -- bytes
  file_format TEXT NOT NULL,          -- extension: pdf, docx, jpg, etc.
  description TEXT,
  folder_id   INTEGER DEFAULT NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (folder_id)   REFERENCES folders(id)    ON DELETE SET NULL
);
```

### `tags`
```sql
CREATE TABLE IF NOT EXISTS tags (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  name       TEXT UNIQUE NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### `document_tags`
```sql
CREATE TABLE IF NOT EXISTS document_tags (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  document_id INTEGER NOT NULL,
  tag_id      INTEGER NOT NULL,
  UNIQUE(document_id, tag_id),
  FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id)      REFERENCES tags(id)      ON DELETE CASCADE
);
```

### `folders`
Self-referential — `parent_id = NULL` means root folder.
```sql
CREATE TABLE IF NOT EXISTS folders (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  name       TEXT NOT NULL,
  user_id    INTEGER NOT NULL,
  parent_id  INTEGER DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE SET NULL
);
```

### `shares`
```sql
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
```

### `password_resets`
```sql
CREATE TABLE IF NOT EXISTS password_resets (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER NOT NULL,
  email_hash TEXT NOT NULL,
  token      TEXT UNIQUE NOT NULL,    -- bin2hex(random_bytes(32)), 64 chars
  expires_at DATETIME NOT NULL,       -- NOW + 1 hour
  used       INTEGER DEFAULT 0,       -- set to 1 after redemption
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## Key Queries

### Recursive folder document fetch
```sql
WITH RECURSIVE subfolder_ids(id) AS (
  SELECT :folder_id
  UNION ALL
  SELECT f.id FROM folders f
  INNER JOIN subfolder_ids s ON f.parent_id = s.id
  WHERE f.user_id = :user_id
)
SELECT d.*, c.name as category_name,
       GROUP_CONCAT(t.name, ', ') as tags
FROM documents d
LEFT JOIN categories c ON d.category_id = c.id
LEFT JOIN document_tags dt ON d.id = dt.document_id
LEFT JOIN tags t ON dt.tag_id = t.id
WHERE d.folder_id IN (SELECT id FROM subfolder_ids)
  AND (d.user_id = :uid OR d.id IN (
    SELECT document_id FROM shares WHERE shared_with_user_id = :uid2
  ))
GROUP BY d.id
ORDER BY d.uploaded_at DESC
```

### Session validation
```sql
SELECT id FROM sessions
WHERE user_id = :user_id
  AND session_token = :token
  AND expires_at > datetime('now')
```

### Shared documents for current user
```sql
SELECT d.*, u.username as owner_username
FROM documents d
JOIN users u ON d.user_id = u.id
JOIN shares s ON d.id = s.document_id
WHERE s.shared_with_user_id = :user_id
ORDER BY s.created_at DESC
```
