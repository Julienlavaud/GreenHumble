<?php
declare(strict_types=1);
// Enregistre une demande explicite et relie ses coordonnées à l'entreprise et au scan public.
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function gh_lead_reply(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function gh_lead_host(string $url): string {
    $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?: ''));
    return preg_replace('/^www\./i', '', $host);
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
$auditRef = trim((string)($input['audit_ref'] ?? ''));
$marketingOptIn = ($input['marketing_opt_in'] ?? false) === true;

if ($companyName === '' || $url === '' || $contactName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    gh_lead_reply(400, ['success' => false, 'error' => 'Entreprise, URL, nom du contact et e-mail valide sont obligatoires.']);
}
$parts = parse_url($url);
if (!is_array($parts) || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true) ||
    empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
    gh_lead_reply(400, ['success' => false, 'error' => 'URL du site invalide.']);
}
$host = gh_lead_host($url);
if ($host === '' || strlen($companyName) > 200 || strlen($contactName) > 150 ||
    strlen($email) > 150 || strlen($phone) > 50 || strlen($url) > 2048) {
    gh_lead_reply(400, ['success' => false, 'error' => 'Un champ dépasse la longueur autorisée.']);
}

// Le lien vers l'audit est accepté seulement s'il existe et cible le même domaine.
if ($auditRef !== '') {
    if (!preg_match('/^aud_[A-Za-z0-9_-]{8,80}$/', $auditRef)) {
        gh_lead_reply(400, ['success' => false, 'error' => 'Référence du scan invalide.']);
    }
    $auditFile = __DIR__ . '/../data/stored_audits.json';
    $audits = is_file($auditFile) ? json_decode((string)@file_get_contents($auditFile), true) : [];
    $matchedAudit = null;
    foreach (is_array($audits) ? $audits : [] as $audit) {
        if (($audit['id'] ?? '') === $auditRef) { $matchedAudit = $audit; break; }
    }
    if (!is_array($matchedAudit) || gh_lead_host((string)($matchedAudit['url'] ?? '')) !== $host) {
        gh_lead_reply(400, ['success' => false, 'error' => 'Le scan associé est introuvable ou ne correspond pas à ce site.']);
    }
}

$originUrl = strtolower((string)$parts['scheme']) . '://' . (string)$parts['host'];
$websiteVariants = [$originUrl, $originUrl . '/', 'https://' . $host, 'https://' . $host . '/', 'http://' . $host, 'http://' . $host . '/'];
$websiteVariants = array_values(array_unique(array_map('strtolower', $websiteVariants)));
try {
    $db = get_db_connection();
    $find = $db->prepare(
        'SELECT id FROM cl_companies
         WHERE LOWER(COALESCE(domain, "")) = ? OR LOWER(COALESCE(clean_url, "")) = ?
            OR LOWER(COALESCE(website, "")) IN (?, ?, ?, ?, ?, ?)
         ORDER BY id ASC LIMIT 1'
    );
    $find->execute(array_merge([$host, $host], array_slice($websiteVariants, 0, 6)));
    $companyId = $find->fetchColumn();

    $companyContactName = function_exists('mb_substr') ? mb_substr($contactName, 0, 100) : substr($contactName, 0, 100);
    if ($companyId === false) {
        $insert = $db->prepare(
            'INSERT INTO cl_companies (name, domain, website, clean_url, contact_name, contact_email, contact_phone, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([$companyName, $host, $originUrl, $host, $companyContactName, $email, $phone ?: null, 'Demande explicite de diagnostic depuis le scan public.']);
        $companyId = (int)$db->lastInsertId();
    } else {
        $update = $db->prepare(
            'UPDATE cl_companies SET name = ?, domain = ?, website = COALESCE(NULLIF(website, ""), ?),
             clean_url = ?, contact_name = ?, contact_email = ?, contact_phone = ? WHERE id = ?'
        );
        $update->execute([$companyName, $host, $originUrl, $host, $companyContactName, $email, $phone ?: null, (int)$companyId]);
    }

    $consentedAt = $marketingOptIn ? date('Y-m-d H:i:s') : null;
    $contact = $db->prepare(
        'INSERT INTO cl_company_contacts
         (company_id, name, email, phone, source, marketing_opt_in, marketing_consent_at, marketing_consent_version, audit_ref)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $contact->execute([
        (int)$companyId, $contactName, $email, $phone ?: null,
        'scan_public_diagnostic_request', $marketingOptIn ? 1 : 0, $consentedAt,
        $marketingOptIn ? 'public-lead-v1' : null, $auditRef ?: null
    ]);

    gh_lead_reply(200, [
        'success' => true,
        'message' => 'Votre demande de diagnostic est enregistrée. L’équipe pourra vous recontacter au sujet de cette demande.',
        'companyId' => (int)$companyId,
        'auditRef' => $auditRef ?: null,
        'marketingOptIn' => $marketingOptIn
    ]);
} catch (Throwable $e) {
    error_log('GreenHumble: demande de diagnostic public non enregistrée (' . get_class($e) . ').');
    gh_lead_reply(503, ['success' => false, 'error' => 'Enregistrement temporairement indisponible. Vos coordonnées n’ont pas été confirmées comme enregistrées ; réessayez plus tard.']);
}
