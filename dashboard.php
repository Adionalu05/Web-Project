<?php

/**
 * Perdor nocionin AJAX (Asynchronous JavaScript and XML) nepermjet jQuery per te bere kerkesat ne background e cila lejon kryerjen e kerkesave pa bere reload faqen. Komunikon me 'handle.php'. Ky file perfshin:
*
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/auth/document_handler.php';

if (!$auth->isAuthenticated()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user = $auth->getCurrentUser();
$categories = $documentHandler->getAllCategories();
$tags = $documentHandler->getAllTags();
$folders = $documentHandler->getFolders();

// Get documents with optional filters
$category_id = $_GET['category_id'] ?? null;
$tag = $_GET['tag'] ?? null;
$search = $_GET['search'] ?? '';

if ($search) {
    $documents = $documentHandler->searchDocuments($search, [
        'category_id' => $category_id,
        'tag' => $tag
    ]);
    // AI reranking applied inside searchDocuments via api, but also apply here for server-side render
    $documents = $documentHandler->aiRerank($search, $documents);
} else {
    $documents = $documentHandler->getUserDocuments([
        'category_id' => $category_id,
        'tag' => $tag
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - File Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">
            <h2>File Management System</h2>
        </div>
        <div class="navbar-menu">
            <span>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</span>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="sidebar">
            <!-- Upload Widget -->
            <div class="widget">
                <h3>Quick Upload</h3>
                <form id="uploadForm" enctype="multipart/form-data" method="POST">
                    <div class="form-group">
                        <label for="title">Document Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="file">Select File</label>
                        <input type="file" id="file" name="file" required>
                        <small>Max size: 10 MB</small>
                    </div>

                    <div class="form-group">
                        <label for="category_upload">Category</label>
                        <select id="category_upload" name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="upload_folder">Folder (optional)</label>
                        <select id="upload_folder" name="folder_id">
                            <option value="">No Folder</option>
                            <?php foreach ($folders as $folder): ?>
                                <option value="<?php echo $folder['id']; ?>">
                                    📁 <?php echo htmlspecialchars($folder['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tags">Tags (comma-separated)</label>
                        <input type="text" id="tags" name="tags" placeholder="e.g., important, urgent">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Upload File</button>
                </form>
                <div id="uploadMessage"></div>
            </div>

            <!-- Folders Widget -->
            <div class="widget">
                <h3>Folders</h3>
                <div id="folderList">
                    <div class="folder-item" onclick="loadAllDocuments()">
                        📂 All Documents
                    </div>
                    <?php
                    // Build a tree and render it with indentation so nested folders are visible
                    function renderFolderTree($folders, $parentId = null, $depth = 0) {
                        foreach ($folders as $folder) {
                            $fp = $folder['parent_id'];
                            if (($fp === null || $fp === '') ? $parentId === null : (int)$fp === (int)$parentId) {
                                $pad   = $depth * 14; // px indent per level
                                $icon  = $depth === 0 ? '📁' : '📂';
                                $id    = (int)$folder['id'];
                                $name  = htmlspecialchars($folder['name']);
                                echo "<div class='folder-item' style='padding-left:{$pad}px' onclick='loadFolder({$id}, this)'>{$icon} {$name}</div>";
                                renderFolderTree($folders, $folder['id'], $depth + 1);
                            }
                        }
                    }
                    renderFolderTree($folders);
                    ?>
                </div>
                <button class="btn btn-secondary" style="width:100%;margin-top:.75rem;font-size:.85rem;" onclick="openNewFolderModal()">
                    + New Folder
                </button>
            </div>

            <!-- Filters Widget -->
            <div class="widget">
                <h3>Quick Filters</h3>
                <form method="GET" id="filterForm">
                    <div class="form-group">
                        <label for="filter_category">By Category</label>
                        <select id="filter_category" name="category_id" onchange="applyFilters()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="filter_tag">By Tag</label>
                        <select id="filter_tag" name="tag" onchange="applyFilters()">
                            <option value="">All Tags</option>
                            <?php foreach ($tags as $t): ?>
                                <option value="<?php echo htmlspecialchars($t['name']); ?>" <?php echo ($tag == $t['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="search">Search Documents</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title...">
                        <button type="submit" class="btn btn-secondary" style="margin-top: 8px; width: 100%;">Search</button>
                    </div>

                    <a href="dashboard.php" class="btn btn-secondary" style="display: block; text-align: center; margin-top: 8px;">Clear Filters</a>
                </form>
            </div>
        </div>

        <div class="main-content">
            <!-- Tab Bar -->
            <div class="tab-bar">
                <button class="tab-btn active" id="tabMyDocs" onclick="switchTab('my')">My Documents</button>
                <button class="tab-btn" id="tabShared" onclick="switchTab('shared')">Shared with Me</button>
            </div>

            <!-- My Documents -->
            <div id="panelMyDocs">
                <div id="documentsContainer">
                    <?php echo renderDocumentsTable($documents, $categories); ?>
                </div>
            </div>

            <!-- Shared with Me -->
            <div id="panelShared" style="display:none;">
                <div id="sharedContainer">
                    <p style="color:#666;padding:1rem 0;">Loading shared documents...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Document Modal -->
    <div class="modal-overlay" id="editModalOverlay" onclick="closeModal('editModalOverlay')"></div>
    <div class="modal-box" id="editModal">
        <h3>Edit Document</h3>
        <form id="editForm">
            <input type="hidden" id="edit_doc_id" name="document_id">
            <div class="form-group">
                <label>Title</label>
                <input type="text" id="edit_title" name="title" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id="edit_category" name="category_id">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tags (comma-separated)</label>
                <input type="text" id="edit_tags" name="tags">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="edit_description" name="description" rows="3"></textarea>
            </div>
            <div style="display:flex;gap:.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModalOverlay')">Cancel</button>
            </div>
        </form>
        <div id="editMessage"></div>
    </div>

    <!-- Share Document Modal -->
    <div class="modal-overlay" id="shareModalOverlay" onclick="closeModal('shareModalOverlay')"></div>
    <div class="modal-box" id="shareModal">
        <h3>Share Document</h3>
        <p style="color:#666;margin-bottom:1rem;font-size:.9rem;">Enter the username of the person you want to share with.</p>
        <input type="hidden" id="share_doc_id">
        <div class="form-group">
            <label>Username</label>
            <input type="text" id="share_username" placeholder="Enter username">
        </div>
        <div style="display:flex;gap:.5rem;">
            <button class="btn btn-primary" style="flex:1;" onclick="submitShare()">Share</button>
            <button class="btn btn-secondary" onclick="closeModal('shareModalOverlay')">Cancel</button>
        </div>
        <div id="shareMessage"></div>
    </div>

    <!-- New Folder Modal -->
    <div class="modal-overlay" id="folderModalOverlay" onclick="closeModal('folderModalOverlay')"></div>
    <div class="modal-box" id="folderModal">
        <h3>New Folder</h3>
        <div class="form-group">
            <label>Folder Name</label>
            <input type="text" id="new_folder_name" placeholder="e.g., Projects">
        </div>
        <div style="display:flex;gap:.5rem;">
            <button class="btn btn-primary" style="flex:1;" onclick="submitNewFolder()">Create</button>
            <button class="btn btn-secondary" onclick="closeModal('folderModalOverlay')">Cancel</button>
        </div>
        <div id="folderMessage"></div>
    </div>

    <!-- jQuery -->
    <script src="js/jquery.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>

<?php
function getFileIcon($ext) {
    $icons = [
        'pdf'  => '📄',
        'doc'  => '📝',
        'docx' => '📝',
        'xls'  => '📊',
        'xlsx' => '📊',
        'txt'  => '📃',
        'jpg'  => '🖼️',
        'jpeg' => '🖼️',
        'png'  => '🖼️',
    ];
    return $icons[strtolower($ext)] ?? '📎';
}

function renderDocumentsTable($documents, $categories) {
    if (count($documents) === 0) {
        return '<div class="empty-state"><p>No documents found. Start by uploading a file!</p></div>';
    }

    $html = '<table class="documents-table"><thead><tr>
        <th>Type</th><th>Title</th><th>Category</th><th>Tags</th><th>Size</th><th>Uploaded</th><th>Actions</th>
    </tr></thead><tbody>';

    foreach ($documents as $doc) {
        $icon = getFileIcon($doc['file_format']);
        $catName = htmlspecialchars($doc['category_name'] ?? 'N/A');
        $title = htmlspecialchars($doc['title']);
        $tagsHtml = '';
        if ($doc['tags']) {
            foreach (explode(', ', $doc['tags']) as $t) {
                $tagsHtml .= '<span class="tag">' . htmlspecialchars(trim($t)) . '</span>';
            }
            $tagsHtml = '<div class="tags">' . $tagsHtml . '</div>';
        } else {
            $tagsHtml = '<span>—</span>';
        }

        $tagsRaw = htmlspecialchars($doc['tags'] ?? '', ENT_QUOTES);
        $desc = htmlspecialchars($doc['description'] ?? '', ENT_QUOTES);
        $catId = (int)($doc['category_id'] ?? 0);

        $html .= "<tr>
            <td style='font-size:1.4rem;text-align:center;'>{$icon}</td>
            <td>{$title}</td>
            <td>{$catName}</td>
            <td>{$tagsHtml}</td>
            <td>" . formatFileSize($doc['file_size']) . "</td>
            <td>" . formatDate($doc['uploaded_at']) . "</td>
            <td>
                <a href='download.php?doc_id={$doc['id']}' class='btn btn-small btn-primary'>Download</a>
                <button class='btn btn-small btn-secondary' onclick='openEditModal({$doc['id']}, \"{$title}\", {$catId}, \"{$tagsRaw}\", \"{$desc}\")'>Edit</button>
                <button class='btn btn-small btn-success' onclick='openShareModal({$doc['id']})'>Share</button>
                <button class='btn btn-small btn-danger' onclick='deleteDocument({$doc['id']})'>Delete</button>
            </td>
        </tr>";
    }

    $html .= '</tbody></table>';
    return $html;
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M d, Y H:i');
}
?>
