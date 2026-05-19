# Dokumentim i të gjitha gabimeve / problemeve të gjetura


1. Login "Akses i Mohuar" — auth/auth.php
isSameOriginRequest() krahasonte localhost (nga referer, porta e hequr nga parse_url) me localhost:8000 (nga HTTP_HOST, porta e përfshirë) → gjithmonë false → bllokonte çdo POST.
Rregullim: mbështjelle HTTP_HOST brenda parse_url gjithashtu, që të dyja palët të heqin portën para krahasimit.

2. Formulari i ngarkimit dorëzon si GET — dashboard.php + js/jquery.min.js
jQuery ishte ngarkuar nga CDN i cili ishte i paarritshëm në rrjetin lokal. Pa jQuery, e.preventDefault() nuk ekzekutohej kurrë dhe formulari binte në sjelljen e parazgjedhur të HTML (GET), duke vendosur të gjitha fushat në URL në vend të POST-it.
Rregullim: shkarkoi jQuery tek js/jquery.min.js dhe ndryshoi etiketën script për të treguar dosjen lokale. Gjithashtu u shtua method="POST" në formular si masë mbrojtëse.

3. Kufiri i ngarkimit të PHP shumë i ulët — C:\php\php.ini
upload_max_filesize ishte 2M dhe post_max_size ishte 8M, duke refuzuar në heshtje çdo skedar mbi 2 MB edhe pse aplikacioni tregon kufirin prej 10 MB.
Rregullim: u rrit upload_max_filesize në 10M dhe post_max_size në 12M.

4. Email-i për rivendosjen e fjalëkalimit nuk u dorëzua — auth/email.php
PHP mail() në Windows kërkon një server SMTP të konfiguruar. Asnjë SMTP nuk ishte ngritur, kështu që mail() dështonte në heshtje — asnjë gabim i shfaqur, asnjë email i dërguar.
Rregullim: u zëvendësua mail() me PHPMailer (lib/PHPMailer/). U konfigurua Gmail SMTP në config/mail.php me STARTTLS në portën 587. Fjalëkalimi i aplikacionit duhet të futet pa hapësira (Gmail e shfaq në grupe prej 4, por autentikimi dështon nëse përfshihen hapësirat). Konfirmuar të funksionojë nga fillimi në fund.

5. Klikimi i një dosjeje prindër nuk kthen dokumente — auth/document_handler.php
getFolderDocuments() përdorte WHERE d.folder_id = :folder_id — një kontroll i barazisë strikte. Një skedar i vendosur në një nëndosje ka ID-në e nëndosjes si folder_id, jo ID-në e prindërit. Klikimi i një dosjeje prindër, skedarët e të cilës janë vetëm në nëndosje, kthente një tabelë boshe pa asnjë gabim.
Rregullim: u rishkrua duke përdorur një CTE rekursiv të SQLite (WITH RECURSIVE) për të mbledhur fillimisht të gjitha ID-të e nëndosjeve, pastaj të marrë të gjitha dokumentet në atë grup. Detaje të plota në feature-recursive-folder-search.md.

7. Ndarja e skedarëve kthen 404 /api/login.php — auth/auth.php + config/database.php
Dy gabime të kombinuara. I pari: roja inline e faqes në auth.php ekzekutohet për çdo skedar që e përfshin atë, përfshirë api/handle.php. Kur isAuthenticated() kthente false, bënte ridrejtim tek login.php (URL relative) — nga /api/handle.php kjo zgjidhej si /api/login.php, që nuk ekziston. I dyti: isAuthenticated() po kthente false sepse config/database.php nuk kishte busy_timeout SQLite. Me dy pajisje duke goditur DB-në njëkohësisht, një shkrim konkurrent do të bllokonte SQLite dhe SELECT-i i sesionit hidhte një përjashtim të heshtur SQLITE_BUSY, të kaptur nga blloku catch, duke e bërë çdo përdorues të duket si i pa-autentikuar.
Rregullim 1: u shtua PRAGMA busy_timeout = 3000 menjëherë pas lidhjes PDO në config/database.php — SQLite tani riprovon deri në 3 sekonda para se të hidhë gabim.
Rregullim 2: u ndryshua ridrejtimi inline në auth.php në rrugë absolute (/login.php) që të zgjidhet saktë nga çdo nëndrejtori.
Rregullim 3: u shtua një degë specifike për handle.php në rojën inline që kthen JSON 401 në vend të ridrejtimit HTML, në përputhje me atë që api/handle.php pret nga thirrësit e tij.

6. openssl_decrypt(): IV i kaluar është vetëm 12 bajt i gjatë — config/database.php
Email-et ishin ruajtur si tekst i thjeshtë ndërkohë që shtesa openssl ishte e çaktivizuar. Pas aktivizimit të openssl, decryptValue() provoi të bënte base64_decode të atyre vargut të email-it të thjeshtë dhe ta përdorte rezultatin si tekst të enkriptuar — bajtet e dekoduara ishin më të shkurtër se IV-ja 16-bajtshe që kërkon AES-256-CBC.
Rregullim: u ndërrua base64_decode($value) me base64_decode($value, true) (modalitet strikt). Nëse rezultati është false ose më i shkurtër se gjatësia e IV-së, funksioni kthen vlerën origjinale të pandryshuar. Email-et me tekst të thjeshtë nga para se enkriptimi të ishte aktivizuar kalojnë saktë.
