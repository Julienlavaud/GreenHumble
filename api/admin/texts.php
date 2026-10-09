<?php
// /api/admin/texts.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['admin_logged_in']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    header('Location: /api/login.php?next=' . rawurlencode('/api/admin/index.php?tab=texts'), true, 303);
    exit;
}

$db = get_db_connection();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_text'])) {
    $key = trim($_POST['text_key'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (!empty($key)) {
        try {
            $stmt = $db->prepare("INSERT INTO cl_texts (text_key, title, content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content)");
            $stmt->execute([$key, $title, $content]);
            $msg = "Texte '$key' mis à jour avec succès.";
        } catch (Exception $e) {}
    }
}

$texts = [];
try {
    $texts = $db->query("SELECT * FROM cl_texts ORDER BY text_key ASC")->fetchAll();
} catch (Exception $e) {}

$page_title = "Gestion des Textes & Mots Bannis EmpCO";
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="/api/admin/index.php" class="hover:text-emerald-700">Administration</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Gestion des textes</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Mots Bannis EmpCO & Textes Clés</h1>
            <p class="text-sm text-slate-500 mt-1">Configurez le dictionnaire de termes interdits et les mentions légales</p>
        </div>
        <a href="/api/admin/analyse_textes.php" class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-700 text-white rounded-xl text-xs font-bold shadow hover:from-emerald-700 hover:to-teal-800 transition-all">
            🤖 Analyse Sémantique IA des Textes ➔
        </a>
    </div>

    <?php if ($msg): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulaire Édition Texte -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-4">Ajouter / Modifier un Texte</h2>

                <form method="POST" action="/api/admin/texts.php" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Identifiant Unique (Clé)</label>
                        <input type="text" name="text_key" id="text_key" required placeholder="banned_words_textile" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Titre explicatif</label>
                        <input type="text" name="title" id="text_title" placeholder="Mots interdits secteur Textile" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Contenu / Liste des mots (séparés par des virgules)</label>
                        <textarea name="content" id="text_content" rows="6" required placeholder="100% propre, vert, sans pollution..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                    </div>

                    <button type="submit" name="save_text" class="w-full py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-sm shadow transition-all">
                        Enregistrer le Contenu
                    </button>
                </form>
            </div>
        </div>

        <!-- Liste des Textes Enregistrés -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900">Dictionnaires & Textes Configurés (<?php echo count($texts); ?>)</h2>
                </div>

                <div class="divide-y divide-slate-100">
                    <?php if (empty($texts)): ?>
                        <div class="p-8 text-center text-xs text-slate-400">
                            Aucun texte spécifique enregistré.
                        </div>
                    <?php else: ?>
                        <?php foreach ($texts as $t): ?>
                            <div class="p-6 hover:bg-slate-50/80">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <span class="font-mono text-xs text-emerald-800 font-bold px-2 py-0.5 bg-emerald-50 rounded-lg">
                                            <?php echo htmlspecialchars($t['text_key']); ?>
                                        </span>
                                        <h3 class="text-sm font-bold text-slate-900 mt-1"><?php echo htmlspecialchars($t['title'] ?: $t['text_key']); ?></h3>
                                    </div>
                                    <button onclick="editText(<?php echo htmlspecialchars(json_encode($t)); ?>)" class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg hover:bg-emerald-100">
                                        Éditer
                                    </button>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-700 font-mono mt-2">
                                    <?php echo nl2br(htmlspecialchars($t['content'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editText(textObj) {
    document.getElementById('text_key').value = textObj.text_key;
    document.getElementById('text_title').value = textObj.title || '';
    document.getElementById('text_content').value = textObj.content || '';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
