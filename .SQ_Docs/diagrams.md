# Diagramet

## Diagramet e Sistemit

### 1. Arkitektura e Nivelit të Lartë (Mermaid)

```mermaid
flowchart LR
    Browser[Shfletuesi i Përdoruesit]
    Server[Serveri Web (PHP)]
    DB[Baza e të Dhënave SQLite]
    Uploads[Dosja e Ngarkimeve]

    Browser -- HTTP(S) --> Server
    Server -- SQL --> DB
    Server -- Lexim/Shkrim --> Uploads

    subgraph App
        Server
        DB
        Uploads
    end
```

### 2. Skema e Bazës së të Dhënave (Mermaid ERD)

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

    USERS ||--o{ DOCUMENTS : zotëron
    USERS ||--o{ SESSIONS : ka
    USERS ||--o{ DOCUMENT_SHARES : ndan
    CATEGORIES ||--o{ DOCUMENTS : kategorizon
    DOCUMENTS ||--o{ DOCUMENT_TAGS : etiketohet_nga
    DOCUMENTS ||--o{ DOCUMENT_SHARES : ndahet_nëpërmjet
    TAGS ||--o{ DOCUMENT_TAGS : etiketat
```

> **Shënim:** Ky dizajn ndjek formën e tretë normale (3NF) duke mbajtur entitetet të ndara (përdorues, dokumente, etiketa, kategori) dhe duke përdorur tabela lidhëse për relacionet shumë-me-shumë.

## Menaxhimi i Punës në Grup

### 1. Mjetet për bashkëpunim dhe menaxhim kodi
- **Kontrolli i versioneve dhe bashkëpunimi i kodit:** Git + GitHub (ose GitLab/Bitbucket)
- **Ndjekja e detyrave:** GitHub Issues / Projects, Trello, ose tabela të ngjashme Kanban
- **Komunikimi:** Slack / Microsoft Teams / Discord / Email
- **Dokumentimi:** Skedarë Markdown në `.EN_Docs/` + README

### 2. Planifikimi dhe ndjekja e projektit (Kryetari i grupit)
Kryetari i grupit krijon një **plan projekti** dhe monitoron ecurinë duke përdorur një **grafik Gantt** për detyrat e përditësuara.

#### Grafiku Gantt (Mermaid)
```mermaid
gantt
    title Plani i Projektit dhe Ndjekja e Detyrave
    dateFormat  YYYY-MM-DD

    section Ngritja
    Ngritja e repo dhe struktura bazë        :done, s1, 2026-03-10, 1d
    Dokumentimi fillestar (propozimi)        :done, s2, 2026-03-11, 1d

    section Veçoritë Themelore
    Autentifikimi i përdoruesit              :done, c1, 2026-03-12, 3d
    Ngarkimi / shkarkimi i skedarëve         :done, c2, 2026-03-15, 4d
    Paneli + AJAX                            :done, c3, 2026-03-19, 3d
    Kërkimi + filtrat                        :done, c4, 2026-03-22, 2d
    CRUD dhe editimi i metadatave            :done, c5, 2026-03-24, 2d

    section Siguria
    Validimi i hyrjeve dhe sanifikimi        :done, sec1, 2026-03-26, 2d
    Kontrolli i aksesit dhe shkarkimi i sigurt :done, sec2, 2026-03-28, 2d
    Enkriptimi i email-it (AES-256-CBC)      :done, sec3, 2026-04-01, 2d
    Forcimi i token-it të sesionit           :done, sec4, 2026-04-03, 1d
    Kontrolli i origjinës CSRF               :done, sec5, 2026-04-04, 1d

    section Takimet me Profesorin
    Takimi 1 — rishikim i kërkesave          :milestone, m1, 2026-04-06, 0d
    Takimi 2 — specifikimi i dosjeve dhe ndarjes :milestone, m2, 2026-04-07, 0d
    Takimi 3 — rishikim i kërkimit rekursiv  :milestone, m3, 2026-05-11, 0d
    Takimi 4 — rishikim final para prezantimit :milestone, m4, 2026-05-18, 0d

    section Veçoritë e Avancuara
    Sistemi i dosjeve (shirit anësor + DB)   :done, f1, 2026-04-08, 5d
    Ndarja e skedarëve mes përdoruesve       :done, f2, 2026-04-13, 4d
    Rivendosja e fjalëkalimit nëpërmjet email :done, f3, 2026-04-17, 5d
    Renditja e kërkimit me AI (Claude API)   :done, f4, 2026-04-22, 4d
    Tema e errët / e ndritshme               :done, f5, 2026-04-26, 3d

    section Veçoria e Kërkimit Rekursiv të Dosjeve
    Gabimi i identifikuar (kufizimi i përputhjes strikte) :milestone, r0, 2026-04-28, 0d
    Implementimi i CTE WITH RECURSIVE        :done, r1, 2026-04-29, 3d
    Skripti i demo-it seeder                 :done, r2, 2026-05-02, 2d
    Renderuesi i pemës në shiritin anësor (PHP) :done, r3, 2026-05-03, 1d

    section Demo me Shumë Pajisje dhe Ndarje
    Lidhja LAN (0.0.0.0) + muri i zjarrit   :done, md1, 2026-05-18, 1d
    Demo live e ndarjes (dy pajisje)         :done, md2, 2026-05-18, 1d

    section Dokumentimi dhe Prezantimi
    Raportet javore + dokumentet e veçorive  :done, d1, 2026-04-06, 43d
    Udhëzuesi i prezantimit i shkruar        :done, d2, 2026-05-14, 5d
    Dosja media + përditësimet e diagrameve  :done, d3, 2026-05-18, 2d
    Provë e fundit dhe bashkimi dev→main     :active, d4, 2026-05-19, 1d
```

### 3. Raporti javor (Personi i kontaktit)
Personi i kontaktit duhet të përgatisë **raport javor** mbi mbledhjet, diskutimet dhe vendimet e marra brenda grupit (shiko skedarët e raporteve).
