# Ekipi — Rolet dhe Përgjegjësitë

Pesë anëtarë. Çdo person zotëroi një pjesë vertikale të projektit nga fillimi në fund.

---

## Personi 1 — Frontend dhe Dizajni UI

**Përgjegjësia:** Gjithçka që përdoruesi sheh dhe ndërvepron me të në shfletues.

**Skedarët e zotëruar:**
- `dashboard.php` — pamja kryesore e aplikacionit (tabela e dokumenteve, shiriti anësor, shiriti i skedave, të gjitha modalitet)
- `login.php` — formulari i kyçjes + lidhja "Keni harruar fjalëkalimin?" + mesazhi i suksesit të rivendosjes
- `register.php` — formulari i regjistrimit
- `forgot_password.php` — formulari i hyrjes së email-it (pamja GET)
- `reset_password.php` — pamja e validimit të token-it + formulari i fjalëkalimit të ri
- `css/style.css` — i gjithë stilimi: paraqitja, mbulesë modalesh, shiriti i skedave, pema e dosjeve, butonat, ikonat e skedarëve

**Çfarë ndërtuan:**
- Paraqitje me dy kolona responsive (shiritin anësor + zona kryesore e përmbajtjes)
- Tabelë dokumentesh me butonat Edit, Share, Download, Delete për çdo rresht
- Modal editimi (i parazgjedhur me titullin aktual, kategorinë, etiketat, përshkrimin)
- Modal ndajeje (hyrja e emrit të përdoruesit → ndarje me një përdorues tjetër)
- Modal i dosjes së re
- Ndërrues skedash mes "Dokumentet e Mia" dhe "Ndarë me Mua"
- Ikona të llojeve të skedarëve të hartuara sipas shtesës (PDF, DOCX, imazh, etj.)
- Mesazhe flash dhe komente formulari nëpër të gjitha faqet e autentifikimit

---

## Personi 2 — Integrimi i API-t

**Përgjegjësia:** Gjithë komunikimi mes shfletuesit dhe serverit, dhe mes serverit dhe shërbimeve të jashtme.

**Skedarët e zotëruar:**
- `api/handle.php` — pikë hyrje e vetme AJAX; rrugëzon të gjitha kërkesat e shfletuesit sipas parametrit `action`
- `js/dashboard.js` — të gjitha thirrjet AJAX nga ana e klientit, përditësimet DOM, logjika e modaleve
- `auth/email.php` — mbështjellësi PHPMailer për Gmail SMTP
- `lib/PHPMailer/` — biblioteka PHPMailer (PHPMailer.php, SMTP.php, Exception.php)
- `config/mail.php` — kredencialet Gmail SMTP (i përjashtuar nga git)

**Çfarë ndërtuan:**
- Router API i unifikuar (`api/handle.php`) që trajton 12 veprime nëpërmjet GET/POST
- Të gjitha thirrjet jQuery AJAX në `dashboard.js` (ngarkim, kërkim, editim, fshirje, ndarje, navigim dosjesh)
- Integrimi i API-t Claude Anthropic — kërkesë cURL tek `api.anthropic.com/v1/messages`, analizim i përgjigjes, fallback i hijshëm nëse çelësi mungon
- Dorëzimi i email-it SMTP Gmail nëpërmjet PHPMailer (STARTTLS, porta 587, autentifikim me fjalëkalim aplikacioni)
- Ruajtja e sigurt e kredencialeve: çelësi Claude nëpërmjet `getenv()`, kredencialet e postës në skedar konfigurimi të përjashtuar nga git

---

## Personi 3 — Dizajni i Bazës së të Dhënave dhe Pyetjet

**Përgjegjësia:** Modeli i të dhënave, skema dhe i gjithë SQL-i që lexon ose shkruan në bazën e të dhënave.

**Skedarët e zotëruar:**
- `config/database.php` — inicializimi i skemës, të gjitha deklaratat `CREATE TABLE`, ndihmëset e enkriptimit AES-256-CBC
- `auth/document_handler.php` — të gjitha metodat DocumentHandler (çdo pyetje SQL gjendet këtu)
- `.EN_Docs/sqlite_queries.md` — fletë referimi e të gjitha pyetjeve të përdorura në projekt

**Çfarë dizajnuan:**
- Tabelat themelore: `users`, `sessions`, `documents`, `categories`, `tags`, `document_tags`
- Skema e zgjeruar: `folders` (me `parent_id` për fushëzim), `shares`, `password_resets`
- Migrimi i kolonës `folder_id` mbi `documents`
- Enkriptimi AES-256-CBC i adresave të email-it në pushim (`encryptValue` / `decryptValue`)
- Hash-imi SHA-256 i email-it për kërkim me indeks pa ekspozuar tekstin e thjeshtë
- Të gjitha metodat DocumentHandler: ngarkim, listim, filtrim, kërkim me fjalë kyçe, editim, fshirje, CRUD dosjesh, ndarje, marrje e dokumenteve të ndara

---

## Personi 4 — Autentifikimi dhe Siguria

**Përgjegjësia:** Identiteti i përdoruesit, menaxhimi i sesionit, kontrolli i aksesit dhe forcimi.

**Skedarët e zotëruar:**
- `auth/auth.php` — klasa `Auth`: regjistrim, kyçje, dalje, validim sesioni, kontroll i të njëjtës origjinë
- `download.php` — shkarkimi i sigurt i skedarëve (me kontroll autentifikimi, pa shteg reale të sistemit të skedarëve të ekspozuar)
- `.htaccess` — rregullat e aksesit të drejtorisë (bllokon aksesin e drejtpërdrejtë tek `auth/`, `config/`, `data/`, `uploads/`)
- `.gitignore` — parandalon kredencialet dhe të dhënat e përdoruesit të komitetohen

**Çfarë ndërtuan:**
- Sistemi i token-it të sesionit: token `bin2hex(random_bytes(32))` i ruajtur në tabelën `sessions`, i validuar në çdo kërkesë
- Hash-imi i fjalëkalimit me `password_hash()` / `password_verify()` (bcrypt)
- Rrjedha e rivendosjes së fjalëkalimit: token i sigurt `bin2hex(random_bytes(32))`, skadim 1 orë, përdorim i vetëm (`used = 1` pas shlyerjes), i ruajtur në `password_resets`
- `download.php`: kontrollon sesionin, verifikon pronësinë OSE lejen e ndarjes para se të shërbejë ndonjë skedar; vendos `Content-Disposition: attachment` kështu që asnjë shteg i papërpunuar nuk ekspozohet kurrë në një lidhje
- `isSameOriginRequest()`: zbutje CSRF — krahason hostin e referuerit me hostin e serverit
- Rregullat `.htaccess` që mohojnë aksesin e drejtpërdrejtë të shfletuesit tek drejtoritë e ndjeshme

---

## Personi 5 — Infrastruktura dhe Dokumentimi

**Përgjegjësia:** Ngritja e projektit, mjedisi i zhvilluesit, higjiena e kontrollit të versioneve dhe dokumentimi i shkruar.

**Skedarët e zotëruar:**
- `.EN_Docs/_project-explanation.md` — pasqyrë e projektit, stack, listë veçorish, shënime skeme DB
- `.EN_Docs/feature_external-services.md` — shpjegues API (çfarë është një API, integrimi Claude, Gmail SMTP, siguria e çelësave, demo, log debug)
- `.EN_Docs/_debugging.md` — log i vazhdueshëm i çdo gabimi të gjetur dhe rregulluar
- `.EN_Docs/report-2026-04-06.md`, `report-2026-04-07.md` — raportet e takimeve me profesorin
- `.EN_Docs/diagrams.md` — diagramet e arkitekturës dhe rrjedhës
- Strategjia e degëzimit Git: `main` i qëndrueshëm, i gjithë puna e veçorive në `dev`

**Çfarë ngritën:**
- Konfigurimi i serverit të integruar PHP (`php -S localhost:8000`)
- Kërkesat e shtesave `php.ini` të dokumentuara dhe verifikuara (`openssl`, `curl`, `sockets`, `pdo_sqlite`)
- Politika `.gitignore`: `config/mail.php`, `data/documents.db`, `data/mail.log`, `uploads/` të gjitha të përjashtuara
- I gjithë dokumentimi i shkruar mbajtur në `.EN_Docs/` dhe i përditësuar krahas çdo veçorie dhe rregullimi gabimi
