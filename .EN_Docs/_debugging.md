# Doc for all bugs/errors/mistakes found


1. Login "Access Denied" — auth/auth.php
isSameOriginRequest() was comparing localhost (from referer, port stripped by parse_url) against localhost:8000 (from HTTP_HOST, port included) → always false → blocked every POST.
Fix: wrap HTTP_HOST in parse_url too so both sides strip the port before comparing.

2. Upload form submitting as GET — dashboard.php + js/jquery.min.js
jQuery was loaded from CDN which was unreachable on the local network. Without jQuery, e.preventDefault() never fired and the form fell back to HTML default (GET), putting all fields in the URL instead of POSTing.
Fix: downloaded jQuery to js/jquery.min.js and pointed the script tag to the local file. Also added method="POST" to the form as a safety net.

3. PHP upload limit too low — C:\php\php.ini
upload_max_filesize was 2M and post_max_size was 8M, silently rejecting any file over 2 MB even though the app advertises a 10 MB limit.
Fix: raised upload_max_filesize to 10M and post_max_size to 12M.

4. Password reset email not delivered — auth/email.php
PHP mail() on Windows requires a configured SMTP server. No SMTP was set up, so mail() silently failed — no error shown, no email sent.
Fix: replaced mail() with PHPMailer (lib/PHPMailer/). Configured Gmail SMTP in config/mail.php with STARTTLS on port 587. App password must be entered without spaces (Gmail displays it in groups of 4 but authentication fails if spaces are included). Confirmed working end-to-end.

5. Clicking a parent folder returns no documents — auth/document_handler.php
getFolderDocuments() used WHERE d.folder_id = :folder_id — a strict equality check. A file placed in a subfolder has the subfolder's ID as folder_id, not the grandparent's. Clicking a parent folder whose files are only in subfolders returned an empty table with no error.
Fix: rewritten using a SQLite recursive CTE (WITH RECURSIVE) to first collect all descendant folder IDs, then fetch all documents in that set. Full details in feature-recursive-folder-search.md.

7. File sharing returns 404 /api/login.php — auth/auth.php + config/database.php
Two bugs combined. First: auth.php's inline page-guard fires for every file that includes it, including api/handle.php. When isAuthenticated() returned false it redirected to login.php (relative URL) — from /api/handle.php that resolved to /api/login.php, which doesn't exist. Second: isAuthenticated() was returning false because config/database.php had no SQLite busy_timeout. With two devices hitting the DB simultaneously, a concurrent write would lock SQLite and the session SELECT threw a silent SQLITE_BUSY exception, swallowed by the catch block, making every user appear unauthenticated.
Fix 1: added PRAGMA busy_timeout = 3000 immediately after the PDO connection in config/database.php — SQLite now retries for up to 3 s before throwing.
Fix 2: changed the inline redirect in auth.php to an absolute path (/login.php) so it resolves correctly from any subdirectory.
Fix 3: added a handle.php-specific branch in the inline guard that returns a JSON 401 instead of an HTML redirect, consistent with what api/handle.php expects from its callers.

6. openssl_decrypt(): IV passed is only 12 bytes long — config/database.php
Emails were stored as plain text while the openssl extension was disabled. After enabling openssl, decryptValue() tried to base64_decode those plain email strings and use the result as ciphertext — the decoded bytes were shorter than the 16-byte IV AES-256-CBC requires.
Fix: changed base64_decode($value) to base64_decode($value, true) (strict mode). If the result is false or shorter than the IV length, the function returns the original value unchanged. Plain-text emails from before encryption was enabled pass through correctly.
