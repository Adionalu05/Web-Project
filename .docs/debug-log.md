# Debug Log

---

## ✅ Login returns "Access Denied"
**Date:** 2026-04-13  
**Root cause:** `isSameOriginRequest()` compared `localhost` (referer, port stripped by `parse_url`) against `localhost:8000` (raw `HTTP_HOST`), always returning false.  
**Fix:** Wrap `HTTP_HOST` in `parse_url` so both sides strip the port before comparing. (`auth/auth.php`)

---

## ✅ Upload form submits as GET, nothing saved
**Date:** 2026-04-13  
**Root cause:** jQuery was loaded from CDN which was unreachable on the local network. Without jQuery, `e.preventDefault()` never fired — the form fell back to HTML default GET behavior, putting all fields in the URL.  
**Fix:** Downloaded jQuery to `js/jquery.min.js` and pointed the script tag to the local file. Added `method="POST"` to the form as a safety net. (`dashboard.php`, `js/jquery.min.js`)

---

## ✅ PHP rejects files over 2 MB
**Date:** 2026-04-13  
**Root cause:** `upload_max_filesize = 2M` in `C:\php\php.ini`, silently dropping any file above that even though the app allows 10 MB.  
**Fix:** Raised `upload_max_filesize` to `10M` and `post_max_size` to `12M` in `C:\php\php.ini`. Server restart required.

---

## 🔄 Password reset email not delivered
**Date:** 2026-04-13  
**Root cause:** PHP `mail()` on Windows requires a configured SMTP server. No SMTP was set up, so `mail()` silently failed.  
**Temporary fix:** Reset link is shown on-screen and logged to `data/mail.log` when `mail()` fails.  
**Permanent fix (in progress):** Installing PHPMailer + configuring Gmail SMTP via `config/mail.php`. Pending credential setup by user.  
**Status:** 🔄 In progress
