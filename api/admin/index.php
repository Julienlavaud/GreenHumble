<?php
// /api/admin/index.php
// Master Admin Cockpit for Climat Local PHP API - PRO Version (v2.9)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/app_state.php';
require_once __DIR__ . '/../includes/auth.php';

// 1. Authentication
// Centralize login: this page must not accept a separate hard-coded password.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true ||
    !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $requestedTab = trim((string)($_GET['tab'] ?? 'scans'));
    $loginTabs = ['audits', 'scans', 'dossiers', 'listes', 'agency', 'team', 'scanner_groups', 'scanner', 'missions', 'legal', 'texts', 'style'];
    if (!in_array($requestedTab, $loginTabs, true)) $requestedTab = 'scans';
    $next = '/api/admin/index.php?tab=' . rawurlencode($requestedTab);
    header('Location: /api/login.php?next=' . rawurlencode($next), true, 303);
    exit;
}

// 2. Data Loading
$activeTab = $_GET['tab'] ?? 'audits';
if ($activeTab === 'dossiers') {
    header('Location: /api/admin/index.php?tab=scans', true, 303);
    exit;
}
if ($activeTab === 'team') {
    header('Location: /api/admin/index.php?tab=agency#organisation-equipe', true, 303);
    exit;
}
if ($activeTab === 'scanner_groups') {
    header('Location: /api/admin/index.php?tab=listes', true, 303);
    exit;
}
$state = load_app_state();
$db = get_db_connection();

// 2.5 POST Handlers for Database Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gh_require_same_origin_mutation();
    if (isset($_POST['delete_audit'])) {
        $id = intval($_POST['delete_audit']);
        $db->prepare("DELETE FROM cl_audits WHERE id = ?")->execute([$id]);
        exit;
    }
    if (isset($_POST['save_db_text'])) {
        $key = trim($_POST['text_key'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = trim($_POST['category'] ?? 'mots_bannis') ?: 'mots_bannis';
        if ($key === '' || $title === '' || $content === '') {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'La clé, le titre et le contenu sont obligatoires.']);
            exit;
        }
        $db->prepare("INSERT INTO cl_texts (text_key, title, content, category) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), category = VALUES(category)")->execute([$key, $title, $content, $category]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }
    if (isset($_POST['delete_db_text'])) {
        $key = trim($_POST['delete_db_text']);
        $db->prepare("DELETE FROM cl_texts WHERE text_key = ?")->execute([$key]);
        exit;
    }
}

// 3. Page Header
$page_title = "Cockpit Admin PRO v2.9";
include __DIR__ . '/../includes/header.php';
?>

<div class="gh-admin-page max-w-[1850px] mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10 animate-fadeIn bg-[#121212] text-[#E0E0E0]">
    <!-- Master Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8 bg-[#1E1E1E] p-10 rounded-[3.5rem] border border-white/10 shadow-lg relative overflow-hidden">
        <div class="flex items-center space-x-8 relative z-10">
            <div class="w-20 h-20 bg-[#2A7AE2] text-white rounded-[2rem] flex items-center justify-center shadow-xl">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            </div>
            <div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight">Console d’administration</h1>
                <div class="flex items-center space-x-3 mt-2">
                    <span class="px-3 py-1 bg-white/10 text-white rounded-full text-[10px] font-bold uppercase tracking-widest border border-white/10">Espace sécurisé</span>
                    <p class="text-[#AAAAAA] font-medium text-sm">Scans · IA · prospection</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3 relative z-10">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="admin-clear-cache" class="inline-flex items-center gap-2 px-4 py-3 bg-white/10 hover:bg-white/15 text-white rounded-xl border border-white/15 transition-all font-bold text-xs" title="Purger le cache PHP du serveur">
                    <span aria-hidden="true">↻</span>
                    <span>Vider le cache</span>
                </button>
                <span id="admin-clear-cache-status" class="text-xs text-[#AAAAAA]" role="status" aria-live="polite"></span>
            </div>
            <a href="/api/admin/backup_database.php" class="inline-flex items-center gap-2 px-5 py-4 bg-emerald-700 hover:bg-emerald-800 text-white rounded-2xl border border-emerald-500/30 shadow-sm transition-all font-bold text-sm" title="Télécharger une sauvegarde SQL de la base GreenHumble">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v3h16v-3" /></svg>
                <span>Sauvegarder la base</span>
            </a>
            <button onclick="window.location.reload()" class="p-4 bg-[#121212] hover:bg-white/10 text-white rounded-3xl border border-white/10 shadow-sm transition-all cursor-pointer" title="Rafraîchir">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            </button>
        </div>
    </div>

    <!-- Container Dynamique -->
    <div class="bg-[#121212] rounded-[3.5rem] border border-white/10 shadow-2xl overflow-hidden min-h-[700px] relative">
        
        <!-- Actions Communes (Flottantes ou Top) -->
        <div class="absolute top-8 right-10 admin-actions z-20">
            <?php if (in_array($activeTab, ['audits', 'missions', 'legal', 'texts', 'scanner'], true)): ?>
            <button onclick="exportTabCSV('<?php echo $activeTab; ?>')" class="px-6 py-3 bg-[#242424] hover:bg-[#303030] text-slate-200 text-xs font-black rounded-2xl transition-all flex items-center space-x-2 shadow-sm border border-white/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                <span>Exporter CSV</span>
            </button>
            <?php endif; ?>
            
            <?php if (in_array($activeTab, ['missions', 'legal', 'texts', 'scanner'])): ?>
                <label class="cursor-pointer px-6 py-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-black rounded-2xl border border-indigo-100 transition-all flex items-center space-x-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                    <span>Importer CSV</span>
                    <input type="file" class="hidden" accept=".csv" onchange="importTabCSV('<?php echo $activeTab; ?>', this)">
                </label>
            <?php endif; ?>

            <?php if (in_array($activeTab, ['agency', 'missions', 'legal', 'scanner'], true)): ?>
                <button onclick="saveTab('<?php echo $activeTab; ?>')" class="save-btn px-10 py-3.5 bg-emerald-700 text-white text-sm font-black rounded-[1.5rem] shadow-2xl hover:bg-emerald-800 transition-all flex items-center space-x-2 ring-4 ring-emerald-500/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    <span>Sauvegarder</span>
                </button>
            <?php endif; ?>
        </div>

        <?php if ($activeTab === 'audits'): ?>
            <div class="p-8 sm:p-12 space-y-8 bg-[#121212] text-[#E0E0E0] min-h-[650px] rounded-[3rem]">
                <div class="max-w-2xl">
                    <h2 class="text-3xl font-extrabold text-white tracking-tight">Leads & Prospects</h2>
                    <p class="text-[#AAAAAA] mt-2 text-sm">Opportunités d'accompagnement RSE et diagnostics enregistrés.</p>
                </div>
                
                <div class="overflow-x-auto rounded-2xl border border-white/10 shadow-lg bg-[#1E1E1E]">
                    <table class="w-full text-left">
                        <thead class="bg-[#121212] border-b border-white/10">
                            <tr class="text-xs font-bold text-[#AAAAAA] uppercase tracking-wider">
                                <th class="px-6 py-4">Entreprise & Potentiel</th>
                                <th class="px-6 py-4 text-center">Score / Risque</th>
                                <th class="px-6 py-4">Pôle / Auditeur</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php 
                            $audits = $db->query("SELECT a.id, a.url, a.score, a.riskLevel, a.headcount, a.turnover, a.analyzedAt, a.assigned_to, c.name as company_name, c.sector, c.headcount as comp_headcount, c.turnover as comp_turnover, CASE WHEN a.company_id IS NULL THEN 1 ELSE (SELECT COUNT(*) FROM cl_audits history WHERE history.company_id = a.company_id) END AS company_audit_count FROM cl_audits a LEFT JOIN cl_companies c ON a.company_id = c.id WHERE a.company_id IS NULL OR a.id = (SELECT latest.id FROM cl_audits latest WHERE latest.company_id = a.company_id ORDER BY latest.analyzedAt DESC, latest.id DESC LIMIT 1) ORDER BY a.analyzedAt DESC, a.id DESC LIMIT 60")->fetchAll();
                            foreach ($audits as $a): 
                                $auditScore = is_numeric($a['score'] ?? null) ? (float)$a['score'] : null;
                                $isHighRisk = $auditScore !== null && $auditScore >= 70;
                                $isModerateRisk = $auditScore !== null && $auditScore >= 45 && $auditScore < 70;
                                ?>
                                <tr class="hover:bg-white/5 transition-colors" id="audit-row-<?php echo $a['id']; ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-[#121212] rounded-xl flex items-center justify-center font-bold text-white border border-white/10"><?php echo strtoupper(substr($a['company_name'] ?: '?', 0, 1)); ?></div>
                                            <div>
                                                <div class="font-bold text-white text-base"><?php echo htmlspecialchars($a['company_name'] ?: preg_replace('#^https?://(www\.)?#i', '', $a['url'])); ?></div>
                                                <div class="text-xs font-mono text-[#AAAAAA]"><?php echo htmlspecialchars($a['url']); ?></div>
                                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                                    <span class="px-2 py-0.5 bg-[#2A7AE2]/20 text-[#2A7AE2] text-[10px] font-bold rounded border border-[#2A7AE2]/30"><?php echo htmlspecialchars($a['sector'] ?: 'Services & B2B'); ?></span>
                                                    <?php 
                                                    $hc = !empty($a['headcount']) ? $a['headcount'] : ($a['comp_headcount'] ?? '');
                                                    $to = !empty($a['turnover']) ? $a['turnover'] : ($a['comp_turnover'] ?? '');
                                                    if ($hc): ?>
                                                        <span class="text-[10px] text-[#AAAAAA]">👥 <?php echo htmlspecialchars($hc); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($to): ?>
                                                        <span class="text-[10px] text-emerald-400 font-bold">💶 <?php echo htmlspecialchars($to); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ((int)($a['company_audit_count'] ?? 0) > 1): ?>
                                                    <div class="text-[10px] text-[#AAAAAA] mt-1">Score actuel · <?php echo (int)$a['company_audit_count']; ?> audits conservés</div>
                                                <?php else: ?>
                                                    <div class="text-[10px] text-[#AAAAAA] mt-1">Score actuel</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="inline-flex flex-col items-center">
                                            <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $isHighRisk ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : ($isModerateRisk ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'); ?>">
                                                <?php echo $auditScore !== null ? htmlspecialchars((string)$a['score']) . '/100' : 'À évaluer'; ?>
                                            </span>
                                            <span class="text-[10px] font-bold <?php echo $isHighRisk ? 'text-rose-400' : ($isModerateRisk ? 'text-amber-400' : 'text-emerald-400'); ?> mt-1 uppercase"><?php echo htmlspecialchars($a['riskLevel']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        <div class="font-bold text-white"><?php echo htmlspecialchars($a['assigned_to'] ?: 'Non assigné'); ?></div>
                                        <div class="text-[#AAAAAA]"><?php echo date('d/m/Y', strtotime($a['analyzedAt'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <a href="/api/analyse.php?id=<?php echo $a['id']; ?>" class="px-3 py-1.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white rounded-lg text-xs font-bold transition-colors inline-block">
                                            Audit →
                                        </a>
                                        <button onclick="deleteAudit(<?php echo $a['id']; ?>)" class="px-3 py-1.5 bg-rose-900/80 hover:bg-rose-800 text-rose-200 rounded-lg text-xs font-bold transition-colors border-none cursor-pointer">
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($activeTab === 'scans'): ?>
            <?php
            require_once __DIR__ . '/../includes/scans_manager.php';
            require_once __DIR__ . '/../includes/scanner_groups.php';
            $allScans = load_all_scans();
            $allGroups = load_scanner_groups();
            ?>
            <div class="p-8 sm:p-12 space-y-10 bg-[#121212] text-[#E0E0E0] min-h-[650px] rounded-[3rem]">
                <!-- Header Scans -->
                <div class="flex flex-wrap items-center justify-between gap-6 border-b border-white/10 pb-8">
                    <div class="flex items-center space-x-3">
                        <span class="p-3 bg-[#1E1E1E] rounded-2xl text-2xl border border-white/10">🔍</span>
                        <div>
                            <h2 class="text-3xl font-bold text-white tracking-tight">Tous les Scans Centralisés</h2>
                            <p class="text-[#AAAAAA] text-sm mt-1">Diagnostic, Prospection Pipeline & Widgets publics enregistrés en base unique.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <span class="px-4 py-2 bg-[#1E1E1E] text-emerald-400 font-bold rounded-xl border border-white/10 text-sm flex items-center gap-2">
                            <span>📊 Total Scans :</span>
                            <span class="text-white font-mono text-base"><?php echo count($allScans); ?></span>
                        </span>
                    </div>
                </div>

                <!-- Barre de Filtres Rapides (Dark Mode) -->
                <div class="p-6 bg-[#1E1E1E] rounded-2xl border border-white/10 shadow-lg space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                        <span>⚡</span>
                        <span>Filtrer les Scans</span>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#AAAAAA] mb-2">Type de Scan</label>
                            <select id="filter_type_scan" onchange="filterScansGrid()" class="w-full px-4 py-3 bg-[#121212] border border-white/15 rounded-xl text-white font-medium focus:border-[#2A7AE2]">
                                <option value="all">Tous les types (Diagnostic, Pipeline, Widget)</option>
                                <option value="diagnostic">🔍 Diagnostic (Admin manuel)</option>
                                <option value="pipeline">💼 Pipeline (Prospection B2B)</option>
                                <option value="widget">🌐 Widget (Scan public site)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#AAAAAA] mb-2">Rechercher par URL</label>
                            <input type="text" id="filter_url_scan" onkeyup="filterScansGrid()" placeholder="Ex: entreprise.com..." class="w-full px-4 py-3 bg-[#121212] border border-white/15 rounded-xl text-white font-medium focus:border-[#2A7AE2] focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Actions groupées sur les scans">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" id="select-all-scans" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                        Sélectionner les scans visibles
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="bulk-rescan-scans" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50" disabled>Relancer la sélection</button>
                        <button type="button" id="bulk-delete-scans" class="rounded-xl bg-rose-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-50" disabled>Supprimer la sélection</button>
                    </div>
                    <p id="bulk-scan-status" class="w-full text-xs text-slate-500" role="status" aria-live="polite"></p>
                </div>

                <!-- Grille des Cartes de Scans -->
                <div class="grid grid-cols-1 gap-4" id="scans-container">
                    <?php if (empty($allScans)): ?>
                        <div class="col-span-full p-12 text-center text-[#AAAAAA] bg-[#1E1E1E] rounded-2xl border border-white/10">
                            Aucun scan enregistré pour le moment.
                        </div>
                    <?php else: ?>
                        <?php foreach ($allScans as $sc): 
                            $scType = $sc['type_scan'] ?? 'diagnostic';
                            $scRes = is_array($sc['resultat_ia'] ?? null) ? $sc['resultat_ia'] : json_decode((string)($sc['resultat_ia'] ?? '{}'), true);
                            if (!is_array($scRes)) $scRes = [];
                            $scScore = is_numeric($scRes['score'] ?? ($scRes['riskScore'] ?? null)) ? (float)($scRes['score'] ?? $scRes['riskScore']) : null;
                            $scRisk = $scRes['riskLevel'] ?? ($scScore === null ? 'a_evaluer' : ($scScore >= 70 ? 'eleve' : ($scScore >= 45 ? 'modere' : 'faible')));
                            $scHost = parse_url((string)($sc['url'] ?? ''), PHP_URL_HOST);
                            $scCompanyName = trim((string)($sc['company_name'] ?? ($scRes['companyName'] ?? '')));
                            if ($scCompanyName === '') $scCompanyName = strtolower(preg_replace('/^www\\./i', '', (string)($scHost ?: 'Scan sans URL')));
                            $scGrp = array_filter($allGroups, fn($g) => $g['id'] === ($sc['group_id'] ?? 'grp_widget_default'));
                            $scGrpObj = reset($scGrp);
                            $grpName = $scGrpObj['nom'] ?? 'scanner widget';
                        ?>
                            <div class="card scan-card bg-[#1E1E1E] p-6 rounded-2xl border border-white/10 shadow-lg space-y-4 flex flex-col justify-between"
                                 data-scan-id="<?php echo htmlspecialchars((string)($sc['id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                 data-scan-url="<?php echo htmlspecialchars((string)($sc['url'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                 data-scan-type-audit="<?php echo htmlspecialchars((string)($sc['type_audit'] ?? 'full'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                 data-type="<?php echo htmlspecialchars($scType); ?>"
                                 data-group="<?php echo htmlspecialchars($sc['group_id'] ?? 'grp_widget_default'); ?>"
                                 data-url="<?php echo htmlspecialchars(strtolower($sc['url'] ?? '')); ?>">
                                
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?php 
                                            echo $scType === 'diagnostic' ? 'bg-[#2A7AE2]/20 text-[#2A7AE2] border border-[#2A7AE2]/30' :
                                                ($scType === 'pipeline' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' :
                                                'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30');
                                        ?>">
                                            <?php echo $scType === 'diagnostic' ? '🔍 Diagnostic' : ($scType === 'pipeline' ? '💼 Pipeline' : '🌐 Widget'); ?>
                                        </span>
                                        <span class="px-3 py-1 bg-white/5 text-[#AAAAAA] text-xs font-mono rounded-lg border border-white/10">
                                            🏷️ <?php echo htmlspecialchars($grpName); ?>
                                        </span>
                                    </div>

                                    <div>
                                        <h3 class="text-lg font-bold text-white truncate max-w-full" title="<?php echo htmlspecialchars($scCompanyName, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($scCompanyName, ENT_QUOTES, 'UTF-8'); ?>
                                        </h3>
                                        <p class="text-xs text-[#AAAAAA] mt-1"><?php echo htmlspecialchars((string)($sc['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="text-xs text-[#AAAAAA] mt-1">
                                            Demandeur : <strong class="text-white"><?php echo htmlspecialchars($sc['utilisateur'] ?? 'visiteur'); ?></strong> • Status : <span class="text-emerald-400 font-semibold"><?php echo htmlspecialchars($sc['statut'] ?? 'termine'); ?></span>
                                        </p>
                                    </div>

                                    <div class="p-3 bg-[#121212] rounded-xl border border-white/5 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-[#AAAAAA]">Risque observé :</span>
                                            <span class="text-lg font-bold <?php echo $scScore === null ? 'text-slate-400' : ($scScore >= 70 ? 'text-rose-400' : ($scScore >= 45 ? 'text-amber-400' : 'text-emerald-400')); ?>">
                                                <?php echo $scScore !== null ? htmlspecialchars(rtrim(rtrim(number_format($scScore, 1, '.', ''), '0'), '.')) . ' / 100' : 'À évaluer'; ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-[#E0E0E0] line-clamp-2 leading-relaxed">
                                            <?php echo htmlspecialchars($scRes['summary'] ?? 'Analyse IA terminée.'); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-white/10 flex flex-wrap gap-2 items-center justify-between text-xs">
                                    <span class="text-[#AAAAAA]">
                                        <?php echo date('d/m/Y H:i', strtotime($sc['date_scan'] ?? 'now')); ?>
                                    </span>
                                    <div class="flex flex-wrap gap-2">
                                        <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-slate-700">
                                            <input type="checkbox" class="scan-select rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" value="<?php echo htmlspecialchars((string)($sc['id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" aria-label="Sélectionner ce scan">
                                            Sélectionner
                                        </label>
                                        <?php $reportId = (string)($scRes['auditId'] ?? ''); ?>
                                        <?php if (preg_match('/^aud_[A-Za-z0-9_-]{8,80}$/', $reportId)): ?>
                                        <a href="/api/analyse.php?id=<?php echo rawurlencode($reportId); ?>" class="rounded-lg bg-[#2A7AE2] px-3 py-1.5 font-semibold text-white hover:bg-[#1D5BBF]">Consulter</a>
                                        <?php endif; ?>
                                        <button type="button" class="rescan-existing-scan rounded-lg bg-emerald-700 px-3 py-1.5 font-semibold text-white hover:bg-emerald-800">Relancer</button>
                                        <button onclick="addScanToListPrompt('<?php echo htmlspecialchars($sc['id']); ?>')" class="px-3 py-1.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-semibold rounded-lg transition-all border-none cursor-pointer">
                                            + Liste
                                        </button>
                                        <button type="button" class="delete-existing-scan px-3 py-1.5 bg-rose-900/80 hover:bg-rose-800 text-rose-200 font-semibold rounded-lg transition-all border-none cursor-pointer">
                                            Supprimer
                                        </button>
                                    </div>
                                    <p class="scan-action-status w-full text-xs text-slate-500" role="status" aria-live="polite"></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        
            
        <?php elseif ($activeTab === 'dossiers'): ?>
            <?php
            require_once __DIR__ . '/../includes/dossiers_manager.php';
            require_once __DIR__ . '/../includes/scans_manager.php';
            $allDossiers = load_all_dossiers();
            $allScans = load_all_scans();
            $latestAuditByDomain = [];
            try {
                foreach ($db->query("SELECT id, clean_url, url FROM cl_audits ORDER BY analyzedAt DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC) as $dosAudit) {
                    $dosHost = parse_url(trim((string)($dosAudit['clean_url'] ?? '')), PHP_URL_HOST) ?: parse_url((string)($dosAudit['url'] ?? ''), PHP_URL_HOST);
                    $dosDomain = strtolower(preg_replace('/^www\\./i', '', trim((string)($dosHost ?: ($dosAudit['clean_url'] ?? '')))));
                    if ($dosDomain !== '' && !isset($latestAuditByDomain[$dosDomain])) $latestAuditByDomain[$dosDomain] = (int)$dosAudit['id'];
                }
            } catch (Throwable $e) {}
            ?>
            <div class="p-8 sm:p-12 space-y-10 bg-[#121212] text-[#E0E0E0] min-h-[650px] rounded-[3rem]">
                <div class="flex flex-wrap items-center justify-between gap-6 border-b border-white/10 pb-8">
                    <div class="flex items-center space-x-3">
                        <span class="p-3 bg-[#1E1E1E] rounded-2xl text-2xl border border-white/10">📁</span>
                        <div>
                            <h2 class="text-3xl font-bold text-white tracking-tight">Gestion des Dossiers</h2>
                            <p class="text-[#AAAAAA] text-sm mt-1">Chaque projet scanné est automatiquement regroupé par domaine d'entreprise.</p>
                        </div>
                    </div>
                    <button onclick="createDossierPrompt()" class="px-6 py-3 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold rounded-2xl transition-all border-none cursor-pointer flex items-center gap-2">
                        <span>+ Créer un dossier</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($allDossiers)): ?>
                        <div class="col-span-full p-12 text-center text-[#AAAAAA] bg-[#1E1E1E] rounded-2xl border border-white/10">
                            Aucun dossier enregistré pour le moment.
                        </div>
                    <?php else: ?>
                        <?php foreach ($allDossiers as $dos): 
                            $dosScansCount = count($dos['scans'] ?? []);
                        ?>
                            <div class="bg-[#1E1E1E] p-6 rounded-2xl border border-white/10 shadow-lg space-y-4 flex flex-col justify-between">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-mono text-[#AAAAAA]">ID: <?php echo htmlspecialchars($dos['id']); ?></span>
                                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 font-bold text-xs rounded-full border border-emerald-500/30">
                                            <?php echo $dosScansCount; ?> scan(s)
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-white truncate max-w-full"><?php echo htmlspecialchars($dos['nom']); ?></h3>
                                        <p class="text-xs text-[#2A7AE2] font-mono mt-0.5">🌐 <?php echo htmlspecialchars($dos['domaine']); ?></p>
                                    </div>
                                    <div class="text-xs text-[#AAAAAA]">
                                        Créé le : <?php echo date('d/m/Y H:i', strtotime($dos['date_creation'] ?? 'now')); ?>
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-white/10 flex flex-wrap gap-2">
                                    <?php $dosAuditId = $latestAuditByDomain[strtolower(preg_replace('/^www\\./i', '', (string)($dos['domaine'] ?? '')))] ?? null; ?>
                                    <?php if ($dosAuditId !== null): ?>
                                    <a href="/api/analyse.php?id=<?php echo (int)$dosAuditId; ?>" class="px-3.5 py-2 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white text-xs font-bold rounded-xl transition-all">
                                        Consulter le dernier audit →
                                    </a>
                                    <?php else: ?>
                                    <span class="px-3.5 py-2 bg-white/5 text-[#AAAAAA] text-xs rounded-xl" title="Aucun audit enregistré pour ce domaine.">Aucun audit</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($activeTab === 'listes'): ?>
            <?php
            require_once __DIR__ . '/../includes/listes_manager.php';
            require_once __DIR__ . '/../includes/scans_manager.php';
            $allListes = load_all_listes();
            $allScans = load_all_scans();
            ?>
            <div class="p-8 sm:p-12 space-y-10 bg-[#121212] text-[#E0E0E0] min-h-[650px] rounded-[3rem]">
                <div class="flex flex-wrap items-center justify-between gap-6 border-b border-white/10 pb-8">
                    <div class="flex items-center space-x-3">
                        <span class="p-3 bg-[#1E1E1E] rounded-2xl text-2xl border border-white/10">📋</span>
                        <div>
                            <h2 class="text-3xl font-bold text-white tracking-tight">Listes de Travail & Prospection</h2>
                            <p class="text-[#AAAAAA] text-sm mt-1">Créez des listes de prospects ou de scans à cibler en priorité.</p>
                        </div>
                    </div>
                    <button onclick="createListPrompt()" class="px-6 py-3 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold rounded-2xl transition-all border-none cursor-pointer flex items-center gap-2">
                        <span>+ Créer une liste</span>
                    </button>
                </div>

                <section class="bg-[#1E1E1E] border border-white/10 rounded-2xl p-6 space-y-5">
                    <div>
                        <h3 class="text-xl font-bold text-white">Importer un groupe de sites</h3>
                        <p class="text-sm text-[#AAAAAA] mt-1">Collez une URL par ligne ou importez le modèle CSV. Une nouvelle liste sera créée automatiquement ; aucun scan ne démarre sans votre action.</p>
                    </div>
                    <form id="bulkSitesForm" class="grid grid-cols-1 lg:grid-cols-2 gap-4" enctype="multipart/form-data">
                        <div class="space-y-3">
                            <label class="block text-sm font-semibold text-white" for="bulkListName">Nom de la liste *</label>
                            <input id="bulkListName" name="nom" required placeholder="Ex. SCOP du BTP" class="w-full rounded-xl bg-[#121212] border border-white/15 p-3 text-white">
                            <label class="block text-sm font-semibold text-white" for="bulkListDescription">Description / cible commerciale</label>
                            <input id="bulkListDescription" name="description" placeholder="Ex. Coopératives du bâtiment à prospecter" class="w-full rounded-xl bg-[#121212] border border-white/15 p-3 text-white">
                            <label class="block text-sm font-semibold text-white" for="bulkCsv">Fichier CSV (facultatif)</label>
                            <input id="bulkCsv" name="csv" type="file" accept=".csv,text/csv" class="w-full text-sm text-[#CCCCCC]">
                            <div class="flex flex-wrap gap-3">
                                <a href="/api/listes/import_export.php?action=template" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800 text-sm font-semibold shadow-sm">Télécharger le modèle CSV</a>
                                <a href="/api/listes/import_export.php?action=export" class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-300 text-slate-800 hover:bg-slate-50 text-sm font-semibold">Exporter toutes les listes</a>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-sm font-semibold text-white" for="bulkUrls">Ou collez les sites (une URL par ligne)</label>
                            <textarea id="bulkUrls" name="urls" rows="8" placeholder="https://entreprise1.fr&#10;https://entreprise2.fr&#10;entreprise3.fr" class="w-full rounded-xl bg-[#121212] border border-white/15 p-3 text-white font-mono text-sm"></textarea>
                            <p class="text-xs text-[#AAAAAA]">Jusqu’à 500 URL par import. Les doublons sont ignorés. Le CSV accepte les colonnes liste, description, url, nom.</p>
                        </div>
                        <div class="lg:col-span-2 flex flex-wrap items-center gap-4">
                            <button type="submit" class="px-6 py-3 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold rounded-xl">Importer et créer la liste</button>
                            <span id="bulkImportStatus" class="text-sm text-[#AAAAAA]" role="status" aria-live="polite"></span>
                        </div>
                    </form>
                </section>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($allListes)): ?>
                        <div class="col-span-full p-12 text-center text-[#AAAAAA] bg-[#1E1E1E] rounded-2xl border border-white/10">
                            Aucune liste enregistrée pour le moment.
                        </div>
                    <?php else: ?>
                        <?php foreach ($allListes as $lst):
                            $itemCount = count($lst['sites'] ?? ($lst['items'] ?? []));
                        ?>
                            <div class="bg-[#1E1E1E] p-6 rounded-2xl border border-white/10 shadow-lg space-y-4 flex flex-col justify-between">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-end">
                                        <span class="px-3 py-1 bg-[#2A7AE2]/20 text-[#2A7AE2] font-bold text-xs rounded-full border border-[#2A7AE2]/30">
                                            <?php echo $itemCount; ?> élément(s)
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-white truncate max-w-full"><?php echo htmlspecialchars($lst['nom']); ?></h3>
                                        <p class="text-xs text-[#AAAAAA] mt-1"><?php echo htmlspecialchars($lst['description'] ?: 'Aucune description'); ?></p>
                                    </div>
                                    <?php if (!empty($lst['sites'])): ?>
                                        <div class="space-y-2 max-h-72 overflow-y-auto border-t border-white/10 pt-3">
                                            <?php foreach ($lst['sites'] as $site): ?>
                                                <div class="flex items-center justify-between gap-2 text-xs">
                                                    <a class="text-blue-300 hover:underline truncate" target="_blank" rel="noopener noreferrer" href="<?php echo htmlspecialchars($site['url'] ?? '#', ENT_QUOTES); ?>"><?php echo htmlspecialchars(($site['nom'] ?? '') ?: ($site['url'] ?? 'Site')); ?></a>
                                                    <span class="text-[#AAAAAA] whitespace-nowrap"><?php echo htmlspecialchars($site['statut'] ?? 'à_analyser'); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <a class="inline-flex justify-center px-4 py-2 rounded-xl border border-slate-300 text-slate-800 hover:bg-slate-50 text-sm font-medium" href="/api/listes/import_export.php?action=export&amp;list_id=<?php echo rawurlencode($lst['id']); ?>">Exporter cette liste CSV</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($activeTab === 'style'): ?>
            <?php
            $userStylePath = __DIR__ . '/../../data/user_style.json';
            if (!file_exists($userStylePath)) {
                $userStylePath = __DIR__ . '/../data/user_style.json';
            }
            $currentStyle = [
                'fontFamily' => 'Inter',
                'fontSize' => 15,
                'textColor' => '#E0E0E0',
                'headingColor' => '#FFFFFF',
                'bgMain' => '#121212',
                'cardBg' => '#1E1E1E',
                'accentColor' => '#2A7AE2',
                'sampleText' => 'GreenHumble v1.1 — Détection et audit de conformité environnementale selon les directives EmpCO et Green Claims.'
            ];
            if (file_exists($userStylePath)) {
                $loaded = json_decode(file_get_contents($userStylePath), true);
                if (is_array($loaded)) {
                    $currentStyle = array_merge($currentStyle, $loaded);
                }
            }
            ?>
            <div class="p-8 sm:p-12 space-y-10">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-3xl font-black text-white tracking-tight">Module d’Édition de Texte & Style</h2>
                        <p class="text-slate-400 mt-2 text-sm">Personnalisez la typographie, la taille de police et la palette de l'application sans toucher au code.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 bg-[#1E1E1E] text-slate-300 text-xs font-semibold rounded-md border border-white/10">
                            Fichier : /data/user_style.json
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 max-w-6xl">
                    <!-- Formulaire de sélection (7 colonnes) -->
                    <div class="lg:col-span-7 bg-[#1E1E1E] p-6 sm:p-8 rounded-2xl border border-white/10 shadow-xl space-y-6">
                        <h3 class="text-lg font-bold text-white mb-4">Sélecteurs Typographiques</h3>

                        <!-- 1. Police (Dropdown Google Fonts) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Choisir la Police (Google Fonts)</label>
                            <select id="style-font-family" class="w-full px-4 py-3 bg-[#242424] text-[#E0E0E0] border border-[#383838] rounded-md font-semibold text-sm focus:ring-2 focus:ring-[#2A7AE2] focus:outline-none">
                                <option value="Inter" <?php echo ($currentStyle['fontFamily'] === 'Inter') ? 'selected' : ''; ?>>Inter (Moderne & Sobre — Recommandé)</option>
                                <option value="Roboto" <?php echo ($currentStyle['fontFamily'] === 'Roboto') ? 'selected' : ''; ?>>Roboto (Neutre & Institutionnel)</option>
                                <option value="Plus Jakarta Sans" <?php echo ($currentStyle['fontFamily'] === 'Plus Jakarta Sans') ? 'selected' : ''; ?>>Plus Jakarta Sans (Moderne AI Studio)</option>
                                <option value="Outfit" <?php echo ($currentStyle['fontFamily'] === 'Outfit') ? 'selected' : ''; ?>>Outfit (Tech & Élégant)</option>
                                <option value="Poppins" <?php echo ($currentStyle['fontFamily'] === 'Poppins') ? 'selected' : ''; ?>>Poppins (Géométrique)</option>
                                <option value="Montserrat" <?php echo ($currentStyle['fontFamily'] === 'Montserrat') ? 'selected' : ''; ?>>Montserrat (Impact & Lisibilité)</option>
                            </select>
                        </div>

                        <!-- 2. Taille de police (Slider px) -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-slate-300 uppercase tracking-wider">Choisir la Taille de Police</label>
                                <span id="style-font-size-display" class="px-2.5 py-0.5 bg-[#2A7AE2] text-white font-mono text-xs font-bold rounded">
                                    <?php echo $currentStyle['fontSize']; ?>px
                                </span>
                            </div>
                            <input type="range" id="style-font-size" min="12" max="22" value="<?php echo $currentStyle['fontSize']; ?>" step="1" 
                                class="w-full h-2 bg-[#2A2A2A] rounded-lg appearance-none cursor-pointer accent-[#2A7AE2]">
                            <div class="flex justify-between text-[11px] text-slate-500 mt-1">
                                <span>12px (Compact)</span>
                                <span>15px (Standard)</span>
                                <span>22px (Grand)</span>
                            </div>
                        </div>

                        <!-- 3. Couleur du texte principal (Picker) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Choisir la Couleur du Texte Principal</label>
                            <div class="flex items-center gap-3">
                                <input type="color" id="style-text-color" value="<?php echo $currentStyle['textColor']; ?>" 
                                    class="w-12 h-10 rounded border border-[#383838] bg-transparent cursor-pointer p-0.5">
                                <input type="text" id="style-text-color-hex" value="<?php echo $currentStyle['textColor']; ?>" 
                                    class="flex-1 px-4 py-2.5 bg-[#242424] text-[#E0E0E0] border border-[#383838] rounded-md font-mono text-xs uppercase">
                            </div>
                        </div>

                        <!-- 4. Couleurs de la palette -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-white/5">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1.5">Fond Général</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="style-bg-color" value="<?php echo $currentStyle['bgMain']; ?>" class="w-8 h-8 rounded border border-[#383838] p-0.5 cursor-pointer">
                                    <span class="text-xs font-mono text-slate-400">#121212</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1.5">Fond Carte</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="style-card-color" value="<?php echo $currentStyle['cardBg']; ?>" class="w-8 h-8 rounded border border-[#383838] p-0.5 cursor-pointer">
                                    <span class="text-xs font-mono text-slate-400">#1E1E1E</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1.5">Accent Bouton</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="style-accent-color" value="<?php echo $currentStyle['accentColor']; ?>" class="w-8 h-8 rounded border border-[#383838] p-0.5 cursor-pointer">
                                    <span class="text-xs font-mono text-slate-400">#2A7AE2</span>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Texte d'exemple -->
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Texte d'Exemple pour la Prévisualisation</label>
                            <input type="text" id="style-sample-text" value="<?php echo htmlspecialchars($currentStyle['sampleText']); ?>" 
                                class="w-full px-4 py-2.5 bg-[#242424] text-[#E0E0E0] border border-[#383838] rounded-md text-xs">
                        </div>

                        <div class="pt-4">
                            <button id="style-apply-btn" class="w-full py-3.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold rounded-md text-sm transition-colors shadow-lg flex items-center justify-center space-x-2">
                                <span>💾 Appliquer & Enregistrer dans /data/user_style.json</span>
                            </button>
                        </div>
                    </div>

                    <!-- Zone de Prévisualisation Live (5 colonnes) -->
                    <div class="lg:col-span-5 flex flex-col justify-between space-y-6">
                        <div class="bg-[#121212] p-6 rounded-2xl border border-white/10 shadow-2xl">
                            <div class="flex items-center justify-between pb-3 mb-4 border-b border-white/10">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">👁️ Prévisualisation Live</span>
                                <span class="text-[10px] px-2 py-0.5 bg-[#00A67E] text-white rounded font-bold">Rendu en direct</span>
                            </div>

                            <!-- Bloc Carte Prévisualisée -->
                            <div id="style-live-preview-card" class="p-5 rounded-lg border border-white/10 transition-all" style="background-color: <?php echo $currentStyle['cardBg']; ?>; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
                                <h3 id="style-live-preview-title" class="text-lg font-bold mb-2 transition-all" style="color: <?php echo $currentStyle['headingColor']; ?>; font-family: '<?php echo $currentStyle['fontFamily']; ?>', sans-serif;">
                                    Audit de Conformité Green Claims
                                </h3>
                                
                                <p id="style-live-preview-text" class="leading-relaxed mb-4 transition-all" style="color: <?php echo $currentStyle['textColor']; ?>; font-size: <?php echo $currentStyle['fontSize']; ?>px; font-family: '<?php echo $currentStyle['fontFamily']; ?>', sans-serif;">
                                    <?php echo htmlspecialchars($currentStyle['sampleText']); ?>
                                </p>

                                <div class="flex items-center justify-between pt-3 border-t border-white/5">
                                    <span class="text-xs text-slate-400">Score de conformité : <strong class="text-[#00A67E]">88/100</strong></span>
                                    <button id="style-live-preview-btn" class="px-4 py-2 text-xs font-semibold rounded" style="background-color: <?php echo $currentStyle['accentColor']; ?>; color: #FFFFFF;">
                                        Consulter l'Audit
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-[#1E1E1E] rounded-lg border border-white/5 text-xs text-slate-400 space-y-2">
                                <div class="font-bold text-white text-[11px] uppercase">Règles de style actives :</div>
                                <div>• Police courante : <span class="font-mono text-[#2A7AE2]" id="preview-font-label"><?php echo $currentStyle['fontFamily']; ?></span></div>
                                <div>• Taille de base : <span class="font-mono text-[#2A7AE2]" id="preview-size-label"><?php echo $currentStyle['fontSize']; ?>px</span></div>
                                <div>• Sauvegarde immédiate dans <code>/data/user_style.json</code></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($activeTab === 'agency'): ?>
            <div class="p-12 space-y-12">
                <div>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight">Organisation & équipe</h2>
                    <p class="text-slate-400 mt-2">Cette page regroupe l’identité de l’agence et le Team Manager pour gérer les accès des collaborateurs.</p>
                    <nav class="mt-5 flex flex-wrap gap-2" aria-label="Sections Organisation">
                        <a href="#organisation-identite" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Identité de l’organisation</a>
                        <a href="#organisation-equipe" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50">Team Manager · accès collaborateurs</a>
                    </nav>
                </div>
                <div id="organisation-identite" class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-6xl scroll-mt-24">
                    <div class="space-y-6">
                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-3 mb-2">Désignation Officielle</label>
                            <input type="text" id="ag_name" value="<?php echo htmlspecialchars($state['agency']['agencyName']); ?>" class="w-full px-8 py-5 bg-slate-50 rounded-3xl border-none focus:ring-8 focus:ring-emerald-500/10 font-black text-slate-900 text-xl transition-all shadow-inner">
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-3 mb-2">Slogan / Promesse RSE</label>
                            <input type="text" id="ag_slogan" value="<?php echo htmlspecialchars($state['agency']['slogan']); ?>" class="w-full px-8 py-5 bg-slate-50 rounded-3xl border-none focus:ring-8 focus:ring-emerald-500/10 font-medium text-slate-600 italic transition-all shadow-inner">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-3 mb-2">Consultant Fondateur</label>
                            <input type="text" id="ag_consultant" value="<?php echo htmlspecialchars($state['agency']['consultantName']); ?>" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-none font-bold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-3 mb-2">Email Contact</label>
                            <input type="email" id="ag_email" value="<?php echo htmlspecialchars($state['agency']['agencyEmail']); ?>" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-none font-bold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-3 mb-2">Téléphone Pro</label>
                            <input type="text" id="ag_phone" value="<?php echo htmlspecialchars($state['agency']['agencyPhone']); ?>" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-none font-bold text-slate-800">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-3 mb-2">Site Web (URL)</label>
                            <input type="text" id="ag_web" value="<?php echo htmlspecialchars($state['agency']['agencyWebsite']); ?>" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-none font-mono text-xs">
                        </div>
                    </div>
                </div>
            </div>

        <section id="organisation-equipe" class="mt-12 border-t border-slate-200 pt-10 scroll-mt-24">
                <p class="mb-6 text-sm font-semibold text-slate-500">ÉQUIPE & ACCÈS</p>

            <div class="p-12 space-y-10">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Comptes utilisateurs</h2>
                        <p class="text-slate-500 mt-2">Créez et gérez les comptes de l’espace collaborateur. Ces comptes n’ont pas accès à la console administrateur ; leurs mots de passe sont hachés.</p>
                    </div>
                    <button type="button" onclick="openCollaboratorModal()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#2A7AE2] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#1D5BBF]">
                        <span aria-hidden="true">＋</span><span>Créer un compte</span>
                    </button>
                </div>
                <p id="collaborator-status" class="min-h-5 text-sm text-slate-500" role="status" aria-live="polite"></p>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5" id="team-container">
                    <?php foreach (($state['collaborators'] ?? []) as $c):
                        $collaboratorId = (string)($c['id'] ?? '');
                        $collaboratorName = (string)($c['name'] ?? '');
                    ?>
                        <article class="team-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md" data-id="<?php echo htmlspecialchars($collaboratorId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-lg font-extrabold text-emerald-800">
                                    <?php echo htmlspecialchars(function_exists('mb_substr') ? mb_substr($collaboratorName, 0, 1) : substr($collaboratorName, 0, 1), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                </div>
                                <div class="min-w-0 flex-1 space-y-3">
                                    <label class="block text-xs font-semibold text-slate-600">Nom complet
                                        <input type="text" value="<?php echo htmlspecialchars($collaboratorName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="t-name mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900" maxlength="120" required>
                                    </label>
                                    <label class="block text-xs font-semibold text-slate-600">Adresse e-mail
                                        <input type="email" value="<?php echo htmlspecialchars((string)($c['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="t-email mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900" maxlength="254" required>
                                    </label>
                                    <label class="block text-xs font-semibold text-slate-600">Fonction
                                        <input type="text" value="<?php echo htmlspecialchars((string)($c['role'] ?? 'Collaborateur'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="t-role mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900" maxlength="80" required>
                                    </label>
                                </div>
                            </div>
                            <div class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" onclick="saveCollaboratorCard(this)" class="collaborator-save rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white transition hover:bg-emerald-800">Enregistrer</button>
                                    <button type="button" onclick="setCollaboratorPassword(this.closest('.team-card').dataset.id)" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Réinitialiser le mot de passe</button>
                                </div>
                                <button type="button" onclick="deleteCollaboratorCard(this)" class="rounded-lg px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">Supprimer le compte</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php if (empty($state['collaborators'])): ?>
                        <p class="col-span-full rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">Aucun compte collaborateur. Créez un compte pour donner accès à l’espace.</p>
                    <?php endif; ?>
                </div>
                <div id="collaborator-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="collaborator-modal-title">
                    <form id="collaborator-form" onsubmit="submitCollaboratorForm(event)" class="w-full max-w-lg space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Nouvel accès</p>
                            <h3 id="collaborator-modal-title" class="mt-1 text-xl font-extrabold text-slate-900">Créer un compte utilisateur</h3>
                            <p class="mt-1 text-sm text-slate-500">L’adresse e-mail servira d’identifiant de connexion.</p>
                        </div>
                        <label class="block text-sm font-semibold text-slate-700">Nom complet
                            <input id="new-collaborator-name" type="text" maxlength="120" required autocomplete="name" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-3 text-sm text-slate-900">
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">Adresse e-mail
                            <input id="new-collaborator-email" type="email" maxlength="254" required autocomplete="email" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-3 text-sm text-slate-900">
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">Fonction
                            <input id="new-collaborator-role" type="text" maxlength="80" value="Collaborateur" required class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-3 text-sm text-slate-900">
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">Mot de passe initial
                            <input id="new-collaborator-password" type="password" minlength="12" required autocomplete="new-password" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-3 text-sm text-slate-900">
                            <span class="mt-1 block text-xs font-normal text-slate-500">12 caractères minimum. Le mot de passe est stocké sous forme hachée.</span>
                        </label>
                        <p id="collaborator-form-error" class="hidden rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700" role="alert"></p>
                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="closeCollaboratorModal()" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Annuler</button>
                            <button type="submit" class="rounded-xl bg-[#2A7AE2] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1D5BBF]">Créer le compte</button>
                        </div>
                    </form>
                </div>
            </div>
        
            </section>
        <?php elseif ($activeTab === 'missions'): ?>
            <div class="p-12 space-y-10">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Missions & Accompagnements</h2>
                        <p class="text-slate-400 mt-2">Éditez les arguments commerciaux et les budgets par métier.</p>
                    </div>
                    <button onclick="addItem('missions')" class="px-6 py-3 bg-slate-900 text-white text-xs font-black rounded-2xl shadow-xl hover:bg-emerald-900 transition-all flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                        <span>Ajouter un Métier</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-3" id="missions-container">
                    <?php foreach (($state['serviceOpps'] ?? []) as $idx => $m):
                        $missionTitle = trim((string)($m['serviceTitle'] ?? ''));
                        $missionId = (string)($m['id'] ?? ('mission_' . $idx));
                    ?>
                        <details class="mission-card group rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-300 hover:shadow-md" data-id="<?php echo htmlspecialchars($missionId, ENT_QUOTES, 'UTF-8'); ?>" data-service-key="<?php echo htmlspecialchars((string)($m['serviceKey'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <summary class="flex cursor-pointer list-none items-center gap-4 px-5 py-4 sm:px-6">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-xl">💼</span>
                                <span class="min-w-0 flex-1">
                                    <span class="m-summary-title block truncate text-base font-bold text-slate-900"><?php echo htmlspecialchars($missionTitle ?: 'Métier sans titre', ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="m-summary-desc mt-1 block truncate text-sm text-slate-500"><?php echo htmlspecialchars(trim((string)($m['description'] ?? '')) ?: 'Cliquez pour compléter la description, le budget et les aides.', ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                                <span class="m-summary-status shrink-0 rounded-full px-3 py-1 text-xs font-semibold <?php echo !empty($m['isActive']) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'; ?>"><?php echo !empty($m['isActive']) ? 'Actif' : 'Inactif'; ?></span>
                                <span class="mission-chevron shrink-0 text-slate-400 transition-transform" aria-hidden="true">⌄</span>
                            </summary>
                            <div class="mission-editor border-t border-slate-100 px-5 py-5 sm:px-6">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <label class="block text-sm font-semibold text-slate-700 md:col-span-2">Nom du métier
                                        <input type="text" value="<?php echo htmlspecialchars($missionTitle, ENT_QUOTES, 'UTF-8'); ?>" class="m-title mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" placeholder="Titre de la mission">
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-700 md:col-span-2">Description
                                        <textarea class="m-desc mt-1.5 min-h-28 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm leading-relaxed text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100" placeholder="Description de l’accompagnement"><?php echo htmlspecialchars((string)($m['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-700">Budget moyen
                                        <input type="text" value="<?php echo htmlspecialchars((string)($m['defaultBudget'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="m-budget mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100">
                                    </label>
                                    <label class="block text-sm font-semibold text-slate-700">Aide / subvention
                                        <input type="text" value="<?php echo htmlspecialchars((string)($m['subsidyAmount'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="m-sub mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100">
                                    </label>
                                    <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700 md:col-span-2">
                                        <input type="checkbox" <?php echo !empty($m['isActive']) ? 'checked' : ''; ?> class="m-active h-5 w-5 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500">
                                        Proposer ce métier dans les recommandations
                                    </label>
                                </div>
                                <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
                                    <button type="button" onclick="deleteItem('missions', <?php echo (int)$idx; ?>)" class="rounded-xl border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">Supprimer ce métier</button>
                                </div>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php elseif ($activeTab === 'legal'): ?>
            <div class="p-12 space-y-8">
                <section class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Conformité & argumentaire</p>
                            <h2 class="mt-2 text-3xl font-black text-slate-900 tracking-tight">Références & conformité</h2>
                            <p class="mt-2 text-sm text-slate-600">Centralisez ici les références juridiques et messages de conformité utilisés dans les audits, les recommandations et la prospection.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                                <?php echo count($state['legalTexts'] ?? []); ?> référence(s) configurée(s)
                            </span>
                            <button onclick="addItem('legal')" class="px-6 py-3 bg-slate-900 text-white text-xs font-black rounded-2xl shadow-xl hover:bg-emerald-900 transition-all flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                <span>Ajouter une référence</span>
                            </button>
                        </div>
                    </div>
                </section>

                <div class="grid gap-4 lg:grid-cols-3">
                    <article class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Usage</p>
                        <p class="mt-3 text-sm leading-6 text-slate-700">Chaque référence nourrit les audits, le score de risque et les arguments du kit commercial.</p>
                    </article>
                    <article class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Bon réflexe</p>
                        <p class="mt-3 text-sm leading-6 text-slate-700">Préférez des formulations prudentes, datées et vérifiables plutôt que des conclusions automatiques.</p>
                    </article>
                    <article class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Synchronisation</p>
                        <p class="mt-3 text-sm leading-6 text-slate-700">Les modifications sont partagées entre l’audit, les recommandations IA et la prospection.</p>
                    </article>
                </div>

                <div class="space-y-12">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">Référentiels juridiques</h3>
                            <p class="text-slate-500 mt-2 text-sm">Configurez les fondements légaux, leur poids et leur activation.</p>
                        </div>
                    </div>

                    <div class="legal-links p-8 bg-slate-900 text-slate-200 rounded-[2.5rem] border border-slate-800 shadow-xl space-y-4">
                        <h3 class="text-xl font-bold text-white flex items-center space-x-2">
                            <span>📜</span>
                            <span>Textes de loi & Sources Officielles</span>
                        </h3>
                        <ul class="flex flex-wrap gap-4 text-sm">
                            <li><a href="https://www.legifrance.gouv.fr" target="_blank" rel="noopener noreferrer" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-emerald-400 rounded-xl font-semibold transition-all flex items-center gap-2"><span>🇫🇷</span> Legifrance</a></li>
                            <li><a href="https://eur-lex.europa.eu" target="_blank" rel="noopener noreferrer" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-emerald-400 rounded-xl font-semibold transition-all flex items-center gap-2"><span>🇪🇺</span> EUR-Lex (UE)</a></li>
                            <li><a href="https://cnil.fr" target="_blank" rel="noopener noreferrer" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-emerald-400 rounded-xl font-semibold transition-all flex items-center gap-2"><span>🔒</span> CNIL - RGPD</a></li>
                        </ul>
                    </div>

                    <div class="space-y-6" id="legal-container">
                        <?php foreach ($state['legalTexts'] as $idx => $l): ?>
                            <div class="legal-row flex flex-col lg:flex-row lg:items-center gap-6 p-8 bg-slate-50 rounded-[3rem] border border-slate-200 shadow-sm hover:bg-white hover:shadow-2xl transition-all relative group" data-id="<?php echo $l['id']; ?>">
                                <button onclick="deleteItem('legal', <?php echo $idx; ?>)" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 p-2 text-slate-300 hover:text-red-500 transition-all">✕</button>
                                <div class="flex items-center space-x-6 flex-grow">
                                    <div class="w-16 h-16 bg-white rounded-3xl flex items-center justify-center font-black text-slate-300 text-2xl shadow-sm border border-slate-100"><?php echo $idx+1; ?></div>
                                    <div class="flex-grow space-y-2">
                                        <input type="text" value="<?php echo htmlspecialchars($l['title']); ?>" class="l-title w-full bg-transparent font-black text-slate-900 text-lg border-none p-0 focus:ring-0 placeholder-slate-300" placeholder="Titre de la loi (ex: CSRD)">
                                        <textarea class="l-desc w-full bg-transparent text-sm text-slate-500 border-none p-0 focus:ring-0 italic h-12 resize-none placeholder-slate-300" placeholder="Description courte pour le rapport..."><?php echo htmlspecialchars($l['description']); ?></textarea>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-8 bg-white/50 p-6 rounded-[2rem] border border-slate-200 shadow-inner">
                                    <div class="text-center w-24">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Impact Risque</label>
                                        <input type="number" value="<?php echo $l['weight']; ?>" class="l-weight w-full bg-transparent text-center font-black text-indigo-700 border-none p-0 text-xl focus:ring-0">
                                    </div>
                                    <div class="h-10 w-px bg-slate-200"></div>
                                    <div class="text-center w-24">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Activer</label>
                                        <input type="checkbox" <?php echo $l['isActive'] ? 'checked' : ''; ?> class="l-active w-8 h-8 text-emerald-600 rounded-xl border-slate-300 cursor-pointer shadow-sm">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($activeTab === 'scanner'): ?>
            <?php
            $scanVars = is_array($state['scanVars'] ?? null) ? $state['scanVars'] : [];
            $promptFields = [
                ['id' => 'ia_prompt', 'label' => 'Rôle et consignes générales', 'key' => 'aiKeywordInstructions', 'fallback' => 'Tu es l’IA GreenHumble, experte en conformité RSE, allégations environnementales et analyse de sites. Réponds en français, avec précision et sans tirer de conclusion au-delà des preuves.', 'rows' => 6, 'hint' => 'Consigne générale appliquée à chaque analyse.'],
                ['id' => 'ia_scope', 'label' => 'Périmètre d’analyse', 'key' => 'aiAnalysisInstructions', 'fallback' => 'Analyse les pages accessibles du site et relève chaque allégation environnementale distincte dans son contexte. Signale aussi les limites de couverture du scan.', 'rows' => 5, 'hint' => 'Pages, contenus et types de signaux à prendre en compte.'],
                ['id' => 'ia_evidence', 'label' => 'Preuves et citations attendues', 'key' => 'aiEvidenceInstructions', 'fallback' => 'Pour chaque signal, donne une citation exacte, l’URL de la page source, les éléments justificatifs manquants et les incertitudes à lever. N’invente jamais de citation ni de preuve.', 'rows' => 5, 'hint' => 'Exigences de traçabilité et éléments à demander à l’entreprise.'],
                ['id' => 'ia_risk', 'label' => 'Évaluation du risque', 'key' => 'aiRiskInstructions', 'fallback' => 'Distingue un signal à vérifier, une preuve insuffisante, un risque plausible et une non-conformité documentée. Explique le niveau de risque sans présenter une suspicion comme une infraction établie.', 'rows' => 5, 'hint' => 'Critères de qualification et de prudence juridique.'],
                ['id' => 'ia_commercial', 'label' => 'Recommandations commerciales', 'key' => 'aiCommercialInstructions', 'fallback' => 'Propose une prochaine action et une priorité adaptées aux constats vérifiables. Ne recommande qu’une offre active du catalogue et relie chaque argument à une preuve effectivement relevée.', 'rows' => 5, 'hint' => 'Conseils d’accompagnement fondés sur les résultats du scan.'],
                ['id' => 'ia_compliance', 'label' => 'Référentiels juridiques et limites', 'key' => 'aiLegalFramework', 'fallback' => 'Examiner les textes applicables selon les faits, la date et le champ de l’entreprise. Citer uniquement les références vérifiées et ne jamais conclure automatiquement à une infraction.', 'rows' => 6, 'hint' => 'Cadre juridique à vérifier sans transformer un signal en conclusion.'],
                ['id' => 'ia_corrections', 'label' => 'Actions correctives', 'key' => 'aiCorrectiveActions', 'fallback' => 'Proposer une reformulation, les preuves à réunir et une action adaptée à chaque risque étayé.', 'rows' => 6, 'hint' => 'Reformulation, preuves, retrait éventuel et recommandations RSE/climat.'],
                ['id' => 'ia_user_prompt', 'label' => 'Consigne de scan', 'key' => 'aiUserPrompt', 'fallback' => 'Analyser les allégations, preuves, risques, fondements applicables, corrections et opportunités RSE/climat/CSRD.', 'rows' => 6, 'hint' => 'Mission transmise au moteur pour chaque scan.'],
                ['id' => 'ia_dictionary', 'label' => 'Dictionnaire de signaux', 'key' => 'aiClaimsDictionary', 'fallback' => 'Expressions environnementales génériques, absolues, vagues, comparatifs non sourcés, preuves absentes ou compensation mise en avant.', 'rows' => 8, 'hint' => 'Dictionnaire de repérage : chaque expression doit être vérifiée dans son contexte.'],
                ['id' => 'ia_banned', 'label' => 'Expressions supplémentaires', 'key' => 'customBannedKeywords', 'fallback' => 'neutre en carbone, éco-responsable, vert, bio, naturel, durable', 'rows' => 4, 'hint' => 'Ajoutez vos repères personnalisés, séparés par des virgules ou retours à la ligne.']
            ];
            ?>
            <section class="p-6 sm:p-9 space-y-7 bg-white rounded-3xl border border-slate-200 shadow-sm">
                <header class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Intelligence artificielle</p>
                    <h2 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Prompts &amp; règles</h2>
                    <p class="mt-2 text-sm text-slate-600">Modifiez ici les consignes qui guident l’analyse. Les fournisseurs, modèles et clés API se règlent séparément dans « Fournisseurs IA ».</p>
                </header>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                    <?php foreach ($promptFields as $field): ?>
                        <label for="<?= htmlspecialchars($field['id'], ENT_QUOTES, 'UTF-8') ?>" class="block rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <span class="block text-sm font-bold text-slate-900"><?= htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="mt-1 block text-xs text-slate-500"><?= htmlspecialchars($field['hint'], ENT_QUOTES, 'UTF-8') ?></span>
                            <textarea id="<?= htmlspecialchars($field['id'], ENT_QUOTES, 'UTF-8') ?>" rows="<?= (int)$field['rows'] ?>" class="mt-3 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm leading-6 text-slate-800 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100"><?= htmlspecialchars((string)($scanVars[$field['key']] ?? $field['fallback']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" onclick="saveScanner()" class="save-btn px-5 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-bold">Enregistrer les prompts</button>
                    <span class="text-xs text-slate-500">Les valeurs déjà enregistrées restent préremplies.</span>
                </div>
            </section>

        <?php elseif ($activeTab === 'scanner_groups'): ?>
            <?php
            require_once __DIR__ . '/../includes/scanner_groups.php';
            $allGroups = load_scanner_groups();
            $allScanners = load_scanners();
            ?>
            <div class="p-8 sm:p-12 space-y-10 bg-[#121212] text-[#E0E0E0] min-h-[650px] rounded-[3rem]">
                <!-- Header Groupe -->
                <div class="flex flex-wrap items-center justify-between gap-6 border-b border-white/10 pb-8">
                    <div>
                        <div class="flex items-center space-x-3">
                            <span class="p-3 bg-[#1E1E1E] rounded-2xl text-2xl border border-white/10">🏷️</span>
                            <div>
                                <h2 class="text-3xl font-bold text-white tracking-tight">Groupes de Scanners</h2>
                                <p class="text-[#AAAAAA] text-sm mt-1">Organisez vos instances de scanners d'audit et assignez vos widgets.</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button onclick="showCreateGroupModal()" class="px-6 py-3.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-semibold rounded-xl transition-all shadow-md flex items-center space-x-2 border-none cursor-pointer">
                            <span>➕</span>
                            <span>Créer un Groupe</span>
                        </button>
                    </div>
                </div>

                <!-- Formulaire Rapide de Création (Inline) -->
                <div id="createGroupCard" class="hidden p-6 bg-[#1E1E1E] rounded-2xl border border-[#2A7AE2]/40 shadow-xl space-y-4">
                    <h3 class="text-lg font-bold text-white">Nouveau Groupe de Scanners</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#AAAAAA] uppercase tracking-wider mb-2">Nom du Groupe</label>
                            <input type="text" id="new_group_nom" placeholder="ex: Scanners PME Auvergne" class="w-full px-4 py-3 bg-[#121212] border border-white/15 rounded-xl text-white font-medium focus:border-[#2A7AE2] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#AAAAAA] uppercase tracking-wider mb-2">Description</label>
                            <input type="text" id="new_group_desc" placeholder="Périmètre ou secteur d'activité..." class="w-full px-4 py-3 bg-[#121212] border border-white/15 rounded-xl text-white font-medium focus:border-[#2A7AE2] focus:outline-none">
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button onclick="submitCreateGroup()" class="px-5 py-2.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-semibold rounded-lg transition-all border-none cursor-pointer">Enregistrer le Groupe</button>
                        <button onclick="hideCreateGroupModal()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-lg transition-all border-none cursor-pointer">Annuler</button>
                    </div>
                </div>

                <!-- Formulaire Rapide de Réassignation Scanner -->
                <div class="p-6 bg-[#1E1E1E] rounded-2xl border border-white/10 shadow-lg space-y-4">
                    <h3 class="text-base font-bold text-white flex items-center space-x-2">
                        <span>🎯</span>
                        <span>Assigner un Scanner à un Groupe</span>
                    </h3>
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex-grow min-w-[240px]">
                            <label class="block text-xs font-semibold text-[#AAAAAA] mb-1">Sélectionner le Scanner</label>
                            <select id="assign_scanner_select" class="w-full px-4 py-3 bg-[#121212] border border-white/15 rounded-xl text-white font-medium focus:border-[#2A7AE2]">
                                <?php foreach ($allScanners as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s['id']); ?>">
                                        <?php echo htmlspecialchars($s['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex-grow min-w-[240px]">
                            <label class="block text-xs font-semibold text-[#AAAAAA] mb-1">Groupe Cible</label>
                            <select id="assign_group_select" class="w-full px-4 py-3 bg-[#121212] border border-white/15 rounded-xl text-white font-medium focus:border-[#2A7AE2]">
                                <?php foreach ($allGroups as $g): ?>
                                    <option value="<?php echo htmlspecialchars($g['id']); ?>">
                                        <?php echo htmlspecialchars($g['nom']); ?> <?php echo !empty($g['is_default']) ? '(Par Défaut)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="pt-5">
                            <button onclick="submitAssignScanner()" class="px-6 py-3 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold rounded-xl transition-all border-none cursor-pointer flex items-center space-x-2">
                                <span>🔗</span>
                                <span>Assigner</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grille des Cartes de Groupes -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="groups-container">
                    <?php foreach ($allGroups as $grp): 
                        $isDefault = !empty($grp['is_default']) || strtolower($grp['nom']) === 'scanner widget' || $grp['id'] === 'grp_widget_default';
                        $groupScanners = array_filter($allScanners, fn($s) => ($s['group_id'] ?? '') === $grp['id']);
                    ?>
                        <div class="card bg-[#1E1E1E] p-6 rounded-2xl border border-white/10 shadow-lg space-y-5 relative flex flex-col justify-between" data-group-id="<?php echo htmlspecialchars($grp['id']); ?>">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="px-3 py-1 bg-[#2A7AE2]/20 text-[#2A7AE2] font-mono text-xs rounded-full border border-[#2A7AE2]/30 font-bold">
                                        <?php echo htmlspecialchars($grp['id']); ?>
                                    </span>
                                    <?php if ($isDefault): ?>
                                        <span class="px-3 py-1 bg-amber-500/20 text-amber-400 font-bold text-xs rounded-full border border-amber-500/30 flex items-center space-x-1">
                                            <span>⭐</span>
                                            <span>Spécial System</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="text-xl font-bold text-white"><?php echo htmlspecialchars($grp['nom']); ?></h3>
                                <p class="text-sm text-[#AAAAAA] leading-relaxed"><?php echo htmlspecialchars($grp['description'] ?? 'Aucune description'); ?></p>
                                <div class="pt-3 border-t border-white/5 text-xs text-[#AAAAAA] flex items-center justify-between">
                                    <span>Créé le : <?php echo date('d/m/Y', strtotime($grp['date_creation'] ?? 'now')); ?></span>
                                    <span class="text-emerald-400 font-semibold"><?php echo count($groupScanners); ?> scanner(s)</span>
                                </div>

                                <!-- Liste des Scanners du groupe -->
                                <?php if (!empty($groupScanners)): ?>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-[#AAAAAA]">Scanners rattachés :</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            <?php foreach ($groupScanners as $sc): ?>
                                                <span class="px-2.5 py-1 bg-[#121212] text-xs text-[#E0E0E0] rounded-lg border border-white/10">
                                                    📌 <?php echo htmlspecialchars($sc['nom']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Actions du Groupe (Flex-wrap & Gap) -->
                            <div class="pt-4 border-t border-white/10 flex flex-wrap gap-3 items-center">
                                <button onclick="editGroupModal('<?php echo htmlspecialchars($grp['id']); ?>', '<?php echo htmlspecialchars(addslashes($grp['nom'])); ?>', '<?php echo htmlspecialchars(addslashes($grp['description'] ?? '')); ?>')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs rounded-lg transition-all border-none cursor-pointer flex items-center space-x-1">
                                    <span>✏️</span>
                                    <span>Renommer</span>
                                </button>

                                <?php if (!$isDefault): ?>
                                    <button onclick="deleteGroupPrompt('<?php echo htmlspecialchars($grp['id']); ?>', '<?php echo htmlspecialchars(addslashes($grp['nom'])); ?>')" class="px-4 py-2 bg-rose-900/80 hover:bg-rose-800 text-rose-200 font-semibold text-xs rounded-lg transition-all border-none cursor-pointer flex items-center space-x-1">
                                        <span>🗑️</span>
                                        <span>Supprimer</span>
                                    </button>
                                <?php else: ?>
                                    <span class="text-[11px] text-amber-400/80 italic font-medium px-2 py-1 bg-amber-950/30 rounded border border-amber-900/40">
                                        🔒 Non supprimable
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php elseif ($activeTab === 'texts'): ?>
            <?php $dbTexts = $db->query("SELECT * FROM cl_texts WHERE category = 'mots_bannis' ORDER BY text_key ASC")->fetchAll(); ?>
            <div class="p-12 space-y-8">
                <section class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Réglages de langage</p>
                            <h2 class="mt-2 text-3xl font-black text-slate-900 tracking-tight">Dictionnaire IA</h2>
                            <p class="mt-2 text-sm text-slate-600">Gérez ici les termes, formulations et contenus réutilisés par le moteur IA. Ces réglages sont partagés entre les audits et les écrans de recommandation.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                                <?php echo count($dbTexts); ?> entrée(s) active(s)
                            </span>
                            <button onclick="addDbText()" class="px-6 py-3 bg-slate-900 text-white text-xs font-black rounded-2xl shadow-xl hover:bg-emerald-900 transition-all flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                <span>Nouvelle entrée</span>
                            </button>
                        </div>
                    </div>
                </section>

                <div class="grid gap-4 lg:grid-cols-3">
                    <article class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Repérage</p>
                        <p class="mt-3 text-sm leading-6 text-slate-700">Ajoutez les termes sensibles, variantes lexicales et formulations à surveiller pendant les scans.</p>
                    </article>
                    <article class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Qualité</p>
                        <p class="mt-3 text-sm leading-6 text-slate-700">Gardez des intitulés courts et un contenu explicite pour que l’équipe comprenne chaque réglage au premier coup d’œil.</p>
                    </article>
                    <article class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Harmonisation</p>
                        <p class="mt-3 text-sm leading-6 text-slate-700">Ces formulations complètent les prompts, les références juridiques et le catalogue métier dans la console.</p>
                    </article>
                </div>

                <div class="grid grid-cols-1 gap-6" id="db-texts-container">
                    <?php foreach ($dbTexts as $t): ?>
                        <div class="db-text-row rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
                            <div class="flex flex-col gap-6 xl:flex-row xl:items-start">
                                <div class="w-full xl:w-72 space-y-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Clé technique</label>
                                        <input type="text" value="<?php echo htmlspecialchars($t['text_key']); ?>" class="t-key w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-xs text-emerald-700" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Libellé</label>
                                        <input type="text" value="<?php echo htmlspecialchars($t['title']); ?>" class="t-title w-full rounded-xl border border-slate-200 bg-white px-4 py-3 font-bold text-slate-900 text-sm">
                                    </div>
                                </div>
                                <div class="flex-grow">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Contenu</label>
                                    <textarea class="t-content h-32 w-full rounded-2xl border border-slate-200 bg-slate-50 px-6 py-4 text-xs font-mono leading-relaxed text-slate-700 focus:ring-4 focus:ring-emerald-500/10"><?php echo htmlspecialchars($t['content']); ?></textarea>
                                </div>
                                <div class="flex gap-2 xl:flex-col">
                                    <button onclick="saveDbText('<?php echo $t['text_key']; ?>', this)" class="px-6 py-3 bg-emerald-100 text-emerald-800 text-[10px] font-black rounded-xl hover:bg-emerald-700 hover:text-white transition-all uppercase tracking-widest">Enregistrer</button>
                                    <button onclick="deleteDbText('<?php echo $t['text_key']; ?>')" class="px-6 py-3 bg-red-50 text-red-700 text-[10px] font-black rounded-xl hover:bg-red-700 hover:text-white transition-all uppercase tracking-widest">Supprimer</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<script>
<?php
$clientState = $state;
$stripPrivateValues = function (&$value) use (&$stripPrivateValues) {
    if (!is_array($value)) return;
    foreach (array_keys($value) as $key) {
        if (preg_match('/(api.?key|secret|password|token|credential)/i', (string)$key)) {
            unset($value[$key]);
        } else {
            $stripPrivateValues($value[$key]);
        }
    }
};
$stripPrivateValues($clientState);
?>
const appState = <?php echo json_encode($clientState, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

// --- Unification de la Synchronisation ---
async function syncState(type, data, isBatch = false) {
    const btns = document.querySelectorAll('.save-btn');
    btns.forEach(b => { if (!b.dataset.label) b.dataset.label = b.innerHTML; b.disabled = true; b.innerHTML = '⏳ Synchronisation...'; });

    const payload = isBatch ? { batch: data } : { type, data };

    try {
        const response = await fetch('/api/admin/save_state.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });
        const res = await response.json().catch(() => ({}));
        if (!response.ok || !res.success) throw new Error(res.error || 'Le serveur a refusé la sauvegarde (HTTP ' + response.status + ').');
        {
            btns.forEach(b => { 
                b.innerHTML = '✅ Modification réussie'; 
                b.classList.replace('bg-emerald-700', 'bg-indigo-600');
            });
            setTimeout(() => {
                btns.forEach(b => { 
                    b.innerHTML = b.dataset.label || 'Sauvegarder'; 
                    b.classList.replace('bg-indigo-600', 'bg-emerald-700');
                    b.disabled = false;
                });
            }, 2500);
            return true;
        }
    } catch (e) {
        alert(e.message || 'Échec de la sauvegarde. Vérifiez la connexion puis réessayez.');
        btns.forEach(b => { b.disabled = false; b.innerHTML = b.dataset.label || 'Sauvegarder'; });
    }
    return false;
}

// --- Dispatcher de Sauvegarde par Onglet ---
async function saveTab(tab) {
    if (tab === 'agency') {
        return saveAgency();
    }
    if (tab === 'missions') return saveMissions();
    if (tab === 'legal') return saveLegal();
    if (tab === 'scanner') return saveScanner();
    return false;
}

function saveAgency() {
    appState.agency.agencyName = document.getElementById('ag_name').value;
    appState.agency.slogan = document.getElementById('ag_slogan').value;
    appState.agency.agencyWebsite = document.getElementById('ag_web').value;
    appState.agency.consultantName = document.getElementById('ag_consultant').value;
    appState.agency.agencyEmail = document.getElementById('ag_email').value;
    appState.agency.agencyPhone = document.getElementById('ag_phone').value;
    return syncState('agency', appState.agency);
}

// Keep the compact service summaries in sync while editing their expanded fields.
document.querySelectorAll('#missions-container > .mission-card').forEach(card => {
    const title = card.querySelector('.m-title');
    const description = card.querySelector('.m-desc');
    const active = card.querySelector('.m-active');
    const summaryTitle = card.querySelector('.m-summary-title');
    const summaryDescription = card.querySelector('.m-summary-desc');
    const summaryStatus = card.querySelector('.m-summary-status');
    title?.addEventListener('input', () => { if (summaryTitle) summaryTitle.textContent = title.value.trim() || 'Métier sans titre'; });
    description?.addEventListener('input', () => { if (summaryDescription) summaryDescription.textContent = description.value.trim() || 'Cliquez pour compléter la description, le budget et les aides.'; });
    active?.addEventListener('change', () => {
        if (!summaryStatus) return;
        summaryStatus.textContent = active.checked ? 'Actif' : 'Inactif';
        summaryStatus.classList.toggle('bg-emerald-50', active.checked);
        summaryStatus.classList.toggle('text-emerald-700', active.checked);
        summaryStatus.classList.toggle('bg-slate-100', !active.checked);
        summaryStatus.classList.toggle('text-slate-600', !active.checked);
    });
});

function saveMissions() {
    const cards = Array.from(document.querySelectorAll('#missions-container > .mission-card'));
    const items = [];
    for (const card of cards) {
        const title = card.querySelector('.m-title')?.value.trim() || '';
        if (!title) {
            card.open = true;
            card.querySelector('.m-title')?.focus();
            alert('Chaque métier doit avoir un titre avant la sauvegarde.');
            return false;
        }
        items.push({
            id: card.dataset.id || 'mission_' + Date.now() + '_' + items.length,
            serviceKey: card.dataset.serviceKey || 'service_' + (card.dataset.id || Date.now() + '_' + items.length),
            isActive: Boolean(card.querySelector('.m-active')?.checked),
            serviceTitle: title,
            description: card.querySelector('.m-desc')?.value.trim() || '',
            defaultBudget: card.querySelector('.m-budget')?.value.trim() || '',
            subsidyAmount: card.querySelector('.m-sub')?.value.trim() || ''
        });
    }
    appState.serviceOpps = items;
    return syncState('serviceOpps', items);
}

function saveLegal() {
    const legalItems = [];
    document.querySelectorAll('#legal-container > .legal-row').forEach((c, i) => {
        legalItems.push({
            id: c.dataset.id || 'leg_' + Date.now() + i,
            title: c.querySelector('.l-title').value,
            description: c.querySelector('.l-desc').value,
            weight: parseInt(c.querySelector('.l-weight').value),
            isActive: c.querySelector('.l-active').checked,
            jurisdiction: 'france',
            reference: 'Ref-' + i
        });
    });
    appState.legalTexts = legalItems;
    syncState('legalTexts', appState.legalTexts);
}

function saveScanner() {
    if (!appState.scanVars || typeof appState.scanVars !== 'object') appState.scanVars = {};
    appState.scanVars.aiKeywordInstructions = document.getElementById('ia_prompt').value;
    appState.scanVars.aiAnalysisInstructions = document.getElementById('ia_scope').value;
    appState.scanVars.aiEvidenceInstructions = document.getElementById('ia_evidence').value;
    appState.scanVars.aiRiskInstructions = document.getElementById('ia_risk').value;
    appState.scanVars.aiCommercialInstructions = document.getElementById('ia_commercial').value;
    appState.scanVars.aiLegalFramework = document.getElementById('ia_compliance').value;
    appState.scanVars.aiCorrectiveActions = document.getElementById('ia_corrections').value;
    appState.scanVars.aiUserPrompt = document.getElementById('ia_user_prompt').value;
    appState.scanVars.aiClaimsDictionary = document.getElementById('ia_dictionary').value;
    appState.scanVars.customBannedKeywords = document.getElementById('ia_banned').value;
    syncState('scanVars', appState.scanVars);
}

async function setCollaboratorPassword(id) {
    const member = appState.collaborators?.find(item => String(item.id) === String(id));
    if (!member?.email) return alert('Enregistrez d’abord une adresse e-mail pour ce collaborateur.');
    const password = prompt('Nouveau mot de passe du collaborateur (12 caractères minimum). Il ne sera pas affiché ensuite :');
    if (password === null) return;
    if (password.length < 12) {
        alert('Le mot de passe doit contenir au moins 12 caractères.');
        return;
    }
    try {
        const response = await fetch('/api/admin/set_collaborator_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ id, password })
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.error || 'Impossible de modifier l’accès.');
        alert('Accès créé/réinitialisé. Communique le mot de passe au collaborateur par un canal sûr.');
    } catch (error) {
        alert(error.message || 'Erreur réseau.');
    }
}

function openCollaboratorModal() {
    const modal = document.getElementById('collaborator-modal');
    const form = document.getElementById('collaborator-form');
    const error = document.getElementById('collaborator-form-error');
    if (form) form.reset();
    if (error) {
        error.textContent = '';
        error.classList.add('hidden');
    }
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
    document.getElementById('new-collaborator-name')?.focus();
}

function closeCollaboratorModal() {
    const modal = document.getElementById('collaborator-modal');
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
}

async function submitCollaboratorForm(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const error = document.getElementById('collaborator-form-error');
    const name = document.getElementById('new-collaborator-name').value.trim();
    const email = document.getElementById('new-collaborator-email').value.trim();
    const role = document.getElementById('new-collaborator-role').value.trim();
    const password = document.getElementById('new-collaborator-password').value;
    const normalizedEmail = email.toLocaleLowerCase();
    const showError = message => {
        error.textContent = message;
        error.classList.remove('hidden');
    };
    if (!name || !email || !role || Array.from(password).length < 12) {
        showError('Renseignez tous les champs et choisissez un mot de passe d’au moins 12 caractères.');
        return;
    }
    if ((appState.collaborators || []).some(member => String(member.email || '').trim().toLocaleLowerCase() === normalizedEmail)) {
        showError('Un compte utilise déjà cette adresse e-mail.');
        return;
    }
    const id = 'collab_' + (window.crypto?.randomUUID ? crypto.randomUUID() : Date.now() + '_' + Math.random().toString(36).slice(2));
    const member = { id, name, email, role, avatarColor: 'bg-emerald-700', assignedProspectsCount: 0 };
    if (!Array.isArray(appState.collaborators)) appState.collaborators = [];
    appState.collaborators.unshift(member);
    const submit = form.querySelector('button[type="submit"]');
    if (submit) {
        submit.disabled = true;
        submit.textContent = 'Création…';
    }
    try {
        if (!await syncState('collaborators', appState.collaborators)) {
            appState.collaborators = appState.collaborators.filter(item => item.id !== id);
            return;
        }
        const response = await fetch('/api/admin/set_collaborator_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ id, password })
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.success) {
            appState.collaborators = appState.collaborators.filter(item => item.id !== id);
            await syncState('collaborators', appState.collaborators);
            throw new Error(result.error || 'Impossible de créer le mot de passe du compte.');
        }
        closeCollaboratorModal();
        window.location.reload();
    } catch (exception) {
        showError(exception.message || 'Impossible de créer ce compte.');
    } finally {
        if (submit) {
            submit.disabled = false;
            submit.textContent = 'Créer le compte';
        }
    }
}

async function saveCollaboratorCard(button) {
    const card = button.closest('.team-card');
    const id = card?.dataset.id || '';
    const member = (appState.collaborators || []).find(item => String(item.id) === id);
    const name = card?.querySelector('.t-name')?.value.trim() || '';
    const email = card?.querySelector('.t-email')?.value.trim() || '';
    const role = card?.querySelector('.t-role')?.value.trim() || '';
    const status = document.getElementById('collaborator-status');
    if (!member) return;
    if (!name || !email || !role || !card.querySelector('.t-email').checkValidity()) {
        status.textContent = 'Nom, fonction et adresse e-mail valide sont obligatoires.';
        status.className = 'min-h-5 text-sm text-rose-700';
        return;
    }
    if ((appState.collaborators || []).some(item => String(item.id) !== id && String(item.email || '').trim().toLocaleLowerCase() === email.toLocaleLowerCase())) {
        status.textContent = 'Un autre compte utilise déjà cette adresse e-mail.';
        status.className = 'min-h-5 text-sm text-rose-700';
        return;
    }
    const previous = { name: member.name, email: member.email, role: member.role };
    Object.assign(member, { name, email, role });
    const saved = await syncState('collaborators', appState.collaborators);
    if (saved) {
        status.textContent = 'Compte utilisateur enregistré.';
        status.className = 'min-h-5 text-sm text-emerald-700';
        const avatar = card.querySelector('.h-12.w-12');
        if (avatar) avatar.textContent = name.slice(0, 1);
    } else {
        Object.assign(member, previous);
    }
}

async function deleteCollaboratorCard(button) {
    const card = button.closest('.team-card');
    const id = card?.dataset.id || '';
    if (!id || !confirm('Supprimer définitivement ce compte et révoquer son accès ?')) return;
    const original = appState.collaborators || [];
    appState.collaborators = original.filter(item => String(item.id) !== id);
    if (await syncState('collaborators', appState.collaborators)) {
        window.location.reload();
    } else {
        appState.collaborators = original;
    }
}

function generatePass(btn) {
    const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    let pass = "";
    for(let i=0; i<8; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    const input = btn.previousElementSibling;
    input.value = pass;
}

// --- Gestion des listes (Add/Delete) ---
async function addItem(type) {
    const id = (type === 'missions' ? 'mission_' : type === 'legal' ? 'leg_' : 'collab_') + Date.now();
    if (type === 'missions') {
        if (!Array.isArray(appState.serviceOpps)) appState.serviceOpps = [];
        appState.serviceOpps.unshift({ id, serviceKey: 'service_' + id, serviceTitle: 'Nouveau métier', description: '', defaultBudget: '', subsidyAmount: '', isActive: true });
    } else if (type === 'legal') {
        appState.legalTexts.unshift({ id, title: 'Nouveau Référentiel', description: '', weight: 5, isActive: true });
    }
    const key = type === 'missions' ? 'serviceOpps' : 'legalTexts';
    const saved = await syncState(key, appState[key]);
    if (saved) window.location.reload();
}

function deleteItem(type, idx) {
    if(!confirm('Confirmer la suppression définitive ?')) return;
    if (type === 'missions') appState.serviceOpps.splice(idx, 1);
    else if (type === 'legal') appState.legalTexts.splice(idx, 1);
    syncState(type === 'missions' ? 'serviceOpps' : (type === 'legal' ? 'legalTexts' : 'collaborators'), 
              type === 'missions' ? appState.serviceOpps : (type === 'legal' ? appState.legalTexts : appState.collaborators))
    .then((saved) => { if (saved) window.location.reload(); });
}

// --- CSV Handlers (Unified) ---
function exportTabCSV(tab) {
    let rows = [];
    let filename = 'climat_local_' + tab + '.csv';
    const csvCell = value => '"' + String(value ?? '').replace(/"/g, '""') + '"';
    if (tab === 'missions') {
        rows = [['ID', 'Clé métier (serviceKey)', 'Titre', 'Budget', 'Subvention', 'Description', 'Actif'],
            ...(Array.isArray(appState.serviceOpps) ? appState.serviceOpps : []).map(m => [
                m.id, m.serviceKey, m.serviceTitle, m.defaultBudget, m.subsidyAmount, m.description, m.isActive ? 1 : 0
            ])];
    } else if (tab === 'legal') {
        rows = [['ID', 'Titre', 'Poids', 'Description', 'Actif'],
            ...(Array.isArray(appState.legalTexts) ? appState.legalTexts : []).map(item => [
                item.id, item.title, item.weight, item.description, item.isActive ? 1 : 0
            ])];
    } else if (tab === 'texts') {
        window.location.href = '/api/admin/export.php?download=texts';
        return;
    } else if (tab === 'scanner') {
        const promptKeys = [
            'aiKeywordInstructions', 'aiAnalysisInstructions', 'aiEvidenceInstructions', 'aiRiskInstructions',
            'aiCommercialInstructions', 'aiLegalFramework', 'aiCorrectiveActions', 'aiUserPrompt',
            'aiClaimsDictionary', 'customBannedKeywords'
        ];
        rows = [['Clé', 'Consigne'],
            ...promptKeys.map(key => [key, appState.scanVars?.[key] ?? ''])];
    } else if (tab === 'audits') {
        window.location.href = '/api/admin/export.php';
        return;
    } else {
        alert('L’export CSV n’est pas disponible pour cette page.');
        return;
    }

    const csv = '\uFEFF' + rows.map(row => row.map(csvCell).join(';')).join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1500);
}

// --- Audits & DB Management ---
async function deleteAudit(id) {
    if (!confirm('Supprimer définitivement cet audit et ses données ?')) return;
    try {
        const fd = new FormData();
        fd.append('delete_audit', id);
        const res = await fetch(window.location.href, { method: 'POST', body: fd });
        if (res.ok) {
            document.getElementById('audit-row-' + id).remove();
        }
    } catch (e) {
        alert('Erreur lors de la suppression.');
    }
}

async function saveDbText(key, btn) {
    const row = btn.closest('.db-text-row');
    const title = row.querySelector('.t-title').value;
    const content = row.querySelector('.t-content').value;
    
    btn.disabled = true;
    btn.innerText = '⏳...';
    
    try {
        const fd = new FormData();
        fd.append('save_db_text', '1');
        fd.append('text_key', key);
        fd.append('title', title);
        fd.append('content', content);
        fd.append('category', 'mots_bannis');
        
        const response = await fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.success) throw new Error(result.error || 'La sauvegarde du dictionnaire a échoué.');
        btn.innerText = '✨ Enregistré';
        setTimeout(() => { btn.disabled = false; btn.innerText = 'Enregistrer'; }, 1500);
    } catch (e) {
        alert('Erreur save DB');
        btn.disabled = false;
    }
}

async function deleteDbText(key) {
    if (!confirm('Supprimer ce dictionnaire ?')) return;
    const fd = new FormData();
    fd.append('delete_db_text', key);
    await fetch(window.location.href, { method: 'POST', body: fd });
    window.location.reload();
}

async function addDbText() {
    const title = prompt('Nom du dictionnaire :');
    if (!title || !title.trim()) return;
    const content = prompt('Mots ou expressions à repérer (séparés par des virgules ou retours à la ligne) :');
    if (!content || !content.trim()) return;
    const slug = title.normalize('NFD').replace(/[\\u0300-\\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    const fd = new FormData();
    fd.append('save_db_text', '1');
    fd.append('text_key', 'banned_words_' + (slug || Date.now()));
    fd.append('title', title.trim());
    fd.append('content', content.trim());
    fd.append('category', 'mots_bannis');
    try {
        const response = await fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.success) throw new Error(result.error || 'La création du dictionnaire a échoué.');
        window.location.reload();
    } catch (error) {
        alert(error.message || 'Erreur réseau.');
    }
}

function importTabCSV(tab, input) {
    const file = input.files[0];
    if(!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const text = String(e.target.result || '').replace(/^\uFEFF/, '');
        const lines = [];
        let row = [];
        let cell = '';
        let quoted = false;
        for (let i = 0; i < text.length; i++) {
            const char = text[i];
            if (quoted && char === '"' && text[i + 1] === '"') {
                cell += '"';
                i++;
            } else if (char === '"') {
                quoted = !quoted;
            } else if (!quoted && char === ';') {
                row.push(cell);
                cell = '';
            } else if (!quoted && (char === '\n' || char === '\r')) {
                if (char === '\r' && text[i + 1] === '\n') i++;
                row.push(cell);
                if (row.some(value => value !== '')) lines.push(row);
                row = [];
                cell = '';
            } else {
                cell += char;
            }
        }
        if (quoted) {
            alert('Le fichier CSV contient un champ entre guillemets non terminé.');
            input.value = '';
            return;
        }
        row.push(cell);
        if (row.some(value => value !== '')) lines.push(row);
        const expectedHeaders = {
            missions: ['ID', 'Clé métier (serviceKey)', 'Titre', 'Budget', 'Subvention', 'Description', 'Actif'],
            legal: ['ID', 'Titre', 'Poids', 'Description', 'Actif'],
            texts: ['text_key', 'title', 'content'],
            scanner: ['Clé', 'Consigne']
        }[tab];
        if (!expectedHeaders || JSON.stringify(lines[0]) !== JSON.stringify(expectedHeaders)) {
            alert('Les en-têtes du CSV ne correspondent pas au format attendu pour cet onglet.');
            input.value = '';
            return;
        }
        if (lines.slice(1).some(parsedRow => parsedRow.length !== expectedHeaders.length)) {
            alert('Une ou plusieurs lignes du CSV ne contiennent pas le nombre de colonnes attendu.');
            input.value = '';
            return;
        }
        const imports = [];
        for(let i=1; i<lines.length; i++) {
            const p = lines[i];
            if (tab === 'scanner') {
                imports.push({ key: p[0].trim(), value: p[1] });
                continue;
            }
            if (tab === 'missions') {
                imports.push({ id: p[0], serviceKey: p[1], serviceTitle: p[2], defaultBudget: p[3], subsidyAmount: p[4], description: p[5], isActive: p[6] === '1' });
            } else if (tab === 'legal') {
                imports.push({ id: p[0], title: p[1], weight: parseInt(p[2]), description: p[3], isActive: p[4] === '1' });
            } else if (tab === 'texts') {
                imports.push({ text_key: p[0], title: p[1], content: p[2] });
            }
        }
        if (tab === 'scanner') {
            const allowedPromptKeys = new Set([
                'aiKeywordInstructions', 'aiAnalysisInstructions', 'aiEvidenceInstructions', 'aiRiskInstructions',
                'aiCommercialInstructions', 'aiLegalFramework', 'aiCorrectiveActions', 'aiUserPrompt',
                'aiClaimsDictionary', 'customBannedKeywords'
            ]);
            const promptUpdates = Object.fromEntries(imports
                .filter(item => allowedPromptKeys.has(item.key))
                .map(item => [item.key, item.value]));
            if (Object.keys(promptUpdates).length === 0) {
                alert('Aucun prompt reconnu. Utilisez les colonnes « Clé » et « Consigne » exportées par le Scanner.');
                input.value = '';
                return;
            }
            appState.scanVars = { ...(appState.scanVars || {}), ...promptUpdates };
            syncState('scanVars', appState.scanVars).then(saved => {
                if (saved) window.location.reload();
            });
            return;
        }
        if(imports.length > 0) {
            if (tab === 'missions') { appState.serviceOpps = imports; syncState('serviceOpps', appState.serviceOpps); }
            else if (tab === 'legal') { appState.legalTexts = imports; syncState('legalTexts', appState.legalTexts); }
            else if (tab === 'texts') { 
                // Special case for DB texts: we need to send each one via POST to save_db_text handler
                alert('Import des textes en cours (traitement DB)...');
                imports.forEach(async t => {
                    const fd = new FormData();
                    fd.append('save_db_text', '1');
                    fd.append('text_key', t.text_key);
                    fd.append('title', t.title);
                    fd.append('content', t.content);
                    await fetch(window.location.href, { method: 'POST', body: fd });
                });
                setTimeout(() => window.location.reload(), 2000);
                return;
            }
            
            setTimeout(() => window.location.reload(), 1000);
        }
    };
    reader.readAsText(file);
}

// Prospection
function generatePitch(auditId) {
    alert("Génération du Pitch Commercial (Module IA) pour l'Audit #" + auditId + "...\n\nFonctionnalité en cours de liaison avec l'API Gemini pour extraire les arguments clés.");
}

// Scanner Groups Actions
function showCreateGroupModal() {
    const card = document.getElementById('createGroupCard');
    if (card) card.classList.remove('hidden');
}

function hideCreateGroupModal() {
    const card = document.getElementById('createGroupCard');
    if (card) card.classList.add('hidden');
}

async function submitCreateGroup() {
    const nom = document.getElementById('new_group_nom')?.value;
    const description = document.getElementById('new_group_desc')?.value;
    if (!nom || !nom.trim()) {
        alert('Veuillez saisir un nom pour le groupe.');
        return;
    }
    try {
        const res = await fetch('/api/groups/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nom, description })
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de créer le groupe'));
        }
    } catch (e) {
        alert('Erreur de communication avec le serveur.');
    }
}

async function editGroupModal(id, currentNom, currentDesc) {
    const newNom = prompt('Nouveau nom pour le groupe :', currentNom);
    if (newNom === null || !newNom.trim()) return;
    const newDesc = prompt('Nouvelle description :', currentDesc);
    try {
        const res = await fetch('/api/groups/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nom: newNom.trim(), description: (newDesc || '').trim() })
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de mettre à jour le groupe'));
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

async function deleteGroupPrompt(id, name) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer le groupe "${name}" ?\nLes scanners de ce groupe seront automatiquement réassignés au groupe par défaut "scanner widget".`)) {
        return;
    }
    try {
        const res = await fetch('/api/groups/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de supprimer ce groupe.'));
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

async function submitAssignScanner() {
    const scanner_id = document.getElementById('assign_scanner_select')?.value;
    const group_id = document.getElementById('assign_group_select')?.value;
    if (!scanner_id || !group_id) return;
    try {
        const res = await fetch('/api/groups/assign_scanner', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ scanner_id, group_id })
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible d\'assigner le scanner.'));
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

// Scans Centralisés Filtering & Actions
const MAX_BULK_RESCANS = 10;
const bulkRescanButton = document.getElementById('bulk-rescan-scans');
if (bulkRescanButton) bulkRescanButton.textContent = `Relancer la sélection (${MAX_BULK_RESCANS} max)`;
function filterScansGrid() {
    const typeVal = document.getElementById('filter_type_scan')?.value || 'all';
    const searchVal = (document.getElementById('filter_url_scan')?.value || '').toLowerCase().trim();

    document.querySelectorAll('.scan-card').forEach(card => {
        const cType = card.getAttribute('data-type') || '';
        const cUrl = card.getAttribute('data-url') || '';
        const matchType = typeVal === 'all' || cType === typeVal;
        const matchUrl = !searchVal || cUrl.includes(searchVal);
        card.style.display = matchType && matchUrl ? 'flex' : 'none';
        if (!matchType || !matchUrl) {
            const checkbox = card.querySelector('.scan-select');
            if (checkbox) checkbox.checked = false;
        }
    });
    updateBulkScanControls();
}

function getSelectedScanCards() {
    return Array.from(document.querySelectorAll('.scan-card')).filter(card => {
        const checkbox = card.querySelector('.scan-select');
        return checkbox?.checked && card.style.display !== 'none';
    });
}

function updateBulkScanControls() {
    const count = getSelectedScanCards().length;
    const rescanButton = document.getElementById('bulk-rescan-scans');
    const deleteButton = document.getElementById('bulk-delete-scans');
    if (rescanButton) rescanButton.disabled = count === 0;
    if (deleteButton) deleteButton.disabled = count === 0;
    const selectAll = document.getElementById('select-all-scans');
    const visibleChecks = Array.from(document.querySelectorAll('.scan-card'))
        .filter(card => card.style.display !== 'none')
        .map(card => card.querySelector('.scan-select'))
        .filter(Boolean);
    if (selectAll) {
        selectAll.checked = visibleChecks.length > 0 && visibleChecks.every(checkbox => checkbox.checked);
        selectAll.indeterminate = visibleChecks.some(checkbox => checkbox.checked) && !selectAll.checked;
    }
}

async function rescanExistingCard(card) {
    const status = card.querySelector('.scan-action-status');
    const button = card.querySelector('.rescan-existing-scan');
    if (!card.dataset.scanUrl) {
        if (status) status.textContent = 'URL de scan manquante.';
        return false;
    }
    if (button) {
        button.disabled = true;
        button.textContent = 'Scan en cours…';
    }
    if (status) status.textContent = 'Nouvelle analyse en cours. Le scan d’origine est conservé.';
    try {
        const response = await fetch('/api/audit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                url: card.dataset.scanUrl,
                type_audit: card.dataset.scanTypeAudit || 'full',
                utilisateur: 'administrateur'
            })
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || result.status !== 'ok' || !result.scan_id) {
            throw new Error(result.error || result.message || 'Le scan n’a pas abouti.');
        }
        if (status) status.textContent = 'Nouveau scan enregistré. Le scan précédent est conservé.';
        return true;
    } catch (error) {
        if (status) status.textContent = error.message || 'Erreur réseau pendant le scan.';
        return false;
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = 'Relancer';
        }
    }
}

document.querySelectorAll('.scan-select').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkScanControls);
});
document.querySelectorAll('.rescan-existing-scan').forEach(button => {
    button.addEventListener('click', async () => {
        const card = button.closest('.scan-card');
        if (card && await rescanExistingCard(card)) window.setTimeout(() => window.location.reload(), 900);
    });
});
document.querySelectorAll('.delete-existing-scan').forEach(button => {
    button.addEventListener('click', () => deleteScanCard(button.closest('.scan-card')?.dataset.scanId || ''));
});
document.getElementById('select-all-scans')?.addEventListener('change', event => {
    document.querySelectorAll('.scan-card').forEach(card => {
        if (card.style.display !== 'none') {
            const checkbox = card.querySelector('.scan-select');
            if (checkbox) checkbox.checked = event.target.checked;
        }
    });
    updateBulkScanControls();
});
document.getElementById('bulk-delete-scans')?.addEventListener('click', async () => {
    const ids = getSelectedScanCards().map(card => card.dataset.scanId).filter(Boolean);
    if (!ids.length || !confirm(`Supprimer définitivement les ${ids.length} scans sélectionnés ?`)) return;
    const status = document.getElementById('bulk-scan-status');
    const button = document.getElementById('bulk-delete-scans');
    button.disabled = true;
    if (status) status.textContent = 'Suppression en cours…';
    try {
        const response = await fetch('/api/scans/bulk_delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ ids })
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.success) throw new Error(result.error || 'Suppression impossible.');
        if (status) status.textContent = `${result.deleted} scan(s) supprimé(s).`;
        window.location.reload();
    } catch (error) {
        if (status) status.textContent = error.message || 'Erreur réseau.';
        updateBulkScanControls();
    }
});
document.getElementById('bulk-rescan-scans')?.addEventListener('click', async () => {
    const cards = getSelectedScanCards();
    if (!cards.length) return;
    const status = document.getElementById('bulk-scan-status');
    if (cards.length > MAX_BULK_RESCANS) {
        if (status) status.textContent = `Pour éviter une longue série d’appels IA, relancez au maximum ${MAX_BULK_RESCANS} scans par lot.`;
        return;
    }
    if (!confirm(`Relancer ${cards.length} scan(s) ? Les scans d’origine resteront conservés.`)) return;
    const button = document.getElementById('bulk-rescan-scans');
    button.disabled = true;
    let completed = 0;
    try {
        for (let index = 0; index < cards.length; index++) {
            if (status) status.textContent = `Relance ${index + 1}/${cards.length}…`;
            if (await rescanExistingCard(cards[index])) completed++;
        }
        if (status) status.textContent = `${completed}/${cards.length} nouveau(x) scan(s) enregistré(s). Les originaux sont conservés.`;
    } catch (error) {
        if (status) status.textContent = error.message || 'Une erreur a interrompu la relance des scans.';
    } finally {
        updateBulkScanControls();
        if (button && getSelectedScanCards().length > 0) button.disabled = false;
    }
    if (completed > 0) window.setTimeout(() => window.location.reload(), 1400);
});

async function addScanToListPrompt(scanId) {
    const listes = <?php echo json_encode(load_all_listes(), JSON_UNESCAPED_UNICODE); ?>;
    if (!listes || listes.length === 0) {
        if (confirm('Aucune liste existante. Voulez-vous en créer une maintenant ?')) {
            createListPrompt();
        }
        return;
    }

    let listPromptText = "Saisissez le nom ou l'ID de la liste où ajouter ce scan :\n\n";
    listes.forEach(l => {
        listPromptText += `- ID: "${l.id}" => ${l.nom}\n`;
    });

    const chosenId = prompt(listPromptText, listes[0].id);
    if (!chosenId) return;

    try {
        const res = await fetch('/api/listes/add_scan', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ list_id: chosenId.trim(), scan_id: scanId })
        });
        const data = await res.json();
        if (data.success) {
            alert('Scan ajouté à la liste !');
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible d\'ajouter à la liste'));
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

async function createDossierPrompt() {
    const nom = prompt('Nom du nouveau dossier :');
    if (!nom || !nom.trim()) return;
    const domaine = prompt('Domaine associé (ex: entreprise.com) :');
    if (!domaine || !domaine.trim()) return;

    try {
        const res = await fetch('/api/dossiers/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nom: nom.trim(), domaine: domaine.trim() })
        });
        const data = await res.json();
        if (data.success) {
            alert('Dossier créé avec succès !');
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de créer le dossier'));
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

// Import en lot : crée une liste de sites sans lancer automatiquement les analyses.
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('bulkSitesForm');
    if (!form) return;
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const status = document.getElementById('bulkImportStatus');
        const csv = document.getElementById('bulkCsv');
        const urls = document.getElementById('bulkUrls').value.trim();
        if ((!csv.files || !csv.files.length) && !urls) {
            status.textContent = 'Ajoutez un CSV ou collez au moins une URL.';
            return;
        }
        const data = new FormData(form);
        status.textContent = 'Import en cours…';
        try {
            const response = await fetch('/api/listes/import_export.php', { method: 'POST', body: data, credentials: 'same-origin' });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.error || 'Import impossible.');
            status.textContent = result.imported + ' site(s) ajoutés à « ' + result.list.nom + ' ». Aucun scan lancé.';
            window.setTimeout(() => window.location.reload(), 1200);
        } catch (error) {
            status.textContent = error.message || 'Erreur réseau pendant l’import.';
        }
    });
});

async function createListPrompt() {
    const nom = prompt('Nom de la nouvelle liste :');
    if (!nom || !nom.trim()) return;
    const description = prompt('Description optionnelle :');

    try {
        const res = await fetch('/api/listes/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nom: nom.trim(), description: (description || '').trim() })
        });
        const data = await res.json();
        if (data.success) {
            alert('Liste créée avec succès !');
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de créer la liste'));
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

async function reassignScanGroup(scanId) {
    const newGroup = prompt('Saisir le nouvel ID de groupe pour ce scan (ex: grp_widget_default) :');
    if (!newGroup || !newGroup.trim()) return;
    try {
        const res = await fetch('/api/scans/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: scanId, group_id: newGroup.trim() })
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de réassigner le scan.'));
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

async function deleteScanCard(scanId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce scan de la base de données ?')) return;
    try {
        const res = await fetch('/api/scans/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: scanId })
        });
        const data = await res.json();
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de supprimer le scan.'));
        }
    } catch (e) {
        alert('Erreur réseau.');
    }
}

document.getElementById('admin-clear-cache')?.addEventListener('click', async event => {
    const button = event.currentTarget;
    const status = document.getElementById('admin-clear-cache-status');
    button.disabled = true;
    if (status) status.textContent = 'Purge du cache en cours…';
    try {
        const response = await fetch('/api/flush.php', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || result.success !== true || result.opcache_reset !== true) {
            throw new Error(result.error || result.message || 'La purge du cache a échoué.');
        }
        if (status) status.textContent = result.message || 'Cache PHP vidé.';
    } catch (error) {
        if (status) status.textContent = error.message || 'Erreur réseau pendant la purge.';
    } finally {
        button.disabled = false;
    }
});
</script>

<style>
    #admin-clear-cache { background: #176b4c; color: #fff !important; }
    #admin-clear-cache:hover { background: #12583e; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .animate-fadeIn { animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    
    input:focus, textarea:focus, select:focus {
        outline: none !important;
        box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.1);
    }
    
    .mission-card > summary::-webkit-details-marker { display: none; }\n    .mission-card[open] .mission-chevron { transform: rotate(180deg); }\n    .mission-card:hover .m-active, .legal-row:hover .l-active {
        transform: scale(1.1);
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
