# Diagrams


## System Diagrams

### 1. High-Level Architecture (Mermaid)

```mermaid
flowchart LR
    Browser[User Browser]
    Server[Web Server (PHP)]
    DB[SQLite Database]
    Uploads[Uploads Directory]

    Browser -- HTTP(S) --> Server
    Server -- SQL --> DB
    Server -- Read/Write --> Uploads

    subgraph App
        Server
        DB
        Uploads
    end
```

##3 2. Database Schema (Mermaid ERD)

```mermaid
erDiagram
    USERS {
        int id PK
        string username
        string email
        string password_hash
        datetime created_at
        datetime updated_at
    }
    CATEGORIES {
        int id PK
        string name
        string description
        datetime created_at
    }
    DOCUMENTS {
        int id PK
        int user_id FK
        string title
        int category_id FK
        string file_path
        string file_name
        int file_size
        string file_format
        string description
        datetime uploaded_at
        datetime updated_at
    }
    TAGS {
        int id PK
        string name
        datetime created_at
    }
    DOCUMENT_TAGS {
        int id PK
        int document_id FK
        int tag_id FK
    }
    DOCUMENT_SHARES {
        int id PK
        int document_id FK
        int from_user_id FK
        int to_user_id FK
        string permission
        datetime shared_at
        datetime expires_at
        string status
    }
    SESSIONS {
        int id PK
        int user_id FK
        string session_token
        datetime expires_at
        datetime created_at
    }

    USERS ||--o{ DOCUMENTS : owns
    USERS ||--o{ SESSIONS : has
    USERS ||--o{ DOCUMENT_SHARES : shares
    CATEGORIES ||--o{ DOCUMENTS : categorizes
    DOCUMENTS ||--o{ DOCUMENT_TAGS : tagged_by
    DOCUMENTS ||--o{ DOCUMENT_SHARES : shared_via
    TAGS ||--o{ DOCUMENT_TAGS : tags
```

> **Note:** This design follows 3rd normal form (3NF) by keeping entities separated (users, documents, tags, categories) and using junction tables for many-to-many relationships.

## Group Work Management (MBI MENAXHIMIN E PUNES NE GRUP)

### 1. Tools for collaboration and code management
- **Version control & code collaboration:** Git + GitHub (or GitLab/Bitbucket)
- **Task tracking:** GitHub Issues / Projects, Trello, or similar Kanban boards
- **Communication:** Slack / Microsoft Teams / Discord / Email
- **Documentation:** Markdown files in `.docs/` + README

### 2. Project planning & tracking (Kryetari i grupit)
Kryetari i grupit (Project Lead) krijon një **plan projekti** dhe monitoron ecurinë duke përdorur një **Gantt chart** për detyrat e përditësuara.

#### Gantt Chart (Mermaid)
```mermaid
gantt
    title Project Plan & Task Tracking
    dateFormat  YYYY-MM-DD

    section Setup
    Repo setup & basic structure        :done, s1, 2026-03-10, 1d
    Initial documentation (proposal)    :done, s2, 2026-03-11, 1d

    section Core Features
    User authentication                 :done, c1, 2026-03-12, 3d
    File upload / download              :done, c2, 2026-03-15, 4d
    Dashboard + AJAX                    :done, c3, 2026-03-19, 3d
    Search + filters                    :done, c4, 2026-03-22, 2d
    CRUD & metadata editing             :done, c5, 2026-03-24, 2d

    section Security
    Input validation & sanitization     :done, sec1, 2026-03-26, 2d
    Access control & secure download    :done, sec2, 2026-03-28, 2d
    Email encryption (AES-256-CBC)      :done, sec3, 2026-04-01, 2d
    Session token hardening             :done, sec4, 2026-04-03, 1d
    CSRF origin check                   :done, sec5, 2026-04-04, 1d

    section Professor Meetings
    Meeting 1 — requirements review     :milestone, m1, 2026-04-06, 0d
    Meeting 2 — folder & sharing spec   :milestone, m2, 2026-04-07, 0d
    Meeting 3 — recursive folder review :milestone, m3, 2026-05-11, 0d
    Meeting 4 — final pre-presentation  :milestone, m4, 2026-05-18, 0d

    section Advanced Features
    Folder system (sidebar + DB)        :done, f1, 2026-04-08, 5d
    File sharing between users          :done, f2, 2026-04-13, 4d
    Password reset via email            :done, f3, 2026-04-17, 5d
    AI search reranking (Claude API)    :done, f4, 2026-04-22, 4d
    Dark / light mode theme             :done, f5, 2026-04-26, 3d

    section Recursive Folder Feature
    Bug identified (exact-match limit)  :milestone, r0, 2026-04-28, 0d
    WITH RECURSIVE CTE implementation   :done, r1, 2026-04-29, 3d
    Seed demo script                    :done, r2, 2026-05-02, 2d
    Sidebar tree renderer (PHP)         :done, r3, 2026-05-03, 1d

    section Multi-Device & Sharing Demo
    LAN binding (0.0.0.0) + firewall    :done, md1, 2026-05-18, 1d
    Live sharing demo (two devices)     :done, md2, 2026-05-18, 1d

    section Documentation & Presentation
    Weekly reports + feature docs       :done, d1, 2026-04-06, 43d
    Presentation walkthrough written    :done, d2, 2026-05-14, 5d
    Media folder + diagram updates      :done, d3, 2026-05-18, 2d
    Final dry-run & dev→main merge      :active, d4, 2026-05-19, 1d
```

### 3. Weekly report (Personi i kontaktit)
Personi i kontaktit duhet të përgatisë **raport javor** mbi mbledhjet, diskutimet dhe vendimet e marra brenda grupit (shiko `report.md`).
