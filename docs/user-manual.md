# User Manual
## Document Management System (DMS)

**Version:** 1.0  
**Date:** 2026-05-19  

---

## Getting Started

### Creating an Account
1. Open `http://localhost:8000` in your browser.
2. Click **Register**.
3. Fill in: **Username** (min 3 chars), **Email**, **Password** (min 6 chars).
4. Click **Register** — you are redirected to the login page.

### Logging In
1. Enter your **Username** and **Password**.
2. Click **Log In** — you land on the dashboard.

### Logging Out
Click **Logout** in the top-right corner. Your session is destroyed server-side immediately.

---

## Dashboard Overview

```
┌─────────────────────────────────────────────────────┐
│  DMS                              [🌙] [Logout]     │
├──────────────┬──────────────────────────────────────┤
│  UPLOAD      │  [My Documents] [Shared with Me]     │
│  ─────────── │  ─────────────────────────────────── │
│  FOLDERS     │  Search: [_______] [Search]          │
│  📁 Project  │  Filter: [Category▼] [Tag▼]         │
│    📁 Docs   │                                      │
│    📄 Notes  │  Title    Category  Tags   Actions   │
│  📁 Archive  │  ──────── ──────── ─────── ───────── │
│              │  Report   Reports  fin...  ↓ ✏ 🗑 🔗  │
└──────────────┴──────────────────────────────────────┘
```

- **Left sidebar:** Upload form + folder tree
- **Main panel:** Document table with filters + search
- **Top bar:** Theme toggle (🌙/☀️) + Logout

---

## Uploading Documents

1. In the **left sidebar**, fill in:
   - **Title** (required)
   - **Category** (Tickets / Contracts / Reports / Other)
   - **Tags** (comma-separated, e.g. `finance, q1, 2026`)
   - **Folder** (optional — select from dropdown)
   - **File** (click Choose File)
2. Click **Upload**.
3. The document table updates immediately — no page reload.

**Limits:** Max 10 MB. Allowed types: PDF, Word, Excel, PowerPoint, TXT, JPG, PNG, GIF, ZIP.

---

## Managing Documents

### Downloading
Click the **↓** (download) button on any document row. Files are served securely — the real path is never exposed.

### Editing Metadata
1. Click the **✏** (edit) button.
2. A modal opens — change title, category, tags, or description.
3. Click **Save** — table updates instantly.

### Deleting
1. Click the **🗑** (delete) button.
2. Confirm the prompt.
3. The file is removed from the server and the row disappears.

---

## Searching and Filtering

### Keyword Search
Type in the **Search** box and click **Search**. Results are ranked by semantic relevance using AI (if configured) — not just alphabetical order.

### Filter by Category
Use the **Category** dropdown to show only documents in one category.

### Filter by Tag
Use the **Tag** dropdown to show only documents with a specific tag.

---

## Folder System

### Creating a Folder
1. In the left sidebar, find the **New Folder** section.
2. Enter a folder name.
3. Optionally select a **Parent Folder** to create a subfolder.
4. Click **Create Folder** — the folder appears in the sidebar tree.

### Navigating Folders
- Click the **▶ arrow** next to a parent folder to expand/collapse its subfolders.
- Click the **folder name** to load all documents in that folder and all its subfolders (recursive).
- **Leaf folders** (no children) show a 📄 icon — click them to load their documents.

---

## Sharing Documents

### Sharing with Another User
1. Click the green **Share** button on any document row.
2. A modal appears — enter the **username** of the recipient.
3. Click **Share**. A success message confirms.

### Accessing Shared Documents
1. Click the **Shared with Me** tab at the top of the document panel.
2. All documents shared with you appear here.
3. Click **↓** to download a shared document.

---

## Password Reset

If you forget your password:
1. On the login page, click **"Forgot your password?"**.
2. Enter your registered email address → click **Send Reset Link**.
3. Check your inbox — a reset link arrives within a minute.
4. Click the link (valid for 1 hour).
5. Enter and confirm your new password → click **Reset Password**.
6. You are redirected to the login page.

> The link can only be used once. Request a new one if it expires.

---

## Dark / Light Mode

Click the **🌙 / ☀️** icon in the top-right corner to switch themes. Your preference is saved automatically and applied on your next visit.

---

## Multi-Device Access

If the server is started with `php -S 0.0.0.0:8000`, anyone on the same Wi-Fi network can access the app at `http://[host-IP]:8000`. All users share the same database — documents uploaded on one device are visible on another.
