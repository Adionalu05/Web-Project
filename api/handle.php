<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/document_handler.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

switch ($action) {
    case 'upload':
        handleUpload();
        break;
    case 'get_documents':
        getDocuments();
        break;
    case 'search':
        searchDocuments();
        break;
    case 'delete':
        deleteDocument();
        break;
    case 'get_tags':
        getTags();
        break;
    case 'get_categories':
        getCategories();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

function handleUpload() {
    global $documentHandler;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'File upload failed']);
        return;
    }

    $title = $_POST['title'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $tags = $_POST['tags'] ?? '';

    $result = $documentHandler->uploadFile($title, $category_id, $tags, $_FILES['file']);
    echo json_encode($result);
}

function getDocuments() {
    global $documentHandler;

    $category_id = $_GET['category_id'] ?? null;
    $tag = $_GET['tag'] ?? null;

    $filters = [];
    if (!empty($category_id)) {
        $filters['category_id'] = $category_id;
    }
    if (!empty($tag)) {
        $filters['tag'] = $tag;
    }

    $documents = $documentHandler->getUserDocuments($filters);
    echo json_encode(['success' => true, 'documents' => $documents]);
}

function searchDocuments() {
    global $documentHandler;

    $searchTerm = $_GET['q'] ?? '';
    $category_id = $_GET['category_id'] ?? null;
    $tag = $_GET['tag'] ?? null;

    if (empty($searchTerm)) {
        echo json_encode(['success' => false, 'error' => 'Search term is required']);
        return;
    }

    $filters = [];
    if (!empty($category_id)) {
        $filters['category_id'] = $category_id;
    }
    if (!empty($tag)) {
        $filters['tag'] = $tag;
    }

    $documents = $documentHandler->searchDocuments($searchTerm, $filters);
    echo json_encode(['success' => true, 'documents' => $documents]);
}

function deleteDocument() {
    global $documentHandler;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }

    $documentId = $_POST['document_id'] ?? null;

    if (!$documentId) {
        echo json_encode(['success' => false, 'error' => 'Document ID is required']);
        return;
    }

    $result = $documentHandler->deleteDocument($documentId);
    echo json_encode($result);
}

function getTags() {
    global $documentHandler;

    $tags = $documentHandler->getAllTags();
    echo json_encode(['success' => true, 'tags' => $tags]);
}

function getCategories() {
    global $documentHandler;

    $categories = $documentHandler->getAllCategories();
    echo json_encode(['success' => true, 'categories' => $categories]);
}
?>
