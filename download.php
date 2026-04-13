<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/auth/document_handler.php';

// Only authenticated users can download files
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$documentId = $_GET['doc_id'] ?? null;
if (!$documentId) {
    http_response_code(400);
    echo 'Document ID is required';
    exit;
}

$userId = $auth->getCurrentUserId();

// Allow access if user owns the document OR it has been shared with them
try {
    $stmt = $db->prepare("
        SELECT d.*, d.user_id
        FROM documents d
        WHERE d.id = :id
          AND (
            d.user_id = :uid
            OR d.id IN (SELECT document_id FROM shares WHERE shared_with_user_id = :uid2)
          )
    ");
    $stmt->execute(['id' => $documentId, 'uid' => $userId, 'uid2' => $userId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo 'Server error';
    exit;
}

if (!$document) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

$filePath = $document['file_path'];
if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'File not found on server';
    exit;
}

// Serve the file securely
$mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
$fileName = basename($document['file_name']);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Flush output buffers
while (ob_get_level()) {
    ob_end_clean();
}

readfile($filePath);
exit;
