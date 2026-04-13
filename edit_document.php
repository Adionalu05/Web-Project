<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/auth/document_handler.php';

if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$documentId = $_GET['doc_id'] ?? null;
if (!$documentId) {
    header('Location: dashboard.php');
    exit;
}

$document = $documentHandler->getDocumentById($documentId);

if (!$document || $document['user_id'] !== $auth->getCurrentUserId()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $tags = $_POST['tags'] ?? '';
    $description = $_POST['description'] ?? '';

    $result = $documentHandler->updateDocumentMetadata($documentId, $title, $category_id, $tags, $description);

    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
        // Refresh document data
        $document = $documentHandler->getDocumentById($documentId);
    } else {
        $message = $result['error'];
        $messageType = 'error';
    }
}

$categories = $documentHandler->getAllCategories();
$tags = $documentHandler->getAllTags();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Document - File Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Edit Document</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($document['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($document['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tags">Tags (comma-separated)</label>
                    <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($document['tags'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($document['description'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="dashboard.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
