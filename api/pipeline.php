<?php
// /api/pipeline.php
// Pipeline de Traitement Commercial & Suivi des Missions RSE
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cms.php';
require_login();

$db = get_db_connection();
$msg = null;

// Traitement changement de statut rapide / assignation / note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pipeline_item'])) {
    $auditId = intval($_POST['audit_id']);
    $newStatus = $_POST['status'] ?? 'scan_termine';
    $notes = trim($_POST['notes'] ?? '');
    $assignedTo = trim($_POST['assigned_to'] ?? '');

    try {
        $stmt = $db->prepare("UPDATE cl_audits SET status = ?, notes = ?, assigned_to = COALESCE(NULLIF(?, ''), assigned_to) WHERE id = ?");
        $stmt->execute([$newStatus, $notes, $assignedTo, $auditId]);
        $msg = "Dossier #$auditId mis à jour dans le pipeline.";
    } catch (Exception $e) {}
    
    header('Location: /api/pipeline.php');
    exit;
}

// 5 Colonnes Métier Conformes à la Maquette Commerciale
$columns = [
    'scan_termine' => [
        'title' => '1. Prospects Détectés',
        'subtitle' => 'Scans récents & Leads entrants',
        'icon' => '📥',
        'color' => 'border-slate-300 bg-slate-50',
        'badge' => 'bg-slate-200 text-slate-800',
        'items' => []
    ],
    'contacte' => [
        'title' => '2. Premier Contact',
        'subtitle' => 'Pitch & Email envoyé',
        'icon' => '💬',
        'color' => 'border-blue-300 bg-blue-50/40',
        'badge' => 'bg-blue-100 text-blue-800',
        'items' => []
    ],
    'en_analyse' => [
        'title' => '3. Diag. Présenté & RDV',
        'subtitle' => 'Soutenance 6-Piliers & Enjeux',
        'icon' => '📊',
        'color' => 'border-amber-300 bg-amber-50/40',
        'badge' => 'bg-amber-100 text-amber-800',
        'items' => []
    ],
    'devis_envoye' => [
        'title' => '4. Négociation & Devis',
        'subtitle' => 'Offre de mission transmise',
        'icon' => '📝',
        'color' => 'border-purple-300 bg-purple-50/40',
        'badge' => 'bg-purple-100 text-purple-800',
        'items' => []
    ],
    'signe' => [
        'title' => '5. Missions Signées',
        'subtitle' => 'Accompagnement actif',
        'icon' => '✍️',
        'color' => 'border-emerald-400 bg-emerald-50/50',
        'badge' => 'bg-emerald-100 text-emerald-800',
        'items' => []
    ]
];

$filterAssigned = $_GET['consultant'] ?? '';
$whereClause = $filterAssigned ? " WHERE a.assigned_to = " . $db->quote($filterAssigned) : "";

$totalPipelineValue = 0;
$totalSignedValue = 0;
$totalItemsCount = 0;

try {
    $audits = $db->query("SELECT a.*, c.name as company_name, c.sector, c.siren, c.contact_name FROM cl_audits a LEFT JOIN cl_companies c ON a.company_id = c.id $whereClause ORDER BY a.id DESC")->fetchAll();
    
    foreach ($audits as $aud) {
        $st = $aud['status'] ?: 'scan_termine';
        if ($st === 'nouveau') $st = 'scan_termine';
        if ($st === 'rdv_fixe' || $st === 'rapport_transmis') $st = 'en_analyse';
        if ($st === 'valide') $st = 'signe';

        if (!isset($columns[$st])) {
            $st = 'scan_termine';
        }

        $columns[$st]['items'][] = $aud;
        $totalItemsCount++;
        
        // Estimation budget par dossier
        $estimatedBudget = 3500;
        if ($aud['riskLevel'] === 'critique') $estimatedBudget = 5500;
        elseif ($aud['riskLevel'] === 'eleve') $estimatedBudget = 4200;
        
        $totalPipelineValue += $estimatedBudget;
        if ($st === 'signe') {
            $totalSignedValue += $estimatedBudget;
        }
    }
} catch (Exception $e) {}

// Consultants pour le filtre
$consultants = [];
try {
    $consultants = $db->query("SELECT DISTINCT assigned_to FROM cl_audits WHERE assigned_to IS NOT NULL AND assigned_to != ''")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

$page_title = "Pipeline Commercial & Suivi des Missions";
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
                <span>Cockpit Commercial • OVH Multiposte</span>
            </div>
            <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tighter italic">PIPELINE <span class="text-emerald-500">PRO</span></h1>
            <p class="text-slate-400 text-sm sm:text-base font-medium max-w-2xl mx-auto">Pilotage centralisé des opportunités RSE et conversion des audits en missions d'accompagnement certifiées.</p>
            
            <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                <select onchange="location.href='?consultant='+encodeURIComponent(this.value)" class="px-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-xs font-black text-white shadow-xl backdrop-blur-md focus:ring-4 focus:ring-emerald-500/20 outline-none transition-all">
                    <option value="" class="bg-slate-900">Tous les consultants (<?php echo $totalItemsCount; ?>)</option>
                    <?php foreach ($consultants as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $filterAssigned === $c ? 'selected' : ''; ?> class="bg-slate-900">
                            👤 <?php echo htmlspecialchars($c); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <a href="/api/scan.php" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-900/20 transition-all flex items-center space-x-2">
                    <span>+ Lancer un nouveau scan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Barres Commerciales -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-slate-400 uppercase">Dossiers Actifs</span>
            <div class="text-2xl font-black text-slate-900 mt-1"><?php echo $totalItemsCount; ?></div>
            <span class="text-[10px] text-emerald-700 font-bold">Prospects qualifiés</span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-slate-400 uppercase">Valeur Totale du Pipe</span>
            <div class="text-2xl font-black text-slate-900 mt-1"><?php echo number_format($totalPipelineValue, 0, ',', ' '); ?> €</div>
            <span class="text-[10px] text-slate-500">Missions estimées</span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-slate-400 uppercase">Chiffre d'Affaires Signé</span>
            <div class="text-2xl font-black text-emerald-700 mt-1"><?php echo number_format($totalSignedValue, 0, ',', ' '); ?> €</div>
            <span class="text-[10px] text-emerald-600 font-bold">Missions actives</span>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-[11px] font-bold text-slate-400 uppercase">Taux de Conversion</span>
            <div class="text-2xl font-black text-indigo-600 mt-1">
                <?php echo $totalItemsCount > 0 ? round((count($columns['signe']['items']) / $totalItemsCount) * 100) : 0; ?>%
            </div>
            <span class="text-[10px] text-slate-500">Scan ➔ Mission</span>
        </div>
    </div>

    <!-- 5 Colonnes Kanban Conformes à la Maquette -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <?php foreach ($columns as $statusKey => $col): ?>
            <div class="bg-white rounded-3xl border <?php echo $col['color']; ?> shadow-sm flex flex-col h-full min-h-[550px] overflow-hidden">
                <!-- En-tête de Colonne -->
                <div class="p-4 border-b border-slate-200/80 flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-1.5">
                            <span class="text-base"><?php echo $col['icon']; ?></span>
                            <h2 class="text-xs font-black uppercase tracking-wider text-slate-900"><?php echo $col['title']; ?></h2>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-0.5"><?php echo $col['subtitle']; ?></p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-black <?php echo $col['badge']; ?>">
                        <?php echo count($col['items']); ?>
                    </span>
                </div>

                <!-- Cartes Dossiers -->
                <div class="p-3 space-y-3 flex-grow overflow-y-auto max-h-[700px]">
                    <?php if (empty($col['items'])): ?>
                        <div class="text-center py-12 text-xs text-slate-400">
                            Aucun dossier à cette étape
                        </div>
                    <?php else: ?>
                        <?php foreach ($col['items'] as $item): ?>
                            <div class="p-4 rounded-2xl border border-slate-200 bg-white hover:border-emerald-500 hover:shadow-md transition-all space-y-2.5">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-xs font-black text-slate-900">#<?php echo $item['id']; ?></span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black <?php echo $item['score'] >= 75 ? 'bg-emerald-100 text-emerald-800' : ($item['score'] >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'); ?>">
                                        <?php echo $item['score'] ?? 50; ?>/100
                                    </span>
                                </div>

                                <div>
                                    <h3 class="text-xs font-bold text-slate-900 leading-tight">
                                        <a href="/api/analyse.php?id=<?php echo $item['id']; ?>" class="hover:text-emerald-700 hover:underline">
                                            <?php echo htmlspecialchars($item['company_name'] ?: preg_replace('#^https?://(www\.)?#i', '', $item['url'])); ?>
                                        </a>
                                    </h3>
                                    <?php if (!empty($item['sector'])): ?>
                                        <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($item['sector']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="text-[11px] font-mono text-emerald-800 bg-emerald-50 px-2 py-1 rounded-lg truncate">
                                    🌐 <?php echo htmlspecialchars(preg_replace('#^https?://#i', '', rtrim($item['url'], '/'))); ?>
                                </div>

                                <?php if (!empty($item['contact_name'])): ?>
                                    <div class="text-[10px] text-slate-600">
                                        👤 <strong>Contact :</strong> <?php echo htmlspecialchars($item['contact_name']); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($item['notes'])): ?>
                                    <div class="p-2 bg-slate-50 rounded-xl text-[10px] text-slate-600 border border-slate-100 italic">
                                        📝 <?php echo htmlspecialchars($item['notes']); ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions rapides et déplacement Kanban -->
                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-1 text-[11px]">
                                    <a href="/api/analyse.php?id=<?php echo $item['id']; ?>&tab=kit" class="font-bold text-emerald-700 hover:underline">
                                        Kit Prospection →
                                    </a>

                                    <form method="POST" action="/api/pipeline.php" class="inline">
                                        <input type="hidden" name="update_pipeline_item" value="1">
                                        <input type="hidden" name="audit_id" value="<?php echo $item['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="text-[10px] font-bold bg-slate-100 border border-slate-200 rounded-lg px-1.5 py-1 text-slate-700 focus:outline-none cursor-pointer">
                                            <option value="scan_termine" <?php echo $statusKey === 'scan_termine' ? 'selected' : ''; ?>>1. Prospect</option>
                                            <option value="contacte" <?php echo $statusKey === 'contacte' ? 'selected' : ''; ?>>2. Contact</option>
                                            <option value="en_analyse" <?php echo $statusKey === 'en_analyse' ? 'selected' : ''; ?>>3. Diag</option>
                                            <option value="devis_envoye" <?php echo $statusKey === 'devis_envoye' ? 'selected' : ''; ?>>4. Devis</option>
                                            <option value="signe" <?php echo $statusKey === 'signe' ? 'selected' : ''; ?>>5. Signé</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
