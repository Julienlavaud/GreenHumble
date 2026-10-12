<?php
declare(strict_types=1);

function bulk_delete_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function bulk_delete_request(string $runner, string $endpoint, string $role, array $payload): array
{
    $pipes = [];
    $process = proc_open(
        [PHP_BINARY, $runner, $endpoint, $role],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Impossible de lancer PHP pour tester l’endpoint.');
    }

    fwrite($pipes[0], json_encode($payload, JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        throw new RuntimeException('L’endpoint a échoué: ' . $stderr . $stdout);
    }

    if (!is_string($stdout) || preg_match('/\n__GH_HTTP_STATUS__(\d+)$/', $stdout, $matches) !== 1) {
        throw new RuntimeException('Réponse HTTP de test invalide: ' . $stderr . $stdout);
    }

    $body = json_decode(substr($stdout, 0, -strlen($matches[0])), true);
    if (!is_array($body)) {
        throw new RuntimeException('JSON de réponse invalide: ' . $stderr . $stdout);
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
$endpoint = $scans . DIRECTORY_SEPARATOR . 'bulk_delete.php';
copy($repository . '/api/scans/bulk_delete.php', $endpoint);
$runner = $root . DIRECTORY_SEPARATOR . 'runner.php';
file_put_contents($runner, <<<'PHP'
<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';
if (($argv[2] ?? '') === 'admin') {
    putenv('GREENHUMBLE_ADMIN_API_TOKEN=test-only-admin-token');
    putenv('GREENHUMBLE_PIPELINE_API_TOKEN=');
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . getenv('GREENHUMBLE_ADMIN_API_TOKEN');
} else {
    putenv('GREENHUMBLE_ADMIN_API_TOKEN=');
    putenv('GREENHUMBLE_PIPELINE_API_TOKEN=');
}
register_shutdown_function(static function (): void {
    echo "\n__GH_HTTP_STATUS__" . (http_response_code() ?: 200);
});
include $argv[1];
PHP
);

$storage = $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'scans.json';
$original = [
    ['id' => 'scan-1', 'url' => 'https://one.example'],
    ['id' => 'scan-2', 'url' => 'https://two.example'],
];
file_put_contents($storage, json_encode($original, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

try {
    $tooMany = bulk_delete_request($runner, $endpoint, 'admin', ['ids' => array_map('strval', range(1, 501))]);
    bulk_delete_assert($tooMany['status'] === 400, '501 selected ids are rejected');

    $atLimit = bulk_delete_request($runner, $endpoint, 'admin', ['ids' => array_map('strval', range(1, 500))]);
    bulk_delete_assert($atLimit['status'] === 404, '500 selected ids are accepted for processing');

    $invalid = bulk_delete_request($runner, $endpoint, 'admin', ['ids' => ['', ' ', 42, str_repeat('x', 129)]]);
    bulk_delete_assert($invalid['status'] === 400, 'a selection containing only invalid ids is rejected');

    $missing = bulk_delete_request($runner, $endpoint, 'admin', ['ids' => ['unknown-scan']]);
    bulk_delete_assert($missing['status'] === 404, 'a selection with no matching scans returns not found');

    $unauthorized = bulk_delete_request($runner, $endpoint, 'public', ['ids' => ['scan-1']]);
    bulk_delete_assert($unauthorized['status'] === 403, 'non-admin requests are forbidden');
    bulk_delete_assert(
        json_decode((string)file_get_contents($storage), true) === $original,
        'rejected bulk-delete requests leave stored scans unchanged'
    );

    $deleted = bulk_delete_request($runner, $endpoint, 'admin', ['ids' => ['scan-1']]);
    $remaining = json_decode((string)file_get_contents($storage), true);
    bulk_delete_assert($deleted['status'] === 200 && ($deleted['body']['deleted'] ?? 0) === 1, 'an admin can delete a matching scan');
    bulk_delete_assert(array_column($remaining, 'id') === ['scan-2'], 'successful deletion preserves unselected scans');

    echo "OK: bulk-delete endpoint permissions, limits, validation, and mutations\n";
} finally {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($root);
}
