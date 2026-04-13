<?php

/**
 * Nderfaqja per trajtimin e kerkesave AJAX nga 'dashboard.js' dhe faqet e tjera te mbrojtura. Ky file perfshin:
 */


header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/document_handler.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;

// ! Ensure the request comes from within the same origin (not from external domains)
if (!isSameOriginRequest()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

switch ($action) {
    case 'upload':           handleUpload();           break;
    case 'get_documents':    getDocuments();           break;
    case 'search':           searchDocuments();        break;
    case 'delete':           deleteDocument();         break;
    case 'get_tags':         getTags();                break;
    case 'get_categories':   getCategories();          break;
    case 'edit_document':    editDocument();           break;
    case 'create_folder':    createFolder();           break;
    case 'get_folders':      getFolders();             break;
    case 'get_folder_documents': getFolderDocuments(); break;
    case 'share_document':   shareDocument();          break;
    case 'get_shared_documents': getSharedDocuments(); break;
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
    $folder_id = $_POST['folder_id'] ?? null;

    $result = $documentHandler->uploadFile($title, $category_id, $tags, $_FILES['file'], $folder_id);
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
    if (!empty($category_id)) $filters['category_id'] = $category_id;
    if (!empty($tag))         $filters['tag'] = $tag;

    $documents = $documentHandler->searchDocuments($searchTerm, $filters);

    // AI reranking via Claude API (falls back gracefully if unavailable)
    $documents = $documentHandler->aiRerank($searchTerm, $documents);

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

function editDocument() {
    global $documentHandler;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    $id          = $_POST['document_id'] ?? null;
    $title       = $_POST['title'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $tags        = $_POST['tags'] ?? '';
    $description = $_POST['description'] ?? '';

    if (!$id) { echo json_encode(['success' => false, 'error' => 'Document ID required']); return; }

    $result = $documentHandler->updateDocumentMetadata($id, $title, $category_id, $tags, $description);
    echo json_encode($result);
}

function createFolder() {
    global $documentHandler;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    $name     = $_POST['name'] ?? '';
    $parentId = $_POST['parent_id'] ?? null;
    echo json_encode($documentHandler->createFolder($name, $parentId));
}

function getFolders() {
    global $documentHandler;
    echo json_encode(['success' => true, 'folders' => $documentHandler->getFolders()]);
}

function getFolderDocuments() {
    global $documentHandler;
    $folderId = $_GET['folder_id'] ?? null;
    if (!$folderId) { echo json_encode(['success' => false, 'error' => 'Folder ID required']); return; }
    echo json_encode(['success' => true, 'documents' => $documentHandler->getFolderDocuments($folderId)]);
}

function shareDocument() {
    global $documentHandler;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    $documentId     = $_POST['document_id'] ?? null;
    $targetUsername = trim($_POST['username'] ?? '');
    if (!$documentId || !$targetUsername) {
        echo json_encode(['success' => false, 'error' => 'Document ID and username are required']);
        return;
    }
    echo json_encode($documentHandler->shareDocument($documentId, $targetUsername));
}

function getSharedDocuments() {
    global $documentHandler;
    echo json_encode(['success' => true, 'documents' => $documentHandler->getSharedDocuments()]);
}
?>
