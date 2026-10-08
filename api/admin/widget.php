<?php
// /api/admin/widget.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    header('Location: /api/admin/index.php');
    exit;
}

$page_title = "Administration des Widgets ClimatLocal";
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="/api/admin/index.php" class="hover:text-emerald-700">Administration</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Widgets Embarquables</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Générateur & Administration des Widgets</h1>
            <p class="text-sm text-slate-500 mt-1">Créez et déployez des widgets d'évaluation Green Claims sur vos sites partenaires ou WordPress pour capter des leads</p>
        </div>
        <div class="flex space-x-2">
            <a href="/api/dashboard.php" class="px-3.5 py-2 bg-emerald-700 text-white rounded-xl text-xs font-bold hover:bg-emerald-800">
                📊 Voir le Tableau de Bord
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Configuration du Widget -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
            <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                <span>⚙️</span>
                <span>Personnalisation du Widget</span>
            </h2>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Titre du Widget</label>
                <input type="text" id="widget_title" value="Audit Express de Conformité Green Claims" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none" oninput="updateWidgetPreview()">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Couleur Principale</label>
                <div class="flex items-center space-x-3">
                    <input type="color" id="widget_color" value="#059669" class="w-10 h-10 rounded-xl border border-slate-200 cursor-pointer" onchange="updateWidgetPreview()">
                    <span id="widget_color_hex" class="text-xs font-mono text-slate-600">#059669</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Format d'affichage</label>
                <select id="widget_format" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none" onchange="updateWidgetPreview()">
                    <option value="compact">Compact (Idéal pour sidebar ou pied de page)</option>
                    <option value="card" selected>Carte Standard (Bannière interactive)</option>
                    <option value="hero">Hero Pleine Largeur</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Pôle Commercial de Réception des Leads</label>
                <select id="widget_team" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="1">Pôle Auvergne & Grand Centre</option>
                    <option value="2">Pôle Île-de-France & Nord</option>
                    <option value="3">Pôle Sud & Méditerranée</option>
                </select>
            </div>

            <div class="pt-4 border-t border-slate-100">
                <button type="button" onclick="copyWidgetCode()" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs flex items-center justify-center space-x-2 shadow transition-all">
                    <span>📋 Copier le Code d'Intégration</span>
                </button>
            </div>
        </div>

        <!-- Aperçu en direct & Codes d'intégration -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Aperçu en direct -->
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-slate-900">Aperçu en direct</h2>
                    <span class="text-xs px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-full">Interactif</span>
                </div>
                
                <div id="preview_container" class="p-6 bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                    <div id="rendered_widget" class="max-w-md mx-auto bg-white p-6 rounded-2xl shadow-md border border-slate-100 text-center">
                        <div class="w-10 h-10 mx-auto rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg mb-3 shadow">
                            🌿
                        </div>
                        <h3 id="preview_title" class="text-base font-bold text-slate-900 mb-1">Audit Express de Conformité Green Claims</h3>
                        <p class="text-xs text-slate-500 mb-4">Vérifiez si votre site respecte la Directive EmpCO (UE 2024/825)</p>
                        <div class="space-y-2">
                            <input type="url" placeholder="https://votre-entreprise.com" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <button type="button" class="w-full py-2.5 bg-emerald-600 text-white font-bold rounded-xl text-xs shadow hover:bg-emerald-700 transition-colors">
                                Lancer l'Évaluation Gratuite
                            </button>
                        </div>
                        <span class="inline-block mt-3 text-[10px] text-slate-400">Propulsé par ClimatLocal &bull; Conforme Barème DGCCRF</span>
                    </div>
                </div>
            </div>

            <!-- Code d'intégration HTML / iFrame / JavaScript -->
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                <h2 class="text-base font-bold text-slate-900">Code d'intégration (HTML / Script)</h2>
                
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase">1. Balise Script Simple (Recommandé)</label>
                    <textarea id="code_script" readonly rows="3" class="w-full p-3 font-mono text-xs bg-slate-900 text-emerald-300 rounded-xl border border-slate-800 focus:outline-none select-all">&lt;script src="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/api/widget.js" data-climatlocal-widget="card" data-color="#059669" async&gt;&lt;/script&gt;</textarea>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase">2. iFrame HTML Responsive</label>
                    <textarea id="code_iframe" readonly rows="3" class="w-full p-3 font-mono text-xs bg-slate-900 text-emerald-300 rounded-xl border border-slate-800 focus:outline-none select-all">&lt;iframe src="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/api/widget_embed.php?color=%23059669" width="100%" height="320" frameborder="0" style="border:none; border-radius:16px;"&gt;&lt;/iframe&gt;</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateWidgetPreview() {
    const title = document.getElementById('widget_title').value;
    const color = document.getElementById('widget_color').value;
    document.getElementById('widget_color_hex').textContent = color;
    document.getElementById('preview_title').textContent = title || 'Audit Express de Conformité';
    
    const host = window.location.origin;
    const scriptTag = `<script src="${host}/api/widget.js" data-climatlocal-widget="card" data-color="${color}" async><\/script>`;
    const iframeTag = `<iframe src="${host}/api/widget_embed.php?color=${encodeURIComponent(color)}" width="100%" height="320" frameborder="0" style="border:none; border-radius:16px;"><\/iframe>`;

    document.getElementById('code_script').value = scriptTag;
    document.getElementById('code_iframe').value = iframeTag;
}

function copyWidgetCode() {
    const code = document.getElementById('code_script').value;
    navigator.clipboard.writeText(code).then(() => {
        alert('Code JavaScript copié dans le presse-papier !');
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
