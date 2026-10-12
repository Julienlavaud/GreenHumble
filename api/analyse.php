<?php
// /api/analyse.php
// Dossier Complet d'Analyse & Diagnostic Prospection Commerciale
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cms.php';
require_once __DIR__ . '/includes/app_state.php';
require_once __DIR__ . '/includes/company_data_provenance.php';
require_login();

$db = get_db_connection();
$st = load_app_state();
$msg = null;
$err = null;
$requestedAuditRef = trim((string)($_GET['id'] ?? ''));
$requestedReportAuditId = preg_match('/^aud_[A-Za-z0-9_-]{8,80}$/', $requestedAuditRef) ? $requestedAuditRef : '';

function gh_analyse_normalize_url(string $value): string {
    $value = trim($value);
    if ($value === '') return '';
    if (!preg_match('~^https?://~i', $value)) $value = 'https://' . $value;
    $host = strtolower((string)(parse_url($value, PHP_URL_HOST) ?: ''));
    $path = trim((string)(parse_url($value, PHP_URL_PATH) ?: ''));
    return preg_replace('/^www\\./', '', $host) . rtrim($path, '/');
}

// Auto-create commercial checklist table if needed
try {
    $db->exec("CREATE TABLE IF NOT EXISTS cl_commercial_checklist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        audit_id INT NOT NULL,
        step_index INT NOT NULL,
        is_done BOOLEAN DEFAULT FALSE,
        notes TEXT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_audit_step (audit_id, step_index)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

// 1. Traitement des formulaires POST
try {
    $hasKitJson = $db->query("SHOW COLUMNS FROM cl_audits LIKE 'kit_json'")->fetch();
    if (!$hasKitJson) {
        $db->exec("ALTER TABLE cl_audits ADD COLUMN kit_json LONGTEXT NULL");
    }
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Validation / Correction d'une allégation spécifique
    if (isset($_POST['update_claim_status'])) {
        $claimId = intval($_POST['claim_id']);
        $validationStatus = $_POST['validation_status'] ?? 'en_attente';
        $correctionNote = trim($_POST['correction_note'] ?? '');
        $severity = $_POST['severity'] ?? 'vigilance';
        $isUnfounded = ($validationStatus === 'non_fonde' || $validationStatus === 'justifie_par_preuve') ? 0 : 1;

        try {
            $stmt = $db->prepare("UPDATE cl_claims SET consultantValidationStatus = ?, correctionNote = ?, severity = ?, isUnfounded = ? WHERE id = ?");
            $stmt->execute([$validationStatus, $correctionNote, $severity, $isUnfounded, $claimId]);

            // Recalcul automatique du score de l'audit
            $stmtAuditId = $db->prepare("SELECT audit_id FROM cl_claims WHERE id = ?");
            $stmtAuditId->execute([$claimId]);
            $audId = $stmtAuditId->fetchColumn();

            if ($audId) {
                $stmtCount = $db->prepare("SELECT COUNT(*) FROM cl_claims WHERE audit_id = ? AND consultantValidationStatus NOT IN ('non_fonde', 'justifie_par_preuve')");
                $stmtCount->execute([$audId]);
                $activeClaimsCount = $stmtCount->fetchColumn();

                $newScore = max(20, min(100, 100 - ($activeClaimsCount * 15)));
                $newRisk = $newScore >= 80 ? 'faible' : ($newScore >= 60 ? 'modere' : ($newScore >= 40 ? 'eleve' : 'critique'));

                $stmtUpdateAud = $db->prepare("UPDATE cl_audits SET score = ?, riskLevel = ?, total_banned_words = ? WHERE id = ?");
                $stmtUpdateAud->execute([$newScore, $newRisk, $activeClaimsCount, $audId]);
            }

            $msg = "Allégation corrigée et score recalculé avec succès.";
        } catch (Exception $e) {
            $err = "Erreur lors de la mise à jour de l'allégation.";
        }
    }

    // B. Enregistrement d'une règle d'apprentissage métier pour l'IA
    if (isset($_POST['save_ai_rule'])) {
        $rule = trim($_POST['ai_rule_text'] ?? '');
        if (!empty($rule)) {
            try {
                $stmtGet = $db->query("SELECT content FROM cl_texts WHERE text_key = 'ai_custom_exceptions' LIMIT 1");
                $existing = $stmtGet->fetchColumn() ?: "";
                $updated = trim($existing . "\n- " . $rule);

                $stmtSave = $db->prepare("INSERT INTO cl_texts (text_key, title, content, category) VALUES ('ai_custom_exceptions', 'Règles Métier & Exceptions IA', ?, 'mots_bannis') ON DUPLICATE KEY UPDATE content = VALUES(content)");
                $stmtSave->execute([$updated]);
                $msg = "Règle métier ajoutée à l'apprentissage de l'IA (Prise en compte immédiate).";
            } catch (Exception $e) {
                $err = "Erreur lors de l'enregistrement de la règle IA.";
            }
        }
    }

    // C. Ajout d'un contact entreprise
    if (isset($_POST['add_company_contact'])) {
        $companyId = intval($_POST['company_id']);
        $name = trim($_POST['contact_name'] ?? '');
        $email = trim($_POST['contact_email'] ?? '');
        $phone = trim($_POST['contact_phone'] ?? '');
        $roleTitle = trim($_POST['contact_role'] ?? '');
        $contactType = $_POST['contact_type'] ?? 'RSE';
        $linkedinUrl = trim($_POST['contact_linkedin'] ?? '');

        if (!empty($name) && $companyId > 0) {
            try {
                $stmt = $db->prepare("INSERT INTO cl_company_contacts (company_id, name, email, phone, roleTitle, contactType, linkedinUrl) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$companyId, $name, $email, $phone, $roleTitle, $contactType, $linkedinUrl]);
                $msg = "Contact ajouté avec succès.";
            } catch (Exception $e) {
                $err = "Erreur lors de l'ajout du contact.";
            }
        }
    }

    // D. Assignation & Statut Dossier
    if (isset($_POST['update_audit_workflow'])) {
        $audId = intval($_POST['audit_id']);
        $assignedTo = trim($_POST['assigned_to'] ?? '');
        $status = $_POST['status'] ?? 'scan_termine';
        $notes = trim($_POST['audit_notes'] ?? '');

        try {
            $stmt = $db->prepare("UPDATE cl_audits SET assigned_to = ?, status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$assignedTo, $status, $notes, $audId]);
            $msg = "Statut du dossier et assignation mis à jour.";
        } catch (Exception $e) {
            $err = "Erreur lors de la mise à jour du dossier.";
        }
    }

    // F. Régénération du Kit de Prospection via IA
    if (isset($_POST['regenerate_kit_ia'])) {
        $audId = intval($_POST['audit_id']);
        $userContext = trim($_POST['user_context'] ?? '');
        
        try {
            // Récupération des données d'audit pour le prompt
            $stmt = $db->prepare("SELECT a.*, c.name as company_name FROM cl_audits a LEFT JOIN cl_companies c ON a.company_id = c.id WHERE a.id = ?");
            $stmt->execute([$audId]);
            $audData = $stmt->fetch();
            
            if ($audData) {
                require_once __DIR__ . '/includes/ai_engine.php';
                
                $stmtCl = $db->prepare("SELECT term, quoteInText, requiredProof, consultantValidationStatus FROM cl_claims WHERE audit_id = ? AND consultantValidationStatus NOT IN ('non_fonde', 'justifie_par_preuve') LIMIT 10");
                $stmtCl->execute([$audId]);
                $claimsForKit = $stmtCl->fetchAll();
                $issuesSummary = json_encode($claimsForKit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $serviceCatalog = [];
                foreach (($st['serviceOpps'] ?? []) as $service) {
                    if (!is_array($service) || !($service['isActive'] ?? true)) continue;
                    $serviceCatalog[] = [
                        'serviceKey' => (string)($service['serviceKey'] ?? $service['id'] ?? ''),
                        'serviceTitle' => (string)($service['serviceTitle'] ?? ''),
                        'description' => (string)($service['description'] ?? ''),
                        'defaultBudget' => (string)($service['defaultBudget'] ?? '')
                    ];
                }

                $customPrompt = "MISSION : Prépare une recommandation commerciale exploitable pour " . ($audData['company_name'] ?: $audData['url']) . ".\\n";
                $customPrompt .= "CONSTATS DU DOSSIER (les statuts justifiés ou non fondés ont été exclus) : " . ($issuesSummary ?: 'Aucun constat documenté : rester général et le signaler.') . "\\n";
                $customPrompt .= "CATALOGUE ACTIF AUTORISÉ : " . json_encode($serviceCatalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\\n";
                if (!empty($userContext)) {
                    $customPrompt .= "CONTEXTE AJOUTÉ PAR LE CONSULTANT : " . $userContext . "\\n";
                }
                $customPrompt .= "CONSIGNE : propose une action concrète, une priorité (haute, moyenne, basse), une offre uniquement par serviceKey/serviceTitle exact du catalogue (sinon null), une justification liée aux citations du dossier, un pitch téléphone et trois emails (juridique, business, réputation). Ne présente jamais un signal non validé comme une infraction et n'invente ni prix, subvention, fait d'entreprise ou risque.";
                $customPrompt .= "\\nRETOUR : JSON uniquement avec {priority, priorityRationale, nextAction, recommendedOffer: {serviceKey, serviceTitle, reason, budget} ou null, evidenceBasedReasons: [{claimTerm, quoteInText, reason}], phone_pitch: {hook, fact, warning, cta}, emails: {legal: {subject, body}, business: {subject, body}, reputation: {subject, body}}}";


                $aiResult = analyze_with_ai($audData['url'], $customPrompt);
                $kitJson = json_decode($aiResult, true);

                if (is_array($kitJson)) {
                    $priority = strtolower(trim((string)($kitJson['priority'] ?? '')));
                    $priorityMap = ['high' => 'haute', 'haute' => 'haute', 'medium' => 'moyenne', 'moyenne' => 'moyenne', 'low' => 'basse', 'basse' => 'basse'];
                    $kitJson['priority'] = $priorityMap[$priority] ?? (in_array((string)($audData['riskLevel'] ?? ''), ['critique', 'eleve'], true) ? 'haute' : (((string)($audData['riskLevel'] ?? '') === 'modere') ? 'moyenne' : 'basse'));
                    $candidateOffer = $kitJson['recommendedOffer'] ?? null;
                    $candidateKey = is_array($candidateOffer) ? trim((string)($candidateOffer['serviceKey'] ?? '')) : '';
                    $candidateTitle = is_array($candidateOffer) ? trim((string)($candidateOffer['serviceTitle'] ?? '')) : trim((string)$candidateOffer);
                    $approvedOffer = null;
                    foreach ($serviceCatalog as $service) {
                        $serviceKey = (string)($service['serviceKey'] ?? $service['id'] ?? '');
                        $serviceTitle = (string)($service['serviceTitle'] ?? '');
                        if (($candidateKey !== '' && $candidateKey === $serviceKey) || ($candidateTitle !== '' && strtolower($candidateTitle) === strtolower($serviceTitle))) {
                            $approvedOffer = [
                                'serviceKey' => $serviceKey,
                                'serviceTitle' => $serviceTitle,
                                'reason' => is_array($candidateOffer) ? trim((string)($candidateOffer['reason'] ?? '')) : ''
                            ];
                            if (trim((string)($service['defaultBudget'] ?? '')) !== '') $approvedOffer['budget'] = (string)$service['defaultBudget'];
                            break;
                        }
                    }
                    $kitJson['recommendedOffer'] = $approvedOffer;
                    $allowedEvidence = [];
                    foreach ($claimsForKit as $claim) {
                        $claimKey = strtolower(trim((string)($claim['term'] ?? ''))) . '|' . strtolower(trim((string)($claim['quoteInText'] ?? '')));
                        $allowedEvidence[$claimKey] = true;
                    }
                    $kitJson['evidenceBasedReasons'] = array_values(array_filter(
                        is_array($kitJson['evidenceBasedReasons'] ?? null) ? $kitJson['evidenceBasedReasons'] : [],
                        static function ($reason) use ($allowedEvidence) {
                            if (!is_array($reason)) return false;
                            $key = strtolower(trim((string)($reason['claimTerm'] ?? ''))) . '|' . strtolower(trim((string)($reason['quoteInText'] ?? '')));
                            return $key !== '|' && isset($allowedEvidence[$key]);
                        }
                    ));
                }

                if (is_array($kitJson) && isset($kitJson['phone_pitch'])) {
                    $stmtSave = $db->prepare("UPDATE cl_audits SET kit_json = ? WHERE id = ?");
                    $stmtSave->execute([json_encode($kitJson), $audId]);
                    $msg = "Kit de prospection régénéré avec succès par l'IA !";
                    // Recharger l'audit pour avoir le nouveau kit
                    $audit['kit_json'] = json_encode($kitJson);
                } else {
                    $err = "L'IA n'a pas pu générer un format valide. Vérifiez votre clé API.";
                }
            }
        } catch (Exception $e) {
            $err = "Erreur lors de la régénération IA : " . $e->getMessage();
        }
    }
}

// 2. Récupération de tous les audits pour le menu déroulant
$allAudits = [];
try {
    $allAudits = $db->query("SELECT a.id, a.url, a.score, a.riskLevel, a.analyzedAt, c.name as company_name, c.sector FROM cl_audits a LEFT JOIN cl_companies c ON a.company_id = c.id ORDER BY a.id DESC LIMIT 50")->fetchAll();
} catch (Exception $e) {}

// Détermination de l'audit actif
$auditId = ctype_digit($requestedAuditRef) ? (int)$requestedAuditRef : ($allAudits[0]['id'] ?? 1);
$requestedReportAudit = null;
if ($requestedReportAuditId !== '') {
    $storedPath = __DIR__ . '/../data/stored_audits.json';
    $storedAudits = is_file($storedPath) ? json_decode((string)@file_get_contents($storedPath), true) : [];
    if (is_array($storedAudits)) {
        foreach ($storedAudits as $storedAudit) {
            if (!is_array($storedAudit) || (string)($storedAudit['id'] ?? '') !== $requestedReportAuditId) continue;
            $requestedReportAudit = $storedAudit;
            break;
        }
    }
    if (is_array($requestedReportAudit)) {
        $requestedCanonicalUrl = gh_analyse_normalize_url((string)($requestedReportAudit['url'] ?? ''));
        foreach ($allAudits as $candidateAudit) {
            $candidateCanonicalUrl = gh_analyse_normalize_url((string)($candidateAudit['url'] ?? ''));
            if ($requestedCanonicalUrl !== '' && $candidateCanonicalUrl === $requestedCanonicalUrl) {
                $auditId = (int)($candidateAudit['id'] ?? 0);
                break;
            }
        }
    }
}
$currentTab = $_GET['tab'] ?? 'diag'; // diag, missio, contact, kit, suivi
if ($currentTab === 'textes') {
    header('Location: /api/admin/index.php?tab=texts', true, 303);
    exit;
}

$audit = null;
if ($auditId) {
    try {
        $stmt = $db->prepare("SELECT a.*, c.name as company_name, c.siren, c.sector, c.headcount as comp_headcount, c.turnover as comp_turnover, c.region as company_region, c.address, c.contact_name as dir_name, c.contact_email as dir_email, c.contact_phone as dir_phone FROM cl_audits a LEFT JOIN cl_companies c ON a.company_id = c.id WHERE a.id = ?");
        $stmt->execute([$auditId]);
        $audit = $stmt->fetch();
    } catch (Exception $e) {}
}

if (!$audit && !empty($allAudits)) {
    $audit = $allAudits[0];
}

// Si la base est vide, n’inventer aucun audit de démonstration.
if (!$audit) {
    $audit = [
        'id' => 0,
        'company_id' => 0,
        'url' => '',
        'score' => 0,
        'riskLevel' => 'a_evaluer',
        'company_name' => 'Aucun audit enregistré',
        'siren' => null,
        'sector' => null,
        'analyzedAt' => date('Y-m-d H:i:s'),
        'summary' => 'Aucun audit n’est encore disponible. Lancez un premier scan pour alimenter cette analyse.',
        'financial_impact' => '',
        'pillar_1_score' => 0,
        'pillar_2_score' => 0,
        'pillar_3_score' => 0,
        'pillar_4_score' => 0,
        'pillar_5_score' => 0,
        'pillar_6_score' => 0,
        'assigned_to' => '',
        'status' => 'vide',
        'notes' => ''
    ];
}

// Récupération des allégations
$claims = [];
if ($audit && isset($audit['id'])) {
    try {
        $stmtClaims = $db->prepare("SELECT * FROM cl_claims WHERE audit_id = ? ORDER BY id ASC");
        $stmtClaims->execute([$audit['id']]);
        $claims = $stmtClaims->fetchAll();
    } catch (Exception $e) {}
}

// Les audits récents sont stockés dans stored_audits.json, tandis que cette page conserve
// les outils historiques basés sur MySQL. Pour une URL déjà auditée, afficher les constats
// du rapport canonique afin que les deux vues ne se contredisent pas.
$reportAuditId = '';
if ($audit && !empty($audit['url'])) {
    $canonicalUrl = strtolower(rtrim((string)$audit['url'], '/'));
    $storedPath = __DIR__ . '/../data/stored_audits.json';
    $storedAudits = is_file($storedPath) ? json_decode((string)@file_get_contents($storedPath), true) : [];
    if (is_array($storedAudits)) {
        foreach ($storedAudits as $storedAudit) {
            if (!is_array($storedAudit) || empty($storedAudit['id']) || empty($storedAudit['url'])) continue;
            if (strtolower(rtrim((string)$storedAudit['url'], '/')) !== $canonicalUrl) continue;
            $reportAuditId = (string)$storedAudit['id'];
            $storedClaims = $storedAudit['detectedClaims'] ?? [];
            if (is_array($storedClaims)) {
                $claims = array_map(static function ($claim) {
                    if (!is_array($claim)) $claim = ['term' => (string)$claim];
                    return array_merge([
                        'term' => $claim['term'] ?? $claim['claim'] ?? $claim['claimText'] ?? $claim['text'] ?? $claim['title'] ?? $claim['label'] ?? 'Allégation détectée',
                        'severity' => $claim['severity'] ?? $claim['riskLevel'] ?? 'vigilance',
                        'consultantValidationStatus' => $claim['consultantValidationStatus'] ?? 'en_attente',
                        'quoteInText' => $claim['quoteInText'] ?? $claim['quote'] ?? $claim['evidence'] ?? $claim['sourceExcerpt'] ?? '',
                        'legalRisk' => $claim['legalRisk'] ?? $claim['riskReason'] ?? $claim['explanation'] ?? '',
                        'recommendation' => $claim['recommendation'] ?? $claim['action'] ?? ''
                    ], $claim);
                }, $storedClaims);
            }
            break;
        }
    }
}
if ($reportAuditId === '' && $requestedReportAuditId !== '') {
    $reportAuditId = $requestedReportAuditId;
}

// Récupération des contacts
$contacts = [];
if ($audit && !empty($audit['company_id'])) {
    try {
        $stmtC = $db->prepare("SELECT * FROM cl_company_contacts WHERE company_id = ? ORDER BY id ASC");
        $stmtC->execute([$audit['company_id']]);
        $contacts = $stmtC->fetchAll();
    } catch (Exception $e) {}
}

// Récupération de l'ensemble des cadres réglementaires complets
$frameworks = [];
try {
    $frameworks = $db->query("SELECT * FROM cl_regulatory_frameworks WHERE is_active = 1 ORDER BY jurisdiction ASC, family ASC, title ASC")->fetchAll();
} catch (Exception $e) {}

// Récupération des utilisateurs / auditeurs pour assignation
$usersList = [];
try {
    $usersList = $db->query("SELECT id, username, role FROM cl_users ORDER BY username ASC")->fetchAll();
} catch (Exception $e) {}

// Récupération des étapes de la checklist commerciale pour cet audit
$checklistState = [];
if ($audit && isset($audit['id'])) {
    try {
        $stmtChk = $db->prepare("SELECT step_index, is_done FROM cl_commercial_checklist WHERE audit_id = ?");
        $stmtChk->execute([$audit['id']]);
        while ($row = $stmtChk->fetch(PDO::FETCH_ASSOC)) {
            $checklistState[$row['step_index']] = (bool)$row['is_done'];
        }
    } catch (Exception $e) {}
}

$page_title = "Dossier & Prospection - " . ($audit['company_name'] ?? 'Audit RSE');
include __DIR__ . '/includes/header.php';
?>

<div class="gh-analysis-page max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php if ($reportAuditId !== ''): ?><div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm text-emerald-900"><span>Constats synchronisés avec l’audit enregistré.</span><a class="font-bold underline" href="/api/analyse.php?id=<?= rawurlencode($reportAuditId) ?>&amp;tab=<?= rawurlencode($currentTab) ?>">Rester sur l’analyse complète →</a></div><?php endif; ?>
    <!-- Messages d'alerte -->
    <?php if ($msg): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center space-x-2">
            <span>✅ <?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded-2xl font-bold">
            <?php echo htmlspecialchars($err); ?>
        </div>
    <?php endif; ?>

    <!-- Header PRO : Sélecteur d'Entreprise Centralisé (Refined for better readability) -->
    <div id="section_header_pro" class="bg-slate-900 rounded-[3rem] p-10 sm:p-14 mb-12 shadow-2xl relative overflow-hidden border border-slate-800 text-center animate-fadeIn">
        <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-5 pointer-events-none"></div>
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/5 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-blue-500/5 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="relative z-10 max-w-4xl mx-auto space-y-8">
            <div class="inline-flex items-center space-x-3 px-4 py-2 bg-emerald-500/10 border border-emerald-400/30 rounded-full text-emerald-300 text-[10px] font-black uppercase tracking-widest shadow-xl">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span>Analyseur de Conformité PRO v2026</span>
            </div>

            <div class="space-y-4">
                <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tighter leading-none italic">
                    <?php echo htmlspecialchars($audit['company_name'] ?? 'Audit en cours'); ?>
                </h1>
                <div class="flex flex-wrap items-center justify-center gap-4 text-slate-400 font-mono text-sm">
                    <span class="flex items-center space-x-1.5">
                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" /></svg>
                        <a href="<?php echo htmlspecialchars($audit['url']); ?>" target="_blank" class="hover:text-emerald-400 transition-colors"><?php echo htmlspecialchars($audit['url']); ?></a>
                    </span>
                    <span class="w-1 h-1 bg-slate-700 rounded-full"></span>
                    <span>Scanné le <?php echo date('d/m/Y à H:i', strtotime($audit['analyzedAt'] ?? 'now')); ?></span>
                </div>
            </div>

            <!-- LE SÉLECTEUR CENTRAL (Gros & Imposant) -->
            <div class="max-w-2xl mx-auto pt-6 no-print">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none z-20">
                        <svg class="h-6 w-6 text-emerald-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <select onchange="window.location.href='/api/analyse.php?id=' + this.value + '&tab=<?php echo urlencode($currentTab); ?>'" 
                            class="w-full pl-16 pr-10 py-6 bg-white/5 hover:bg-white/10 border-2 border-emerald-500/30 rounded-[2rem] text-xl font-black text-white focus:ring-8 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all cursor-pointer shadow-2xl backdrop-blur-md appearance-none">
                        <?php foreach ($allAudits as $aItem): ?>
                            <option value="<?php echo $aItem['id']; ?>" <?php echo $aItem['id'] == $audit['id'] ? 'selected' : ''; ?> class="bg-slate-900 text-white py-4 font-bold">
                                <?php echo htmlspecialchars($aItem['company_name'] ?: preg_replace('#^https?://(www\.)?#i', '', $aItem['url'])); ?> • Score <?php echo $aItem['score']; ?>/100
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 right-6 flex items-center pointer-events-none z-20">
                        <svg class="h-6 w-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 mt-4 uppercase tracking-[0.2em] font-black">Changer d'entreprise scannée</p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                    <button type="button" onclick="openPdfModal()" class="inline-flex items-center gap-2 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-5 py-3 text-xs font-black uppercase tracking-widest text-emerald-300 shadow-xl transition hover:bg-emerald-500/20">
                        <span>🖨️</span>
                        <span>Imprimer le rapport</span>
                    </button>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Choix des sections avant impression</span>
                </div>
            <!-- Scores & Statut (Version PRO) -->
            <div id="section_scores_pro" class="grid grid-cols-2 sm:grid-cols-4 gap-6 pt-10 border-t border-white/10 mt-10">
                <div class="p-6 rounded-[2rem] bg-white/5 border border-white/10 backdrop-blur-sm group hover:bg-emerald-500/10 hover:border-emerald-500/30 transition-all">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-2">Score Conformité</span>
                    <div class="text-3xl font-black <?php echo $audit['score'] >= 80 ? 'text-emerald-400' : ($audit['score'] >= 60 ? 'text-amber-400' : 'text-red-400'); ?>">
                        <?php echo $audit['score']; ?><span class="text-xs font-normal text-slate-500">/100</span>
                    </div>
                    <div class="text-[9px] text-slate-600 mt-1 uppercase font-bold italic">Calcul multi-piliers</div>
                </div>

                <div class="p-6 rounded-[2rem] bg-white/5 border border-white/10 backdrop-blur-sm group hover:bg-indigo-500/10 hover:border-indigo-500/30 transition-all">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-2">Risque Juridique</span>
                    <div class="text-sm font-black uppercase <?php echo $audit['riskLevel'] === 'faible' ? 'text-emerald-400' : ($audit['riskLevel'] === 'modere' ? 'text-amber-400' : 'text-red-400'); ?> mt-2">
                        <?php echo htmlspecialchars($audit['riskLevel'] ?? 'modere'); ?>
                    </div>
                    <div class="text-[9px] text-slate-600 mt-1 uppercase font-bold italic italic">Directive EmpCO (2024)</div>
                </div>

                <div class="p-6 rounded-[2rem] bg-white/5 border border-white/10 backdrop-blur-sm group hover:bg-blue-500/10 hover:border-blue-500/30 transition-all">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-2">Allégations</span>
                    <div class="text-3xl font-black text-white">
                        <?php echo count($claims); ?>
                    </div>
                    <div class="text-[9px] text-slate-600 mt-1 uppercase font-bold italic italic">Extraits à risque</div>
                </div>

                <div class="p-6 rounded-[2rem] bg-white/5 border border-white/10 backdrop-blur-sm group hover:bg-amber-500/10 hover:border-amber-500/30 transition-all">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-2">Statut Audit</span>
                    <div class="text-xs font-black text-white mt-3 uppercase tracking-tighter">
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $audit['status'] ?? 'Nouveau'))); ?>
                    </div>
                    <div class="text-[9px] text-emerald-500 mt-1 font-bold truncate"><?php echo htmlspecialchars($audit['assigned_to'] ? $audit['assigned_to'] : 'Dossier Ouvert'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6 Onglets de Navigation Cockpit PRO - Simplified & High Contrast -->
    <div class="flex flex-wrap gap-2 p-2 bg-slate-100 rounded-2xl border border-slate-200 shadow-sm mb-10 overflow-x-auto no-scrollbar no-print">
        <?php 
        $navTabs = [
            'diag' => ['label' => 'Diagnostic', 'emoji' => '📊', 'count' => count($claims)],
            'missio' => ['label' => 'Offres & Missions', 'emoji' => '💼', 'count' => '4 Offres'],
            'contact' => ['label' => 'Contacts', 'emoji' => '👥', 'count' => count($contacts)],
            'kit' => ['label' => 'Kit Prospection', 'emoji' => '🎯', 'count' => 'Emails'],
            'suivi' => ['label' => 'Checklist', 'emoji' => '📋', 'count' => null]
        ];
        foreach ($navTabs as $id => $info): 
            $isActive = ($currentTab === $id);
            ?>
            <a href="?id=<?php echo $audit['id']; ?>&tab=<?php echo $id; ?>" 
               class="px-5 py-3 rounded-xl text-xs sm:text-sm font-extrabold transition-all flex items-center space-x-2 shadow-sm <?php echo $isActive ? 'bg-emerald-700 text-white border border-emerald-800 shadow-md ring-2 ring-emerald-500/20' : 'bg-white text-slate-700 hover:bg-slate-200 border border-slate-200 hover:text-slate-900'; ?>">
                <span class="text-base"><?php echo $info['emoji']; ?></span>
                <span><?php echo $info['label']; ?></span>
                <?php if ($info['count'] !== null): ?>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold <?php echo $isActive ? 'bg-emerald-900 text-emerald-100' : 'bg-slate-100 text-slate-600 border border-slate-200'; ?>"><?php echo $info['count']; ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- ONGLET 1 : DIAGNOSTIC APPROFONDI & ALLÉGATIONS ÉVALUÉES -->
    <!-- ========================================================================= -->
    <?php if ($currentTab === 'diag'): ?>
        <div class="space-y-8">
            <!-- Profil & Identité Entreprise (Nombre de salariés, CA & Secteur) -->
            <?php
            $auditPayload = gh_company_raw_audit_payload($audit);
            $companyProvenance = is_array($auditPayload['companyRegistry'] ?? null) ? $auditPayload['companyRegistry'] : [];
            $headcountValue = $auditPayload['entreprise_salaries'] ?? ($audit['headcount'] ?? ($audit['comp_headcount'] ?? null));
            $turnoverValue = $auditPayload['entreprise_ca'] ?? ($audit['turnover'] ?? ($audit['comp_turnover'] ?? null));
            $sectorValue = $auditPayload['entreprise_secteur'] ?? ($audit['sector'] ?? null);
            $displayHeadcount = trim((string)$headcountValue) !== '' ? (string)$headcountValue : 'Non communiqué';
            $displayTurnover = trim((string)$turnoverValue) !== '' ? (string)$turnoverValue : 'Non communiqué';
            $displaySector = trim((string)$sectorValue) !== '' ? (string)$sectorValue : 'Non renseigné';
            $headcountSource = gh_company_provenance_label($companyProvenance['headcountSource'] ?? null, $displayHeadcount !== 'Non communiqué');
            $turnoverSource = gh_company_provenance_label($companyProvenance['turnoverSource'] ?? null, $displayTurnover !== 'Non communiqué');
            $sectorSource = gh_company_provenance_label($companyProvenance['sectorSource'] ?? null, $displaySector !== 'Non renseigné');
            $headcountYear = trim((string)($companyProvenance['headcountYear'] ?? ''));
            $turnoverPeriod = trim((string)($companyProvenance['turnoverPeriod'] ?? ''));
            ?>
            <div id="section_company_profile" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-xs font-bold uppercase text-emerald-700 tracking-wider">Identité & Profil Entreprise</span>
                        <h2 class="text-xl font-black text-slate-900 mt-0.5"><?php echo htmlspecialchars($audit['company_name'] ?? 'Entreprise'); ?></h2>
                    </div>
                    <span class="px-3 py-1 bg-slate-100 text-slate-800 rounded-full text-xs font-bold border border-slate-200">
                        SIREN : <?php echo htmlspecialchars($audit['siren'] ?? 'N/C'); ?>
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-6">
                    <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-100 flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-lg">👥</div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800">Nombre de salariés</span>
                            <div class="text-base font-black text-slate-900"><?php echo htmlspecialchars($displayHeadcount); ?></div>
                            <div class="text-[10px] text-slate-500">Source : <?php echo htmlspecialchars($headcountSource); ?><?php echo $headcountYear !== '' ? ' · année ' . htmlspecialchars($headcountYear) : ''; ?></div>
                        </div>
                    </div>

                    <div class="p-4 bg-teal-50/60 rounded-2xl border border-teal-100 flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-lg">💶</div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-teal-800">Chiffre d'Affaires (CA)</span>
                            <div class="text-base font-black text-slate-900"><?php echo htmlspecialchars($displayTurnover); ?></div>
                            <div class="text-[10px] text-slate-500">Source : <?php echo htmlspecialchars($turnoverSource); ?><?php echo $turnoverPeriod !== '' ? ' · période ' . htmlspecialchars($turnoverPeriod) : ''; ?></div>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-200 text-slate-800 flex items-center justify-center font-bold text-lg">🏭</div>
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Secteur d'activité</span>
                            <div class="text-base font-black text-slate-900 truncate max-w-[200px]"><?php echo htmlspecialchars($displaySector); ?></div>
                            <div class="text-[10px] text-slate-500">Source : <?php echo htmlspecialchars($sectorSource); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Synthèse Juridique & Impact Financier -->
            <div id="section_synthese" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                        <span>🔍</span>
                        <span>Synthèse Juridique & Conformité EmpCO</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <?php echo nl2br(htmlspecialchars($audit['summary'] ?? 'Aucune synthèse disponible.')); ?>
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 pt-4 text-xs">
                        <?php 
                        $pillars = [
                            1 => '🚫 Mots Bannis',
                            2 => '⚖️ Preuves',
                            3 => '🔍 Transparence',
                            4 => '🛡️ Risque Légal',
                            5 => '🌱 Éco-conception',
                            6 => '📈 Action'
                        ];
                        foreach ($pillars as $num => $label): 
                            $score = $audit["pillar_{$num}_score"] ?? 50;
                            $colorClass = $score >= 80 ? 'text-emerald-700 bg-emerald-50 border-emerald-100' : ($score >= 60 ? 'text-amber-700 bg-amber-50 border-amber-100' : 'text-red-700 bg-red-50 border-red-100');
                        ?>
                            <div class="p-3 <?php echo $colorClass; ?> rounded-2xl border transition-all hover:shadow-md">
                                <strong class="block text-[9px] uppercase tracking-tighter opacity-70"><?php echo $label; ?></strong>
                                <div class="text-lg font-black mt-1"><?php echo $score; ?><span class="text-[10px] font-normal opacity-50">/100</span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Risque Financier & Amende DGCCRF -->
                <div id="section_sanctions" class="bg-gradient-to-br from-slate-900 to-climat-950 text-white p-6 sm:p-8 rounded-3xl shadow-xl space-y-4 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-black uppercase text-red-400 tracking-wider">Exposition Financière DGCCRF</span>
                        <h3 class="text-xl font-black text-white mt-1">Évaluation des Risques</h3>
                        <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                            <?php echo htmlspecialchars($audit['financial_impact'] ?? 'Sanction pouvant atteindre 100 000 € ou 4% du chiffre d\'affaires annuel.'); ?>
                        </p>
                    </div>
                    <div class="p-4 bg-red-950/60 border border-red-800/80 rounded-2xl text-xs space-y-1">
                        <div class="font-bold text-red-200">Article L. 121-2 Code Conso :</div>
                        <div class="text-red-300/90 text-[11px]">Pratique commerciale trompeuse • Jusqu'à 2 ans d'emprisonnement et 300 000 € d'amende.</div>
                    </div>
                </div>
            </div>

            <!-- Bloc d'Apprentissage Métier & Correction IA -->
            <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-100 border border-emerald-200 rounded-3xl p-6 no-print">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-emerald-950 flex items-center space-x-2">
                            <span>🎓</span>
                            <span>Affiner & Corriger le Moteur d'IA (Apprentissage & Exceptions Métier)</span>
                        </h3>
                        <p class="text-xs text-emerald-800">
                            Enseignez des exceptions au moteur IA (ex: <em>"Nous vendons des projets carbone, autoriser le mot compensation carbone sans pénalité"</em>).
                        </p>
                    </div>
                    <form method="POST" action="/api/analyse.php?id=<?php echo $audit['id']; ?>&tab=diag" class="flex items-center gap-2 min-w-[320px]">
                        <input type="text" name="ai_rule_text" required placeholder="Ex: Autoriser 'compensation carbone' pour nos services..." class="w-full px-3.5 py-2 bg-white border border-emerald-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <button type="submit" name="save_ai_rule" class="px-4 py-2 bg-emerald-800 hover:bg-emerald-900 text-white font-bold rounded-xl text-xs whitespace-nowrap shadow-sm">
                            Enregistrer
                        </button>
                    </form>
                </div>
            </div>

            <!-- Pépites de Prospection & Finesse Commerciale -->
            <div id="section_prospecting_insights" class="grid grid-cols-1 md:grid-cols-2 gap-6 no-print">
                <div class="bg-indigo-900 text-indigo-50 p-6 sm:p-8 rounded-[2.5rem] shadow-xl border border-indigo-500/20 space-y-4">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">🎯</span>
                        <h3 class="text-lg font-black tracking-tight text-white">Leviers de Prospection</h3>
                    </div>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-2 text-xs">
                            <span class="text-indigo-400 font-bold mt-0.5">➔</span>
                            <span><strong>Angle Sanction :</strong> Évoquer les contrôles DGCCRF sur l'allégation « <?php echo htmlspecialchars($claims[0]['term'] ?? 'Générique'); ?> » (Article L. 121-2).</span>
                        </li>
                        <li class="flex items-start space-x-2 text-xs">
                            <span class="text-indigo-400 font-bold mt-0.5">➔</span>
                            <span><strong>Angle Concurrentiel :</strong> Les leaders du secteur <?php echo htmlspecialchars($audit['sector'] ?? 'du client'); ?> utilisent déjà des ACV normées.</span>
                        </li>
                        <li class="flex items-start space-x-2 text-xs">
                            <span class="text-indigo-400 font-bold mt-0.5">➔</span>
                            <span><strong>Angle Subvention :</strong> Financement possible via Bpifrance (Diag Décarbon'Action) pour corriger ces points.</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-white p-6 sm:p-8 rounded-[2.5rem] border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">📑</span>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Éléments de Réassurance</h3>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl text-[11px] text-slate-600 leading-relaxed italic border-l-4 border-emerald-500">
                        "L'objectif de notre approche n'est pas de sanctionner mais de sécuriser votre communication avant l'entrée en vigueur de la directive Green Claims (2026), tout en valorisant vos réels engagements RSE."
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="navigator.clipboard.writeText('Bonjour, nous avons analysé votre site web et identifié quelques allégations à risque au regard de la nouvelle directive EmpCO. Nous pouvons vous aider à les sécuriser.'); alert('Accroche copiée !');" class="flex-grow py-3 bg-slate-900 text-white text-[10px] font-black rounded-xl uppercase tracking-widest hover:bg-emerald-700 transition-all">Copier Accroche Email</button>
                    </div>
                </div>
            </div>

            <!-- Tableau Détaillé des Allégations & Citations Relevées -->
            <div id="section_allegations" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Tableau Exhaustif des Allégations Détectées</h2>
                        <p class="text-xs text-slate-500">Examen sémantique, citation textuelle, texte de loi opposable et opportunité de rectification</p>
                    </div>
                    <span class="text-xs px-3 py-1 bg-slate-100 text-slate-700 font-bold rounded-full">
                        <?php echo count($claims); ?> mention(s) analysée(s)
                    </span>
                </div>

                <div class="divide-y divide-slate-100">
                    <?php if (empty($claims)): ?>
                        <div class="p-8 text-center text-xs text-slate-400">
                            Aucune allégation à haut risque détectée sur cette URL.
                        </div>
                    <?php else: ?>
                        <?php foreach ($claims as $cl): ?>
                            <div class="p-6 sm:p-8 space-y-4 hover:bg-slate-50/50 transition-colors">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center space-x-3">
                                        <span class="text-xl">
                                            <?php echo $cl['severity'] === 'interdit_strict' ? '⛔' : ($cl['severity'] === 'haut_risque' ? '⚠️' : '🔍'); ?>
                                        </span>
                                        <div>
                                            <h3 class="text-base font-black text-slate-900"><?php echo htmlspecialchars($cl['term']); ?></h3>
                                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full uppercase tracking-wider <?php echo $cl['severity'] === 'interdit_strict' ? 'bg-red-100 text-red-800 border border-red-200' : ($cl['severity'] === 'haut_risque' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-blue-100 text-blue-800 border border-blue-200'); ?>">
                                                <?php echo htmlspecialchars(str_replace('_', ' ', $cl['severity'])); ?>
                                            </span>
                                            <?php if ($cl['consultantValidationStatus'] === 'justifie_par_preuve'): ?>
                                                <span class="ml-2 text-[10px] font-bold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full">✅ Justifié par Preuve RSE</span>
                                            <?php elseif ($cl['consultantValidationStatus'] === 'non_fonde'): ?>
                                                <span class="ml-2 text-[10px] font-bold px-2 py-0.5 bg-slate-200 text-slate-700 rounded-full">⚖️ Exception Validée</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Bouton Modal de Correction / Justification -->
                                    <div class="no-print">
                                        <button onclick="openClaimCorrectionModal(<?php echo htmlspecialchars(json_encode($cl), ENT_QUOTES, 'UTF-8'); ?>)" class="px-3.5 py-1.5 bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-bold shadow-sm transition-all flex items-center space-x-1">
                                            <span>✏️ Justifier / Corriger la note</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Citation exacte / Masque grisé du texte concerné -->
                                <?php if (!empty($cl['quoteInText'])): ?>
                                    <div class="gray-mask text-xs space-y-2 mb-6">
                                        <div class="flex items-center justify-between text-[10px] text-slate-400 font-sans tracking-widest uppercase font-black">
                                            <span class="flex items-center space-x-1.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                <span>Contenu scanné sur le site de l'entreprise</span>
                                            </span>
                                            <span class="px-2 py-0.5 bg-red-500/10 text-red-400 border border-red-500/20 rounded-md">Extrait Identifié</span>
                                        </div>
                                        <div class="italic text-slate-300 leading-relaxed border-l-2 border-emerald-500/30 pl-4 py-1">
                                            « <?php echo htmlspecialchars($cl['quoteInText']); ?> »
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Grille d'Analyse Légale & Risque -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                                    <div class="p-3 bg-red-50/60 rounded-xl border border-red-100 space-y-1">
                                        <strong class="text-red-900 block text-[11px] uppercase">Risque Légal & Motif :</strong>
                                        <p class="text-red-800/90"><?php echo htmlspecialchars($cl['legalRisk'] ?? 'Non-conformité identifiée.'); ?></p>
                                    </div>

                                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                                        <strong class="text-slate-800 block text-[11px] uppercase">Texte & Article Opposable :</strong>
                                        <p class="text-slate-700 font-semibold"><?php echo htmlspecialchars($cl['lawArticle'] ?? 'Directive (UE) 2024/825'); ?></p>
                                        <span class="text-[10px] text-slate-500 block"><?php echo htmlspecialchars($cl['sanction'] ?? 'DGCCRF'); ?></span>
                                    </div>

                                    <div class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-100 space-y-1">
                                        <strong class="text-emerald-900 block text-[11px] uppercase">Preuve Requise & Opportunité :</strong>
                                        <p class="text-emerald-800"><?php echo htmlspecialchars($cl['requiredProof'] ?? 'Bilan Carbone ou ACV normée.'); ?></p>
                                    </div>
                                </div>

                                <?php if (!empty($cl['correctionNote'])): ?>
                                    <div class="p-3 bg-blue-50 border border-blue-200 text-blue-950 rounded-xl text-xs">
                                        <strong>💡 Note & Formulation Conforme Conseillée :</strong>
                                        <div class="mt-0.5"><?php echo htmlspecialchars($cl['correctionNote']); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- ONGLET 2 : MISSIONS & OPPORTUNITÉS DE VALORISATION -->
    <!-- ========================================================================= -->
    <?php if ($currentTab === 'missio'): ?>
        <div id="section_missions" class="space-y-6">
            <?php
            $missions = [];
            $kitForMissions = json_decode((string)($audit['kit_json'] ?? ''), true);
            $kitOffer = is_array($kitForMissions['recommendedOffer'] ?? null) ? $kitForMissions['recommendedOffer'] : [];
            $kitOfferKey = trim((string)($kitOffer['serviceKey'] ?? ''));
            $kitOfferTitle = trim((string)($kitOffer['serviceTitle'] ?? ''));
            $scanSignalText = '';
            foreach ($claims as $claim) {
                $status = (string)($claim['consultantValidationStatus'] ?? '');
                if (in_array($status, ['non_fonde', 'justifie_par_preuve'], true)) continue;
                foreach (['term', 'quoteInText', 'requiredProof', 'legalRisk'] as $field) {
                    if (!empty($claim[$field])) $scanSignalText .= ' ' . (string)$claim[$field];
                }
            }
            $missionRules = [
                ['triggers' => ['carbone', 'émission', 'emission', 'ges', 'scope', 'neutralité', 'neutralite', 'compensation', 'climat'], 'services' => ['carbone', 'climat', 'bilan ges', 'scope', 'émission', 'emission']],
                ['triggers' => ['acv', 'fdes', 'epd', 'recycl', 'biodégrad', 'biodegrad', 'matériau', 'materiau', 'durable', 'preuve'], 'services' => ['acv', 'fdes', 'epd', 'matériau', 'materiau', 'recycl', 'écoconception', 'ecoconception', 'conformité', 'conformite']],
                ['triggers' => ['écologique', 'ecologique', 'éco-responsable', 'eco-responsable', 'vert', 'greenwashing', 'allégation', 'allegation', 'rse', 'esg', 'reporting'], 'services' => ['rse', 'esg', 'conformité', 'conformite', 'reporting', 'stratégie', 'strategie']]
            ];
            $containsText = static function ($haystack, $needle) {
                return function_exists('mb_stripos')
                    ? mb_stripos((string)$haystack, (string)$needle, 0, 'UTF-8') !== false
                    : stripos((string)$haystack, (string)$needle) !== false;
            };
            foreach (($st['serviceOpps'] ?? []) as $idx => $mission) {
                if (!is_array($mission) || !($mission['isActive'] ?? true)) continue;
                $missionTitle = trim((string)($mission['serviceTitle'] ?? ''));
                if ($missionTitle === '') continue;
                $missionText = $missionTitle . ' ' . (string)($mission['description'] ?? '');
                $missionKey = (string)($mission['serviceKey'] ?? $mission['id'] ?? '');
                $recommended = ($kitOfferKey !== '' && $kitOfferKey === $missionKey)
                    || ($kitOfferTitle !== '' && strcasecmp($kitOfferTitle, $missionTitle) === 0);
                if (!$recommended && $scanSignalText !== '') {
                    foreach ($missionRules as $rule) {
                        $triggered = false;
                        foreach ($rule['triggers'] as $trigger) {
                            if ($containsText($scanSignalText, $trigger)) { $triggered = true; break; }
                        }
                        if (!$triggered) continue;
                        foreach ($rule['services'] as $serviceKeyword) {
                            if ($containsText($missionText, $serviceKeyword)) { $recommended = true; break 2; }
                        }
                    }
                }
                $mission['_recommended'] = $recommended;
                $mission['_id'] = (string)($mission['id'] ?? ('mission_' . $idx));
                $missions[] = $mission;
            }
            $recommendedCount = count(array_filter($missions, static fn($mission) => !empty($mission['_recommended'])));
            $showAllInitially = $recommendedCount === 0;
            ?>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-5 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-800">
                            <span aria-hidden="true">💼</span><span><?php echo count($missions); ?> accompagnement(s) actif(s)</span>
                        </div>
                        <h2 class="text-xl font-black tracking-tight text-slate-900 sm:text-2xl">Missions recommandées</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">Sélection basée sur les constats encore à vérifier dans ce dossier. Une recommandation ne signifie pas qu’une infraction est établie.</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2" role="group" aria-label="Filtrer les missions">
                        <?php if ($recommendedCount > 0): ?>
                            <button type="button" class="mission-filter-btn rounded-xl bg-emerald-800 px-4 py-2.5 text-xs font-bold text-white" data-filter="recommended" aria-pressed="true">Recommandées (<?php echo $recommendedCount; ?>)</button>
                            <button type="button" class="mission-filter-btn rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50" data-filter="all" aria-pressed="false">Toutes (<?php echo count($missions); ?>)</button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($recommendedCount === 0): ?>
                    <p class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm leading-relaxed text-blue-900">Aucune mission ne correspond directement aux constats non écartés du scan. Voici le catalogue complet pour garder le choix au consultant.</p>
                <?php else: ?>
                    <p id="mission-filter-status" class="mt-4 text-xs font-medium text-slate-500" role="status"><?php echo $recommendedCount; ?> mission(s) mise(s) en avant à partir des résultats du scan.</p>
                <?php endif; ?>

                <div id="mission-catalogue" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($missions as $mission): ?>
                        <article class="mission-pro-card flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-emerald-300 hover:bg-white hover:shadow-md"
                                 data-recommended="<?php echo !empty($mission['_recommended']) ? '1' : '0'; ?>"
                                 <?php echo (!$showAllInitially && empty($mission['_recommended'])) ? 'hidden' : ''; ?>>
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="min-w-0 flex-1 break-words text-base font-extrabold leading-snug text-slate-900"><?php echo htmlspecialchars($mission['serviceTitle'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h3>
                                <?php if (!empty($mission['_recommended'])): ?>
                                    <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-800">Liée au scan</span>
                                <?php endif; ?>
                            </div>
                            <p class="mt-3 flex-1 whitespace-pre-line break-words text-sm leading-relaxed text-slate-600"><?php echo htmlspecialchars(trim((string)($mission['description'] ?? '')) ?: 'Description à compléter dans le catalogue métier.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                            <div class="mt-5 space-y-2 border-t border-slate-200 pt-4">
                                <?php if (trim((string)($mission['defaultBudget'] ?? '')) !== ''): ?>
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                        <span class="font-semibold text-slate-500">Budget indicatif</span>
                                        <span class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 font-bold text-slate-800"><?php echo htmlspecialchars((string)$mission['defaultBudget'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($mission['subsidyAmount']) && $mission['subsidyAmount'] !== '0 €'): ?>
                                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                        <span class="font-semibold text-emerald-700">Aide / subvention</span>
                                        <span class="rounded-lg border border-emerald-100 bg-emerald-50 px-2.5 py-1.5 font-bold text-emerald-800"><?php echo htmlspecialchars((string)$mission['subsidyAmount'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php
                            $copyText = 'Offre Climat Local : ' . (string)($mission['serviceTitle'] ?? '') . ' — Budget indicatif : ' . (string)($mission['defaultBudget'] ?? '') . '. ' . (string)($mission['description'] ?? '');
                            ?>
                            <button type="button" class="mission-copy-btn mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3 text-xs font-bold text-white transition hover:bg-emerald-800" data-copy="<?php echo htmlspecialchars($copyText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                <span>Copier dans le devis</span><span aria-hidden="true">↗</span>
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($missions)): ?>
                    <p class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-600">Aucun accompagnement actif. Activez les offres à proposer dans le catalogue métier de l’administration.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- ONGLET 4 : CONTACTS & DÉCIDEURS RSE -->
    <!-- ========================================================================= -->
    <?php if ($currentTab === 'contact'): ?>
        <div id="section_contacts" class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Formulaire d'ajout de contact -->
                <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-4 no-print">
                    <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                        <span>➕</span>
                        <span>Ajouter un Décideur / Interlocuteur</span>
                    </h2>

                    <form method="POST" action="/api/analyse.php?id=<?php echo $audit['id']; ?>&tab=contact" class="space-y-3">
                        <input type="hidden" name="company_id" value="<?php echo $audit['company_id'] ?? 1; ?>">

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nom & Prénom</label>
                            <input type="text" name="contact_name" required placeholder="Ex: Claire Martin" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Fonction / Rôle</label>
                            <input type="text" name="contact_role" placeholder="Directrice RSE, Dirigeant, Resp. Marketing" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email professionnel</label>
                            <input type="email" name="contact_email" placeholder="claire.martin@entreprise.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Téléphone direct</label>
                            <input type="tel" name="contact_phone" placeholder="06 12 34 56 78" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Profil LinkedIn</label>
                            <input type="url" name="contact_linkedin" placeholder="https://linkedin.com/in/..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>

                        <button type="submit" name="add_company_contact" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs shadow transition-all">
                            Enregistrer le Contact
                        </button>
                    </form>
                </div>

                <!-- Liste des Contacts Renseignés -->
                <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Répertoire des Décideurs Référencés</h2>
                            <p class="text-xs text-slate-500">Interlocuteurs RSE, Direction Générale et Responsables Achats</p>
                        </div>
                        <span class="text-xs px-3 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-full">
                            <?php echo count($contacts); ?> contact(s)
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php if (empty($contacts)): ?>
                            <div class="sm:col-span-2 p-8 text-center text-xs text-slate-400 bg-slate-50 rounded-2xl border border-slate-100">
                                Aucun contact spécifique enregistré pour cette entreprise. Utilisez le formulaire à gauche pour en ajouter.
                            </div>
                        <?php else: ?>
                            <?php foreach ($contacts as $c): ?>
                                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/60 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($c['name']); ?></h3>
                                        <span class="text-[10px] font-bold px-2 py-0.5 bg-slate-200 text-slate-700 rounded-full"><?php echo htmlspecialchars($c['contactType'] ?? 'RSE'); ?></span>
                                    </div>
                                    <p class="text-xs text-emerald-800 font-semibold"><?php echo htmlspecialchars($c['roleTitle'] ?? 'Poste non renseigné'); ?></p>
                                    
                                    <div class="pt-2 text-xs text-slate-600 space-y-1">
                                        <?php if (!empty($c['email'])): ?>
                                            <div>📧 <a href="mailto:<?php echo htmlspecialchars($c['email']); ?>" class="hover:underline"><?php echo htmlspecialchars($c['email']); ?></a></div>
                                        <?php endif; ?>
                                        <?php if (!empty($c['phone'])): ?>
                                            <div>📞 <a href="tel:<?php echo htmlspecialchars($c['phone']); ?>" class="hover:underline"><?php echo htmlspecialchars($c['phone']); ?></a></div>
                                        <?php endif; ?>
                                        <?php if (!empty($c['linkedinUrl'])): ?>
                                            <div>🔗 <a href="<?php echo htmlspecialchars($c['linkedinUrl']); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">Profil LinkedIn ↗</a></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="pt-3 border-t border-slate-200/80 no-print">
                                        <a href="/api/analyse.php?id=<?php echo $audit['id']; ?>&tab=kit" class="text-xs font-bold text-emerald-800 hover:underline flex items-center space-x-1">
                                            <span>Générer Pitch & Email pour ce contact →</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- ONGLET 5 : KIT PROSPECTION IA SUR-MESURE -->
    <!-- ========================================================================= -->
    <?php if ($currentTab === 'kit'): ?>
        <div id="section_kit_prospection" class="space-y-8">
            <div class="bg-slate-900 text-white p-8 sm:p-10 rounded-[3rem] shadow-xl border border-slate-800 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <svg class="w-32 h-32 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" /></svg>
                </div>
                <div class="relative z-10">
                    <span class="text-[10px] font-black uppercase text-emerald-400 tracking-[0.2em] mb-4 block">Génération Commerciale Augmentée</span>
                    <h2 class="text-3xl font-black text-white">Kit de Prospection Intelligent</h2>
                    <p class="text-sm text-slate-400 mt-2 max-w-2xl">
                        Nous avons analysé <strong><?php echo htmlspecialchars($audit['company_name'] ?? 'l\'entreprise'); ?></strong> pour extraire les leviers de conviction les plus puissants au regard de ses non-conformités.
                    </p>
                    
                    <!-- Formulaire de Correction / Contexte IA -->
                    <div class="mt-8 p-6 bg-white/5 border border-white/10 rounded-[2rem] backdrop-blur-md no-print">
                        <form method="POST" action="/api/analyse.php?id=<?php echo $audit['id']; ?>&tab=kit" class="space-y-4">
                            <input type="hidden" name="audit_id" value="<?php echo $audit['id']; ?>">
                            <label class="block text-xs font-black text-emerald-400 uppercase tracking-widest mb-2 italic">Ajuster la finesse du texte (Ajouter du contexte pour l'IA) :</label>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <textarea name="user_context" rows="2" placeholder="Ex: Je connais le dirigeant, c'est le fournisseur de mon client X, ils sont très portés sur l'innovation..." class="flex-grow bg-slate-800/50 border border-slate-700 rounded-2xl p-4 text-xs text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none placeholder:text-slate-600"></textarea>
                                <button type="submit" name="regenerate_kit_ia" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-black rounded-2xl text-xs uppercase tracking-widest shadow-lg shadow-emerald-900/20 transition-all flex items-center justify-center space-x-2 shrink-0">
                                    <span>🪄 Recoder avec l'IA</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php
            // Récupération du kit (soit DB, soit par défaut)
            $kit = null;
            if (!empty($audit['kit_json'])) {
                $kit = json_decode($audit['kit_json'], true);
            }
            
            // Fallback si pas de kit IA
            if (!$kit) {
                $kit = [
                    'phone_pitch' => [
                        'hook' => "Bonjour [Nom], je suis [Prénom] de ClimatLocal. J'ai analysé la communication environnementale de " . ($audit['company_name'] ?? 'votre entreprise') . " face à la directive EmpCO.",
                        'fact' => "Notre audit a relevé des allégations à risque (ex: " . ($claims[0]['term'] ?? 'Neutralité') . ") qui nécessitent désormais des preuves normées.",
                        'warning' => "La DGCCRF renforce les contrôles. Une non-conformité peut coûter jusqu'à 100 000 €.",
                        'cta' => "Je vous ai préparé un rapport de mise en conformité. Quand seriez-vous libre pour une présentation de 15 min ?"
                    ],
                    'emails' => [
                        'legal' => [
                            'subject' => "Conformité Directive EmpCO : " . ($audit['company_name'] ?? 'Audit'),
                            'body' => "Bonjour,\n\nSuite à l'entrée en vigueur de la directive (UE) 2024/825, nous avons audité votre site. Certains termes utilisés sont désormais strictement encadrés.\n\nSouhaitez-vous recevoir notre analyse détaillée ?"
                        ],
                        'business' => [
                            'subject' => "Valoriser vos engagements RSE réels",
                            'body' => "Bonjour,\n\nVos engagements sur votre site sont louables. Pour les transformer en avantage concurrentiel certifié, nous vous aidons à les sécuriser juridiquement.\n\nParlons-en."
                        ],
                        'reputation' => [
                            'subject' => "Sécuriser votre image de marque",
                            'body' => "Bonjour,\n\nFace au risque de greenwashing perçu, nous aidons les directions marketing à sécuriser leurs prises de parole environnementales.\n\nÊtes-vous disponible ?"
                        ]
                    ]
                ];
            }
            ?>

            <?php if (!empty($kit['priority']) || !empty($kit['nextAction']) || !empty($kit['recommendedOffer'])): ?>
                <section class="bg-white p-6 sm:p-8 rounded-3xl border border-emerald-200 shadow-sm grid gap-4 sm:grid-cols-3">
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Priorité</p><p class="mt-1 font-black text-slate-900"><?php echo htmlspecialchars((string)($kit['priority'] ?? 'À confirmer')); ?></p></div>
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Prochaine action</p><p class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars((string)($kit['nextAction'] ?? '')); ?></p></div>
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Offre du catalogue</p><p class="mt-1 text-sm font-bold text-emerald-800"><?php echo htmlspecialchars((string)(is_array($kit['recommendedOffer'] ?? null) ? ($kit['recommendedOffer']['serviceTitle'] ?? '') : ($kit['recommendedOffer'] ?? 'Aucune offre retenue'))); ?></p></div>
                </section>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- 1. Script d'Appel Téléphonique -->
                <div class="bg-white p-8 sm:p-10 rounded-[3rem] border border-slate-200 shadow-sm space-y-8">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl">📞</div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">Script d'Appel</h3>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Le pitch gagnant en 4 temps</p>
                            </div>
                        </div>
                        <button onclick="navigator.clipboard.writeText(document.getElementById('phone_pitch_text').innerText); alert('Script téléphonique copié !');" class="px-4 py-2 bg-slate-50 hover:bg-slate-900 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all no-print">
                            Copier
                        </button>
                    </div>

                    <div id="phone_pitch_text" class="space-y-6 text-sm">
                        <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100 relative group overflow-hidden">
                            <span class="absolute -right-4 -top-4 text-4xl opacity-5 group-hover:scale-110 transition-transform">💬</span>
                            <strong class="text-emerald-900 block mb-2 uppercase text-[10px] font-black tracking-widest">1. L'Accroche :</strong>
                            <p class="text-slate-700 leading-relaxed italic"><?php echo nl2br(htmlspecialchars($kit['phone_pitch']['hook'])); ?></p>
                        </div>

                        <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100 relative group overflow-hidden">
                            <span class="absolute -right-4 -top-4 text-4xl opacity-5 group-hover:scale-110 transition-transform">📊</span>
                            <strong class="text-emerald-900 block mb-2 uppercase text-[10px] font-black tracking-widest">2. Le Constat :</strong>
                            <p class="text-slate-700 leading-relaxed italic"><?php echo nl2br(htmlspecialchars($kit['phone_pitch']['fact'])); ?></p>
                        </div>

                        <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100 relative group overflow-hidden">
                            <span class="absolute -right-4 -top-4 text-4xl opacity-5 group-hover:scale-110 transition-transform">⚖️</span>
                            <strong class="text-red-900 block mb-2 uppercase text-[10px] font-black tracking-widest">3. Le Risque :</strong>
                            <p class="text-slate-700 leading-relaxed italic"><?php echo nl2br(htmlspecialchars($kit['phone_pitch']['warning'])); ?></p>
                        </div>

                        <div class="p-6 bg-emerald-900 text-white rounded-[2rem] shadow-xl shadow-emerald-900/20 relative group overflow-hidden">
                            <span class="absolute -right-4 -top-4 text-4xl opacity-10 group-hover:scale-110 transition-transform">🚀</span>
                            <strong class="text-emerald-400 block mb-2 uppercase text-[10px] font-black tracking-widest">4. L'Action (CTA) :</strong>
                            <p class="text-white leading-relaxed font-bold"><?php echo nl2br(htmlspecialchars($kit['phone_pitch']['cta'])); ?></p>
                        </div>
                    </div>
                </div>

                <!-- 2. Objections Courantes & Réponses (Restylé) -->
                <div class="bg-white p-8 sm:p-10 rounded-[3rem] border border-slate-200 shadow-sm space-y-8">
                    <div class="flex items-center space-x-4 border-b border-slate-100 pb-6">
                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl">🛡️</div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">Traitement Objections</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Répartie & Finesse</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="p-6 rounded-[2rem] bg-slate-50 border border-slate-100 hover:bg-white hover:border-amber-400 transition-all">
                            <strong class="text-amber-900 block mb-1 text-xs font-black uppercase">« Trop petit pour être contrôlé »</strong>
                            <p class="text-slate-600 text-xs leading-relaxed italic">
                                ↳ « La loi s'applique dès la première visite sur votre site. Un concurrent peut aussi vous dénoncer via SignalConso. »
                            </p>
                        </div>

                        <div class="p-6 rounded-[2rem] bg-slate-50 border border-slate-100 hover:bg-white hover:border-amber-400 transition-all">
                            <strong class="text-amber-900 block mb-1 text-xs font-black uppercase">« On est sincères dans notre démarche »</strong>
                            <p class="text-slate-600 text-xs leading-relaxed italic">
                                ↳ « C'est tout le problème : la directive punit le manque de preuve, pas seulement le mensonge. Sécurisons votre sincérité. »
                            </p>
                        </div>

                        <div class="p-6 rounded-[2rem] bg-slate-50 border border-slate-100 hover:bg-white hover:border-amber-400 transition-all">
                            <strong class="text-amber-900 block mb-1 text-xs font-black uppercase">« Pas de budget pour ça »</strong>
                            <p class="text-slate-600 text-xs leading-relaxed italic">
                                ↳ « Nous activons les subventions Bpifrance qui couvrent l'essentiel de l'audit. C'est une assurance contre une amende de 100k€. »
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3 Templates d'Emails Froids Sur-Mesure -->
            <div class="bg-white p-8 sm:p-10 rounded-[3rem] border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-8 mb-8">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl">✉️</div>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">Séquences d'Emails Froids</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">3 Angles stratégiques pour percer</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <?php 
                    $angles = [
                        'legal' => ['label' => 'Risque Légal', 'color' => 'red', 'emoji' => '⚖️'],
                        'business' => ['label' => 'Business & ROI', 'color' => 'emerald', 'emoji' => '💰'],
                        'reputation' => ['label' => 'E-Réputation', 'color' => 'indigo', 'emoji' => '🛡️']
                    ];
                    foreach ($angles as $id => $info): ?>
                        <div class="p-8 rounded-[2.5rem] bg-slate-50 border border-slate-100 hover:bg-white hover:border-<?php echo $info['color']; ?>-400 transition-all space-y-6 flex flex-col justify-between group">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full bg-<?php echo $info['color']; ?>-50 text-<?php echo $info['color']; ?>-800 border border-<?php echo $info['color']; ?>-100">
                                        <?php echo $info['emoji'] . ' ' . $info['label']; ?>
                                    </span>
                                    <button onclick="navigator.clipboard.writeText(document.getElementById('email_<?php echo $id; ?>_text').innerText); alert('Email copié !');" class="text-[10px] font-black text-slate-400 hover:text-slate-900 transition-colors uppercase tracking-widest no-print">Copier</button>
                                </div>
                                <div id="email_<?php echo $id; ?>_text" class="text-xs text-slate-600 space-y-3">
                                    <div class="font-black text-slate-900 text-sm">Objet : <?php echo htmlspecialchars($kit['emails'][$id]['subject']); ?></div>
                                    <div class="leading-relaxed italic border-l-2 border-slate-200 pl-4 py-1">
                                        <?php echo nl2br(htmlspecialchars($kit['emails'][$id]['body'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- ONGLET 6 : SUIVI & CHECKLIST COMMERCIALE (10 ÉTAPES MÉTIER) -->
    <!-- ========================================================================= -->
    <?php if ($currentTab === 'suivi'): ?>
        <div id="section_suivi" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formulaire d'assignation et statut -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm sticky top-24 space-y-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Gestion & Assignation du Dossier</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Affectez le dossier à un auditeur et suivez son avancement</p>
                    </div>

                    <form method="POST" action="/api/analyse.php?id=<?php echo $audit['id']; ?>&tab=suivi" class="space-y-4">
                        <input type="hidden" name="audit_id" value="<?php echo $audit['id']; ?>">

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Consultant / Auditeur Assigné</label>
                            <select name="assigned_to" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="">-- Non assigné --</option>
                                <?php foreach ($usersList as $u): ?>
                                    <option value="<?php echo htmlspecialchars($u['username']); ?>" <?php echo ($audit['assigned_to'] ?? '') === $u['username'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u['username']); ?> (<?php echo htmlspecialchars($u['role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Étape du Pipeline Commercial</label>
                            <select name="status" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                <option value="scan_termine" <?php echo ($audit['status'] ?? '') === 'scan_termine' || ($audit['status'] ?? '') === 'nouveau' ? 'selected' : ''; ?>>1. Prospect Détecté / Scan initial</option>
                                <option value="contacte" <?php echo ($audit['status'] ?? '') === 'contacte' ? 'selected' : ''; ?>>2. Premier Contact & Qualification</option>
                                <option value="en_analyse" <?php echo ($audit['status'] ?? '') === 'en_analyse' || ($audit['status'] ?? '') === 'rdv_fixe' ? 'selected' : ''; ?>>3. Diagnostic Présenté & Offre</option>
                                <option value="devis_envoye" <?php echo ($audit['status'] ?? '') === 'devis_envoye' ? 'selected' : ''; ?>>4. Négociation & Devis Transmis</option>
                                <option value="signe" <?php echo ($audit['status'] ?? '') === 'signe' ? 'selected' : ''; ?>>5. Mission Signée / En cours ✅</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Notes Internes de Suivi</label>
                            <textarea name="audit_notes" rows="4" placeholder="Ex: Premier contact chaleureux, rendez-vous prévu avec la directrice RSE mardi prochain..." class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500"><?php echo htmlspecialchars($audit['notes'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" name="update_audit_workflow" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-sm shadow transition-all">
                            Enregistrer le Suivi
                        </button>
                    </form>
                </div>
            </div>

            <!-- Checklist Commerciale Opérationnelle (10 Étapes de Vente & Suivi) -->
            <div class="lg:col-span-2 space-y-6">
                <form method="POST" action="/api/analyse.php?id=<?php echo $audit['id']; ?>&tab=suivi" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
                    <input type="hidden" name="audit_id" value="<?php echo $audit['id']; ?>">
                    <input type="hidden" name="update_commercial_checklist" value="1">

                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                                <span>💼</span>
                                <span>Checklist Commerciale de Prospection & Vente</span>
                            </h2>
                            <p class="text-xs text-slate-500">Suivi des étapes d'engagement prospect et conversion de mission</p>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs shadow transition-colors">
                            💾 Enregistrer la progression
                        </button>
                    </div>

                    <?php
                    $commercialSteps = [
                        1 => ['title' => '1. Qualification SIREN & Fiche Décideur', 'desc' => 'Vérification du code NAF, chiffre d\'affaires, effectifs et identification du responsable RSE / Dirigeant.'],
                        2 => ['title' => '2. Envoi du Premier Contact Personnalisé', 'desc' => 'Transmission de l\'email cold ou message LinkedIn ciblant les allégations spécifiques du site.'],
                        3 => ['title' => '3. Appel de Découverte & Traitement des Objections', 'desc' => 'Échange téléphonique de 15 min, présentation bienveillante des risques EmpCO et qualification du besoin.'],
                        4 => ['title' => '4. Soutenance du Diagnostic 6-Piliers', 'desc' => 'Présentation en visio de la cartographie des allégations et du barème des sanctions DGCCRF.'],
                        5 => ['title' => '5. Envoi de la Proposition Commerciale Chiffrée', 'desc' => 'Transmission du devis formalisé avec forfait d\'audit d\'alignement et chiffrage des aides ADEME.'],
                        6 => ['title' => '6. Entretien de Négociation & Validation du Périmètre', 'desc' => 'Ajustement des livrables (attestation de conformité, guide sémantique) et calendrier d\'intervention.'],
                        7 => ['title' => '7. Signature de la Convention de Mission', 'desc' => 'Validation formelle du devis / bon pour accord et encaissement de l\'acompte initial.'],
                        8 => ['title' => '8. Collecte des Justificatifs & Données d\'Entrée', 'desc' => 'Récupération des bilans GES existants, fiches fournisseurs, certificats et données d\'approvisionnement.'],
                        9 => ['title' => '9. Rédaction du Dossier de Preuves & Attestation Formelle', 'desc' => 'Délivrance de l\'attestation de conformité Green Claims et remise des préconisations de reformulation.'],
                        10 => ['title' => '10. Clôture de Mission & Contrat de Veille Réglementaire', 'desc' => 'Restitution finale et proposition d\'abonnement annuel de surveillance des évolutions légales.']
                    ];
                    ?>

                    <div class="space-y-3">
                        <?php foreach ($commercialSteps as $idx => $st): ?>
                            <?php $isDone = !empty($checklistState[$idx]); ?>
                            <label class="flex items-start space-x-3.5 p-4 rounded-2xl border transition-all cursor-pointer <?php echo $isDone ? 'bg-emerald-50/60 border-emerald-300' : 'bg-slate-50/60 border-slate-200 hover:bg-slate-50'; ?>">
                                <input type="checkbox" name="checklist_steps[]" value="<?php echo $idx; ?>" <?php echo $isDone ? 'checked' : ''; ?> class="mt-1 rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                                <div class="text-xs">
                                    <strong class="text-slate-900 block font-bold text-sm <?php echo $isDone ? 'text-emerald-950' : ''; ?>">
                                        <?php echo htmlspecialchars($st['title']); ?>
                                        <?php if ($isDone): ?>
                                            <span class="ml-2 text-[10px] font-black px-2 py-0.5 bg-emerald-200 text-emerald-900 rounded-full">Validé</span>
                                        <?php endif; ?>
                                    </strong>
                                    <p class="text-slate-600 mt-1 leading-relaxed"><?php echo htmlspecialchars($st['desc']); ?></p>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs shadow-md transition-all">
                            Enregistrer la Checklist Commerciale
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1 : CORRECTION D'ALLÉGATION & APPRENTISSAGE MÉTIER -->
<!-- ========================================================================= -->
<div id="claim_modal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Justifier / Corriger cette Allégation</h3>
                <p class="text-xs text-slate-500">Ajustez la sévérité, qualifiez une exception ou validez une preuve RSE</p>
            </div>
            <button onclick="closeClaimCorrectionModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <form method="POST" action="/api/analyse.php?id=<?php echo $audit['id']; ?>&tab=diag" class="space-y-4">
            <input type="hidden" name="claim_id" id="modal_claim_id">
            <input type="hidden" name="update_claim_status" value="1">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Terme Évalué</label>
                <input type="text" id="modal_claim_term" readonly class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-800">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Statut de Qualification par l'Auditeur</label>
                <select name="validation_status" id="modal_validation_status" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="en_attente">⚠️ En attente / Allégation à risque non justifiée</option>
                    <option value="justifie_par_preuve">✅ Justifié par Preuve RSE (Bilan Carbone, ACV, Écolabel officiel)</option>
                    <option value="non_fonde">⚖️ Exception Métier Valide (Service vendu par l'entreprise, non abusif)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Niveau de Sévérité Réévalué</label>
                <select name="severity" id="modal_claim_severity" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="interdit_strict">⛔ Interdiction Stricte (EmpCO Art. 2)</option>
                    <option value="haut_risque">⚠️ Haut Risque (Allégation générique)</option>
                    <option value="vigilance">🔍 Vigilance & Traçabilité</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Note de Justification / Correction de la note</label>
                <textarea name="correction_note" id="modal_correction_note" rows="3" placeholder="Ex: Preuve fournie via le Bilan Carbone 2025 ou exception validée..." class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-2">
                <button type="button" onclick="closeClaimCorrectionModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs">
                    Annuler
                </button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-800 hover:bg-emerald-900 text-white font-bold rounded-xl text-xs shadow-md">
                    Enregistrer la Correction
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2 : CONFIGURATEUR DE RAPPORT PDF PERSONNALISÉ -->
<!-- ========================================================================= -->
<div id="pdf_customizer_modal" class="fixed inset-0 z-50 hidden bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                    <span>📑</span>
                    <span>Configurer l'Export du Rapport PDF</span>
                </h3>
                <p class="text-xs text-slate-500">Sélectionnez les éléments à inclure dans le dossier pour le client</p>
            </div>
            <button onclick="closePdfModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <div class="space-y-4 text-xs">
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-2">Sections du Rapport à Inclure :</label>
                <div class="space-y-2.5 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="pdf_opt_header" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-bold text-slate-800">1. Fiche d'Identité Entreprise & Scores Globaux</span>
                    </label>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="pdf_opt_synthese" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-bold text-slate-800">2. Synthèse Juridique & Conformité 6-Piliers</span>
                    </label>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="pdf_opt_allegations" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-bold text-slate-800">3. Tableau Détaillé des Allégations & Citations Relevées</span>
                    </label>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="pdf_opt_sanctions" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-bold text-slate-800">4. Risques Légaux & Sanctions DGCCRF Encourues</span>
                    </label>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="pdf_opt_missions" checked class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-bold text-slate-800">5. Catalogue des Missions d'Accompagnement & Devis</span>
                    </label>

                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="pdf_opt_textes" class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                        <span class="font-bold text-slate-800">6. Référentiel des 45+ Textes de Lois Applicables</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1.5">Format du Document</label>
                <select id="pdf_format_type" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500">
                    <option value="complet" selected>Dossier d'Audit Exhaustif (Complet)</option>
                    <option value="synthese">Synthèse Décideur / Direction (1 à 2 pages)</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-2 border-t border-slate-100">
            <button type="button" onclick="closePdfModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs">
                Annuler
            </button>
            <button type="button" onclick="generateAndPrintPdf()" class="px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs shadow-md flex items-center space-x-2">
                <span>🖨️ Générer & Imprimer le PDF</span>
            </button>
        </div>
    </div>
</div>

<style>
.gray-mask {
    background-color: #0f172a !important; /* Slate 900 */
    color: #e2e8f0 !important; /* Slate 200 */
    border-radius: 1.5rem;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    border: 1px solid #1e293b;
    box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
}
.gray-mask::after {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.03) 10px, rgba(255,255,255,0.03) 20px);
    pointer-events: none;
}

@media print {
    body {
        background-color: #ffffff !important;
        font-size: 11pt !important;
    }
    header, footer, .no-print, #cl_cms_toolbar {
        display: none !important;
    }
    .pdf-hidden {
        display: none !important;
    }
    .bg-slate-900, .bg-climat-950, .bg-emerald-950 {
        background-color: #064e3b !important;
        color: #ffffff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<script>
document.addEventListener('click', async (event) => {
    const filterButton = event.target.closest('.mission-filter-btn');
    if (filterButton) {
        const mode = filterButton.dataset.filter;
        document.querySelectorAll('#mission-catalogue .mission-pro-card').forEach(card => {
            card.hidden = mode === 'recommended' && card.dataset.recommended !== '1';
        });
        document.querySelectorAll('.mission-filter-btn').forEach(button => {
            const selected = button === filterButton;
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            button.classList.toggle('bg-emerald-800', selected);
            button.classList.toggle('text-white', selected);
            button.classList.toggle('border', !selected);
            button.classList.toggle('border-slate-200', !selected);
            button.classList.toggle('bg-white', !selected);
            button.classList.toggle('text-slate-700', !selected);
        });
        const count = mode === 'all'
            ? document.querySelectorAll('#mission-catalogue .mission-pro-card').length
            : document.querySelectorAll('#mission-catalogue .mission-pro-card[data-recommended="1"]').length;
        const status = document.getElementById('mission-filter-status');
        if (status) status.textContent = mode === 'all' ? count + ' mission(s) dans le catalogue.' : count + ' mission(s) mise(s) en avant à partir des résultats du scan.';
        return;
    }
    const copyButton = event.target.closest('.mission-copy-btn');
    if (copyButton) {
        try {
            await navigator.clipboard.writeText(copyButton.dataset.copy || '');
            copyButton.innerHTML = '<span>Copié pour le devis</span><span aria-hidden="true">✓</span>';
        } catch (error) {
            window.prompt('Copiez le texte de l’offre :', copyButton.dataset.copy || '');
        }
    }
});

function openClaimCorrectionModal(claim) {
    document.getElementById('modal_claim_id').value = claim.id;
    document.getElementById('modal_claim_term').value = claim.term;
    document.getElementById('modal_validation_status').value = claim.consultantValidationStatus || 'en_attente';
    document.getElementById('modal_claim_severity').value = claim.severity || 'haut_risque';
    document.getElementById('modal_correction_note').value = claim.correctionNote || '';
    
    document.getElementById('claim_modal').classList.remove('hidden');
}

function closeClaimCorrectionModal() {
    document.getElementById('claim_modal').classList.add('hidden');
}

function openPdfModal() {
    document.getElementById('pdf_customizer_modal').classList.remove('hidden');
}

function closePdfModal() {
    document.getElementById('pdf_customizer_modal').classList.add('hidden');
}

function generateAndPrintPdf() {
    closePdfModal();

    const optHeader = document.getElementById('pdf_opt_header').checked;
    const optSynthese = document.getElementById('pdf_opt_synthese').checked;
    const optAllegations = document.getElementById('pdf_opt_allegations').checked;
    const optSanctions = document.getElementById('pdf_opt_sanctions').checked;
    const optMissions = document.getElementById('pdf_opt_missions').checked;
    const optTextes = document.getElementById('pdf_opt_textes').checked;

    const elHeader = document.getElementById('section_header_pro');
    const elSynthese = document.getElementById('section_synthese');
    const elAllegations = document.getElementById('section_allegations');
    const elSanctions = document.getElementById('section_sanctions');
    const elMissions = document.getElementById('section_missions');
    const elTextes = document.getElementById('section_textes');

    if (elHeader) elHeader.classList.toggle('pdf-hidden', !optHeader);
    if (elSynthese) elSynthese.classList.toggle('pdf-hidden', !optSynthese);
    if (elAllegations) elAllegations.classList.toggle('pdf-hidden', !optAllegations);
    if (elSanctions) elSanctions.classList.toggle('pdf-hidden', !optSanctions);
    if (elMissions) elMissions.classList.toggle('pdf-hidden', !optMissions);
    if (elTextes) elTextes.classList.toggle('pdf-hidden', !optTextes);

    setTimeout(() => {
        window.print();
        // Restore visibility after print
        document.querySelectorAll('.pdf-hidden').forEach(el => el.classList.remove('pdf-hidden'));
    }, 200);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
