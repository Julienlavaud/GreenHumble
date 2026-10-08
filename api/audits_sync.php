<?php
// /api/audits_sync.php
// Sync audits between React frontend and SQL database on OVH
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

$db = get_db_connection();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['audits']) && is_array($input['audits'])) {
        foreach ($input['audits'] as $audit) {
            // Check if audit exists
            $stmt = $db->prepare("SELECT id FROM cl_audits WHERE id = ?");
            $stmt->execute([$audit['id']]);
            $exists = $stmt->fetch();

            if ($exists) {
                // Update
                $stmt = $db->prepare("UPDATE cl_audits SET 
                    company_name = ?, 
                    url = ?, 
                    status = ?, 
                    score = ?, 
                    risk_level = ?, 
                    audit_data = ?,
                    kit_json = ?
                    WHERE id = ?");
                $stmt->execute([
                    $audit['companyName'],
                    $audit['url'],
                    $audit['status'] ?? 'lead',
                    $audit['score'] ?? 0,
                    $audit['riskLevel'] ?? 'low',
                    json_encode($audit),
                    isset($audit['prospectingKit']) ? json_encode($audit['prospectingKit']) : null,
                    $audit['id']
                ]);
            } else {
                // Insert
                $stmt = $db->prepare("INSERT INTO cl_audits (id, company_name, url, status, score, risk_level, audit_data, kit_json, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $audit['id'],
                    $audit['companyName'],
                    $audit['url'],
                    $audit['status'] ?? 'lead',
                    $audit['score'] ?? 0,
                    $audit['riskLevel'] ?? 'low',
                    json_encode($audit),
                    isset($audit['prospectingKit']) ? json_encode($audit['prospectingKit']) : null
                ]);
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

// GET audits (not really used by React sync which usually pulls from /api/state, but good to have)
$stmt = $db->query("SELECT audit_data FROM cl_audits ORDER BY created_at DESC");
$audits = [];
while ($row = $stmt->fetch()) {
    $audits[] = json_decode($row['audit_data'], true);
}
echo json_encode(['success' => true, 'audits' => $audits]);
