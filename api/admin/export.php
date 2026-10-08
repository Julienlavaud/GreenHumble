<?php
// /api/admin/export.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$db = get_db_connection();

if (isset($_GET['download']) && $_GET['download'] === 'audits') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="climatlocal_audits_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['ID', 'Entreprise', 'URL', 'Score RSE', 'Niveau Risque', 'Date Analyse', 'Statut Pipeline', 'Notes']);
    
    $audits = $db->query("SELECT a.id, c.name as company_name, a.url, a.score, a.riskLevel, a.analyzedAt, a.status, a.notes FROM cl_audits a LEFT JOIN cl_companies c ON a.company_id = c.id ORDER BY a.id DESC")->fetchAll();
    
    foreach ($audits as $row) {
        fputcsv($output, [$row['id'], $row['company_name'] ?: 'N/A', $row['url'], $row['score'], $row['riskLevel'], $row['analyzedAt'], $row['status'], $row['notes']]);
    }
    fclose($output);
    exit;
}

if (isset($_GET['download']) && $_GET['download'] === 'texts') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="climatlocal_texts_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Key', 'Title', 'Content']);
    $texts = $db->query("SELECT text_key, title, content FROM cl_texts ORDER BY text_key ASC")->fetchAll();
    foreach ($texts as $row) {
        fputcsv($output, [$row['text_key'], $row['title'], $row['content']]);
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
        fputcsv($output, [$c['id'] ?? '', $c['name'] ?? '', $c['email'] ?? '', $c['role'] ?? '']);
    }
    fclose($output);
    exit;
}

$page_title = "Exportation des Données & Rapports";
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
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
