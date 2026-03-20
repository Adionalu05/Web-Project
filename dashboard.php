<?php
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

// Get documents with optional filters
$category_id = $_GET['category_id'] ?? null;
$tag = $_GET['tag'] ?? null;
$search = $_GET['search'] ?? '';

if ($search) {
    $documents = $documentHandler->searchDocuments($search, [
        'category_id' => $category_id,
        'tag' => $tag
    ]);
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
            <div class="widget">
                <h3>Quick Upload</h3>
                <form id="uploadForm" enctype="multipart/form-data">
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
                        <label for="tags">Tags (comma-separated)</label>
                        <input type="text" id="tags" name="tags" placeholder="e.g., important, urgent">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Upload File</button>
                </form>
                <div id="uploadMessage"></div>
            </div>

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
            <h1>My Documents</h1>

            <?php if (count($documents) === 0): ?>
                <div class="empty-state">
                    <p>No documents found. Start by uploading a file!</p>
                </div>
            <?php else: ?>
                <table class="documents-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Tags</th>
                            <th>File Type</th>
                            <th>Size</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['title']); ?></td>
                                <td><?php echo htmlspecialchars($doc['category_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($doc['tags']): ?>
                                        <div class="tags">
                                            <?php foreach (explode(', ', $doc['tags']) as $tag): ?>
                                                <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span>—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo strtoupper($doc['file_format']); ?></td>
                                <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                <td><?php echo formatDate($doc['uploaded_at']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download class="btn btn-small btn-primary">Download</a>
                                    <button class="btn btn-small btn-danger" onclick="deleteDocument(<?php echo $doc['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
    <script>
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }
    </script>
</body>
</html>

<?php
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
