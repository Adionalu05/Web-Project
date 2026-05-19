# Integrimi i Shërbimeve të Jashtme

---

## Çfarë është një API?

Një **API** (Ndërfaqe e Programimit të Aplikacionit) është një kontratë mes dy programeve që përcakton si mund të komunikojnë mes tyre. Njëra anë ekspozoron një grup URL-sh (pikash fundore) që pranojnë kërkesa në një format të përcaktuar dhe kthejnë përgjigje të strukturuara (zakonisht JSON). Ana tjetër thërret ato URL-t për të marrë të dhëna ose për të aktivizuar veprime pa pasur nevojë të dijë asgjë për kodin e brendshëm pas tyre.

Ky projekt përdor API-t në dy drejtime:
- **Hyrës** — `api/handle.php` ynë ekspozohet si pikë fundore që shfletuesi e thërret nëpërmjet AJAX
- **Dalës** — kodi ynë PHP thërret dy shërbime të jashtme (Claude dhe Gmail)

---

## API-t Jonë i Brendshëm — `api/handle.php`

Çdo kërkesë AJAX nga paneli shkon tek ky skedar i vetëm. Parametri `action` e rrugëzon tek funksioni i duhur:

| Veprimi | Metoda | Çfarë bën |
|---------|--------|-----------|
| `upload` | POST | Valido + ruaj skedarin, ruaj metadatat në DB |
| `get_documents` | GET | Kthe të gjitha dokumentet për përdoruesin aktual (me filtra) |
| `search` | GET | Kërkim me fjalë kyçe + renditje me AI nëpërmjet Claude |
| `delete` | POST | Fshi skedarin dhe rekordin DB (kontroll pronësie) |
| `get_tags` | GET | Kthe të gjitha etiketat |
| `get_categories` | GET | Kthe të gjitha kategoritë |
| `edit_document` | POST | Përditëso titullin, kategorinë, etiketat, përshkrimin |
| `create_folder` | POST | Fut dosje të re për përdoruesin aktual |
| `get_folders` | GET | Kthe të gjitha dosjet për përdoruesin aktual |
| `get_folder_documents` | GET | Kthe dokumentet brenda një dosjeje specifike |
| `share_document` | POST | Nda një dokument me një përdorues tjetër sipas emrit të përdoruesit |
| `get_shared_documents` | GET | Kthe dokumentet e ndara me përdoruesin aktual |

Të gjitha pikat fundore kërkojnë një sesion aktiv — kërkesat e paautentifikuara marrin përgjigje `401`.

---

## API e Jashtme 1 — Anthropic Claude (Kërkim me AI)

**Qëllimi:** Pasi një pyetje kërkimi kthen rezultate nga SQLite, Claude i rirendit sipas rëndësisë semantike kështu që dokumenti më relevant shfaqet i pari — jo vetëm ai me përputhjen më të afërt të fjalës kyçe.

**Skedari:** `auth/document_handler.php` → metoda `aiRerank($query, $documents)`

**Pika fundore:** `https://api.anthropic.com/v1/messages`

**Si funksionon:**
```php
// Rrjedha e thjeshtëzuar brenda aiRerank()
$payload = json_encode([
    'model'    => 'claude-haiku-4-5-20251001',
    'messages' => [[
        'role'    => 'user',
        'content' => "Pyetja e kërkimit: \"$query\"\nDokumentet:\n$docList\nKthe ID-t në rendin e rëndësisë, të ndara me presje."
    ]]
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-api-key: ' . CLAUDE_API_KEY,
    'anthropic-version: 2023-06-01',
    'Content-Type: application/json'
]);
// → Claude kthen "3,1,2" → dokumentet rirenditen sipas kësaj
```

**Fallback i hijshëm:** Nëse `CLAUDE_API_KEY` është bosh ose kërkesa dështon, `aiRerank()` kthen rezultatet origjinale të pandryshura. Kërkimi vazhdon të funksionojë normalisht.

---

## API e Jashtme 2 — Gmail SMTP (Email Rivendosje Fjalëkalimi)

**Qëllimi:** Dorëzo lidhjen e rivendosjes së fjalëkalimit në kutinë e postës së përdoruesit me siguri.

**Skedari:** `auth/email.php` → funksioni `sendEmail($to, $subject, $body)`
**Biblioteka:** PHPMailer (`lib/PHPMailer/`)
**Kredencialet:** `config/mail.php`

**Si funksionon:**
```php
$mail->isSMTP();
$mail->Host       = MAIL_HOST;       // smtp.gmail.com
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = MAIL_PORT;       // 587
$mail->Username   = MAIL_USERNAME;   // Adresa Gmail
$mail->Password   = MAIL_PASSWORD;   // Fjalëkalimi i aplikacionit (jo fjalëkalimi juaj real)
$mail->send();
```

PHPMailer hap një lidhje TLS tek `smtp.gmail.com:587`, autentifikohet dhe dorëzon email-in. Kthen `true` në sukses, `false` në dështim (dështimi regjistrohet në `data/mail.log`).

---

## Si Ruhen Çelësat me Siguri

Çelësat API dhe fjalëkalimet janë kredenciale — nëse rrjedhin, cilido mund të imitojë aplikacionin, të dërgojë email nga llogaria juaj ose të grumbullojë faturën tuaj API.

**Rregulli 1 — Çelësat jetojnë në skedarë konfigurimi, kurrë në kodin e aplikacionit**

| Kredenciali | Ku gjendet | I komituar në git? |
|-------------|-----------|-------------------|
| Adresa Gmail + fjalëkalimi i aplikacionit | `config/mail.php` | ❌ Jo |
| Çelësi API Anthropic | Variabla mjedisi `CLAUDE_API_KEY` | ❌ Jo |

Çelësi Claude lexohet nga mjedisi në kohë ekzekutimi kështu që nuk prek kurrë sistemin e skedarëve:
```php
// config/database.php
define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');
```

**Rregulli 2 — Skedarët e ndjeshëm listohen në `.gitignore`**

`.gitignore` i tregon Git-it të injorojë tërësisht këta skedarë, edhe me `git add .`:
```
config/mail.php
data/documents.db
data/mail.log
uploads/
```

Nëse një çelës do të shtyhej aksidentalisht në një repo publik GitHub, ai do të duhej të revokohej dhe rigenerohej menjëherë. `.gitignore` e bën të pamundur me dizajn.

---

## Demonstrimet

### Demo 1 — Rivendosja e Fjalëkalimit nëpërmjet Email-it

1. Dil → shko tek `login.php` → kliko **"Keni harruar fjalëkalimin?"**
2. Fut adresën email të përdorur në regjistrim → kliko **Dërgo Lidhjen e Rivendosjes**
3. Kontrollo kutinë e postës — email-i arrin nga sistemi me lidhjen e rivendosjes
4. Kliko lidhjen → vendos fjalëkalimin e ri → ridrejtohet tek kyçja me mesazh suksesi
5. Kyçu me fjalëkalimin e ri ✅

**Pas skenës:**
```
forgot_password.php (POST)
  → SHA-256 hash i email-it → kërko përdoruesin sipas email_hash
  → bin2hex(random_bytes(32)) → token 64 karakteresh
  → INSERT në password_resets (skadon pas 1 ore)
  → sendEmail() → PHPMailer → smtp.gmail.com:587 → kutia e postës

reset_password.php (GET ?token=...)
  → valido: token ekziston, used=0, expires_at > TANI

reset_password.php (POST)
  → password_hash() → UPDATE users SET password
  → UPDATE password_resets SET used = 1
  → ridrejto login.php?reset=1
```

---

### Demo 2 — Kërkim i Zgjeruar me AI

1. Ngarko disa dokumente me tituj të ndryshëm (p.sh. "Raporti i Shitjeve Q4", "Kontrata e Serverit 2025", "Biletë Gabimi #42")
2. Kërko për një term të paqartë/konceptual si `financiar` ose `problem infrastrukture`
3. Rezultatet shfaqen të renditura sipas rëndësisë — jo vetëm afërsisë së fjalës kyçe ✅

**Pas skenës:**
```
Formulari i kërkimit (GET ?search=financiar)
  → searchDocuments() → pyetje SQL LIKE → kthehen përputhjet e papërpunuara
  → aiRerank("financiar", $documents)
      → prompt-i i dërguar tek api.anthropic.com/v1/messages
      → Claude kthen: "3,1,2" (ID-t të renditura sipas rëndësisë)
      → dokumentet rirenditen
  → lista e rirenditur dërguar tek paneli
```

Nëse nuk është konfiguruar çelësi API, `aiRerank()` anashkalon thirrjen Claude dhe kthen rezultatet SQL siç janë.

---

## Log Debug — Shërbimet e Jashtme

### ✅ Shtesat PHP mungonin (openssl, curl, sockets)
**Data:** 2026-04-14
**Simptomë:** PHPMailer nuk mund të vendoste lidhje TLS me Gmail. Thirrjet cURL të API-t Claude gjithashtu dështonin në heshtje. `data/mail.log` ishte bosh — email-i nuk ishte provuar kurrë.
**Shkaku rrënjësor:** `openssl`, `curl` dhe `sockets` ishin komantuar të gjitha në `C:\php\php.ini`. PHPMailer kërkon `openssl` për TLS dhe `sockets` si transport. Integrimi Claude kërkon `curl` për kërkesa HTTP dalëse.
**Rregullim:** U hiqën koment të tria rreshtat në `php.ini` dhe serveri u rinis:
```
extension=curl
extension=openssl
extension=sockets
```

---

### ✅ Fjalëkalimi i aplikacionit Gmail u fut me hapësira
**Data:** 2026-04-14
**Simptomë:** Autentikimi tek `smtp.gmail.com:587` dështonte edhe me fjalëkalim të vlefshëm aplikacioni.
**Shkaku rrënjësor:** Gmail e shfaq fjalëkalimin 16-karakterësh të aplikacionit në grupe prej 4 (p.sh. `abcd efgh ijkl mnop`). Nëse kopjohet me hapësira në `config/mail.php`, autentikimi SMTP dështon.
**Rregullim:** Hiq të gjitha hapësirat nga vlera në `config/mail.php`:
```php
define('MAIL_PASSWORD', 'abcdefghijklmnop'); // pa hapësira
```

---

### ✅ Gmail SMTP — konfirmuar të funksionojë
**Data:** 2026-04-14
**Statusi:** Email-i i rivendosjes së fjalëkalimit u dorëzua me sukses nga fillimi në fund. PHPMailer → smtp.gmail.com:587 → kutia e postës konfirmuar.

---

### 🔄 Kërkim AI Claude — pret çelësin API
**Data:** 2026-04-14
**Statusi:** Kodi është implementuar dhe degradohet hijshëm. Pret çelësin API Anthropic për të testuar renditjen live.
