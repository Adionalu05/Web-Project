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
    public function uploadFile($title, $category_id, $tags, $file, $folder_id = null) {
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
                INSERT INTO documents (user_id, title, category_id, file_path, file_name, file_size, file_format, description, folder_id)
                VALUES (:user_id, :title, :category_id, :file_path, :file_name, :file_size, :file_format, :description, :folder_id)
            ");
            $stmt->execute([
                'user_id'     => $userId,
                'title'       => $title,
                'category_id' => $category_id ?: null,
                'file_path'   => $filePath,
                'file_name'   => $fileName,
                'file_size'   => $file['size'],
                'file_format' => $fileExtension,
                'description' => $_POST['description'] ?? null,
                'folder_id'   => $folder_id ?: null,
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
     * Get a document by ID (with tags)
     */
    public function getDocumentById($documentId) {
        $userId = $this->auth->getCurrentUserId();

        if (!$userId) {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT d.*, GROUP_CONCAT(t.name, ', ') as tags
                FROM documents d
                LEFT JOIN document_tags dt ON d.id = dt.document_id
                LEFT JOIN tags t ON dt.tag_id = t.id
                WHERE d.id = :id AND d.user_id = :user_id
                GROUP BY d.id"
            );
            $stmt->execute(['id' => $documentId, 'user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Update document metadata (title/category/tags/description)
     */
    public function updateDocumentMetadata($documentId, $title, $category_id, $tags, $description) {
        $userId = $this->auth->getCurrentUserId();

        if (!$userId) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }

        if (empty($title)) {
            return ['success' => false, 'error' => 'Title is required'];
        }

        try {
            // Ensure document belongs to user
            $stmt = $this->db->prepare("SELECT id FROM documents WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $documentId, 'user_id' => $userId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'error' => 'Document not found'];
            }

            // Update document entry
            $stmt = $this->db->prepare("UPDATE documents SET title = :title, category_id = :category_id, description = :description, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([
                'id' => $documentId,
                'title' => $title,
                'category_id' => $category_id ?: null,
                'description' => $description
            ]);

            // Reset tags
            $stmt = $this->db->prepare("DELETE FROM document_tags WHERE document_id = :document_id");
            $stmt->execute(['document_id' => $documentId]);

            if (!empty($tags)) {
                $this->addTagsToDocument($documentId, $tags);
            }

            return ['success' => true, 'message' => 'Document updated successfully'];
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
     * Create a folder
     */
    public function createFolder($name, $parentId = null) {
        $userId = $this->auth->getCurrentUserId();
        if (!$userId) return ['success' => false, 'error' => 'User not authenticated'];
        if (empty(trim($name))) return ['success' => false, 'error' => 'Folder name is required'];

        try {
            $stmt = $this->db->prepare("INSERT INTO folders (name, user_id, parent_id) VALUES (:name, :user_id, :parent_id)");
            $stmt->execute(['name' => trim($name), 'user_id' => $userId, 'parent_id' => $parentId ?: null]);
            return ['success' => true, 'folder_id' => $this->db->lastInsertId(), 'message' => 'Folder created'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get all folders for current user
     */
    public function getFolders() {
        $userId = $this->auth->getCurrentUserId();
        if (!$userId) return [];
        try {
            $stmt = $this->db->prepare("SELECT * FROM folders WHERE user_id = :user_id ORDER BY name");
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get documents inside a folder (owned + shared with user)
     */
    public function getFolderDocuments($folderId) {
        $userId = $this->auth->getCurrentUserId();
        if (!$userId) return [];
        try {
            $stmt = $this->db->prepare("
                SELECT d.*, c.name as category_name, GROUP_CONCAT(t.name, ', ') as tags
                FROM documents d
                LEFT JOIN categories c ON d.category_id = c.id
                LEFT JOIN document_tags dt ON d.id = dt.document_id
                LEFT JOIN tags t ON dt.tag_id = t.id
                WHERE d.folder_id = :folder_id
                  AND (d.user_id = :user_id OR d.id IN (
                        SELECT document_id FROM shares WHERE shared_with_user_id = :user_id2
                  ))
                GROUP BY d.id ORDER BY d.uploaded_at DESC
            ");
            $stmt->execute(['folder_id' => $folderId, 'user_id' => $userId, 'user_id2' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Share a document with another user by username
     */
    public function shareDocument($documentId, $targetUsername) {
        $userId = $this->auth->getCurrentUserId();
        if (!$userId) return ['success' => false, 'error' => 'User not authenticated'];

        try {
            // Verify ownership
            $stmt = $this->db->prepare("SELECT id FROM documents WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $documentId, 'user_id' => $userId]);
            if (!$stmt->fetch()) return ['success' => false, 'error' => 'Document not found'];

            // Resolve target user
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute(['username' => $targetUsername]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) return ['success' => false, 'error' => 'User not found'];
            if ($target['id'] == $userId) return ['success' => false, 'error' => 'Cannot share with yourself'];

            // Insert share
            $stmt = $this->db->prepare("
                INSERT OR IGNORE INTO shares (document_id, owner_id, shared_with_user_id)
                VALUES (:document_id, :owner_id, :shared_with)
            ");
            $stmt->execute(['document_id' => $documentId, 'owner_id' => $userId, 'shared_with' => $target['id']]);
            return ['success' => true, 'message' => 'Document shared with ' . htmlspecialchars($targetUsername)];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get documents shared with the current user
     */
    public function getSharedDocuments() {
        $userId = $this->auth->getCurrentUserId();
        if (!$userId) return [];
        try {
            $stmt = $this->db->prepare("
                SELECT d.*, c.name as category_name, GROUP_CONCAT(t.name, ', ') as tags,
                       u.username as owner_username
                FROM documents d
                JOIN shares s ON d.id = s.document_id
                JOIN users u ON d.user_id = u.id
                LEFT JOIN categories c ON d.category_id = c.id
                LEFT JOIN document_tags dt ON d.id = dt.document_id
                LEFT JOIN tags t ON dt.tag_id = t.id
                WHERE s.shared_with_user_id = :user_id
                GROUP BY d.id ORDER BY s.created_at DESC
            ");
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Rerank search results using Claude API
     */
    public function aiRerank($query, $documents) {
        if (empty(CLAUDE_API_KEY) || empty($documents)) return $documents;

        try {
            $docList = '';
            foreach ($documents as $d) {
                $docList .= "{$d['id']}: {$d['title']} [{$d['category_name']}] tags: {$d['tags']}\n";
            }

            $payload = json_encode([
                'model' => 'claude-haiku-4-5-20251001',
                'max_tokens' => 256,
                'messages' => [[
                    'role' => 'user',
                    'content' => "Search query: \"{$query}\"\n\nDocuments:\n{$docList}\nReturn ONLY the document IDs in order of relevance, comma-separated (e.g. 3,1,2). Include all IDs."
                ]]
            ]);

            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-api-key: ' . CLAUDE_API_KEY,
                    'anthropic-version: 2023-06-01'
                ],
                CURLOPT_TIMEOUT => 8
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            if (!$response) return $documents;

            $data = json_decode($response, true);
            $text = trim($data['content'][0]['text'] ?? '');

            // Parse comma-separated IDs
            $orderedIds = array_filter(array_map('intval', explode(',', $text)));
            if (empty($orderedIds)) return $documents;

            // Reorder documents by returned ID order
            $indexed = [];
            foreach ($documents as $d) $indexed[$d['id']] = $d;

            $reranked = [];
            foreach ($orderedIds as $id) {
                if (isset($indexed[$id])) {
                    $reranked[] = $indexed[$id];
                    unset($indexed[$id]);
                }
            }
            // Append any remaining docs not in the AI response
            foreach ($indexed as $d) $reranked[] = $d;

            return $reranked;
        } catch (Exception $e) {
            return $documents;
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
