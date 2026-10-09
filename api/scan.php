<?php
// /api/scan.php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/scans_manager.php';
require_once __DIR__ . '/includes/scanner_groups.php';
require_once __DIR__ . '/includes/listes_manager.php';
require_once __DIR__ . '/includes/dossiers_manager.php';
require_login();

$db = get_db_connection();
$allGroups = load_scanner_groups();
$allListes = load_all_listes();
$allScans = load_all_scans();

// Récupération des clients pour la liste déroulante
$companies = [];
try {
    $companies = $db->query("SELECT id, name, siren, website FROM cl_companies ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}

$page_title = "Scanner d'Allégations RSE & Diagnostic";
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10 bg-[#121212] text-[#E0E0E0]">
    <!-- Header PRO -->
    <div class="bg-[#1E1E1E] rounded-3xl p-8 sm:p-12 shadow-2xl relative overflow-hidden border border-white/10 text-center animate-fadeIn no-print">
        <div class="relative z-10 max-w-4xl mx-auto space-y-4">
            <div class="inline-flex items-center space-x-2 px-3 py-1 bg-[#2A7AE2]/20 border border-[#2A7AE2]/30 rounded-full text-[#2A7AE2] text-xs font-bold uppercase tracking-widest">
                <span class="w-2 h-2 rounded-full bg-[#2A7AE2] animate-pulse"></span>
                <span>Scanner Intelligent • Directive (UE) 2024/825 & EmpCO</span>
            </div>
            <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">SCANNER <span class="text-[#2A7AE2]">PRO</span></h1>
            <p class="text-[#AAAAAA] text-sm sm:text-base font-medium max-w-2xl mx-auto">Audit automatisé Green Claims, détection EmpCO et analyse de conformité RSE via Intelligence Artificielle.</p>
            
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <button onclick="openCreateListModal()" class="px-5 py-2.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all border-none cursor-pointer">
                    + Créer une liste
                </button>
                <a href="/api/audit.php" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                    Audit & Prospection →
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulaire de Scan -->
        <div class="lg:col-span-2">
            <div class="bg-[#1E1E1E] p-6 sm:p-8 rounded-3xl border border-white/10 shadow-lg space-y-6">
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span>⚡</span>
                        <span>Nouvel Audit de Conformité RSE</span>
                    </h2>
                    <span class="text-xs px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full font-bold border border-emerald-500/30">
                        Directive Green Claims Ready
                    </span>
                </div>

                <form action="/api/audit" method="POST" enctype="multipart/form-data" class="space-y-5" id="auditScanForm">
                    <!-- Client associé & Groupe -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#AAAAAA] uppercase tracking-wider mb-2">Client / Entreprise</label>
                            <select name="company_id" class="w-full px-4 py-3 bg-[#121212] border border-white/15 rounded-xl text-sm font-medium text-white focus:border-[#2A7AE2]">
                                <option value="">-- Sélectionner un client --</option>
                                <?php foreach ($companies as $comp): ?>
                                    <option value="<?php echo $comp['id']; ?>">
                                        <?php echo htmlspecialchars($comp['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#AAAAAA] uppercase tracking-wider mb-2">Groupe de scanners</label>
                            <select name="group_id" class="w-full px-4 py-3 bg-[#121212] border border-white/15 rounded-xl text-sm font-medium text-white focus:border-[#2A7AE2]">
                                <?php foreach ($allGroups as $g): ?>
                                    <option value="<?php echo htmlspecialchars($g['id']); ?>" <?php echo ($g['id'] === 'grp_widget_default') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($g['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- URL du site -->
                    <div>
                        <label class="block text-xs font-bold text-[#AAAAAA] uppercase tracking-wider mb-2">URL du site web à auditer *</label>
                        <div class="relative">
                            <input type="text" name="url" required placeholder="ex: www.entreprise.fr ou marque.com" class="w-full px-4 py-3 bg-[#121212] border border-white/15 rounded-xl text-sm font-medium text-white focus:border-[#2A7AE2] focus:outline-none">
                        </div>
                    </div>

                    <!-- Profil Entreprise -->
                    <div class="p-5 bg-[#121212] rounded-2xl border border-white/5 space-y-3">
                        <label class="block text-xs font-bold text-white uppercase tracking-wider">Profil Entreprise pour Diagnostic Pro</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] text-[#AAAAAA] mb-1">Salariés</label>
                                <input type="text" name="headcount" placeholder="Ex: 25 salariés" class="w-full p-2.5 bg-[#1E1E1E] border border-white/10 rounded-xl text-xs text-white">
                            </div>
                            <div>
                                <label class="block text-[11px] text-[#AAAAAA] mb-1">CA Annuel</label>
                                <input type="text" name="turnover" placeholder="Ex: 4.5 M€" class="w-full p-2.5 bg-[#1E1E1E] border border-white/10 rounded-xl text-xs text-white">
                            </div>
                            <div>
                                <label class="block text-[11px] text-[#AAAAAA] mb-1">Secteur</label>
                                <select name="sector" class="w-full p-2.5 bg-[#1E1E1E] border border-white/10 rounded-xl text-xs text-white">
                                    <option value="Services & Conseil B2B">Services & Conseil B2B</option>
                                    <option value="Bâtiment, Matériaux & RE2020">Bâtiment, Matériaux & RE2020</option>
                                    <option value="Agroalimentaire & Distribution">Agroalimentaire & Distribution</option>
                                    <option value="Industrie, Chimie & Métallurgie">Industrie, Chimie & Métallurgie</option>
                                    <option value="Mode, Textile & Luxe">Mode, Textile & Luxe</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="pt-2 flex flex-wrap gap-3">
                        <button type="submit" class="flex-1 px-6 py-3.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold rounded-xl text-sm transition-all border-none cursor-pointer flex items-center justify-center gap-2">
                            <span>Lancer l'Audit Global</span>
                        </button>
                        <button type="button" onclick="openCreateListModal()" class="px-4 py-3.5 bg-[#121212] hover:bg-white/10 text-white font-bold rounded-xl text-sm border border-white/15 transition-all cursor-pointer">
                            Créer une liste
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Listes & Groupes -->
        <div class="space-y-6">
            <div class="bg-[#1E1E1E] p-6 rounded-3xl border border-white/10 shadow-lg space-y-4">
                <div class="flex items-center justify-between border-b border-white/10 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>📋</span> Listes de Travail (<?php echo count($allListes); ?>)
                    </h3>
                    <button onclick="openCreateListModal()" class="px-2.5 py-1 bg-[#2A7AE2] text-white text-xs font-bold rounded-lg border-none cursor-pointer">
                        + Nouvelle
                    </button>
                </div>
                <div class="space-y-2">
                    <?php if (empty($allListes)): ?>
                        <p class="text-xs text-[#AAAAAA]">Aucune liste créée pour le moment.</p>
                    <?php else: ?>
                        <?php foreach ($allListes as $lst): ?>
                            <div class="p-3 bg-[#121212] rounded-xl border border-white/5 flex items-center justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-bold text-white"><?php echo htmlspecialchars($lst['nom'] ?? 'Liste sans nom'); ?></div>
                                    <div class="text-[11px] text-[#AAAAAA]"><?php echo count($lst['sites'] ?? []); ?> sites · <?php echo count($lst['items'] ?? []); ?> scans associés</div>
                                    <?php foreach (($lst['sites'] ?? []) as $site): ?>
                                        <div class="mt-3 rounded-lg border border-white/10 p-2">
                                            <div class="text-xs text-white break-all"><?php echo htmlspecialchars($site['nom'] ?? $site['url'] ?? 'Site'); ?></div>
                                            <div class="text-[10px] text-[#AAAAAA] break-all"><?php echo htmlspecialchars($site['url'] ?? ''); ?></div>
                                            <div class="mt-2 flex items-center justify-between gap-2">
                                                <span class="text-[10px] text-amber-300"><?php echo htmlspecialchars($site['statut'] ?? 'à_analyser'); ?></span>
                                                <?php if (!empty($site['url']) && in_array(($site['statut'] ?? 'à_analyser'), ['à_analyser', 'erreur'], true)): ?>
                                                    <button type="button" onclick="scanSiteFromList(this, <?php echo htmlspecialchars(json_encode((string)$lst['id']), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode((string)($site['id'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode((string)$site['url']), ENT_QUOTES, 'UTF-8'); ?>)" class="px-2 py-1 bg-[#2A7AE2] text-white text-[10px] font-bold rounded-md border-none cursor-pointer">Scanner ce site</button>
                                                <?php elseif (($site['statut'] ?? '') === 'en_cours'): ?>
                                                    <span class="text-[10px] text-sky-300">Scan en cours…</span>
                                                <?php elseif (($site['statut'] ?? '') === 'termine'): ?>
                                                    <span class="text-[10px] text-emerald-300">Terminé</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-[#1E1E1E] p-6 rounded-3xl border border-white/10 shadow-lg space-y-3">
                <h3 class="text-base font-bold text-white">Groupes de Scanners Actifs</h3>
                <div class="space-y-2">
                    <?php foreach ($allGroups as $grp): ?>
                        <div class="p-3 bg-[#121212] rounded-xl border border-white/5 flex items-center justify-between text-xs">
                            <span class="font-bold text-white">🏷️ <?php echo htmlspecialchars($grp['nom']); ?></span>
                            <?php if (!empty($grp['is_default'])): ?>
                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded text-[10px] font-bold">Protégé</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scans Récents avec Boutons Listes & Groupes -->
    <div class="bg-[#1E1E1E] rounded-3xl border border-white/10 shadow-lg p-6 sm:p-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 pb-4">
            <div>
                <h2 class="text-xl font-bold text-white">Scans & Audits Récents (<?php echo count($allScans); ?>)</h2>
                <p class="text-xs text-[#AAAAAA]">Chaque scan est enregistré dans la base unifiée avec son dossier et groupe associé.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button onclick="openCreateListModal()" class="px-4 py-2 bg-[#2A7AE2] text-white text-xs font-bold rounded-xl border-none cursor-pointer">
                    + Créer une liste
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($allScans)): ?>
                <div class="col-span-full p-8 text-center text-[#AAAAAA] bg-[#121212] rounded-2xl">
                    Aucun scan enregistré.
                </div>
            <?php else: ?>
                <?php foreach (array_slice($allScans, 0, 12) as $sc): 
                    $scRes = is_array($sc['resultat_ia'] ?? null) ? $sc['resultat_ia'] : json_decode($sc['resultat_ia'] ?? '{}', true);
                    $scScore = $scRes['score'] ?? $scRes['riskScore'] ?? 50;
                    $scType = $sc['type_scan'] ?? 'diagnostic';
                ?>
                    <div class="bg-[#121212] p-5 rounded-2xl border border-white/10 space-y-4 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-1 bg-[#2A7AE2]/20 text-[#2A7AE2] rounded-md text-[11px] font-bold uppercase">
                                    <?php echo htmlspecialchars($scType); ?>
                                </span>
                                <span class="text-xs font-bold <?php echo $scScore >= 75 ? 'text-emerald-400' : ($scScore >= 50 ? 'text-amber-400' : 'text-rose-400'); ?>">
                                    <?php echo $scScore; ?> / 100
                                </span>
                            </div>

                            <div>
                                <h3 class="text-base font-bold text-white truncate max-w-full" title="<?php echo htmlspecialchars($sc['url']); ?>">
                                    <?php echo htmlspecialchars($sc['url']); ?>
                                </h3>
                                <p class="text-xs text-[#AAAAAA] line-clamp-2 mt-1">
                                    <?php echo htmlspecialchars($scRes['summary'] ?? 'Analyse effectuée'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Actions : Ajouter à une liste / Assignation Groupe -->
                        <div class="pt-3 border-t border-white/10 flex flex-wrap gap-2 items-center justify-between">
                            <button onclick="addScanToListPrompt('<?php echo htmlspecialchars($sc['id']); ?>')" class="px-3 py-1.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white text-xs font-bold rounded-lg border-none cursor-pointer">
                                + Ajouter à une liste
                            </button>
                            <button onclick="reassignScanGroupPrompt('<?php echo htmlspecialchars($sc['id']); ?>')" class="px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded-lg border-none cursor-pointer">
                                Groupe
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Créer une Liste -->
<div id="createListModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-[#1E1E1E] text-white max-w-md w-full p-6 rounded-2xl border border-white/15 space-y-5 shadow-2xl">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
            <h3 class="text-lg font-bold">Créer une nouvelle liste</h3>
            <button onclick="closeCreateListModal()" class="text-[#AAAAAA] hover:text-white bg-transparent border-none text-xl font-bold cursor-pointer">&times;</button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-[#AAAAAA] uppercase mb-1">Nom de la liste *</label>
                <input type="text" id="new_list_name" placeholder="ex: Prospects Prioritaires RSE" class="w-full px-4 py-2.5 bg-[#121212] border border-white/15 rounded-xl text-sm text-white focus:border-[#2A7AE2] focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-[#AAAAAA] uppercase mb-1">Description</label>
                <textarea id="new_list_desc" rows="2" placeholder="Description de la liste..." class="w-full px-4 py-2.5 bg-[#121212] border border-white/15 rounded-xl text-sm text-white focus:border-[#2A7AE2] focus:outline-none"></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <button onclick="closeCreateListModal()" class="px-4 py-2 bg-white/10 text-white text-xs font-bold rounded-xl border-none cursor-pointer">Annuler</button>
            <button onclick="submitCreateList()" class="px-5 py-2 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white text-xs font-bold rounded-xl border-none cursor-pointer">Créer la liste</button>
        </div>
    </div>
</div>

<script>
function openCreateListModal() {
    document.getElementById('createListModal').classList.remove('hidden');
}

function closeCreateListModal() {
    document.getElementById('createListModal').classList.add('hidden');
}

async function submitCreateList() {
    const name = document.getElementById('new_list_name').value.trim();
    const desc = document.getElementById('new_list_desc').value.trim();
    if (!name) return alert('Le nom est requis.');

    try {
        const res = await fetch('/api/listes/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nom: name, description: desc })
        });
        const data = await res.json();
        if (data.success) {
            alert('Liste créée avec succès !');
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de créer la liste'));
        }
    } catch (e) {
        alert('Erreur réseau');
    }
}

async function addScanToListPrompt(scanId) {
    const listes = <?php echo json_encode($allListes, JSON_UNESCAPED_UNICODE); ?>;
    if (!listes || listes.length === 0) {
        if (confirm('Aucune liste existante. Voulez-vous en créer une maintenant ?')) {
            openCreateListModal();
        }
        return;
    }

    let listPromptText = "Sélectionnez l'ID de la liste où ajouter ce scan :\n\n";
    listes.forEach(l => {
        listPromptText += `- ID: "${l.id}" => ${l.nom}\n`;
    });

    const chosenId = prompt(listPromptText, listes[0].id);
    if (!chosenId) return;

    try {
        const res = await fetch('/api/listes/add_scan', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ list_id: chosenId, scan_id: scanId })
        });
        const data = await res.json();
        if (data.success) {
            alert('Scan ajouté à la liste !');
        } else {
            alert('Erreur: ' + (data.error || 'Impossible d\'ajouter au groupe'));
        }
    } catch (e) {
        alert('Erreur réseau');
    }
}

async function reassignScanGroupPrompt(scanId) {
    const groups = <?php echo json_encode($allGroups, JSON_UNESCAPED_UNICODE); ?>;
    let groupPromptText = "Sélectionnez l'ID du groupe pour ce scan :\n\n";
    groups.forEach(g => {
        groupPromptText += `- ID: "${g.id}" => ${g.nom}\n`;
    });

    const chosenGroupId = prompt(groupPromptText, groups[0].id);
    if (!chosenGroupId) return;

    try {
        const res = await fetch('/api/scans/assign_group', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ scan_id: scanId, group_id: chosenGroupId })
        });
        const data = await res.json();
        if (data.success) {
            alert('Groupe mis à jour avec succès !');
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de réassigner'));
        }
    } catch (e) {
        alert('Erreur réseau');
    }
}

// Lance uniquement le site sélectionné dans sa liste et conserve son statut/scan_id.
async function scanSiteFromList(button, listId, siteId, url) {
    if (!listId || !siteId || !url) return alert('Liste, site ou URL manquant.');
    if (button.disabled) return;
    const original = button.textContent;
    button.disabled = true;
    button.textContent = 'Démarrage…';
    const statusUrl = '/api/listes/update_site_status.php';
    async function updateStatus(statut, scanId = '') {
        const response = await fetch(statusUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({list_id: listId, site_id: siteId, statut, scan_id: scanId})
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload.success) throw new Error(payload.error || 'Impossible de mettre à jour le statut du site.');
        return payload;
    }
    try {
        await updateStatus('en_cours');
        const response = await fetch('/api/audit.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({url, type_audit: 'full', type_scan: 'diagnostic', liste_pre_audit: [listId]})
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || result.status === 'analysis_incomplete' || result.status === 'storage_unavailable' || result.error || !result.scan_id) {
            throw new Error(result.error || result.message || 'Le scan n’a pas abouti ou son résultat n’a pas été enregistré.');
        }
        await updateStatus('termine', String(result.scan_id));
        alert('Scan terminé pour ' + url + '. Score de risque : ' + (result.riskScore ?? 'non communiqué') + '/100.');
        location.reload();
    } catch (error) {
        try { await updateStatus('erreur'); } catch (statusError) { console.error(statusError); }
        alert('Échec du scan : ' + (error.message || 'erreur inconnue') + '. Vous pourrez réessayer depuis la liste.');
        button.disabled = false;
        button.textContent = original;
    }
}

</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
