# Menaxhimi i bazës së të dhënave nga terminali duke përdorur sqlite3


```
sqlite3 .\data\documents.db
SQLite version 3.52.0 2026-03-06 16:01:44
Shkruaj ".help" për udhëzime përdorimi.
sqlite> .tables
categories     document_tags     documents     sessions     tags     users
sqlite> sqlite3 .
sqlite> PRAGMA table_info (USERS);
╭─────┬────────────┬──────────┬─────────┬───────────────────┬────╮
│ cid │    name    │   type   │ notnull │    dflt_value     │ pk │
╞═════╪════════════╪══════════╪═════════╪═══════════════════╪════╡
│   0 │ id         │ INTEGER  │       0 │ NULL              │  1 │
│   1 │ username   │ TEXT     │       1 │ NULL              │  0 │
│   2 │ email      │ TEXT     │       1 │ NULL              │  0 │
│   3 │ password   │ TEXT     │       1 │ NULL              │  0 │
│   4 │ created_at │ DATETIME │       0 │ CURRENT_TIMESTAMP │  0 │
│   5 │ updated_at │ DATETIME │       0 │ CURRENT_TIMESTAMP │  0 │
│   6 │ email_hash │ TEXT     │       1 │ ''''''            │  0 │
╰─────┴────────────┴──────────┴─────────┴───────────────────┴────╯
sqlite> .mode column
sqlite> .headers on
sqlite> SELECT id, username, email, password FROM users;
id username    email                           password
-- -------- ----------- -------------------------------------------------------
 1 AAA      A@gmail.com $2y$10$PkO1XKbaxAf4lXL/sK0k.OS8APwWHa030zFUZn4hG.      
                        KOYpdngLaH2
sqlite> DELETE FROM users WHERE id=1;
sqlite> SELECT id, username, email, password FROM users;

```
