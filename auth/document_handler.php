<?php
require_once __DIR__ . '/../config/database.php';

class DocumentHandler {
    private $db;
    private $auth;

    public function __construct($db, $auth) {
        $this->db = $db;
        $this->auth = $auth;
    }

    /**
     * Upload a file
     */
    public function uploadFile($title, $category_id, $tags, $file) {
        $userId = $this->auth->getCurrentUserId();

        if (!$userId) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }

        // Validate file
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'File size exceeds maximum limit of 10 MB'];
        }

        if ($file['size'] <= 0) {
            return ['success' => false, 'error' => 'Invalid file'];
        }

        // Get file extension
        $fileName = basename($file['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, ALLOWED_FILE_TYPES)) {
            return ['success' => false, 'error' => 'File type not allowed. Allowed types: ' . implode(', ', ALLOWED_FILE_TYPES)];
        }

        // Validate title
        if (empty($title)) {
            return ['success' => false, 'error' => 'Document title is required'];
        }

        try {
            // Create unique file path
            $uploadDir = __DIR__ . '/../uploads';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uniqueFileName = uniqid('doc_') . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . '/' . $uniqueFileName;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return ['success' => false, 'error' => 'Failed to upload file'];
            }

            // Save document metadata to database
            $stmt = $this->db->prepare("
                INSERT INTO documents (user_id, title, category_id, file_path, file_name, file_size, file_format, description)
                VALUES (:user_id, :title, :category_id, :file_path, :file_name, :file_size, :file_format, :description)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'title' => $title,
                'category_id' => $category_id ?: null,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $file['size'],
                'file_format' => $fileExtension,
                'description' => $_POST['description'] ?? null
            ]);

            $documentId = $this->db->lastInsertId();

            // Add tags
            if (!empty($tags)) {
                $this->addTagsToDocument($documentId, $tags);
            }

            return ['success' => true, 'message' => 'File uploaded successfully', 'document_id' => $documentId];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Add tags to a document
     */
    private function addTagsToDocument($documentId, $tags) {
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }

        foreach ($tags as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;

            try {
                // Get or create tag
                $stmt = $this->db->prepare("SELECT id FROM tags WHERE name = :name");
                $stmt->execute(['name' => $tagName]);
                $tag = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$tag) {
                    $stmt = $this->db->prepare("INSERT INTO tags (name) VALUES (:name)");
                    $stmt->execute(['name' => $tagName]);
                    $tagId = $this->db->lastInsertId();
                } else {
                    $tagId = $tag['id'];
                }

                // Link tag to document
                $stmt = $this->db->prepare("
                    INSERT OR IGNORE INTO document_tags (document_id, tag_id)
                    VALUES (:document_id, :tag_id)
                ");
                $stmt->execute(['document_id' => $documentId, 'tag_id' => $tagId]);
            } catch (Exception $e) {
                // Continue adding other tags
            }
        }
    }

    /**
     * Get documents for current user
     */
    public function getUserDocuments($filters = []) {
        $userId = $this->auth->getCurrentUserId();

        if (!$userId) {
            return [];
        }

        try {
            $query = "
                SELECT d.*, c.name as category_name,
                       GROUP_CONCAT(t.name, ', ') as tags
                FROM documents d
                LEFT JOIN categories c ON d.category_id = c.id
                LEFT JOIN document_tags dt ON d.id = dt.document_id
                LEFT JOIN tags t ON dt.tag_id = t.id
                WHERE d.user_id = :user_id
            ";

            if (!empty($filters['category_id'])) {
                $query .= " AND d.category_id = :category_id";
            }

            if (!empty($filters['tag'])) {
                $query .= "
                    AND d.id IN (
                        SELECT dt.document_id FROM document_tags dt
                        JOIN tags t ON dt.tag_id = t.id
                        WHERE t.name = :tag
                    )
                ";
            }

            $query .= " GROUP BY d.id ORDER BY d.uploaded_at DESC";

            $stmt = $this->db->prepare($query);
            $params = ['user_id' => $userId];

            if (!empty($filters['category_id'])) {
                $params['category_id'] = $filters['category_id'];
            }

            if (!empty($filters['tag'])) {
                $params['tag'] = $filters['tag'];
            }

            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Delete a document
     */
    public function deleteDocument($documentId) {
        $userId = $this->auth->getCurrentUserId();

        if (!$userId) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }

        try {
            // Check if document belongs to user
            $stmt = $this->db->prepare("SELECT file_path FROM documents WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $documentId, 'user_id' => $userId]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document) {
                return ['success' => false, 'error' => 'Document not found'];
            }

            // Delete file from server
            if (file_exists($document['file_path'])) {
                unlink($document['file_path']);
            }

            // Delete from database
            $stmt = $this->db->prepare("DELETE FROM documents WHERE id = :id");
            $stmt->execute(['id' => $documentId]);

            return ['success' => true, 'message' => 'Document deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get all tags
     */
    public function getAllTags() {
        try {
            $stmt = $this->db->query("SELECT id, name FROM tags ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get all categories
     */
    public function getAllCategories() {
        try {
            $stmt = $this->db->query("SELECT id, name, description FROM categories ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Search documents
     */
    public function searchDocuments($searchTerm, $filters = []) {
        $userId = $this->auth->getCurrentUserId();

        if (!$userId) {
            return [];
        }

        try {
            $query = "
                SELECT d.*, c.name as category_name,
                       GROUP_CONCAT(t.name, ', ') as tags
                FROM documents d
                LEFT JOIN categories c ON d.category_id = c.id
                LEFT JOIN document_tags dt ON d.id = dt.document_id
                LEFT JOIN tags t ON dt.tag_id = t.id
                WHERE d.user_id = :user_id
                AND (d.title LIKE :search OR d.description LIKE :search)
            ";

            if (!empty($filters['category_id'])) {
                $query .= " AND d.category_id = :category_id";
            }

            if (!empty($filters['tag'])) {
                $query .= "
                    AND d.id IN (
                        SELECT dt.document_id FROM document_tags dt
                        JOIN tags t ON dt.tag_id = t.id
                        WHERE t.name = :tag
                    )
                ";
            }

            $query .= " GROUP BY d.id ORDER BY d.uploaded_at DESC";

            $stmt = $this->db->prepare($query);
            $params = [
                'user_id' => $userId,
                'search' => '%' . $searchTerm . '%'
            ];

            if (!empty($filters['category_id'])) {
                $params['category_id'] = $filters['category_id'];
            }

            if (!empty($filters['tag'])) {
                $params['tag'] = $filters['tag'];
            }

            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

$documentHandler = new DocumentHandler($db, $auth);
?>
