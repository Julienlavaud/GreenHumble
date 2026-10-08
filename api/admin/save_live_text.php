<?php
// /api/admin/save_live_text.php
// API de sauvegarde et restauration des textes en direct (Live CMS)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    echo json_encode(['success' => false, 'error' => 'Accès réservé aux administrateurs.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? 'save';

try {
    $db = get_db_connection();
    
    // Auto-create table if needed
    $db->exec("CREATE TABLE IF NOT EXISTS cl_page_contents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_key VARCHAR(100) NOT NULL,
        text_key VARCHAR(100) NOT NULL,
        content LONGTEXT NOT NULL,
        previous_content LONGTEXT NULL,
        updated_by VARCHAR(100) DEFAULT 'Admin',
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_page_text (page_key, text_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if ($action === 'save_batch') {
        $items = $input['items'] ?? [];
        $savedCount = 0;

        foreach ($items as $item) {
            $pageKey = trim($item['page'] ?? '');
            $textKey = trim($item['key'] ?? '');
            $content = trim($item['content'] ?? '');

            if ($pageKey && $textKey) {
                // Fetch current to save into previous_content
                $stmt = $db->prepare("SELECT content FROM cl_page_contents WHERE page_key = ? AND text_key = ?");
                $stmt->execute([$pageKey, $textKey]);
                $prev = $stmt->fetchColumn();

                $sql = "INSERT INTO cl_page_contents (page_key, text_key, content, previous_content, updated_by)
                        VALUES (?, ?, ?, ?, 'Admin')
                        ON DUPLICATE KEY UPDATE 
                            previous_content = IF(content != VALUES(content), content, previous_content),
                            content = VALUES(content),
                            updated_at = CURRENT_TIMESTAMP";
                $insertStmt = $db->prepare($sql);
                $insertStmt->execute([$pageKey, $textKey, $content, $prev ?: null]);
                $savedCount++;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "$savedCount élément(s) de texte mis à jour et enregistrés avec succès.",
            'count' => $savedCount
        ]);
        exit;
    }

    if ($action === 'rollback') {
        $pageKey = trim($input['page'] ?? '');
        $textKey = trim($input['key'] ?? '');

        if ($pageKey && $textKey) {
            $stmt = $db->prepare("SELECT content, previous_content FROM cl_page_contents WHERE page_key = ? AND text_key = ?");
            $stmt->execute([$pageKey, $textKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['previous_content'] !== null) {
                $update = $db->prepare("UPDATE cl_page_contents SET content = previous_content, previous_content = content WHERE page_key = ? AND text_key = ?");
                $update->execute([$pageKey, $textKey]);
                echo json_encode([
                    'success' => true, 
                    'restoredContent' => $row['previous_content'],
                    'message' => 'Texte restauré avec succès à sa version précédente.'
                ]);
                exit;
            }
        }

        echo json_encode(['success' => false, 'error' => 'Aucune version précédente disponible pour cet élément.']);
        exit;
    }

    if ($action === 'reset_all_page') {
        $pageKey = trim($input['page'] ?? '');
        if ($pageKey) {
            $stmt = $db->prepare("DELETE FROM cl_page_contents WHERE page_key = ?");
            $stmt->execute([$pageKey]);
            echo json_encode(['success' => true, 'message' => 'Textes de la page réinitialisés aux valeurs par défaut.']);
            exit;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Action non reconnue.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données : ' . $e->getMessage()]);
}
