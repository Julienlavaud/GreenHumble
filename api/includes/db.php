<?php
// /api/includes/db.php
require_once __DIR__ . '/../config/config.php';

function ensure_db_schema($pdo) {
    try {
        // Ensure headcount & turnover in cl_companies
        $cols = $pdo->query("SHOW COLUMNS FROM cl_companies")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('headcount', $cols)) {
            $pdo->exec("ALTER TABLE cl_companies ADD COLUMN headcount VARCHAR(50) NULL AFTER sector");
        }
        if (!in_array('turnover', $cols)) {
            $pdo->exec("ALTER TABLE cl_companies ADD COLUMN turnover VARCHAR(50) NULL AFTER headcount");
        }

        // Ensure headcount & turnover in cl_audits
        $colsAudit = $pdo->query("SHOW COLUMNS FROM cl_audits")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('headcount', $colsAudit)) {
            $pdo->exec("ALTER TABLE cl_audits ADD COLUMN headcount VARCHAR(50) NULL");
        }
        if (!in_array('turnover', $colsAudit)) {
            $pdo->exec("ALTER TABLE cl_audits ADD COLUMN turnover VARCHAR(50) NULL");
        }
    } catch (Exception $e) {
        // Silently handle if table doesn't exist yet before install
    }
}

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        ensure_db_schema($pdo);
        return $pdo;
    } catch (PDOException $e) {
        die("<div style='background:#fee2e2;color:#991b1b;padding:16px;border-radius:12px;font-family:sans-serif;margin:20px;'>"
            . "<strong>Erreur de connexion à la base de données :</strong> " . htmlspecialchars($e->getMessage())
            . "<br><a href='/api/install/' style='color:#1e40af;text-decoration:underline;margin-top:8px;display:inline-block;'>Lancer l'installation / vérifier la configuration</a></div>");
    }
}

