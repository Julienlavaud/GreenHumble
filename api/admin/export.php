<?php
// /api/admin/export.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/listes_manager.php';
require_once __DIR__ . '/../includes/scans_manager.php';
require_login('admin');

$db = get_db_connection();
$exportLists = load_all_listes();

function gh_export_domain(string $value): string {
    $host = strtolower((string)(parse_url($value, PHP_URL_HOST) ?: $value));
    $host = preg_replace('/^www\\./i', '', $host);
    return trim($host);
}
function gh_export_csv_cell($value): string {
    if (is_array($value) || is_object($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $cell = is_scalar($value) ? (string)$value : '';
    if (preg_match('/^[\\x00-\\x20]*[=+@-]/u', $cell)) $cell = "'" . $cell;
    return $cell;
}

if (isset($_GET['download']) && $_GET['download'] === 'prospects') {
    $selectedListId = trim((string)($_GET['list_id'] ?? ''));
    $listNames = [];
    $listItems = [];
    foreach ($exportLists as $list) {
        if (!is_array($list) || empty($list['id'])) continue;
        $listNames[(string)$list['id']] = (string)($list['nom'] ?? 'Liste');
        $listItems[(string)$list['id']] = is_array($list['items'] ?? null) ? $list['items'] : [];
    }
    if ($selectedListId !== '' && !isset($listNames[$selectedListId])) {
        http_response_code(400);
        exit('Liste invalide.');
    }

    $listIdsByScan = [];
    foreach ($listItems as $listId => $scanIds) {
        foreach ($scanIds as $scanId) $listIdsByScan[(string)$scanId][] = $listId;
    }
    $listIdsByDomain = [];
    foreach (load_all_scans() as $scan) {
        if (!is_array($scan)) continue;
        $domain = gh_export_domain((string)($scan['url'] ?? ''));
        if ($domain === '') continue;
        $ids = array_merge(
            is_array($scan['liste_pre_audit'] ?? null) ? $scan['liste_pre_audit'] : [],
            $listIdsByScan[(string)($scan['id'] ?? '')] ?? []
        );
        foreach (array_unique(array_map('strval', $ids)) as $listId) {
            if (isset($listNames[$listId])) $listIdsByDomain[$domain][$listId] = $listNames[$listId];
        }
    }

    $rows = $db->query(
        "SELECT c.id AS company_id, c.name AS company_name, c.domain, c.clean_url, c.website, c.sector, c.siren,
                cc.name AS contact_name, cc.email AS contact_email, cc.phone AS contact_phone, cc.audit_ref,
                a.id AS audit_id, a.url AS audit_url, a.score, a.riskLevel, a.summary, a.status, a.analyzedAt, a.raw_response
         FROM cl_companies c
         INNER JOIN cl_company_contacts cc ON cc.company_id = c.id AND cc.marketing_opt_in = 1
         LEFT JOIN cl_audits a ON a.company_id = c.id
         ORDER BY c.name ASC, a.analyzedAt DESC, cc.id ASC"
    )->fetchAll();

    $csvRows = [];
    foreach ($rows as $row) {
        $domain = gh_export_domain((string)($row['domain'] ?: ($row['clean_url'] ?: $row['website'])));
        $memberships = $listIdsByDomain[$domain] ?? [];
        if ($selectedListId !== '' && !isset($memberships[$selectedListId])) continue;
        $listLabel = $memberships ? implode(' | ', array_values($memberships)) : 'Sans liste';

        $raw = json_decode((string)($row['raw_response'] ?? ''), true);
        $kit = is_array($raw) ? ($raw['commercialProspectingKit'] ?? $raw['prospection'] ?? null) : null;
        $offer = is_array($kit) ? ($kit['recommendedOffer'] ?? null) : null;
        $offerTitle = is_array($offer) ? ($offer['serviceTitle'] ?? '') : (is_string($offer) ? $offer : '');
        $priority = is_array($kit) ? (string)($kit['priority'] ?? '') : '';
        if ($priority === '') $priority = in_array((string)($row['riskLevel'] ?? ''), ['critique', 'eleve'], true) ? 'haute' : (((string)($row['riskLevel'] ?? '') === 'modere') ? 'moyenne' : 'basse');
        $claims = is_array($raw) && is_array($raw['detectedClaims'] ?? null)
            ? implode(' | ', array_filter(array_map(static function ($claim) { return is_array($claim) ? (string)($claim['term'] ?? '') : ''; }, $raw['detectedClaims'])))
            : '';
        $auditUrl = (string)($row['audit_url'] ?: $row['website']);
        $csvRows[] = [
            'list' => $listLabel,
            'priority' => $priority,
            'company' => (string)($row['company_name'] ?? ''),
            'domain' => $domain,
            'website' => $auditUrl,
            'siren' => (string)($row['siren'] ?? ''),
            'sector' => (string)($row['sector'] ?? ''),
            'contact' => (string)($row['contact_name'] ?? ''),
            'email' => (string)($row['contact_email'] ?? ''),
            'phone' => (string)($row['contact_phone'] ?? ''),
            'audit_id' => (string)($row['audit_id'] ?? $row['audit_ref'] ?? ''),
            'score' => (string)($row['score'] ?? ''),
            'risk' => (string)($row['riskLevel'] ?? ''),
            'date' => (string)($row['analyzedAt'] ?? ''),
            'status' => (string)($row['status'] ?? ''),
            'claims' => $claims,
            'offer' => (string)$offerTitle,
            'next_action' => is_array($kit) ? (string)($kit['nextAction'] ?? '') : '',
            'summary' => (string)($row['summary'] ?? '')
        ];
    }
    $rank = ['haute' => 0, 'high' => 0, 'moyenne' => 1, 'medium' => 1, 'basse' => 2, 'low' => 2];
    usort($csvRows, static function ($a, $b) use ($rank) {
        $listCompare = strcmp($a['list'], $b['list']);
        if ($listCompare !== 0) return $listCompare;
        $priorityCompare = ($rank[strtolower($a['priority'])] ?? 3) <=> ($rank[strtolower($b['priority'])] ?? 3);
        if ($priorityCompare !== 0) return $priorityCompare;
        return ((int)$a['score']) <=> ((int)$b['score']);
    });

    header('Content-Type: text/csv; charset=utf-8');
    header('Cache-Control: no-store');
    header('Content-Disposition: attachment; filename="greenhumble_prospects_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['Liste', 'Priorité', 'Entreprise', 'Domaine', 'Site web', 'SIREN', 'Secteur', 'Contact', 'E-mail', 'Téléphone', 'Audit', 'Score', 'Risque', 'Date analyse', 'Statut', 'Allégations à examiner', 'Offre recommandée', 'Prochaine action', 'Synthèse'], ';');
    foreach ($csvRows as $row) fputcsv($output, array_map('gh_export_csv_cell', array_values($row)), ';');
    fclose($output);
    exit;
}

if (isset($_GET['download']) && $_GET['download'] === 'audits') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="climatlocal_audits_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Entreprise', 'URL', 'Score RSE', 'Niveau Risque', 'Date Analyse', 'Statut Pipeline', 'Notes']);
    
    $audits = $db->query("SELECT a.id, c.name as company_name, a.url, a.score, a.riskLevel, a.analyzedAt, a.status, a.notes FROM cl_audits a LEFT JOIN cl_companies c ON a.company_id = c.id ORDER BY a.id DESC")->fetchAll();
    
    foreach ($audits as $row) {
        fputcsv($output, array_map('gh_export_csv_cell', [$row['id'], $row['company_name'] ?: 'N/A', $row['url'], $row['score'], $row['riskLevel'], $row['analyzedAt'], $row['status'], $row['notes']]));
    }
    fclose($output);
    exit;
}

if (isset($_GET['download']) && $_GET['download'] === 'texts') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="climatlocal_texts_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['text_key', 'title', 'content'], ';', '"', '');
    $texts = $db->query("SELECT text_key, title, content FROM cl_texts ORDER BY text_key ASC")->fetchAll();
    foreach ($texts as $row) {
        fputcsv($output, array_map('gh_export_csv_cell', [$row['text_key'], $row['title'], $row['content']]), ';', '"', '');
    }
    fclose($output);
    exit;
}

if (isset($_GET['download']) && $_GET['download'] === 'team') {
    require_once __DIR__ . '/../includes/app_state.php';
    $st = load_app_state();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="climatlocal_team_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Name', 'Email', 'Role']);
    foreach (($st['collaborators'] ?? []) as $c) {
        fputcsv($output, array_map('gh_export_csv_cell', [$c['id'] ?? '', $c['name'] ?? '', $c['email'] ?? '', $c['role'] ?? '']));
    }
    fclose($output);
    exit;
}

$page_title = "Exportation des Données & Rapports";
$activeTab = "exports";
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-[1850px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 animate-fadeIn">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 rounded-3xl p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6 border border-emerald-500/20">
        <div class="space-y-2">
            <span class="px-3 py-1 bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 rounded-full text-xs font-bold uppercase tracking-wider">Cockpit Administration • OVH Multiposte</span>
            <h1 class="text-3xl sm:text-4xl font-black tracking-tight">Exportation des Données & Rapports</h1>
            <p class="text-emerald-100/80 text-sm max-w-2xl">Téléchargez et exportez l'intégralité de vos audits de conformité, prospects et données de pipeline au format CSV compatible Excel & Google Sheets.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="/api/admin/console.php" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition-all border border-white/10 flex items-center space-x-1.5">
                <span>← Retour Console Admin</span>
            </a>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Export Card -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm space-y-6">
            <div class="flex items-center space-x-3 pb-4 border-b border-slate-100">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700 font-bold">
                    📥
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Export Complet du Pipeline & Audits</h3>
                    <p class="text-xs text-slate-500">Génération instantanée du fichier CSV contenant l'historique des scans</p>
                </div>
            </div>

            <div class="p-5 bg-slate-50 rounded-2xl border border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Format d'export</span>
                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-lg text-xs font-bold">CSV (UTF-8 avec BOM Excel)</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">Champs inclus</span>
                    <span class="text-xs text-slate-600">ID, Entreprise, URL, Score RSE, Risque, Date, Statut, Notes</span>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end">
                <a href="/api/admin/export.php?download=audits" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-2xl text-xs shadow-md transition-all flex items-center space-x-2 transform hover:-translate-y-0.5">
                    <span>📥 Télécharger le rapport CSV complet</span>
                </a>
            </div>
        </div>

        <!-- Info Card -->
        <div class="bg-gradient-to-br from-emerald-900 to-slate-900 rounded-3xl p-8 text-white shadow-sm space-y-4 border border-emerald-700/30">
            <h3 class="font-bold text-base text-emerald-200">Rappel Sécurité & Multiposte</h3>
            <p class="text-xs text-slate-300 leading-relaxed">
                Toutes les modifications effectuées sur les postes clients ou via l'API PHP sont synchronisées en temps réel. Les exports CSV reflètent l'état actuel de votre base de données OVH.
            </p>
            <div class="pt-4 border-t border-emerald-800/60 flex items-center justify-between text-xs text-emerald-300 font-semibold">
                <span>Cabinet Climat Local</span>
                <span>Version 2026.4</span>
            </div>
        </div>
    </div>

    <section class="mt-8 bg-white rounded-3xl p-8 border border-emerald-200 shadow-sm space-y-5">
        <div>
            <h2 class="text-xl font-black text-slate-900">Exporter les prospects par liste</h2>
            <p class="text-sm text-slate-600 mt-2">Le fichier contient les coordonnées uniquement lorsque la personne a accepté les communications commerciales. Les résultats d’audit sont regroupés par liste et classés par priorité.</p>
        </div>
        <form method="GET" action="/api/admin/export.php" class="flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="download" value="prospects">
            <label class="sr-only" for="prospect-export-list">Filtrer par liste</label>
            <select id="prospect-export-list" name="list_id" class="min-w-64 flex-1 px-4 py-3 border border-slate-200 rounded-xl text-sm">
                <option value="">Toutes les listes</option>
                <?php foreach ($exportLists as $exportList): ?>
                    <?php if (!is_array($exportList) || empty($exportList['id'])) continue; ?>
                    <option value="<?php echo htmlspecialchars((string)$exportList['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)($exportList['nom'] ?? 'Liste'), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-sm">Télécharger le CSV des prospects</button>
        </form>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
