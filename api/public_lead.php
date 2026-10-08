<?php
// /api/public_lead.php
// Enregistrement des leads et demandes de diagnostics gratuits issus de la page d'accueil
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$companyName = trim($input['company_name'] ?? '');
$url = trim($input['url'] ?? '');
$contactName = trim($input['contact_name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$notes = trim($input['notes'] ?? 'Demande de diagnostic complet gratuit via le scanner public.');

if (empty($url) && empty($companyName)) {
    echo json_encode(['success' => false, 'error' => 'Veuillez renseigner au moins le nom de l\'entreprise ou l\'URL.']);
    exit;
}

if (empty($companyName)) {
    $parsed = parse_url($url, PHP_URL_HOST) ?: $url;
    $companyName = ucfirst(preg_replace('/^www\./', '', $parsed));
}

try {
    $db = get_db_connection();

    // 1. Insertion ou récupération de l'entreprise
    $stmt = $db->prepare("SELECT id FROM cl_companies WHERE website LIKE ? OR name = ? LIMIT 1");
    $stmt->execute(['%' . $url . '%', $companyName]);
    $companyId = $stmt->fetchColumn();

    if (!$companyId) {
        $insertCompany = $db->prepare("INSERT INTO cl_companies (name, website, contact_name, contact_email, contact_phone, sector, notes) VALUES (?, ?, ?, ?, ?, 'Services & Industrie', ?)");
        $insertCompany->execute([$companyName, $url, $contactName, $email, $phone, $notes]);
        $companyId = $db->lastInsertId();
    } else {
        $updateCompany = $db->prepare("UPDATE cl_companies SET contact_name = COALESCE(NULLIF(?, ''), contact_name), contact_email = COALESCE(NULLIF(?, ''), contact_email), contact_phone = COALESCE(NULLIF(?, ''), contact_phone) WHERE id = ?");
        $updateCompany->execute([$contactName, $email, $phone, $companyId]);
    }

    // 2. Création d'une fiche prospect dans le pipeline (Étape 1 : Prospect Détecté)
    $stmtProspect = $db->prepare("INSERT INTO cl_prospects (company_id, user_id, current_step, commercial_priority, source, notes) VALUES (?, 1, 'prospect_detecte', 'haute', 'scan_public', ?)");
    $stmtProspect->execute([$companyId, "Lead capturé via le formulaire public : $contactName ($email / $phone)"]);

    // 3. Création ou liaison de l'audit
    $stmtAudit = $db->prepare("INSERT INTO cl_audits (company_id, url, score, riskLevel, summary, source, status) VALUES (?, ?, 65, 'eleve', 'Audit initial généré pour le prospect web.', 'scan_public', 'nouveau')");
    $stmtAudit->execute([$companyId, $url]);
    $auditId = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Merci ! Votre demande a été transmise à notre équipe. Un auditeur RSE vous contactera sous 24h avec le dossier complet.',
        'companyId' => $companyId,
        'auditId' => $auditId
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()]);
}
