# Sprint 1 Review
**Period:** 6 April – 12 April 2026  
**Participants:** Elguga, Arsela Sokolaj, Dejsi Omari, Adiona, Xhensila  
**Focus:** Project setup, architecture, authentication system, database schema, base UI

---

## What We Completed

- Repository created on GitHub, README with team info and project description
- `.gitignore` configured for PHP/SQLite (excludes `data/`, `uploads/`, `config/mail.php`)
- Database schema designed and implemented: 9 tables with FK constraints and cascade deletes
- `Auth` class implemented: `register()`, `login()`, `logout()`, `isAuthenticated()`
- Password stored as bcrypt; email stored AES-256-CBC encrypted with SHA-256 hash for lookups
- DB-backed session tokens: `bin2hex(random_bytes(32))` inserted into `sessions` table on login
- Login, register, logout pages built and working end-to-end
- Initial dashboard layout: upload sidebar + document table + tab bar
- Basic document upload: extension whitelist, 10 MB limit, `uniqid()` filename on disk
- `api/handle.php` entry point created with `?action=X` routing
- CSS base: sidebar layout, document table, purple colour theme

## What We Did Not Complete

- Password reset via email (moved to Sprint 2 — PHPMailer setup was underestimated)
- Dark mode toggle (moved to Sprint 2)

## Metrics

| Metric | Value |
|--------|-------|
| Issues planned | 8 |
| Issues completed | 6 |
| Completion rate | 75% |
| PRs merged | 5 |
| Bugs found | 2 (B-01, B-02) |
| Bugs fixed | 2 |
