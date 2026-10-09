<?php
/**
 * Import/export CSV des listes de sites GreenHumble.
 * GET ?action=template : modèle CSV
 * GET ?action=export&list_id=... : export d'une liste (ou de toutes)
 * POST multipart/form-data ou JSON : crée une nouvelle liste depuis un CSV ou une liste d'URLs.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/listes_manager.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
header('X-Content-Type-Options: nosniff');

$role = $_SESSION['role'] ?? '';
$adminOk = (($_SESSION['admin_logged_in'] ?? false) === true && $role === 'admin');
if (!$adminOk) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Accès réservé aux administrateurs connectés.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function gh_csv_url($value) {
    $value = trim((string)$value);
    if ($value === '') return '';
    if (!preg_match('~^https?://~i', $value)) $value = 'https://' . $value;
    $parts = parse_url($value);
    if (!$parts || empty($parts['host']) || !preg_match('/^[a-z0-9.-]+$/i', $parts['host'])) return '';
    if (!in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) return '';
    return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
}
function gh_csv_safe_cell($value) {
    $value = (string)$value;
    // Prevent spreadsheet formula execution when exported CSV is opened in Excel/LibreOffice.
    if ($value !== '' && preg_match('/^[\\s\\x00-\\x1F]*[=+@-]/u', $value)) {
        return "'" . $value;
    }
    return $value;
}
function gh_csv_download($filename, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $out = fopen('php://output', 'w');
    // BOM Excel-friendly (UTF-8)
    fwrite($out, "\xEF\xBB\xBF");
    foreach ($rows as $row) fputcsv($out, array_map('gh_csv_safe_cell', $row), ';');
    fclose($out);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'template';
    if ($action === 'template') {
        gh_csv_download('greenhumble-modele-sites.csv', [
            ['liste', 'description', 'url', 'nom'],
            ['SCOP du BTP', 'Prospects SCOP du bâtiment', 'https://exemple.fr', 'Entreprise exemple']
        ]);
    }
    if ($action === 'export') {
        $lists = load_all_listes();
        $wanted = trim((string)($_GET['list_id'] ?? ''));
        if ($wanted !== '') {
            $found = false;
            foreach ($lists as $candidate) {
                if (($candidate['id'] ?? '') === $wanted) { $found = true; break; }
            }
            if (!$found) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Liste introuvable.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        $rows = [['liste', 'description', 'url', 'nom', 'statut', 'scan_id']];
        foreach ($lists as $list) {
            if ($wanted !== '' && ($list['id'] ?? '') !== $wanted) continue;
            foreach (($list['sites'] ?? []) as $site) {
                $rows[] = [
                    $list['nom'] ?? '',
                    $list['description'] ?? '',
                    $site['url'] ?? '',
                    $site['nom'] ?? '',
                    $site['statut'] ?? 'à_analyser',
                    $site['scan_id'] ?? ''
                ];
            }
            // Compatibility with legacy lists containing scan IDs only.
            if (empty($list['sites'])) {
                foreach (($list['items'] ?? []) as $item) {
                    $rows[] = [$list['nom'] ?? '', $list['description'] ?? '', '', '', 'scan_existant', (string)$item];
                }
            }
        }
        gh_csv_download('greenhumble-listes.csv', $rows);
    }
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Action inconnue.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
if (function_exists('gh_require_same_origin_mutation')) {
    gh_require_same_origin_mutation();
}
$input = $_POST;
if (empty($input)) {
    $json = json_decode(file_get_contents('php://input'), true);
    if (is_array($json)) $input = $json;
}
$listName = trim((string)($input['nom'] ?? $input['liste'] ?? ''));
$description = trim((string)($input['description'] ?? ''));
$rawSites = trim((string)($input['urls'] ?? ''));
$parsedRows = [];

if (isset($_FILES['csv'])) {
    if (($_FILES['csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
        !is_uploaded_file($_FILES['csv']['tmp_name'] ?? '')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Le téléversement du CSV a échoué. Vérifiez le fichier et réessayez.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (($_FILES['csv']['size'] ?? 0) > 2 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['success' => false, 'error' => 'Le fichier CSV dépasse 2 Mo.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $fh = fopen($_FILES['csv']['tmp_name'], 'r');
    if (!$fh) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Impossible de lire le fichier CSV.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $first = fgetcsv($fh, 0, ';');
    if ($first === false) $first = [];
    $first[0] = isset($first[0]) ? preg_replace('/^\xEF\xBB\xBF/', '', (string)$first[0]) : '';
    $header = array_map(static fn($v) => strtolower(trim((string)$v)), $first);
    $urlIndex = array_search('url', $header, true);
    $nameIndex = array_search('nom', $header, true);
    $listIndex = array_search('liste', $header, true);
    $descIndex = array_search('description', $header, true);
    if ($urlIndex === false) {
        // Accepte aussi un CSV simple à une seule colonne sans en-tête.
        rewind($fh);
        $urlIndex = 0;
        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            if (isset($row[0]) && strtolower(trim((string)$row[0])) !== 'url') {
                $parsedRows[] = ['url' => trim((string)$row[0]), 'nom' => trim((string)($row[1] ?? ''))];
            }
        }
    } else {
        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            $url = trim((string)($row[$urlIndex] ?? ''));
            if ($url === '') continue;
            $parsedRows[] = [
                'url' => $url,
                'nom' => trim((string)($nameIndex !== false ? ($row[$nameIndex] ?? '') : '')),
                'liste' => trim((string)($listIndex !== false ? ($row[$listIndex] ?? '') : '')),
                'description' => trim((string)($descIndex !== false ? ($row[$descIndex] ?? '') : ''))
            ];
        }
        if ($listName === '' && $listIndex !== false) {
            foreach ($parsedRows as $row) if (!empty($row['liste'])) { $listName = $row['liste']; break; }
        }
        if ($description === '' && $descIndex !== false) {
            foreach ($parsedRows as $row) if (!empty($row['description'])) { $description = $row['description']; break; }
        }
    }
    fclose($fh);
} elseif ($rawSites !== '') {
    foreach (preg_split('/[\r\n,;\t]+/', $rawSites) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = preg_split('/\s*\|\s*/', $line, 2);
        $parsedRows[] = ['url' => $parts[0], 'nom' => $parts[1] ?? ''];
    }
}

if (mb_strlen($listName) > 150) $listName = mb_substr($listName, 0, 150);
if (mb_strlen($description) > 1000) $description = mb_substr($description, 0, 1000);
if ($listName === '') $listName = 'Liste importée du ' . date('d/m/Y');
if (count($parsedRows) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Aucun site trouvé. Collez des URL ou importez le modèle CSV rempli.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (count($parsedRows) > 500) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'Limite de 500 sites par import. Séparez les gros fichiers en plusieurs listes.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sites = [];
$seen = [];
$invalid = [];
foreach ($parsedRows as $row) {
    $url = gh_csv_url($row['url'] ?? '');
    if ($url === '') {
        $invalid[] = (string)($row['url'] ?? '');
        continue;
    }
    $key = strtolower(rtrim($url, '/'));
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $sites[] = [
        'id' => 'site_' . bin2hex(random_bytes(6)),
        'url' => $url,
        'nom' => mb_substr(trim((string)($row['nom'] ?? '')), 0, 200),
        'statut' => 'à_analyser',
        'scan_id' => null,
        'created_at' => date('c')
    ];
}
if (!$sites) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Aucune URL valide dans cet import.', 'invalides' => $invalid], JSON_UNESCAPED_UNICODE);
    exit;
}

$list = create_liste($listName, $description);
if (!is_array($list) || empty($list['id'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Impossible de créer la liste. Vérifiez les droits d’écriture du dossier data.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$allLists = load_all_listes();
foreach ($allLists as &$entry) {
    if (($entry['id'] ?? '') === $list['id']) {
        $entry['sites'] = $sites;
        $entry['items'] = [];
        break;
    }
}
unset($entry);
if (!save_all_listes($allLists)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Échec d’écriture de la liste importée. Vérifiez les droits d’écriture du dossier data.'], JSON_UNESCAPED_UNICODE);
    exit;
}
// Verify that the JSON-backed list store actually persisted the imported records.
$verifiedLists = load_all_listes();
$persisted = null;
foreach ($verifiedLists as $candidate) {
    if (($candidate['id'] ?? '') === $list['id']) { $persisted = $candidate; break; }
}
if (!is_array($persisted) || count($persisted['sites'] ?? []) !== count($sites)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'La liste n’a pas pu être enregistrée durablement. Vérifiez les droits d’écriture du dossier data.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Nouvelle liste créée. Les sites sont enregistrés sans lancer les scans automatiquement.',
    'list' => array_merge($list, ['sites' => $sites]),
    'imported' => count($sites),
    'duplicates_ignored' => count($parsedRows) - count($sites) - count($invalid),
    'invalid_count' => count($invalid),
    'invalid_urls' => array_slice($invalid, 0, 20)
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
