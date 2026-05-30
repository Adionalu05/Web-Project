# Diagrams

---

## 1. High-Level Architecture

```mermaid
flowchart LR
    Browser[User Browser]
    Server[Web Server PHP]
    DB[SQLite Database]
    Uploads[Uploads Directory]

    Browser -- HTTP --> Server
    Server -- SQL --> DB
    Server -- Read/Write --> Uploads

    subgraph App
        Server
        DB
        Uploads
    end
```

---

## 2. Database ER Diagram

```mermaid
erDiagram
    USERS {
        int id PK
        string username
        string email
        string email_hash
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
        int folder_id FK
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
    FOLDERS {
        int id PK
        string name
        int user_id FK
        int parent_id FK
        datetime created_at
    }
    SHARES {
        int id PK
        int document_id FK
        int owner_id FK
        int shared_with_user_id FK
        string permission
        datetime created_at
    }
    SESSIONS {
        int id PK
        int user_id FK
        string session_token
        datetime expires_at
        datetime created_at
    }
    PASSWORD_RESETS {
        int id PK
        int user_id FK
        string email_hash
        string token
        datetime expires_at
        int used
        datetime created_at
    }

    USERS ||--o{ DOCUMENTS : owns
    USERS ||--o{ SESSIONS : has
    USERS ||--o{ SHARES : shares
    USERS ||--o{ FOLDERS : owns
    CATEGORIES ||--o{ DOCUMENTS : categorizes
    FOLDERS ||--o{ DOCUMENTS : contains
    FOLDERS ||--o{ FOLDERS : parent_of
    DOCUMENTS ||--o{ DOCUMENT_TAGS : tagged_by
    DOCUMENTS ||--o{ SHARES : shared_via
    TAGS ||--o{ DOCUMENT_TAGS : tags
```

> Design follows 3NF. Entities are separated; many-to-many relationships use junction tables (`document_tags`, `shares`).

---

## 3. Project Timeline (Gantt)

```mermaid
gantt
    title DMS Project Timeline
    dateFormat  YYYY-MM-DD

    section Sprint 1 — Setup
    Repo + .gitignore                    :done, s1, 2026-04-06, 1d
    DB schema (9 tables)                 :done, s2, 2026-04-06, 2d
    Auth system (register/login/logout)  :done, s3, 2026-04-07, 3d
    Base dashboard + upload              :done, s4, 2026-04-09, 3d

    section Professor Meetings
    Meeting 1 — Sprint 1 review         :milestone, m1, 2026-04-06, 0d
    Meeting 2 — folder + sharing spec   :milestone, m2, 2026-04-07, 0d
    Meeting 3 — recursive folder review :milestone, m3, 2026-05-11, 0d
    Meeting 4 — final pre-presentation  :milestone, m4, 2026-05-18, 0d

    section Sprint 2 — Features
    Secure file download                 :done, f1, 2026-04-13, 2d
    Document edit modal                  :done, f2, 2026-04-13, 2d
    Folder system + file icons           :done, f3, 2026-04-13, 4d
    File sharing between users           :done, f4, 2026-04-17, 4d
    Password reset via email             :done, f5, 2026-04-21, 5d
    AI search (Claude API)               :done, f6, 2026-04-26, 4d
    Dark mode + loading states           :done, f7, 2026-04-30, 2d
    Legacy cleanup                       :done, f8, 2026-05-02, 1d

    section Sprint 3 — Stability
    Recursive CTE + collapsible tree     :done, r1, 2026-05-03, 4d
    Multi-device fix (busy_timeout)      :done, r2, 2026-05-05, 1d
    Auth redirect fix                    :done, r3, 2026-05-05, 1d
    SRS + SDD + DB schema doc            :done, d1, 2026-05-06, 4d
    Test report (49 cases)               :done, d2, 2026-05-09, 2d
    Demo seeder                          :done, d3, 2026-05-11, 1d

    section Sprint 4 — Presentation
    User manual + deployment guide       :done, p1, 2026-05-12, 2d
    Multi-device LAN demo                :done, p2, 2026-05-14, 2d
    Collapsible folder tree UI           :done, p3, 2026-05-19, 1d
    Presentation walkthrough             :done, p4, 2026-05-18, 2d
    Final dry-run + dev to main merge    :done, p5, 2026-05-19, 1d
```
