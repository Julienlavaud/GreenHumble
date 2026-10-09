<?php
declare(strict_types=1);
// Enregistre une demande explicite de diagnostic public, sans créer de faux audit ni de faux score.
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function gh_lead_reply(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    gh_lead_reply(405, ['success' => false, 'error' => 'Méthode non autorisée.']);
}
$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;

$companyName = trim((string)($input['company_name'] ?? ''));
$url = trim((string)($input['url'] ?? ''));
$contactName = trim((string)($input['contact_name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$marketingOptIn = ($input['marketing_opt_in'] ?? false) === true;
if ($companyName === '' || $url === '' || $contactName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    gh_lead_reply(400, ['success' => false, 'error' => 'Entreprise, URL, nom du contact et e-mail valide sont obligatoires.']);
}
$parts = parse_url($url);
if (!is_array($parts) || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host'])) {
    gh_lead_reply(400, ['success' => false, 'error' => 'URL du site invalide.']);
}
$host = strtolower((string)$parts['host']);
if (strlen($companyName) > 255 || strlen($contactName) > 255 || strlen($email) > 254 || strlen($phone) > 80 || strlen($url) > 2048) {
    gh_lead_reply(400, ['success' => false, 'error' => 'Un champ dépasse la longueur autorisée.']);
}
try {
    $db = get_db_connection();
    $find = $db->prepare('SELECT id FROM cl_companies WHERE domain = ? LIMIT 1');
    $find->execute([$host]);
    $companyId = $find->fetchColumn();
    if ($companyId === false) {
        $insert = $db->prepare('INSERT INTO cl_companies (name, domain, contact_name, contact_email, contact_phone, notes) VALUES (?, ?, ?, ?, ?, ?)');
        $insert->execute([$companyName, $host, $contactName, $email, $phone ?: null, 'Demande explicite de diagnostic depuis le scan public.']);
        $companyId = (int)$db->lastInsertId();
    } else {
        $update = $db->prepare('UPDATE cl_companies SET name = ?, contact_name = ?, contact_email = ?, contact_phone = ? WHERE id = ?');
        $update->execute([$companyName, $contactName, $email, $phone ?: null, (int)$companyId]);
    }
    $contact = $db->prepare('INSERT INTO cl_company_contacts (company_id, name, email, phone, source, marketing_opt_in) VALUES (?, ?, ?, ?, ?, ?)');
    $contact->execute([(int)$companyId, $contactName, $email, $phone ?: null, 'scan_public_diagnostic_request', $marketingOptIn ? 1 : 0]);

    gh_lead_reply(200, [
        'success' => true,
        'message' => 'Votre demande de diagnostic est enregistrée. L’équipe pourra vous recontacter au sujet de cette demande.',
        'companyId' => (int)$companyId,
        'marketingOptIn' => $marketingOptIn
    ]);
} catch (Throwable $e) {
    error_log('GreenHumble: demande de diagnostic public non enregistrée (' . get_class($e) . ').');
    gh_lead_reply(503, ['success' => false, 'error' => 'Enregistrement temporairement indisponible. Vos coordonnées n’ont pas été confirmées comme enregistrées ; réessayez plus tard.']);
}
