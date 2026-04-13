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

$document = $documentHandler->getDocumentById($documentId);

if (!$document) {
    http_response_code(404);
    echo 'Document not found';
    exit;
}

// Enforce ownership
if ($document['user_id'] !== $auth->getCurrentUserId()) {
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
