# Sprint 2 Review
**Period:** 13 April – 2 May 2026  
**Participants:** Elguga, Arsela Sokolaj, Dejsi Omari, Adiona, Xhensila  
**Focus:** All 6 remaining features, dark mode, legacy cleanup

---

## What We Completed

- **Secure file download** (`download.php`) — auth-gated, ownership check, raw path never exposed
- **Document edit modal** — inline editing of title, category, tags, description via AJAX modal
- **Folder system** — `folders` table, sidebar tree, folder creation with parent support, file icons per extension
- **File sharing** — `shares` table, share modal, Shared with Me tab, download permission check
- **Password reset via email** — PHPMailer + Gmail SMTP STARTTLS, token generation, 1-hour expiry, single-use enforcement
- **AI-enhanced search** — Claude API integration via cURL, semantic reranking, graceful fallback when key absent
- **Dark/light mode toggle** — `theme.js`, localStorage persistence, applied on page load
- **Dashboard loading states** — spinner/skeleton shown during AJAX operations
- **Legacy file cleanup** — removed 5 dead JS files (`app.js`, `upload.js`, `search.js`, `ui.js`, `validation.js`) and 9 unused HTML prototypes
- Professor meeting (7 April) requirements incorporated: folder click shows documents, file icons, safe query for new folders

## What We Did Not Complete

- All planned items completed in this sprint

## Metrics

| Metric | Value |
|--------|-------|
| Issues planned | 10 |
| Issues completed | 10 |
| Completion rate | 100% |
| PRs merged | 9 |
| Bugs found | 3 (B-03, B-04, B-05) |
| Bugs fixed | 3 |
