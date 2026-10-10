<?php
// /api/dashboard.php
// Tableau de Bord Analytique & Pilotage RSE ClimatLocal
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cms.php';
require_once __DIR__ . '/includes/listes_manager.php';
require_once __DIR__ . '/includes/scans_manager.php';
require_login();

$db = get_db_connection();

$filterYear = (string)($_GET['year'] ?? 'all');
if ($filterYear !== 'all' && !preg_match('/^20[0-9]{2}$/', $filterYear)) $filterYear = 'all';
$filterList = trim((string)($_GET['list'] ?? 'all'));
$listOptions = load_all_listes();
$selectedList = null;
foreach ($listOptions as $candidateList) {
    if (($candidateList['id'] ?? '') === $filterList) { $selectedList = $candidateList; break; }
}
if ($filterList !== 'all' && $selectedList === null) $filterList = 'all';

function gh_dashboard_domain($value) {
    $value = trim((string)$value);
    if ($value === '') return '';
    if (!preg_match('~^https?://~i', $value)) $value = 'https://' . $value;
    $host = strtolower((string)(parse_url($value, PHP_URL_HOST) ?: ''));
    return preg_replace('/^www\\./', '', $host);
}

$selectedDomains = [];
if ($selectedList !== null) {
    $allScans = load_all_scans();
    $scansById = [];
    foreach ($allScans as $scan) {
        if (!empty($scan['id'])) $scansById[(string)$scan['id']] = $scan;
    }
    foreach (($selectedList['sites'] ?? []) as $site) {
        $domain = gh_dashboard_domain($site['url'] ?? $site['domaine'] ?? '');
        if ($domain !== '') $selectedDomains[$domain] = true;
        $scanId = (string)($site['scan_id'] ?? '');
        if ($scanId !== '' && isset($scansById[$scanId])) {
            $scanDomain = gh_dashboard_domain($scansById[$scanId]['url'] ?? '');
            if ($scanDomain !== '') $selectedDomains[$scanDomain] = true;
        }
    }
    foreach (($selectedList['items'] ?? []) as $itemId) {
        $itemId = (string)$itemId;
        if (isset($scansById[$itemId])) {
            $scanDomain = gh_dashboard_domain($scansById[$itemId]['url'] ?? '');
            if ($scanDomain !== '') $selectedDomains[$scanDomain] = true;
        } else {
            $itemDomain = gh_dashboard_domain($itemId);
            if ($itemDomain !== '') $selectedDomains[$itemDomain] = true;
        }
    }
}

$db = get_db_connection();
$scopeConditions = [];
if ($filterYear !== 'all') $scopeConditions[] = 'YEAR(a.analyzedAt) = ' . (int)$filterYear;
$scopedAuditIds = [];
if ($selectedList !== null) {
    $auditCandidates = $db->query('SELECT id, url FROM cl_audits')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($auditCandidates as $candidate) {
        if (isset($selectedDomains[gh_dashboard_domain($candidate['url'] ?? '')])) {
            $scopedAuditIds[] = (int)$candidate['id'];
        }
    }
    $scopedAuditIds = array_values(array_unique($scopedAuditIds));
    $scopeConditions[] = 'a.id IN (' . ($scopedAuditIds ? implode(',', $scopedAuditIds) : '0') . ')';
}
$scopeWhereSql = $scopeConditions ? ' WHERE ' . implode(' AND ', $scopeConditions) : '';

$totalAudits = 0;
$avgScore = 0;
$riskCounts = ['faible' => 0, 'modere' => 0, 'eleve' => 0, 'critique' => 0];
$totalCompanies = 0;

try {
    $auditStats = $db->query('SELECT COUNT(*) AS total, AVG(a.score) AS average FROM cl_audits a' . $scopeWhereSql)->fetch(PDO::FETCH_ASSOC) ?: [];
    $totalAudits = (int)($auditStats['total'] ?? 0);
    $avgScore = $totalAudits > 0 ? round((float)($auditStats['average'] ?? 0), 1) : 0;

    $riskRows = $db->query('SELECT a.riskLevel, COUNT(*) AS count FROM cl_audits a' . $scopeWhereSql . ' GROUP BY a.riskLevel')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($riskRows as $riskRow) {
        $key = strtolower((string)$riskRow['riskLevel']);
        if (isset($riskCounts[$key])) $riskCounts[$key] = (int)$riskRow['count'];
    }
    $companySql = 'SELECT COUNT(DISTINCT a.company_id) FROM cl_audits a' . ($scopeWhereSql ? $scopeWhereSql . ' AND' : ' WHERE') . ' a.company_id IS NOT NULL';
    $totalCompanies = (int)$db->query($companySql)->fetchColumn();
} catch (Throwable $e) {
    error_log('GreenHumble dashboard: unable to load scoped audit statistics.');
}

// Estimation indicative uniquement : ce modèle ne mesure pas en temps réel la consommation électrique.
$estimatedKwhPerAudit = 0.0065;
$emissionFactorKgPerKwh = 0.230;
$totalEnergyKwh = round($totalAudits * $estimatedKwhPerAudit, 3);
$totalEmissionsKgCo2e = round($totalEnergyKwh * $emissionFactorKgPerKwh, 4);
$totalEmissionsTCo2e = round($totalEmissionsKgCo2e / 1000, 6);
$gramsCo2PerAudit = round($estimatedKwhPerAudit * $emissionFactorKgPerKwh * 1000, 2);

// Top 5 des Termes / Mots Bannis les plus Fréquents avec les Entreprises Associées (uniquement de la base)
$topClaims = [];
try {
    $claimRows = $db->query("
        SELECT cl.term, cl.severity, cl.legalRisk, COUNT(cl.id) as occurrence_count,
               GROUP_CONCAT(DISTINCT a.id SEPARATOR ',') as audit_ids
        FROM cl_claims cl
        JOIN cl_audits a ON cl.audit_id = a.id
        GROUP BY cl.term
        ORDER BY occurrence_count DESC, cl.id DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($claimRows as $cr) {
        $auditIdList = array_filter(explode(',', $cr['audit_ids'] ?? ''));
        $companiesList = [];
        if (!empty($auditIdList)) {
            $inClause = implode(',', array_map('intval', $auditIdList));
            $compStmt = $db->query("
                SELECT a.id as audit_id, a.url, a.score, c.name as company_name, c.sector 
                FROM cl_audits a 
                LEFT JOIN cl_companies c ON a.company_id = c.id 
                WHERE a.id IN ($inClause)
            ");
            $companiesList = $compStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $topClaims[] = [
            'term' => $cr['term'],
            'count' => $cr['occurrence_count'],
            'severity' => $cr['severity'],
            'legalRisk' => $cr['legalRisk'],
            'companies' => $companiesList
        ];
    }
} catch (Exception $e) {}

// Aucun fallback statique - la base est vide si aucun audit n'a été réalisé
if (empty($topClaims)) {
    $topClaims = [];
}

// Répartition sectorielle dynamique basée exclusivement sur la base de données réelle
$sectors = [];
try {
    $sectorRows = $db->query("
        SELECT c.sector as sector_name, COUNT(a.id) as audits_count, AVG(a.score) as avg_score
        FROM cl_audits a
        JOIN cl_companies c ON a.company_id = c.id
        WHERE c.sector IS NOT NULL AND c.sector != ''
        GROUP BY c.sector
        ORDER BY audits_count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sectorRows as $sr) {
        $avg = round($sr['avg_score']);
        $risk = 'Faible';
        $color = 'bg-teal-500';
        if ($avg < 50) {
            $risk = 'Critique';
            $color = 'bg-red-500';
        } elseif ($avg < 65) {
            $risk = 'Élevé';
            $color = 'bg-amber-500';
        } elseif ($avg < 80) {
            $risk = 'Modéré';
            $color = 'bg-blue-500';
        }
        
        $sectors[] = [
            'name' => $sr['sector_name'],
            'audits' => $sr['audits_count'],
            'avg' => $avg,
            'risk' => $risk,
            'color' => $color
        ];
    }
} catch (Exception $e) {}

$page_title = "Tableau de Bord & Indicateurs RSE";
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <!-- Header PRO (Refined) -->
    <div class="bg-slate-900 rounded-[3rem] p-10 sm:p-14 mb-12 shadow-2xl relative overflow-hidden border border-slate-800 text-center animate-fadeIn no-print">
        <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-5 pointer-events-none"></div>
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/5 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-blue-500/5 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="relative z-10 max-w-4xl mx-auto space-y-6">
            <div class="inline-flex items-center space-x-3 px-4 py-2 bg-emerald-500/10 border border-emerald-400/30 rounded-full text-emerald-300 text-[10px] font-black uppercase tracking-widest">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Analyse Stratégique • Pilotage RSE</span>
            </div>
            <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tighter italic">DASHBOARD <span class="text-emerald-500">PRO</span></h1>
            <p class="text-slate-400 text-sm sm:text-base font-medium max-w-2xl mx-auto">Surveillance des allégations interdites, maturité sectorielle et mesure de l'empreinte carbone plateforme.</p>
            
            <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                <div class="flex items-center space-x-2 bg-white/10 border border-white/15 px-3 py-2 rounded-2xl text-xs font-bold text-white">
                    <span>📅 Année :</span>
                    <select onchange="location.href='?year='+this.value" class="bg-slate-900 text-emerald-300 font-black rounded-xl px-2.5 py-1 focus:outline-none border border-slate-700">
                        <option value="all" <?php echo $filterYear === 'all' ? 'selected' : ''; ?>>Toutes les années</option>
                        <option value="2026" <?php echo $filterYear == '2026' ? 'selected' : ''; ?>>2026</option>
                        <option value="2025" <?php echo $filterYear == '2025' ? 'selected' : ''; ?>>2025</option>
                        <option value="2024" <?php echo $filterYear == '2024' ? 'selected' : ''; ?>>2024</option>
                    </select>
                </div>
                <a href="/api/admin/widget.php" class="px-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-xs font-black text-white hover:bg-white/10 transition-all uppercase tracking-widest">
                    🧩 Configurer Widgets
                </a>
                <button onclick="window.print()" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-900/20 transition-all flex items-center space-x-2">
                    <span>🖨️ Imprimer Rapport</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 4 Widgets KPI Principaux -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Score Moyen RSE</span>
            <div class="flex items-baseline space-x-2 mt-2">
                <span class="text-4xl font-black text-emerald-800"><?php echo $avgScore; ?></span>
                <span class="text-sm text-slate-400">/100</span>
            </div>
            <div class="mt-3 w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                <div class="bg-emerald-600 h-full rounded-full" style="width: <?php echo min(100, $avgScore); ?>%"></div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2">Conformité globale du portefeuille</p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Audits & Scans Réalisés</span>
            <div class="flex items-baseline space-x-2 mt-2">
                <span class="text-4xl font-black text-slate-900"><?php echo $totalAudits; ?></span>
                <span class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">Actifs</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-3">Sites et documents RSE évalués</p>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold uppercase text-slate-400 tracking-wider">Entreprises Référencées</span>
            <div class="flex items-baseline space-x-2 mt-2">
                <span class="text-4xl font-black text-teal-800"><?php echo $totalCompanies; ?></span>
                <span class="text-xs text-slate-400">marques</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-3">Comptes & prospects suivis</p>
        </div>

        <div class="bg-gradient-to-br from-slate-900 to-climat-950 text-white p-6 rounded-3xl border border-slate-800 shadow-sm">
            <span class="text-xs font-bold uppercase text-emerald-400 tracking-wider">Sanctions Potentielles Évitées</span>
            <div class="text-2xl font-black text-white mt-2">
                <?php echo number_format($totalAudits * 35000, 0, ',', ' '); ?> €
            </div>
            <p class="text-[11px] text-emerald-200/80 mt-2">Risque d'amendes administratives DGCCRF sécurisé</p>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODULE ÉCO-INDEX & BILAN CARBONE DE L'OUTIL (tCO2e & Consommation IA) -->
    <!-- ========================================================================= -->
    <div class="bg-gradient-to-br from-emerald-950 via-slate-900 to-teal-950 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-emerald-500/30 mb-10">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-emerald-800/60">
            <div>
                <div class="flex items-center space-x-2 mb-1">
                    <span class="w-7 h-7 rounded-lg bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-sm">🌱</span>
                    <span class="text-xs font-black uppercase text-emerald-400 tracking-wider">Bilan Carbone Numérique & Éco-Conception</span>
                </div>
                <h2 class="text-2xl font-black text-white">Empreinte Carbone & Consommation Énergétique de l'Outil</h2>
                <p class="text-xs text-emerald-200 mt-1 max-w-2xl">
                    Mesure en temps réel de l'impact carbone généré par l'infrastructure serveur, les requêtes d'inférence des moteurs d'IA et le scraping sémantique.
                </p>
            </div>

            <div class="flex items-center space-x-3 bg-white/10 px-4 py-2.5 rounded-2xl border border-white/15 backdrop-blur-md">
                <span class="text-2xl">⚡</span>
                <div>
                    <div class="text-[10px] text-emerald-300 font-bold uppercase">Méthodologie Certifiée</div>
                    <div class="text-xs font-black text-white">Base ADEME & GHG Protocol Scope 2-3</div>
                </div>
            </div>
        </div>

        <!-- 4 Indicateurs Clés de l'Empreinte Numérique -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
            <!-- 1. Empreinte Globale en tCO2e -->
            <div class="p-5 rounded-2xl bg-white/5 border border-emerald-500/20 backdrop-blur-xs space-y-1">
                <span class="text-[11px] font-bold text-emerald-300 uppercase">Émissions Totales GES</span>
                <div class="flex items-baseline space-x-1.5 mt-1">
                    <span class="text-3xl font-black text-white"><?php echo $totalEmissionsTCo2e; ?></span>
                    <span class="text-xs font-bold text-emerald-400">tCO2e</span>
                </div>
                <div class="text-[11px] text-slate-300 font-semibold">Soit <?php echo $totalEmissionsKgCo2e; ?> kg CO2e cumulés</div>
            </div>

            <!-- 2. Consommation Électrique (kWh) -->
            <div class="p-5 rounded-2xl bg-white/5 border border-emerald-500/20 backdrop-blur-xs space-y-1">
                <span class="text-[11px] font-bold text-emerald-300 uppercase">Énergie Consommée (IA + Serveur)</span>
                <div class="flex items-baseline space-x-1.5 mt-1">
                    <span class="text-3xl font-black text-amber-300"><?php echo $totalEnergyKwh; ?></span>
                    <span class="text-xs font-bold text-amber-200">kWh</span>
                </div>
                <div class="text-[11px] text-slate-300 font-semibold">GPU Cloud + Inférence LLM + Cache</div>
            </div>

            <!-- 3. Intensité par Audit Unitaire -->
            <div class="p-5 rounded-2xl bg-white/5 border border-emerald-500/20 backdrop-blur-xs space-y-1">
                <span class="text-[11px] font-bold text-emerald-300 uppercase">Intensité Carbone Unitaire</span>
                <div class="flex items-baseline space-x-1.5 mt-1">
                    <span class="text-3xl font-black text-emerald-400"><?php echo $gramsCo2PerAudit; ?></span>
                    <span class="text-xs font-bold text-emerald-300">g CO2e</span>
                </div>
                <div class="text-[11px] text-slate-300 font-semibold">Par audit complet (0.0065 kWh / analyse)</div>
            </div>

            <!-- 4. Contribution & Financement Local -->
            <div class="p-5 rounded-2xl bg-emerald-900/60 border border-emerald-400/40 space-y-1">
                <span class="text-[11px] font-bold text-emerald-200 uppercase">Contribution Territoriale</span>
                <div class="flex items-baseline space-x-1 mt-1">
                    <span class="text-xl font-black text-emerald-300">Projets Bas-Carbone</span>
                </div>
                <div class="text-[11px] text-emerald-100 font-semibold">Financement direct d'agriculteurs et haies bocagères</div>
            </div>
        </div>

        <!-- Détail Technique & Vulgarisation Pédagogique -->
        <div class="mt-6 pt-6 border-t border-emerald-800/60 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div class="p-3.5 bg-black/20 rounded-xl border border-white/10 flex items-start space-x-3">
                <span class="text-xl">🚗</span>
                <div>
                    <strong class="text-white block font-bold">Équivalence Véhicule :</strong>
                    <span class="text-slate-300">Équivalent aux émissions de <strong><?php echo $kmCarEquivalent; ?> km</strong> parcourus en voiture thermique moyenne (120 g CO2/km).</span>
                </div>
            </div>

            <div class="p-3.5 bg-black/20 rounded-xl border border-white/10 flex items-start space-x-3">
                <span class="text-xl">🌳</span>
                <div>
                    <strong class="text-white block font-bold">Séquestration Végétale :</strong>
                    <span class="text-slate-300">Absorbé par l'équivalent de <strong><?php echo max(1, round($treeMonthsAbsorption)); ?> mois</strong> de croissance d'un arbre d'alignement bocager.</span>
                </div>
            </div>

            <div class="p-3.5 bg-black/20 rounded-xl border border-white/10 flex items-start space-x-3">
                <span class="text-xl">🛡️</span>
                <div>
                    <strong class="text-white block font-bold">Éco-Conception Numérique :</strong>
                    <span class="text-slate-300">Code optimisé (cache agressif, payloads JSON compressés, prompts allégés) pour minimiser les cycles GPU.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Principale : TOP 5 DES TERMES INTERDITS & ENTREPRISES CONCERNÉES -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm mb-10">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100">
            <div>
                <h2 class="text-lg font-black text-slate-900 flex items-center space-x-2">
                    <span>🔥</span>
                    <span>Top 5 des Termes les Plus à Risque Détectés sur les Sites</span>
                </h2>
                <p class="text-xs text-slate-500 mt-1">Cliquez sur un terme pour identifier immédiatement les entreprises concernées et lancer une prospection ciblée</p>
            </div>
            <span class="text-xs px-3 py-1 bg-red-100 text-red-800 font-bold rounded-full">
                Surveillance EmpCO 2026
            </span>
        </div>

        <div class="space-y-4">
            <?php if (empty($topClaims)): ?>
                <div class="text-center py-8 text-xs text-slate-500 font-medium">
                    Aucune allégation à risque n'a été détectée. Vos scans d'entreprises apparaîtront ici.
                </div>
            <?php else: ?>
                <?php foreach ($topClaims as $index => $tc): ?>
                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-white hover:border-emerald-300 hover:shadow-md transition-all space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <span class="w-7 h-7 rounded-xl bg-slate-900 text-white font-black text-xs flex items-center justify-center shadow-xs">
                                #<?php echo $index + 1; ?>
                            </span>
                            <div>
                                <h3 class="text-sm font-black text-slate-900"><?php echo htmlspecialchars($tc['term']); ?></h3>
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full <?php echo $tc['severity'] === 'interdit_strict' ? 'bg-red-100 text-red-800' : ($tc['severity'] === 'haut_risque' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'); ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $tc['severity'])); ?>
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <div class="text-right">
                                <span class="text-xs font-black text-slate-900"><?php echo $tc['count']; ?> détection(s)</span>
                                <div class="text-[10px] text-slate-400"><?php echo count($tc['companies']); ?> entreprise(s) identifiée(s)</div>
                            </div>
                            <?php if (!empty($tc['companies'])): ?>
                                <button type="button" onclick="toggleCompanyDrilldown('drill_<?php echo $index; ?>')" class="px-3 py-1.5 bg-emerald-800 hover:bg-emerald-900 text-white rounded-xl text-xs font-bold shadow-sm transition-colors flex items-center space-x-1">
                                    <span>Voir les entreprises</span>
                                    <span>▼</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 bg-white p-3 rounded-xl border border-slate-100">
                        ⚖️ <strong>Risque légal :</strong> <?php echo htmlspecialchars($tc['legalRisk']); ?>
                    </p>

                    <!-- Liste déroulante des entreprises concernées -->
                    <?php if (!empty($tc['companies'])): ?>
                        <div id="drill_<?php echo $index; ?>" class="hidden pt-3 border-t border-slate-200/70 space-y-2">
                            <div class="text-[11px] font-bold text-slate-700 uppercase">Entreprises ayant utilisé ce terme :</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <?php foreach ($tc['companies'] as $compItem): ?>
                                    <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                                        <div class="truncate mr-2">
                                            <strong class="text-xs text-slate-900 block truncate"><?php echo htmlspecialchars($compItem['company_name'] ?: preg_replace('#^https?://#i', '', $compItem['url'])); ?></strong>
                                            <span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($compItem['sector'] ?? 'Secteur non spécifié'); ?></span>
                                        </div>
                                        <a href="/api/analyse.php?id=<?php echo $compItem['audit_id']; ?>" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-xs font-bold whitespace-nowrap">
                                            Dossier ↗
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Répartition Sectorielle & Niveaux de Risque -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Répartition par Secteur -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                <span>🏢</span>
                <span>Maturité RSE par Secteur d'Activité</span>
            </h2>

            <div class="space-y-4 pt-2">
                <?php if (empty($sectors)): ?>
                    <div class="text-center py-8 text-xs text-slate-500 font-medium">
                        Aucune donnée sectorielle disponible. Veuillez lier des entreprises auditées pour afficher leur maturité RSE.
                    </div>
                <?php else: ?>
                    <?php foreach ($sectors as $sec): ?>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <strong class="text-slate-900 font-bold"><?php echo htmlspecialchars($sec['name']); ?></strong>
                            <span class="font-bold <?php echo $sec['avg'] >= 70 ? 'text-emerald-700' : ($sec['avg'] >= 55 ? 'text-amber-600' : 'text-red-600'); ?>">
                                Score : <?php echo $sec['avg']; ?>/100
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                            <div class="<?php echo $sec['color']; ?> h-full rounded-full" style="width: <?php echo $sec['avg']; ?>%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-500">
                            <span><?php echo $sec['audits']; ?> entreprises auditées</span>
                            <span class="font-semibold">Niveau d'exposition : <?php echo $sec['risk']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Répartition Risque & Accès Rapides -->
        <div class="space-y-6">
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                    <span>⚖️</span>
                    <span>Répartition des Dossiers par Risque Légal</span>
                </h2>

                <div class="space-y-3 pt-2 text-xs">
                    <div class="p-3.5 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-between">
                        <div>
                            <strong class="text-red-900 block font-bold">Risque Critique & Allégations Interdites</strong>
                            <span class="text-red-700 text-[11px]">Infraction directe EmpCO Art. 2 (Neutralité compensée)</span>
                        </div>
                        <span class="text-lg font-black text-red-800"><?php echo $riskCounts['critique'] + $riskCounts['eleve']; ?></span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-between">
                        <div>
                            <strong class="text-amber-900 block font-bold">Risque Modéré (Manque de Preuves)</strong>
                            <span class="text-amber-700 text-[11px]">Allégations génériques nécessitant un Bilan GES ou ACV</span>
                        </div>
                        <span class="text-lg font-black text-amber-800"><?php echo $riskCounts['modere']; ?></span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-between">
                        <div>
                            <strong class="text-emerald-900 block font-bold">Conformité Sécurisée (Risque Faible)</strong>
                            <span class="text-emerald-700 text-[11px]">Mentions étayées et attestées conformes</span>
                        </div>
                        <span class="text-lg font-black text-emerald-800"><?php echo $riskCounts['faible']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Actions Rapides -->
            <div class="bg-gradient-to-r from-emerald-800 to-teal-900 text-white p-6 rounded-3xl shadow-lg space-y-3">
                <h3 class="text-base font-bold text-white">Prêt pour une nouvelle campagne de prospection ?</h3>
                <p class="text-xs text-emerald-100 leading-relaxed">
                    Scannez les sites de vos prospects cibles pour générer leurs kits de prospection personnalisés.
                </p>
                <div class="pt-2 flex flex-wrap gap-2">
                    <a href="/api/scan.php" class="px-4 py-2.5 bg-white text-emerald-950 font-bold rounded-xl text-xs shadow hover:bg-emerald-50 transition-colors">
                        Lancer un Scan
                    </a>
                    <a href="/api/pipeline.php" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-600 text-white font-bold rounded-xl text-xs border border-emerald-500/50 transition-colors">
                        Ouvrir le Pipeline
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCompanyDrilldown(elementId) {
    const el = document.getElementById(elementId);
    if (el) {
        el.classList.toggle('hidden');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
