<?php
declare(strict_types=1);

require_once __DIR__ . '/../api/includes/scans_manager.php';

function bulk_delete_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function scan_endpoint_request(int $port, string $endpoint, string $role, array $payload, string $adminToken): array
{
    $headers = ['Content-Type: application/json'];
    if ($role === 'admin') $headers[] = 'Authorization: Bearer ' . $adminToken;
    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => implode("\r\n", $headers),
        'content' => json_encode($payload, JSON_THROW_ON_ERROR),
        'ignore_errors' => true,
        'timeout' => 5,
    ]]);
    $bodyText = file_get_contents("http://127.0.0.1:{$port}/api/scans/{$endpoint}.php", false, $context);
    $statusLine = $http_response_header[0] ?? '';
    if (!is_string($bodyText) || preg_match('/\s(\d{3})\s/', $statusLine, $matches) !== 1) {
        throw new RuntimeException('Réponse HTTP de test invalide: ' . $statusLine . ' ' . (string)$bodyText);
    }
    $body = json_decode($bodyText, true);
    if (!is_array($body)) {
        throw new RuntimeException('JSON de réponse invalide: ' . $bodyText);
    }
    return ['status' => (int)$matches[1], 'body' => $body];
}

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'greenhumble-bulk-delete-' . bin2hex(random_bytes(6));
$api = $root . DIRECTORY_SEPARATOR . 'api';
$includes = $api . DIRECTORY_SEPARATOR . 'includes';
$scans = $api . DIRECTORY_SEPARATOR . 'scans';
mkdir($includes, 0700, true);
mkdir($scans, 0700, true);
mkdir($root . DIRECTORY_SEPARATOR . 'data', 0700, true);
mkdir($api . DIRECTORY_SEPARATOR . 'data', 0700, true);

$repository = dirname(__DIR__);
foreach (['api_guard.php', 'auth.php', 'scans_manager.php'] as $file) {
    copy($repository . '/api/includes/' . $file, $includes . DIRECTORY_SEPARATOR . $file);
}
foreach (['bulk_delete.php', 'delete.php', 'update.php'] as $file) {
    copy($repository . '/api/scans/' . $file, $scans . DIRECTORY_SEPARATOR . $file);
}

$storage = $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'scans.json';
$original = [
    ['id' => 'scan-1', 'url' => 'https://one.example'],
    ['id' => 'scan-2', 'url' => 'https://two.example'],
];
file_put_contents($storage, json_encode($original, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if ($socket === false) throw new RuntimeException("Impossible d’allouer un port de test: {$error}");
$address = stream_socket_get_name($socket, false);
$port = (int)substr(strrchr((string)$address, ':'), 1);
fclose($socket);
$adminToken = 'bulk-test-' . bin2hex(random_bytes(12));
$serverLog = $root . DIRECTORY_SEPARATOR . 'server.log';
$server = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', $root],
    [0 => ['file', '/dev/null', 'r'], 1 => ['file', $serverLog, 'a'], 2 => ['file', $serverLog, 'a']],
    $serverPipes,
    $root,
    array_merge(getenv(), [
        'GREENHUMBLE_ADMIN_API_TOKEN' => $adminToken,
        'GREENHUMBLE_PIPELINE_API_TOKEN' => '',
    ])
);
if (!is_resource($server)) throw new RuntimeException('Impossible de démarrer le serveur PHP de test.');

try {
    $connected = false;
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $probe = @fsockopen('127.0.0.1', $port, $connectErrno, $connectError, 0.1);
        if (is_resource($probe)) {
            fclose($probe);
            $connected = true;
            break;
        }
        usleep(50000);
    }
    if (!$connected) throw new RuntimeException('Le serveur PHP de test n’a pas démarré: ' . (string)file_get_contents($serverLog));

    $tooMany = scan_endpoint_request($port, 'bulk_delete', 'admin', ['ids' => array_map('strval', range(1, MAX_BULK_DELETE_SCANS + 1))], $adminToken);
    bulk_delete_assert($tooMany['status'] === 400, '501 selected ids are rejected');

    $atLimit = scan_endpoint_request($port, 'bulk_delete', 'admin', ['ids' => array_map('strval', range(1, MAX_BULK_DELETE_SCANS))], $adminToken);
    bulk_delete_assert(
        $atLimit['status'] === 404,
        '500 selected ids are accepted for processing (received ' . $atLimit['status'] . ': ' . json_encode($atLimit['body']) . ')'
    );

    $invalid = scan_endpoint_request($port, 'bulk_delete', 'admin', ['ids' => ['', ' ', 42, str_repeat('x', 129)]], $adminToken);
    bulk_delete_assert($invalid['status'] === 400, 'a selection containing only invalid ids is rejected');

    $missing = scan_endpoint_request($port, 'bulk_delete', 'admin', ['ids' => ['unknown-scan']], $adminToken);
    bulk_delete_assert($missing['status'] === 404, 'a selection with no matching scans returns not found');

    $unauthorized = scan_endpoint_request($port, 'bulk_delete', 'public', ['ids' => ['scan-1']], $adminToken);
    bulk_delete_assert($unauthorized['status'] === 403, 'non-admin requests are forbidden');
    bulk_delete_assert(
        json_decode((string)file_get_contents($storage), true) === $original,
        'rejected bulk-delete requests leave stored scans unchanged'
    );

    $deleted = scan_endpoint_request($port, 'bulk_delete', 'admin', ['ids' => ['scan-1']], $adminToken);
    $remaining = json_decode((string)file_get_contents($storage), true);
    bulk_delete_assert($deleted['status'] === 200 && ($deleted['body']['deleted'] ?? 0) === 1, 'an admin can delete a matching scan');
    bulk_delete_assert(array_column($remaining, 'id') === ['scan-2'], 'successful deletion preserves unselected scans');

    $updated = scan_endpoint_request($port, 'update', 'admin', ['id' => 'scan-2', 'statut' => 'reviewed', 'type_scan' => 'pipeline'], $adminToken);
    $remaining = json_decode((string)file_get_contents($storage), true);
    bulk_delete_assert($updated['status'] === 200, 'an admin can update a scan');
    bulk_delete_assert(($remaining[0]['statut'] ?? '') === 'reviewed' && ($remaining[0]['type_scan'] ?? '') === 'pipeline', 'scan updates persist the requested fields');

    $deletedSingle = scan_endpoint_request($port, 'delete', 'admin', ['id' => 'scan-2'], $adminToken);
    bulk_delete_assert($deletedSingle['status'] === 200 && json_decode((string)file_get_contents($storage), true) === [], 'an admin can delete one scan');

    $corruptStorage = '{invalid json';
    file_put_contents($storage, $corruptStorage);
    $updateFailure = scan_endpoint_request($port, 'update', 'admin', ['id' => 'scan-1', 'statut' => 'reviewed'], $adminToken);
    $deleteFailure = scan_endpoint_request($port, 'delete', 'admin', ['id' => 'scan-1'], $adminToken);
    bulk_delete_assert($updateFailure['status'] === 500 && $deleteFailure['status'] === 500, 'single update and delete return 500 when scan history cannot be safely written');
    bulk_delete_assert((string)file_get_contents($storage) === $corruptStorage, 'failed single-scan mutations preserve the invalid source file');

    echo "OK: scan endpoint permissions, limits, validation, persistence, and rollback handling\n";
} finally {
    proc_terminate($server);
    proc_close($server);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($root);
}
