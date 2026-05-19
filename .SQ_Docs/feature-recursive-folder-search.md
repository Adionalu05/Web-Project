# Implementimi i Veçorisë — Kërkimi Rekursiv i Dosjeve

**Data:** 2026-05-03

---

## Sfondi

Sistemi i dosjeve u prezantua pas takimit me profesorin më 2026-04-07 (shiko `report-2026-04-07.md`). Spec-i i rënë dakord në atë pikë ishte: *"Dosjet tregojnë dokumentet vetëm kur klikohen, pa kërkim rekursiv."* Implementimi i parë përputhte pikërisht me këtë — `WHERE d.folder_id = :folder_id` kthen vetëm dokumentet që qëndrojnë drejtpërdrejt brenda dosjes së klikuar.

Kufizimi bëhet i dukshëm sapo fushëzimi është më shumë se një nivel i thellë. Çdo dokument ruan saktësisht një `folder_id` — ID-n e dosjes në të cilën është vendosur drejtpërdrejt. Nuk ka kolonë për gjyshin, asnjë varg shtegu, asnjë zinxhir gjenealogjik. Pyetja `WHERE d.folder_id = :folder_id` është pra një kontroll i barazisë strikte: përputhet me dokumentet prindi i menjëhershëm i të cilëve është dosja e klikuar dhe asgjë tjetër.

Me pemën `Demo 1 → Demo 1.1 → file.txt`, `file.txt` u fut me `folder_id = Demo 1.1`. Kur përdoruesi klikon `Demo 1`, pyetja ekzekuton `WHERE d.folder_id = Demo 1` — `Demo 1.1` është një vlerë e ndryshme, kështu që `file.txt` përjashtohet. Shiriti anësor tregon `Demo 1` si dosje që përmban nëndosje, përdoruesi pret të shohë gjithçka brenda saj, dhe tabela kthehet bosh. Nuk ka gabim, asnjë paralajmërim — vetëm një rezultat bosh që duket si defekt.

Spec-i i profesorit tha "pa kërkim rekursiv" sepse përdorimi i pritur ishte sipërfaqësor: një nivel dosjesh që veprojnë si kategori. Pema demo me tre nivele ekspozoi që pyetja origjinale thjesht nuk shkallëzohet me fushëzim real. Sapo një përdorues krijon një nëndosje dhe vendos një skedar në të, ai skedar zhduket nga prindi — gjë që është e kundërta e sjelljes së pritur. Kërkimi rekursiv u shtua si shtesë për ta bërë sistemin e dosjeve të sillet ashtu siç do ta supozonte çdo përdorues. Rregullimi është regjistruar në `_debugging.md`.

Skema e tabelës `folders` (`id`, `name`, `user_id`, `parent_id`) është përcaktuar në `presentation_summary.md` nën Database → Skema e Plotë.

---

## Pyetja origjinale

```sql
WHERE d.folder_id = :folder_id
  AND (d.user_id = :user_id OR d.id IN (
        SELECT document_id FROM shares WHERE shared_with_user_id = :user_id2
  ))
```

Filtri i pronësisë + ndarjeve ishte tashmë i saktë (i trashëguar nga i njëjti model i përdorur nëpër të gjitha pyetjet e dokumenteve). Pjesa e vetme e dëmtuar ishte vetë filtri i dosjes.

---

## Rregullimi — CTE rekursiv

Klauzola `WITH RECURSIVE` e SQLite lejon një pyetje të referojë daljen e vet, duke e bërë të mundur kalimin e një peme të ruajtur si listë ngjitur (`parent_id` duke treguar tek `id` në të njëjtën tabelë). Forma standarde nga dokumentacioni i SQLite është:

```sql
WITH RECURSIVE
  tree(x) AS (
    SELECT id FROM nodes WHERE id = :start    -- ankorë
    UNION ALL
    SELECT n.id FROM nodes n
    INNER JOIN tree t ON n.parent_id = t.x    -- hap rekursiv
  )
SELECT * FROM nodes WHERE id IN (SELECT x FROM tree);
```

Implementimi ynë ndjek këtë drejtpërsëdrejti, duke zëvendësuar `folders` me `nodes` dhe `subfolder_ids` me `tree`:

```sql
WITH RECURSIVE subfolder_ids AS (
    SELECT id FROM folders WHERE id = :folder_id
    UNION ALL
    SELECT f.id FROM folders f
    INNER JOIN subfolder_ids s ON f.parent_id = s.id
)
SELECT d.*, c.name as category_name, GROUP_CONCAT(t.name, ', ') as tags
FROM documents d
LEFT JOIN categories c ON d.category_id = c.id
LEFT JOIN document_tags dt ON d.id = dt.document_id
LEFT JOIN tags t ON dt.tag_id = t.id
WHERE d.folder_id IN (SELECT id FROM subfolder_ids)
  AND (d.user_id = :user_id OR d.id IN (
        SELECT document_id FROM shares WHERE shared_with_user_id = :user_id2
  ))
GROUP BY d.id ORDER BY d.uploaded_at DESC
```

**Pas skenës — klikimi i "Demo 1" (id = 5):**
```
subfolder_ids ndërton:
  hapi 1 → {5}       ankorë: Demo 1 vetë
  hapi 2 → {5, 8}    Demo 1.1 ka parent_id = 5, shtuar
  hapi 3 → asnjë rresht   Demo 1.1 është gjethe, cikli përfundon

Pyetja kryesore:
  WHERE d.folder_id IN (5, 8)
  → kthen Demo 1.2, Demo 1.3 (në dosjen 5)
           + Demo 1.1.1, Demo 1.1.2 (në dosjen 8)
```

CTE-të rekursive kanë qenë të disponueshme në SQLite që nga versioni 3.8.3 (2014). Çdo instalim aktual PHP vjen me një version shumë mbi atë.

---

## Shiriti anësor — renderimi i pemës me dhëmbëzim

Shiriti anësor ishte gjithashtu i dëmtuar për dosjet e fushëzuara: një `foreach` i sheshtë renderonte gjithçka në të njëjtin nivel dhëmbëzimi. Rregullimi është një funksion PHP rekursiv që pasqyron të njëjtin kalim të listës ngjitur të përdorur në SQL:

```php
// Renderimi standard i pemës me listë ngjitur — parent_id = NULL do të thotë niveli rrënjë
function renderFolderTree($folders, $parentId = null, $depth = 0) {
    foreach ($folders as $folder) {
        $fp = $folder['parent_id'];
        if (($fp === null || $fp === '') ? $parentId === null : (int)$fp === (int)$parentId) {
            $pad  = $depth * 14; // px dhëmbëzim për nivel
            $icon = $depth === 0 ? '📁' : '📂';
            $id   = (int)$folder['id'];
            $name = htmlspecialchars($folder['name']);
            echo "<div class='folder-item' style='padding-left:{$pad}px'
                       onclick='loadFolder({$id}, this)'>{$icon} {$name}</div>";
            renderFolderTree($folders, $folder['id'], $depth + 1);
        }
    }
}
```

Thirret një herë në `dashboard.php` me grupin e sheshtë tashmë të kthyer nga `$documentHandler->getFolders()` — nuk nevojitet pyetje shtesë.

---

## Seeder demo — `seed_demo.php`

Një seeder është një skript që popullron bazën e të dhënave me të dhëna të njohura, të kontrolluara për testim ose demonstrim. Koncepti është praktikë standarde në kornizat web (Rails `db:seed`, Laravel `DatabaseSeeder`) — implementimi këtu është një skedar i vetëm PHP i ekzekutuar manualisht ndërkohë që je i kyçur.

**Çfarë bën:**
1. Kontrollon për një sesion aktiv — ndalon me ndërtim kyçjeje nëse nuk është i autentifikuar
2. Fshin çdo ekzekutim të mëparshëm: fshin të gjitha dokumentet e shënuara `'Auto-generated demo file.'` dhe emrat e dosjeve përputhëse, duke përfshirë skedarët në disk
3. Fut pemën e dosjeve dhe 8 skedarë `.txt`, secili me `folder_id` duke treguar tek prindi i menjëhershëm

```
📁 Demo Project
  📂 Demo 1
    📂 Demo 1.1
      📄 Demo 1.1.1
      📄 Demo 1.1.2
    📄 Demo 1.2
    📄 Demo 1.3
  📂 Demo 2
    📂 Demo 2.1
      📄 Demo 2.1.1
    📄 Demo 2.2
  📂 Demo 3
    📄 Demo 3.1
    📄 Demo 3.2
```

Pema është projektuar të ushtrojë çdo nivel të pyetjes rekursive:

| Klikimi | Dokumentet e pritura | Çfarë teston |
|---------|---------------------|-------------|
| Demo Project | 8 — të gjitha | Kalim i plotë me 3 nivele |
| Demo 1 | 4 — Demo 1.2, 1.3, 1.1.1, 1.1.2 | Kalim me 2 nivele |
| Demo 1.1 | 2 — Demo 1.1.1, 1.1.2 | Dosje gjethe, pa rekursion |
| Demo 2 | 3 — Demo 2.2, 2.1.1 | Kalim me 2 nivele |
| Demo 2.1 | 1 — Demo 2.1.1 | Dosje gjethe |
| Demo 3 | 2 — Demo 3.1, 3.2 | Dosje e sheshtë, pa nëndosje |

Nëse CTE është e dëmtuar, klikimi i Demo Project kthen 0 dokumente — sinjal i menjëhershëm dëmtimi binar gjatë demo live.

`seed_demo.php` liston në `.gitignore` krahas `config/mail.php` dhe `data/documents.db`. Është vetëm një mjet zhvillimi dhe duhet fshirë para çdo vendosje.

---

## Shtesë — UI i Pemës së Dosjeve me Rënie/Hapje

**Data:** 2026-05-19

### Motivimi

Pyetja rekursive shfaq të gjitha dokumentet në një nënpemë saktë, por vetë shiriti anësor tregonte çdo dosje të sheshtë dhe plotësisht të dukshme gjatë gjithë kohës. Për një pemë me tre nivele fushëzimi kjo bëhet vizualisht e ndërlikuar. Qëllimi ishte të shtohej një shtresë rënie/hapje kështu që vetëm niveli i sipërm është i dukshëm si parazgjedhje, dhe dosjet fëmijë zbulohen kur përdoruesi hap qartë një prind — pa prekur shtresën e të dhënave fare.

### Qasja

Dy veprimet mbi një dosje prindër janë tani të ndara mes dy objekteve të veçanta klikimi:

- **Shigjeta (▶/▼)** — kalon listën e dosjeve fëmijë mes hapur dhe mbyllur
- **Emri i dosjes** — shkakton `loadFolder(id)` dhe ngarkon të gjitha dokumentet rekursivisht, saktësisht si më parë

Dosjet gjethe (pa fëmijë) mbajnë një objektiv të vetëm klikimi dhe një ikonë 📄 për t'i dalluar vizualisht.

### Ndryshimet

**`dashboard.php` — `renderFolderTree()`**

Para renderimit të një dosjeje, funksioni skanon grupin e sheshtë `$folders` për të kontrolluar nëse ndonjë vëlla ka `parent_id === $id`. Nëse po, dosja është prind dhe merr paraqitjen me dy objektiva; përndryshe renditet si element i thjeshtë i klikueshëm.

```php
$hasChildren = false;
foreach ($folders as $f) {
    if ((int)$f['parent_id'] === $id) { $hasChildren = true; break; }
}

if ($hasChildren) {
    echo "<div class='folder-item folder-item--parent' style='padding-left:{$pad}px'>
        <span class='folder-toggle' onclick='toggleFolder({$id}, event)'>▶</span>
        <span onclick='loadFolder({$id}, this.closest(\".folder-item\"))'>{$icon} {$name}</span>
    </div>";
    echo "<div class='folder-children' id='folder-children-{$id}'>";
    renderFolderTree($folders, $folder['id'], $depth + 1);
    echo "</div>";
} else {
    echo "<div class='folder-item' style='padding-left:{$pad}px'
               onclick='loadFolder({$id}, this)'>📄 {$name}</div>";
}
```

Shënim: `this.closest('.folder-item')` kalohet tek `loadFolder` në vend të `this` kështu që theksimi `.active` gjithmonë ulet mbi kontejnerin `.folder-item`, jo mbi `<span>`-in e brendshëm.

**`js/dashboard.js` — `toggleFolder()`**

```js
function toggleFolder(id, e) {
    e.stopPropagation();
    var $children = $('#folder-children-' + id);
    var open = $children.slideToggle(150).is(':visible');
    $(e.currentTarget).text(open ? '▼' : '▶');
}
```

`e.stopPropagation()` parandalon klikun të flluskojë lart tek div `.folder-item`, gjë që përndryshe do të aktivizonte `loadFolder`.

**`css/style.css`**

```css
.folder-item--parent { display: flex; align-items: center; gap: .3rem; }
.folder-toggle { font-size: .6rem; width: 12px; cursor: pointer; color: #7e2a98; }
.folder-item.active .folder-toggle { color: white; }
.folder-children { display: none; }
```

### Çfarë nuk ndryshoi

- `getFolderDocuments()` dhe CTE `WITH RECURSIVE` — e paprekur
- `loadFolder()` në `dashboard.js` — e paprekur
- `api/handle.php` — e paprekur
- Sjellja e theksimit të gjendjes aktive — e njëjtë si më parë, vetëm e ridrejtuar nëpërmjet `.closest()`
