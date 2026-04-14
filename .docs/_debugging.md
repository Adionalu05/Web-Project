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
