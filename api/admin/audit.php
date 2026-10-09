<?php
// /api/admin/audit.php
// Interface d'Audit Anti-Greenwashing & Analyse RSE — GreenHumble v1.1
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/ai_engine.php';

if (($_SESSION['admin_logged_in'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /api/login.php?next=' . rawurlencode('/api/scan.php'), true, 303);
    exit;
}

$page_title = "Module d'Audit Anti-Greenwashing";
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-[1850px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 animate-fadeIn">
    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="/api/admin/index.php" class="hover:text-emerald-700">Administration</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Audit Anti-Greenwashing</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Module d'Audit REST & IA Anti-Greenwashing</h1>
            <p class="text-sm text-slate-500 mt-1">Analyse des allégations environnementales, conformité Directives EmpCO & Green Claims</p>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="loadStoredAudits()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors flex items-center space-x-2">
                <span>🔄 Rafraîchir l'Historique</span>
            </button>
            <a href="/api/admin/index.php" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-colors">
                ← Retour Cockpit
            </a>
        </div>
    </div>

    <!-- Zone Principale : Formulaire de Lancement & Résultat Direct -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Formulaire d'Audit (5 Colonnes) -->
        <div class="lg:col-span-5 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 flex items-center justify-center font-bold text-lg">
                        ⚡
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Lancer un Audit REST</h2>
                        <p class="text-xs text-slate-500">Scan automatisé par l'API Gemini / Groq</p>
                    </div>
                </div>

                <form id="audit-form" onsubmit="submitAuditForm(event)" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">URL du Site Entreprise à Auditer *</label>
                        <input type="url" id="audit-url" placeholder="https://exemple-entreprise.fr" required 
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Extrait de Rapport RSE / Documents (Optionnel)</label>
                        <textarea id="audit-csr-text" rows="4" placeholder="Collez ici du texte ou des extraits du rapport de développement durable..." 
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Niveau de Sévérité Réglementaire</label>
                        <select id="audit-strictness" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="strict">Strict — Directives Européennes EmpCO & Green Claims (2026)</option>
                            <option value="standard">Standard — Décret France 2022-539 / Code de la Consommation</option>
                            <option value="permissive">Pédagogique — Recommandations ARPP & Pré-diagnostic</option>
                        </select>
                    </div>

                    <button type="submit" id="submit-btn" 
                        class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-2xl text-sm shadow-lg shadow-emerald-600/20 flex items-center justify-center space-x-2 transition-all">
                        <span>⚡ Lancer l'Audit Anti-Greenwashing</span>
                    </button>
                </form>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 text-xs text-slate-400">
                <span>🛡️ Analyse sécurisée par la cascade Gemini 3.8 Flash → 2.5 Flash → Groq Llama 3.3.</span>
            </div>
        </div>

        <!-- Conteneur des Résultats d'Audit (7 Colonnes) -->
        <div class="lg:col-span-7 bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 text-slate-100 shadow-xl min-h-[500px]" id="audit-results-container">
            <div class="h-full flex flex-col items-center justify-center text-center py-16 text-slate-500">
                <div class="w-16 h-16 rounded-3xl bg-slate-900 border border-slate-800 flex items-center justify-center text-3xl mb-4">
                    🔍
                </div>
                <h3 class="text-lg font-bold text-slate-300">Aucune analyse active</h3>
                <p class="text-xs text-slate-500 max-w-sm mt-1">Saisissez l'URL d'une entreprise ci-contre pour lancer une analyse en direct via l'API REST <code>POST /api/audit</code>.</p>
            </div>
        </div>
    </div>

    <!-- Historique des Audits Enregistrés -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Audits Récents & Historique Persistant</h2>
                <p class="text-xs text-slate-500">Audits enregistrés dans <code>/data/stored_audits.json</code></p>
            </div>
            <span id="stored-count-badge" class="px-3 py-1 bg-slate-100 text-slate-700 font-bold text-xs rounded-full border border-slate-200">
                0 audit(s)
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 text-[11px] uppercase text-slate-400 font-bold">
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Entreprise / URL</th>
                        <th class="py-3 px-4">Score Risque</th>
                        <th class="py-3 px-4">Niveau</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="stored-audits-tbody" class="text-xs divide-y divide-slate-100">
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400 italic">Chargement de l'historique...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/admin/assets/js/audit.js?v=<?php echo floor(time()/60)*60; ?>"></script>
<script>
// Chargement automatique des audits archivés
async function loadStoredAudits() {
    const tbody = document.getElementById('stored-audits-tbody');
    const badge = document.getElementById('stored-count-badge');
    if (!tbody) return;

    try {
        const res = await fetch('/api/audit');
        const data = await res.json();
        const audits = data.audits || (Array.isArray(data) ? data : []);

        if (badge) badge.innerText = `${audits.length} audit(s)`;

        if (audits.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="py-8 text-center text-slate-400 italic">Aucun audit enregistré pour le moment.</td></tr>`;
            return;
        }

        let html = '';
        audits.forEach(item => {
            const dateStr = item.createdAt ? new Date(item.createdAt).toLocaleString('fr-FR') : 'Inconnue';
            const company = item.companyName || item.company || 'Entreprise';
            const url = item.targetUrl || item.url || '#';
            const score = item.riskScore ?? item.score ?? 50;
            const level = item.riskLevel || 'modere';

            let badgeClass = 'bg-amber-100 text-amber-800 border-amber-300';
            if (score < 40 || level === 'critique') badgeClass = 'bg-red-100 text-red-800 border-red-300';
            else if (score >= 70) badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-300';

            html += `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">${dateStr}</td>
                    <td class="py-3 px-4 font-bold text-slate-800">
                        <div>${company}</div>
                        <a href="${url}" target="_blank" class="text-[10px] text-emerald-600 hover:underline font-mono">${url}</a>
                    </td>
                    <td class="py-3 px-4 font-black text-slate-900">${score}/100</td>
                    <td class="py-3 px-4">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase ${badgeClass}">
                            ${level}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-right space-x-2">
                        <button onclick='displayAuditDetail(${JSON.stringify(item).replace(/'/g, "&apos;")})' class="px-3 py-1 bg-slate-900 text-white rounded-lg text-[11px] font-bold hover:bg-slate-800">
                            👁️ Voir
                        </button>
                        <button onclick="deleteAudit('${item.id}')" class="px-3 py-1 bg-red-50 text-red-600 rounded-lg text-[11px] font-bold hover:bg-red-100">
                            🗑️ Supprimer
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="py-4 text-center text-red-500">Erreur lors du chargement de l'historique : ${e.message}</td></tr>`;
    }
}

function displayAuditDetail(item) {
    if (typeof renderAuditCards === 'function') {
        renderAuditCards(item);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

async function deleteAudit(id) {
    if (!confirm("Voulez-vous vraiment supprimer cet audit de l'historique ?")) return;
    try {
        const res = await fetch('/api/audit', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            loadStoredAudits();
        } else {
            alert(data.error || 'Erreur lors de la suppression.');
        }
    } catch (e) {
        alert('Erreur réseau : ' + e.message);
    }
}

document.addEventListener('DOMContentLoaded', loadStoredAudits);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
