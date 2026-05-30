# Sprint 3 Review
**Period:** 3 May – 11 May 2026  
**Participants:** Elguga, Arsela Sokolaj, Dejsi Omari, Adiona, Xhensila  
**Focus:** Recursive folder traversal, multi-device stability, documentation, testing

---

## What We Completed

- **Recursive folder CTE** — `WITH RECURSIVE` query replaces the broken non-recursive folder document fetch; clicking a parent folder now returns all documents in all descendant folders
- **Collapsible folder tree** — sidebar folders collapse/expand with ▶/▼ toggle; state preserved across page loads via `localStorage`
- **File icons per extension** — PDF, Word, Excel, PowerPoint, image, and generic icons shown in folder sidebar and document table
- **Multi-device stability fix** — `PRAGMA busy_timeout = 3000` added to `config/database.php`; SQLITE_BUSY no longer silently returns false from `isAuthenticated()`
- **Auth redirect fix** — `auth.php` inline guard changed from relative `login.php` to absolute `/login.php`; JSON 401 branch added for `handle.php` requests
- **SRS document** — Functional (FR-01 – FR-14) and non-functional requirements written
- **SDD document** — Architecture diagram, API design table, 6 key design decisions
- **DB schema doc** — Full SQL for all 9 tables, ER diagram, key queries
- **Test report** — 49 test cases across 8 modules; all pass
- **Demo seeder** (`seed_demo.php`) — Creates 3-level folder tree with 8 test documents for repeatable demo setup
- Professor meeting (7 May) feedback incorporated: collapsible tree, per-extension icons

## What We Did Not Complete

- User manual (moved to Sprint 4)
- Deployment guide (moved to Sprint 4)

## Metrics

| Metric | Value |
|--------|-------|
| Issues planned | 8 |
| Issues completed | 8 |
| Completion rate | 100% |
| PRs merged | 7 |
| Bugs found | 2 (B-06, B-07) |
| Bugs fixed | 2 |

