# Feature Implementation — Recursive Folder Search

**Date:** 2026-05-03

---

## Background

The folder system was introduced following the professor meeting on 2026-04-07 (see `report-2026-04-07.md`). The agreed spec at that point was: *"Folder show documents only when clicked, no recursive search."* The first implementation matched that exactly — `WHERE d.folder_id = :folder_id` returns only documents sitting directly inside the clicked folder.

The limitation becomes visible as soon as nesting is more than one level deep. Each document stores exactly one `folder_id` — the ID of the folder it was directly placed in. There is no column for grandparent, no path string, no ancestry chain. The query `WHERE d.folder_id = :folder_id` is therefore a strict equality check: it matches documents whose immediate parent is the clicked folder and nothing else.

With the tree `Demo 1 → Demo 1.1 → file.txt`, `file.txt` was inserted with `folder_id = Demo 1.1`. When the user clicks `Demo 1`, the query runs `WHERE d.folder_id = Demo 1` — `Demo 1.1` is a different value, so `file.txt` is excluded. The sidebar shows `Demo 1` as a folder containing subfolders, the user expects to see everything inside it, and the table comes back empty. There is no error, no warning — just a blank result that looks like a bug.

The professor spec said "no recursive search" because the expected use was shallow: one level of folders acting as categories. The three-level demo tree exposed that the original query simply does not scale to real nesting. Once a user creates a subfolder and puts a file in it, that file disappears from the parent — which is the opposite of expected behaviour. Recursive search was added as an extension to make the folder system behave the way any user would assume it does. The fix is logged in `debug-log.md`.

The `folders` table schema (`id`, `name`, `user_id`, `parent_id`) is defined in `DMS_php.md` under Database → Full Schema.

---

## The original query

```sql
WHERE d.folder_id = :folder_id
  AND (d.user_id = :user_id OR d.id IN (
        SELECT document_id FROM shares WHERE shared_with_user_id = :user_id2
  ))
```

The ownership + shares filter was already correct (carried over from the same pattern used across all document queries — see `external-services.md`, Internal API table, `get_folder_documents`). The only broken part was the folder filter itself.

---

## The fix — recursive CTE

SQLite's `WITH RECURSIVE` clause lets a query reference its own output, making it possible to walk a tree stored as an adjacency list (`parent_id` pointing back to `id` in the same table). The standard form from the SQLite documentation is:

```sql
WITH RECURSIVE
  tree(x) AS (
    SELECT id FROM nodes WHERE id = :start    -- anchor
    UNION ALL
    SELECT n.id FROM nodes n
    INNER JOIN tree t ON n.parent_id = t.x    -- recursive step
  )
SELECT * FROM nodes WHERE id IN (SELECT x FROM tree);
```

Our implementation follows this directly, substituting `folders` for `nodes` and `subfolder_ids` for `tree`:

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

**Behind the scenes — clicking "Demo 1" (id = 5):**
```
subfolder_ids builds:
  step 1 → {5}       anchor: Demo 1 itself
  step 2 → {5, 8}    Demo 1.1 has parent_id = 5, added
  step 3 → no rows   Demo 1.1 is a leaf, loop ends

Main query:
  WHERE d.folder_id IN (5, 8)
  → returns Demo 1.2, Demo 1.3 (in folder 5)
           + Demo 1.1.1, Demo 1.1.2 (in folder 8)
```

Recursive CTEs have been available in SQLite since version 3.8.3 (2014). Any current PHP installation ships with a version well above that.

---

## Sidebar — rendering the tree with indentation

The sidebar was also broken for nested folders: a flat `foreach` rendered everything at the same indent level. The fix is a recursive PHP function that mirrors the same adjacency-list traversal used in SQL — a standard pattern for rendering hierarchical data from a flat array:

```php
// Standard adjacency-list tree render — parent_id = NULL means root level
function renderFolderTree($folders, $parentId = null, $depth = 0) {
    foreach ($folders as $folder) {
        $fp = $folder['parent_id'];
        if (($fp === null || $fp === '') ? $parentId === null : (int)$fp === (int)$parentId) {
            $pad  = $depth * 14; // px indent per level
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

Called once in `dashboard.php` with the flat array already returned by `$documentHandler->getFolders()` — no extra query needed.

---

## Demo seeder — `seed_demo.php`

A seeder is a script that populates the database with known, controlled data for testing or demonstration. The concept is standard practice in web frameworks (Rails `db:seed`, Laravel `DatabaseSeeder`) — the implementation here is a single PHP file run manually while logged in.

**What it does:**
1. Checks for an active session — stops with a login prompt if not authenticated
2. Wipes any previous run: deletes all documents marked `'Auto-generated demo file.'` and matching folder names, including files on disk
3. Inserts the folder tree and 8 `.txt` files, each with a `folder_id` pointing to its immediate parent

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

The tree is designed to exercise every level of the recursive query:

| Click | Expected documents | What it tests |
|-------|--------------------|---------------|
| Demo Project | 8 — all | Full 3-level traversal |
| Demo 1 | 4 — Demo 1.2, 1.3, 1.1.1, 1.1.2 | 2-level traversal |
| Demo 1.1 | 2 — Demo 1.1.1, 1.1.2 | Leaf folder, no recursion |
| Demo 2 | 3 — Demo 2.2, 2.1.1 | 2-level traversal |
| Demo 2.1 | 1 — Demo 2.1.1 | Leaf folder |
| Demo 3 | 2 — Demo 3.1, 3.2 | Flat folder, no subfolders |

If the CTE is broken, clicking Demo Project returns 0 documents — immediate binary fail signal during the live demo.

`seed_demo.php` is listed in `.gitignore` alongside `config/mail.php` and `data/documents.db` (see `.gitignore`). It is a development tool only and should be deleted before any deployment.
