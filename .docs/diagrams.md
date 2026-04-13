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
    Repo setup & basic structure       :done,    a1, 2026-03-10, 1d
    Initial documentation (proposal)   :done,    a2, 2026-03-11, 1d

    section Core Features
    User authentication               :active,  a3, 2026-03-12, 2d
    File upload/download              :active,  a4, 2026-03-14, 3d
    Search + filters                  :        a5, 2026-03-17, 2d
    CRUD & metadata editing           :        a6, 2026-03-19, 2d

    section Security
    Input validation & sanitization   :        a7, 2026-03-21, 2d
    Access control & secure download  :        a8, 2026-03-23, 2d
    Data encryption (email at rest)    :        a9, 2026-03-25, 1d

    section Extras & Reporting
    Reporting & documentation         :        a10, 2026-03-26, 1d
    Email Integration                  :    a10, 2026-03-26, 1d
    AI Integration                      : a10, 2026-03-26, 1d
    File Sharing                        : a10, 2026-03-26, 1d
```

### 3. Weekly report (Personi i kontaktit)
Personi i kontaktit duhet të përgatisë **raport javor** mbi mbledhjet, diskutimet dhe vendimet e marra brenda grupit (shiko `report.md`).
