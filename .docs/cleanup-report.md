# Cleanup Report — Legacy File Removal

**Date:** 2026-04-13  
**Branch:** dev  
**Reason:** Pre-implementation cleanup before adding new features. The files below were accumulated during early prototyping and are now dead weight — they either reference non-existent endpoints or have been fully superseded by PHP equivalents.

---

## Legacy JavaScript Files

These files were written for an older AJAX architecture that called `ajax/*.php` endpoints. Those endpoints were never created. All functionality has since been reimplemented in `js/dashboard.js` using the unified `api/handle.php` router.

| File | Why deleted |
|------|-------------|
| `js/app.js` | Original delete confirmation handler. Calls `ajax/delete.php` which does not exist. Delete logic is now in `dashboard.js`. |
| `js/upload.js` | Original upload handler with file preview. Calls `ajax/upload.php` which does not exist. Upload is now handled in `dashboard.js`. |
| `js/search.js` | Original search handler. Calls `ajax/search.php` which does not exist. Search is now handled in `dashboard.js`. |
| `js/ui.js` | Sidebar toggle utility written for an older layout. The current `dashboard.php` layout does not use it. |
| `js/validation.js` | Client-side form validation for auth pages. All validation is already enforced server-side in `auth/auth.php`, making this file redundant. |

---

## Unused HTML Prototype Files

These are early static mockups created before the PHP backend existed. They were replaced by their `.php` equivalents and are no longer linked from anywhere in the application.

| File | Why deleted |
|------|-------------|
| `dashboard.html` | Early static mockup of the dashboard. Replaced by `dashboard.php` which has real authentication and data loading. |
| `dms.html` | Unnamed prototype page, never integrated into any navigation flow. |
| `dms1.html` | Second iteration of the `dms.html` prototype. Same status — never integrated. |
| `documents.html` | Static document listing mockup. Functionality has been moved into `dashboard.php`. |
| `login1.html` | Early static login form. Replaced by `login.php` with real authentication. |
| `ngarko.html` | Albanian-language upload form prototype. Upload functionality moved into the `dashboard.php` sidebar widget. |
| `regjistohu.html` | Albanian-language registration form prototype. Replaced by `register.php`. |
| `forgetpass.html` | Static password reset request placeholder with no backend wiring. Its UI content has been absorbed into the new `forgot_password.php`. |
| `resetsent.html` | Static password reset confirmation placeholder. Its UI content has been absorbed into `forgot_password.php` (shown after form submission). |

---

## Files Retained

| File | Reason kept |
|------|-------------|
| `js/dashboard.js` | Active — handles all upload, delete, filter, search via `api/handle.php` |
| `settings.html` | Static settings UI retained as-is; no backend required for current grade scope |
| `dms.jpg` | Image asset, may be used in landing page |
