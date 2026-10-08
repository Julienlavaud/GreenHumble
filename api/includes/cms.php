<?php
// /api/includes/cms.php
// Système de Gestion de Contenu Live & Édition Inline pour Administrateurs
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

function get_live_text($page_key, $text_id, $default_text, $as_raw = false) {
    static $cmsCache = null;
    
    if ($cmsCache === null) {
        $cmsCache = [];
        try {
            $db = get_db_connection();
            // Create table if not exists automatically
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

            $stmt = $db->query("SELECT page_key, text_key, content FROM cl_page_contents");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cmsCache[$row['page_key'] . '::' . $row['text_key']] = $row['content'];
            }
        } catch (Exception $e) {
            // Fallback gracefully to default
        }
    }

    $cacheKey = $page_key . '::' . $text_id;
    $text = isset($cmsCache[$cacheKey]) && trim($cmsCache[$cacheKey]) !== '' ? $cmsCache[$cacheKey] : $default_text;

    $isAdmin = !empty($_SESSION['admin_logged_in']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

    if ($as_raw || !$isAdmin) {
        return $text;
    }

    // Wrap in editable data container for live admin editing
    $safePage = htmlspecialchars($page_key, ENT_QUOTES, 'UTF-8');
    $safeKey = htmlspecialchars($text_id, ENT_QUOTES, 'UTF-8');
    
    return '<span class="cl-cms-editable relative group inline-block" data-page="' . $safePage . '" data-key="' . $safeKey . '">' 
        . $text 
        . '<span class="cl-cms-badge hidden group-hover:inline-block absolute -top-4 -right-2 bg-amber-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded shadow pointer-events-none z-20">✏️ Éditer</span></span>';
}
