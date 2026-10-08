<?php
// /api/admin/companies.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$db = get_db_connection();
$msg = null;

// Suppression d'entreprise
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_company'])) {
    $cId = intval($_POST['company_id']);
    try {
        $stmtDelAudits = $db->prepare("DELETE FROM cl_audits WHERE company_id = ?");
        $stmtDelAudits->execute([$cId]);
        $stmtDel = $db->prepare("DELETE FROM cl_companies WHERE id = ?");
        $stmtDel->execute([$cId]);
        $msg = "Fiche entreprise et ses audits associés supprimés.";
    } catch (Exception $e) {
        $msg = "Erreur lors de la suppression de l'entreprise.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_company'])) {
    $name = trim($_POST['name'] ?? '');
    $siren = trim($_POST['siren'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $sector = trim($_POST['sector'] ?? 'Services & Conseil B2B');
    $headcount = trim($_POST['headcount'] ?? '');
    $turnover = trim($_POST['turnover'] ?? '');

    if (!empty($name)) {
        try {
            $stmt = $db->prepare("INSERT INTO cl_companies (name, siren, website, sector, headcount, turnover) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $siren, $website, $sector, $headcount, $turnover]);
            $msg = "Entreprise '$name' enregistrée.";
        } catch (Exception $e) {}
    }
}

// Recherche
$search = trim($_GET['q'] ?? '');
$companies = [];
try {
    if (!empty($search)) {
        $stmt = $db->prepare("SELECT c.*, COUNT(a.id) as audit_count FROM cl_companies c LEFT JOIN cl_audits a ON c.id = a.company_id WHERE c.name LIKE ? OR c.siren LIKE ? GROUP BY c.id ORDER BY c.name ASC");
        $stmt->execute(["%$search%", "%$search%"]);
        $companies = $stmt->fetchAll();
    } else {
        $companies = $db->query("SELECT c.*, COUNT(a.id) as audit_count FROM cl_companies c LEFT JOIN cl_audits a ON c.id = a.company_id GROUP BY c.id ORDER BY c.name ASC")->fetchAll();
    }
} catch (Exception $e) {}

$page_title = "Répertoire des Entreprises Suivies";
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="/api/admin/index.php" class="hover:text-emerald-700">Administration</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Entreprises</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Répertoire des Entreprises & Clients</h1>
            <p class="text-sm text-slate-500 mt-1">Fiches clients, numéros SIREN et historique des audits associés</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="https://annuaire-entreprises.data.gouv.fr/" target="_blank" rel="noopener noreferrer" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                🔍 Recherche Répertoire National ↗
            </a>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulaire Création Entreprise -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-4">Ajouter un Client</h2>

                <form method="POST" action="/api/admin/companies.php" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Raison Sociale</label>
                        <input type="text" name="name" required placeholder="Ex: SAS EcoFashion" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Numéro SIREN (9 chiffres)</label>
                        <input type="text" name="siren" placeholder="Ex: 842109842" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Site Web</label>
                        <input type="url" name="website" placeholder="https://www.ecofashion.fr" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nombre de salariés</label>
                        <input type="text" name="headcount" placeholder="Ex: 25 salariés (10-49)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Chiffre d'Affaires (CA)</label>
                        <input type="text" name="turnover" placeholder="Ex: 4.5 M€" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Secteur d'activité (Automatique)</label>
                        <select name="sector" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="Services & Conseil B2B">Services & Conseil B2B</option>
                            <option value="Bâtiment, Matériaux & RE2020">Bâtiment, Matériaux & RE2020</option>
                            <option value="Agroalimentaire & Distribution">Agroalimentaire & Distribution</option>
                            <option value="Mode, Textile & Luxe">Mode, Textile & Luxe</option>
                            <option value="Industrie, Chimie & Métallurgie">Industrie, Chimie & Métallurgie</option>
                            <option value="Transports & Logistique">Transports & Logistique</option>
                            <option value="Technologies & Numérique">Technologies & Numérique</option>
                            <option value="Énergie & Environnement">Énergie & Environnement</option>
                            <option value="Hôtellerie & Tourisme">Hôtellerie & Tourisme</option>
                            <option value="Santé & Pharmacie">Santé & Pharmacie</option>
                        </select>
                    </div>

                    <button type="submit" name="create_company" class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-bold rounded-xl text-sm shadow transition-all">
                        + Enregistrer l'Entreprise
                    </button>
                </form>
            </div>
        </div>

        <!-- Liste des Entreprises avec Recherche -->
        <div class="lg:col-span-2 space-y-4">
            <form method="GET" action="/api/admin/companies.php" class="flex gap-2">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher par nom d'entreprise ou SIREN..." class="flex-grow px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-bold hover:bg-slate-800">
                    Rechercher
                </button>
                <?php if ($search): ?>
                    <a href="/api/admin/companies.php" class="px-3 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-200 flex items-center">Reset</a>
                <?php endif; ?>
            </form>

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h2 class="text-base font-bold text-slate-900">Entreprises Enregistrées (<?php echo count($companies); ?>)</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold">
                            <tr>
                                <th class="px-6 py-3.5">Entreprise / SIREN</th>
                                <th class="px-6 py-3.5">Site Web</th>
                                <th class="px-6 py-3.5">Secteur</th>
                                <th class="px-6 py-3.5">Audits</th>
                                <th class="px-6 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($companies)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-xs">
                                        Aucune entreprise trouvée.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($companies as $c): ?>
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-900"><?php echo htmlspecialchars($c['name']); ?></div>
                                            <div class="text-xs text-slate-500 font-mono">SIREN : <?php echo htmlspecialchars($c['siren'] ?: 'N/A'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-mono text-emerald-800">
                                            <?php if ($c['website']): ?>
                                                <a href="<?php echo htmlspecialchars($c['website']); ?>" target="_blank" class="hover:underline">
                                                    <?php echo htmlspecialchars($c['website']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-slate-400">Non renseigné</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-600">
                                            <?php echo htmlspecialchars($c['sector'] ?: 'Général'); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                <?php echo $c['audit_count']; ?> audit(s)
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <?php if ($c['website']): ?>
                                                <form action="/api/audit" method="POST" class="inline">
                                                    <input type="hidden" name="url" value="<?php echo htmlspecialchars($c['website']); ?>">
                                                    <input type="hidden" name="company_id" value="<?php echo $c['id']; ?>">
                                                    <button type="submit" class="text-xs font-bold text-emerald-700 hover:underline">
                                                        Auditer ➔
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form action="/api/admin/companies.php" method="POST" class="inline" onsubmit="return confirm('Supprimer cette entreprise et ses audits ?');">
                                                <input type="hidden" name="company_id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" name="delete_company" class="text-xs font-bold text-rose-600 hover:underline ml-2">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
