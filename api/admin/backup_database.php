<?php
declare(strict_types=1);

/**
 * Téléchargement d'une sauvegarde SQL de la base GreenHumble.
 * Accès réservé à une session administrateur authentifiée.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (($_SESSION['admin_logged_in'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Accès réservé à l’administrateur.');
}
if (!defined('DB_HOST') || trim((string)DB_HOST) === '' ||
    !defined('DB_NAME') || trim((string)DB_NAME) === '' ||
    !defined('DB_USER') || trim((string)DB_USER) === '') {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Base de données non configurée.');
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM);
    $filename = 'greenhumble-db-' . date('Y-m-d-His') . '.sql';
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');

    echo "-- GreenHumble SQL backup\n-- Created: " . gmdate('c') . " UTC\n";
    echo "-- Database: " . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)DB_NAME) . "\n\n";
    echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $tableRow) {
        $table = (string)$tableRow[0];
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) continue;
        $quotedTable = '`' . str_replace('`', '``', $table) . '`';
        $createRow = $pdo->query('SHOW CREATE TABLE ' . $quotedTable)->fetch(PDO::FETCH_NUM);
        if (!$createRow || !isset($createRow[1])) continue;

        echo "-- Table: " . $table . "\nDROP TABLE IF EXISTS " . $quotedTable . ";\n";
        echo $createRow[1] . ";\n\n";

        $rows = $pdo->query('SELECT * FROM ' . $quotedTable);
        $columns = $pdo->query('SHOW COLUMNS FROM ' . $quotedTable)->fetchAll(PDO::FETCH_COLUMN);
        $quotedColumns = implode(', ', array_map(static function ($column) {
            return '`' . str_replace('`', '``', (string)$column) . '`';
        }, $columns));

        while ($row = $rows->fetch(PDO::FETCH_NUM)) {
            $values = array_map(static function ($value) use ($pdo) {
                if ($value === null) return 'NULL';
                return $pdo->quote((string)$value);
            }, $row);
            echo 'INSERT INTO ' . $quotedTable . ' (' . $quotedColumns . ') VALUES (' . implode(', ', $values) . ");\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
} catch (Throwable $e) {
    // Do not expose database credentials or internal SQL errors to the browser.
    error_log('GreenHumble: échec de la sauvegarde SQL (' . get_class($e) . ').');
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo "La sauvegarde n’a pas pu être générée. Vérifiez les journaux serveur.";
}
