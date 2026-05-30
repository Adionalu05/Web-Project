# Meeting Notes

All professor meetings and key team sessions.

---

## 2026-04-06 — Professor Meeting (Sprint 1 kickoff)

**Decisions:**
- Add password reset via email — token-based, 1-hour expiry
- Add AI-enhanced search — smarter query matching via external API
- Add file sharing — users can share documents with other users; track permissions

**Next steps:** update DB schema for shares, design reset token flow, research AI search options

---

## 2026-04-07 — Professor Meeting (Folder spec)

**Decisions:**
- Folders show documents only when clicked (no recursive search at this stage)
- Folder table: `id`, `name`, `user_id`, `parent_id`
- Sidebar renders with folder icons; file extension icons shown per document
- Folder query: `WHERE user_id = current`
- Document query for folder: documents owned by user OR shared with user via `shares` table
- Use prepared statements when inserting new folders

---

## 2026-04-13 — Team Session (Sprint 2 implementation)

All 6 remaining features implemented and committed to `dev` in a single session:

| Feature | Files changed |
|---------|--------------|
| Secure file download (ownership + share check) | `download.php` |
| Edit document via inline modal | `dashboard.php`, `js/dashboard.js`, `api/handle.php` |
| Folder system with file icons | `config/database.php`, `auth/document_handler.php`, `api/handle.php`, `dashboard.php`, `js/dashboard.js`, `css/style.css` |
| File sharing between users | `config/database.php`, `auth/document_handler.php`, `api/handle.php`, `dashboard.php`, `js/dashboard.js` |
| Password reset via email | `forgot_password.php`, `reset_password.php`, `auth/auth.php`, `login.php` |
| AI-enhanced search (Claude API) | `config/database.php`, `auth/document_handler.php`, `api/handle.php` |

**Also:** removed 5 dead JS files and 9 unused HTML prototypes (see `docs/bug-log.md` for rationale).

---

## 2026-05-11 — Professor Meeting (Recursive folder review)

**Context:** team demonstrated the folder implementation; professor noted that clicking a parent folder should show documents from all nested subfolders, not just direct children.

**Decisions:**
- The April 7 "no recursive search" decision is superseded
- Replace `WHERE folder_id = :id` with a `WITH RECURSIVE` CTE
- Build a seed script for a reproducible nested demo

**Implementation:** `getFolderDocuments()` rewritten with `WITH RECURSIVE subfolder_ids` CTE; `renderFolderTree()` rewritten as recursive PHP function with depth-based indentation. `seed_demo.php` creates a 3-level tree with 8 test documents.

---

## 2026-05-18 — Professor Meeting (Final pre-presentation review)

**Context:** walked through all completed features against the requirements checklist. Multi-device access demonstrated live in the lab — second laptop connected over classroom Wi-Fi.

**Decisions:**
- Presentation order: Authentication → Password Reset → Upload + Folders → Multi-Device + Sharing → AI Search
- Each demo step references key files so professor can follow the code path
- Feature freeze — no new functionality before presentation
- `seed_demo.php` to be run at start of demo for a clean state
- `CLAUDE_API_KEY` to be set in environment on presentation machine

**Issues resolved:**
- Windows Firewall was blocking port 8000 → added inbound rule for TCP 8000
- Server defaulted to `localhost` binding → restarted with `php -S 0.0.0.0:8000`

---

## 2026-05-19 — Team Session (Final dry-run)

**Completed:**
- Added collapsible folder tree UI — ▶/▼ toggle on parent folders; leaf folders keep single-click behaviour with 📄 icon
- No backend changes required; purely frontend (`dashboard.php`, `js/dashboard.js`, `css/style.css`)
- Fixed `.active` highlight: `loadFolder(id, this.closest('.folder-item'))` instead of `this` when folder has children

**Final decisions:**
- Leaf folders: 📄 icon, single click loads documents
- Parent folders: 📁/📂 icon + arrow toggle; arrow expands/collapses, name loads documents
- Recursive CTE and `loadFolder()` logic unchanged
