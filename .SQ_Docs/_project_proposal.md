# Propozimi i Projektit

## 1. Pasqyrë e Projektit
Ky Sistem i Menaxhimit të Dokumenteve (SMD) ofron një aplikacion web të sigurt ku përdoruesit mund të ngarkojnë, menaxhojnë dhe shkarkojnë skedarë duke i mbajtur dokumentet private dhe të organizuara. Përfshin autentifikim të përdoruesit, menaxhim të metadatave të skedarëve (kategori + etiketa), kërkim/filtrim dhe kontroll aksesi.

### Objektivat Kryesore
- Të lejojë përdoruesit e regjistruar të ngarkojnë dhe menaxhojnë dokumentet e tyre.
- Të ruajë dokumentet me siguri në server dhe metadatat në një bazë të dhënash relacionale.
- Të mbrojë informacionin e ndjeshëm të përdoruesit me hash/enkriptim.
- Të ofrojë aftësi kërkimi dhe filtrimi.
- Të parandalojë aksesin e paautorizuar në skedarë dhe faqet e aplikacionit.

## 2. Shtrirja
### Kërkesat Funksionale Themelore
- Regjistrimi, kyçja dhe menaxhimi i sesionit të përdoruesit
- Ngarkimi dhe shkarkimi i sigurt i skedarëve
- Operacione CRUD mbi metadatat e dokumenteve
- Kërkim dhe filtrim sipas kategorisë, etiketave dhe titullit
- Kontrolli i aksesit bazuar në rol (pronësia e dokumenteve për çdo përdorues)

### Kërkesat Jo-Funksionale
- Përdorimi i HTML/CSS/JavaScript (AJAX/jQuery) në anën e klientit
- Përdorimi i PHP + SQLite në anën e serverit
- Validimi i hyrjeve dhe mbrojtja ndaj injektimit SQL
- Ruajtja e një paraqitjeje UI të pastër dhe responsive

### Paraqitja e UI-it (Skicë)
- **Shiriti i navigimit** (lart): titulli i aplikacionit, përshëndetja, dalja
- **Shiriti anësor** (majtas): formulari i ngarkimit + kontrollet e filtrimit
- **Përmbajtja kryesore** (djathtas): tabelë e renditshme e dokumenteve me veprime (shkarkim, editim, fshirje)
- **Formularët** janë projektuar të jenë të përshtatshëm për pajisje mobile duke përdorur CSS responsive

## 3. Arkitektura
- Ana e klientit: HTML/CSS i thjeshtë për paraqitje, JavaScript (Fetch/AJAX dhe jQuery) për operacione dinamike.
- Ana e serverit: skriptet PHP trajtojnë rrugëzimin, autentifikimin dhe ndërveprimin me bazën e të dhënave.
- Baza e të dhënave: skedari SQLite i ruajtur në `/data/documents.db`. Testimi fillestar u bë në mySQL.

### Faqet
- `index.php` — faqja kryesore / ndërtim hyrjes
- `register.php` — regjistrimi i përdoruesit
- `login.php` — kyçja e përdoruesit
- `dashboard.php` — menaxhimi i skedarëve + kërkimi
- `download.php` — shkarkimi i sigurt i dokumenteve (kontroll autorizimi)
- `edit_document.php` — editimi i metadatave të dokumentit

## Harta e Faqeve / Rrjedha e Faqeve
1. Përdoruesi arrin tek `index.php` (publike)
2. Përdoruesi regjistrohet nëpërmjet `register.php` ose kyçet nëpërmjet `login.php`
3. Pas kyçjes, përdoruesi ridrejtohet tek `dashboard.php`
4. Nga paneli, përdoruesi mund të ngarkojë, kërkojë, filtrojë, shkarkojë, editojë dhe fshijë dokumente
5. `download.php` siguron që vetëm pronari i dokumentit mund të aksesojë skedarin

## 4. Konsideratat e Sigurisë
- Fjalëkalimet e hash-uara duke përdorur `password_hash()` (bcrypt).
- Deklaratat e përgatitura të përdorura për SQL (PDO me parametra të lidhur).
- Ngarkesat e skedarëve të validuara për madhësi dhe shtesa të lejuara.
- Ngarkesat e ruajtura jashtë kodit publik (`/uploads`), dhe aksesi i drejtpërdrejtë është i kontrolluar.
- Sesionet janë ruajtur në bazën e të dhënave me një token sesioni.

## 5. Vendosja
1. Sigurohu që PHP 7.4+ dhe shtesa `pdo_sqlite` janë aktivizuar.
2. Sigurohu që `/data/` dhe `/uploads/` janë të shkrueshme nga serveri web.
3. Drejtoje rrënjën e serverit web tek dosja e projektit.
4. Vizito `http://localhost/` dhe regjistro një përdorues.

---

*Përditësuar për herë të fundit: Mars 2026*
