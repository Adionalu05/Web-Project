# Manual instalimi dhe perdorimi

---

## Instalimi

### 1. Instalo PHP

* Shko tek https://windows.php.net/download/
* Shkarko **Non Thread Safe** — versioni 8.3
* Unzip tek `C:\php`

Konfirmo qe PHP eshte instaluar:
```
php -v
```

---

### 2. Krijo php.ini

Ne folderin `C:\php`, ekzekuto:
```
Copy-Item php.ini-development php.ini
```

---

### 3. Konfiguro php.ini

Hap `C:\php\php.ini` dhe bej ndryshimet e meposhtme.

**Vendos extension_dir me path absolut** (i domosdoshem ne Windows):
```ini
extension_dir = "C:\php\ext"
```

**Aktivizo ekstensionet e nevojshme** — gjej dhe hiq `;` perpara cdo rreshti:
```ini
extension=curl
extension=openssl
extension=sockets
extension=pdo_sqlite
extension=sqlite3
```

**Rrit limitin e upload-it:**
```ini
upload_max_filesize = 10M
post_max_size = 12M
```

Konfirmo qe ekstensionet jane aktive:
```
php -m
```

Duhet te shohesh ne output: `curl`, `openssl`, `PDO`, `pdo_sqlite`, `sqlite3`.

![alt text](media/image.png)

![alt text](media/image-1.png)

---

### 4. Shto PHP ne PATH

Per te ekzekutuar `php` nga cdo folder ne terminal:

* Hap **System Properties** → **Environment Variables**
* Tek **System Variables**, gjej `Path` → **Edit**
* Shto: `C:\php`
* Kliko OK dhe rinis terminalin

Konfirmo:
```
php -v
```

---

### 5. Merr projektin

Klono nga GitHub:
```
git clone https://github.com/username/Web-Project.git
cd Web-Project
```

Ose kopjo folderin e projektit direkt ne `Desktop\Web-Project`.

---

### 6. Konfiguro kredencialet

**Gmail SMTP** — krijo skedarin `config/mail.php` (ky skedar nuk eshte ne git):
```php
<?php
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',  'emailijot@gmail.com');
define('MAIL_PASSWORD',  'apppasswordketu');   // 16 karaktere, pa spaces
define('MAIL_FROM',      'emailijot@gmail.com');
define('MAIL_FROM_NAME', 'File Management System');
```

Per te marre app password nga Gmail:
1. Shko tek myaccount.google.com → Security
2. Aktivizo **2-Step Verification**
3. Kerko "App passwords" → krijo nje per "Mail"
4. Kopjo 16 karakteret **pa spaces**

**Claude API** (opsionale — per AI search):
```
set CLAUDE_API_KEY=sk-ant-...
```
Nese nuk vendoset, search funksionon normalisht pa AI reranking.

---

## Nisja e serverit

Hap terminalin dhe shko tek folderi i projektit:
```
cd C:\Users\User\Desktop\Web-Project
```

Nis serverin:
```
php -S localhost:8000
```

Hap ne browser:
```
http://localhost:8000
```

Per aksesim nga kompjutera te tjere ne te njejtin network (p.sh. gjate prezantimit):
```
php -S 0.0.0.0:8000
```

---

## Demo data (opsionale)

Per te ngarkuar strukturen e foldereve dhe skedaret e testimit per prezantim, ekzekuto me browser ndërkohë që je i loguar:
```
http://localhost:8000/seed_demo.php
```

Krijon:
```
📁 Demo Project
  📂 Demo 1
    📂 Demo 1.1
      📄 Demo 1.1.1, Demo 1.1.2
    📄 Demo 1.2, Demo 1.3
  📂 Demo 2
    📂 Demo 2.1
      📄 Demo 2.1.1
    📄 Demo 2.2
  📂 Demo 3
    📄 Demo 3.1, Demo 3.2
```

Mund te ekzekutohet me shume here — fshin te dhenat e meparshme dhe i rinderton.

---

## Probleme te zakonshme

**`Database connection error: could not find driver`**  
`pdo_sqlite` ose `extension_dir` nuk jane konfiguruar sic duhet ne `php.ini`. Kontrollo qe `extension_dir` ka path absolut dhe nuk eshte me `;` perpara.

**`localhost can't currently handle this request`**  
`openssl`, `curl` ose `sockets` jane te çaktivizuara. Hiq `;` perpara te treja ne `php.ini` dhe rinis serverin.

**Skedari nuk ngarkohet (mbi 2 MB)**  
`upload_max_filesize` eshte ende 2M. Konfirmo qe `upload_max_filesize = 10M` dhe `post_max_size = 12M` jane vendosur dhe serveri eshte rinis.

**SMTP authentication failed**  
App password eshte kopjuar me spaces. Gmail e shfaq ne grupe prej 4 karakteresh — vendose pa spaces ne `config/mail.php`.

Shiko `debug-log.md` per historikun e plote te problemeve dhe zgjidhjeve.
