<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/api_guard.php';
gh_api_guard(true, ['admin']);
require_once __DIR__ . '/../includes/scans_manager.php';

$input = json_decode((string)file_get_contents('php://input'), true);
$ids = is_array($input) ? ($input['ids'] ?? null) : null;
if (!is_array($ids) || $ids === [] || count($ids) > MAX_BULK_DELETE_SCANS) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Sélectionnez entre 1 et ' . MAX_BULK_DELETE_SCANS . ' scans.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$selectedIds = [];
foreach ($ids as $id) {
    if (!is_string($id) || trim($id) === '' || strlen($id) > 128) continue;
    $selectedIds[trim($id)] = true;
}
if ($selectedIds === []) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Identifiants de scan invalides.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$deletedCount = 0;
$saved = mutate_all_scans(static function (array $scans) use ($selectedIds, &$deletedCount): array {
    $remainingScans = [];
    foreach ($scans as $scan) {
        $id = (string)($scan['id'] ?? '');
        if (isset($selectedIds[$id])) {
            $deletedCount++;
            continue;
        }
        $remainingScans[] = $scan;
    }
    return $remainingScans;
});
if (!$saved) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Enregistrement impossible (droits d’écriture du dossier data).'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($deletedCount === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Aucun scan sélectionné n’a été trouvé.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => true, 'deleted' => $deletedCount], JSON_UNESCAPED_UNICODE);
