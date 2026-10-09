<?php
// /api/admin/analyse_textes.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gemini.php';

if (($_SESSION['admin_logged_in'] ?? false) !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /api/login.php?next=' . rawurlencode('/api/admin/index.php?tab=texts'), true, 303);
    exit;
}

$db = get_db_connection();
$texts = [];
try {
    $texts = $db->query("SELECT * FROM cl_texts")->fetchAll();
} catch (Exception $e) {}

$aiFeedback = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allContent = "";
    foreach ($texts as $t) {
        $allContent .= "Clé: " . $t['text_key'] . "\nTitre: " . $t['title'] . "\nContenu: " . $t['content'] . "\n\n";
    }

    $prompt = "Tu es un juriste expert RSE. Voici la liste des dictionnaires de mots bannis et contenus marketing configurés :\n\n"
        . $allContent . "\n\n"
        . "Fais une analyse sémantique critique : Identifie les manques réglementaires par rapport à la directive EmpCO 2024/825 et suggère 5 nouveaux termes à ajouter pour durcir l'audit.";

    $aiFeedback = analyze_url_with_gemini('https://climatlocal.com', $prompt);
}

$page_title = "Audit Sémantique IA des Textes EmpCO";
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="/api/admin/texts.php" class="hover:text-emerald-700">Gestion des textes</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Analyse IA</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Audit Sémantique IA des Dictionnaires</h1>
            <p class="text-sm text-slate-500 mt-1">L'IA Gemini analyse l'exhaustivité de vos listes de mots bannis et vous suggère des ajouts légaux</p>
        </div>
        <a href="/api/admin/texts.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
            ← Retour aux textes
        </a>
    </div>

    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm mb-8">
        <h2 class="text-base font-bold text-slate-900 mb-2">Lancer l'audit sémantique global</h2>
        <p class="text-xs text-slate-500 mb-6">L'intelligence artificielle va compiler l'ensemble des <?php echo count($texts); ?> dictionnaires actifs et vérifier leur conformité aux dernières directives européennes.</p>

        <form method="POST">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-bold rounded-xl text-sm shadow transition-all flex items-center space-x-2">
                <span>🤖 Exécuter l'Analyse Critique Gemini</span>
            </button>
        </form>
    </div>

    <?php if ($aiFeedback): ?>
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center space-x-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">💡</span>
                <span>Résultats & Recommandations de l'IA</span>
            </h3>
            <div class="bg-slate-900 text-emerald-300 p-6 rounded-2xl font-mono text-xs sm:text-sm leading-relaxed overflow-x-auto whitespace-pre-wrap">
<?php echo htmlspecialchars($aiFeedback); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
