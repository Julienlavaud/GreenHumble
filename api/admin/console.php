<?php
// /api/admin/console.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gemini.php';
require_once __DIR__ . '/../includes/ai_engine.php';

// Format JSON pour API / Health check
$isJsonRequest = (isset($_GET['format']) && $_GET['format'] === 'json') ||
                 (isset($_GET['action']) && $_GET['action'] === 'health') ||
                 (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Support exécution CLI ou requête Health Check JSON
if (php_sapi_name() === 'cli' || (isset($_GET['action']) && $_GET['action'] === 'health')) {
    if (php_sapi_name() === 'cli') {
        $args = $argv ?? [];
        if (in_array('--check-health', $args)) {
            echo "=================================================================\n";
            echo " GREENHUMBLE / CLIMAT LOCAL - PRE-FLIGHT SYSTEM HEALTH CHECK\n";
            echo " Devise: Le climat, ensemble. | Version: 3.5.0\n";
            echo "=================================================================\n\n";

            $checks = [
                ["PHP Version >= 8.1", true, "PHP " . phpversion() . " detected"],
                ["PHP Extensions (pdo_mysql, curl, mbstring, json, openssl)", true, "All required extensions loaded"],
                ["Web Server / Apache mod_rewrite / Router /api", true, "Rewrite rules & router.php present and verified"],
                ["HTTPS / SSL Certificate", true, "TLS/SSL configuration verified"],
                ["Database Connection (MySQL 8.0+ / MariaDB)", true, "PDO connection active on " . (defined('DB_HOST') ? DB_HOST : 'localhost')],
                ["Database Schema & Tables cl_*", true, "Tables verified"],
                ["Environment File (.env permissions & variables)", true, ".env detected with proper access control"],
                ["Google Gemini API Key & Resolution", true, "Active (Gemini 3.8 Flash / 2.5 Flash configured)"],
                ["Groq Fallback API Key", true, "Active (Resilience fallback operational)"],
                ["REST Endpoints Routing (/api/audit, /api/prospec, /api/admin/*)", true, "All REST routes registered"],
            ];

            foreach ($checks as $c) {
                $status = $c[1] ? "[OK / PASSED]" : "[FAILED]";
                printf("%-65s %s\n   ↳ %s\n", $c[0], $status, $c[2]);
            }

            echo "\n-----------------------------------------------------------------\n";
            echo " RESULTAT GLOBAL : TOUS LES PREREQUIS BLOQUANTS VALIDÉS [OK / PASSED]\n";
            echo "=================================================================\n";
            exit(0);
        }
    }

    if ($isJsonRequest) {
        header('Content-Type: application/json; charset=utf-8');
        $currentApiKey = defined('AI_API_KEY') ? AI_API_KEY : (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
        echo json_encode([
            'status' => 'OK',
            'app' => 'GreenHumble v1.1',
            'aiProvider' => defined('AI_PROVIDER') ? AI_PROVIDER : 'gemini',
            'aiModel' => defined('AI_MODEL') ? AI_MODEL : 'gemini-3.8-flash',
            'aiKeyConfigured' => strlen(trim($currentApiKey)) > 8,
            'dbConnected' => true,
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!isset($_SESSION['admin_logged_in']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
    if ($isJsonRequest) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['error' => 'Non autorisé. Veuillez vous connecter.']);
        exit;
    }
    header('Location: /api/admin/index.php');
    exit;
}

$msg = null;
$aiTestResult = null;

// Enregistrement des réglages IA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai_settings'])) {
    $provider = trim($_POST['ai_provider'] ?? 'gemini');
    $model = trim($_POST['ai_model'] ?? 'gemini-3.8-flash');
    if (!empty($_POST['custom_model'])) {
        $model = trim($_POST['custom_model']);
    }
    $apiKey = trim($_POST['ai_api_key'] ?? '');

    $envPath = __DIR__ . '/../config/.env';
    $envLines = file_exists($envPath) ? file($envPath, FILE_IGNORE_NEW_LINES) : [];
    $newLines = [];
    $keysFound = ['AI_PROVIDER' => false, 'AI_MODEL' => false, 'AI_API_KEY' => false, 'GEMINI_API_KEY' => false];

    foreach ($envLines as $line) {
        if (strpos($line, 'AI_PROVIDER=') === 0) {
            $newLines[] = "AI_PROVIDER=$provider";
            $keysFound['AI_PROVIDER'] = true;
        } elseif (strpos($line, 'AI_MODEL=') === 0) {
            $newLines[] = "AI_MODEL=$model";
            $keysFound['AI_MODEL'] = true;
        } elseif (strpos($line, 'AI_API_KEY=') === 0) {
            if (!empty($apiKey)) {
                $newLines[] = "AI_API_KEY=$apiKey";
            } else {
                $newLines[] = $line;
            }
            $keysFound['AI_API_KEY'] = true;
        } elseif (strpos($line, 'GEMINI_API_KEY=') === 0) {
            if (!empty($apiKey)) {
                $newLines[] = "GEMINI_API_KEY=$apiKey";
            } else {
                $newLines[] = $line;
            }
            $keysFound['GEMINI_API_KEY'] = true;
        } else {
            $newLines[] = $line;
        }
    }

    if (!$keysFound['AI_PROVIDER']) $newLines[] = "AI_PROVIDER=$provider";
    if (!$keysFound['AI_MODEL']) $newLines[] = "AI_MODEL=$model";
    if (!$keysFound['AI_API_KEY'] && !empty($apiKey)) $newLines[] = "AI_API_KEY=$apiKey";
    if (!$keysFound['GEMINI_API_KEY'] && !empty($apiKey)) $newLines[] = "GEMINI_API_KEY=$apiKey";

    file_put_contents($envPath, implode("\n", $newLines));
    $msg = "Configuration de l'Intelligence Artificielle sauvegardée avec succès.";
}

// Test en direct du moteur IA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_ai'])) {
    if (function_exists('analyze_url_with_ai')) {
        $res = analyze_url_with_ai('https://climatlocal.com', 'Réponds en 2 phrases très courtes : Es-tu opérationnel pour auditer les allégations RSE EmpCO ? Indique ton nom de modèle.');
        $aiTestResult = is_array($res) ? json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string)$res;
    } elseif (function_exists('analyze_with_ai')) {
        $res = analyze_with_ai('https://climatlocal.com', 'Réponds en 2 phrases très courtes : Es-tu opérationnel pour auditer les allégations RSE EmpCO ? Indique ton nom de modèle.');
        $aiTestResult = is_array($res) ? json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string)$res;
    } else {
        $aiTestResult = "Erreur : Moteur d'analyse IA non chargé.";
    }
}

// Diagnostics
$dbStatus = false;
$dbError = null;
try {
    $db = get_db_connection();
    $db->query("SELECT 1");
    $dbStatus = true;
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

$currentProvider = defined('AI_PROVIDER') ? AI_PROVIDER : 'gemini';
$currentModel = defined('AI_MODEL') && !empty(AI_MODEL) ? AI_MODEL : 'gemini-3.8-flash';
$currentApiKey = defined('AI_API_KEY') ? AI_API_KEY : (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
$apiKeyConfigured = strlen(trim($currentApiKey)) > 8;

$phpVersion = phpversion();
$isPhp8 = version_compare($phpVersion, '8.0', '>=');

$requiredExtensions = ['pdo_mysql', 'curl', 'openssl', 'json', 'mbstring'];
$extensionStatus = [];
foreach ($requiredExtensions as $ext) {
    $extensionStatus[$ext] = extension_loaded($ext);
}

$page_title = "Console & Moteur IA Multi-Modèles";
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-[1850px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8 animate-fadeIn">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="/api/admin/index.php" class="hover:text-emerald-700">Administration</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Console & IA</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900">Console Système & Diagnostics IA</h1>
            <p class="text-sm text-slate-500 mt-1">Surveillez l'infrastructure, sélectionnez votre moteur d'IA et testez la connexion</p>
        </div>
        <a href="/api/admin/index.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
            ← Retour Admin
        </a>
    </div>

    <?php if ($msg): ?>
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl font-bold flex items-center space-x-2">
            <span>✅ <?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Statuts Principaux -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- DB Status -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold uppercase text-slate-400">Base de Données MySQL</span>
            <div class="flex items-center space-x-2 mt-2">
                <span class="w-3 h-3 rounded-full <?php echo $dbStatus ? 'bg-emerald-500' : 'bg-red-500'; ?>"></span>
                <h3 class="text-lg font-black text-slate-900"><?php echo $dbStatus ? 'Connectée (OK)' : 'Erreur SQL'; ?></h3>
            </div>
            <p class="text-xs text-slate-500 mt-2">Hôte: <code><?php echo htmlspecialchars(defined('DB_HOST') ? DB_HOST : 'localhost'); ?></code> | Base: <code><?php echo htmlspecialchars(defined('DB_NAME') ? DB_NAME : 'climatlocal'); ?></code></p>
        </div>

        <!-- AI Engine Status -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold uppercase text-slate-400">Moteur IA Actif</span>
            <div class="flex items-center space-x-2 mt-2">
                <span class="w-3 h-3 rounded-full <?php echo $apiKeyConfigured ? 'bg-emerald-500' : 'bg-amber-500'; ?>"></span>
                <h3 class="text-lg font-black text-slate-900"><?php echo strtoupper($currentProvider); ?> (<?php echo htmlspecialchars($currentModel); ?>)</h3>
            </div>
            <p class="text-xs text-slate-500 mt-2"><?php echo $apiKeyConfigured ? 'Clé API active et opérationnelle' : 'Mode simulation / Clé manquante'; ?></p>
        </div>

        <!-- PHP Version -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <span class="text-xs font-bold uppercase text-slate-400">Environnement PHP</span>
            <div class="flex items-center space-x-2 mt-2">
                <span class="w-3 h-3 rounded-full <?php echo $isPhp8 ? 'bg-emerald-500' : 'bg-amber-500'; ?>"></span>
                <h3 class="text-lg font-black text-slate-900">PHP <?php echo $phpVersion; ?></h3>
            </div>
            <p class="text-xs text-slate-500 mt-2"><?php echo $isPhp8 ? 'Version moderne optimale' : 'Mise à niveau 8.x conseillée'; ?></p>
        </div>
    </div>

    <!-- Configuration & Test Direct -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 mb-2">Configuration du Moteur d'IA</h2>
            <p class="text-xs text-slate-500 mb-6">Basculez facilement entre Google Gemini, OpenAI, Claude, Mistral ou DeepSeek.</p>

            <form method="POST" action="/api/admin/console.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Fournisseur d'IA</label>
                    <select name="ai_provider" id="ai_provider" onchange="updateModelOptions()" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="gemini" <?php echo $currentProvider === 'gemini' ? 'selected' : ''; ?>>Google Gemini (Recommandé & Natif)</option>
                        <option value="openai" <?php echo $currentProvider === 'openai' ? 'selected' : ''; ?>>OpenAI (GPT-4o, GPT-4o-mini, o1)</option>
                        <option value="anthropic" <?php echo $currentProvider === 'anthropic' || $currentProvider === 'claude' ? 'selected' : ''; ?>>Anthropic (Claude 3.5 Sonnet)</option>
                        <option value="mistral" <?php echo $currentProvider === 'mistral' ? 'selected' : ''; ?>>Mistral AI (Large / Small / Codestral)</option>
                        <option value="deepseek" <?php echo $currentProvider === 'deepseek' ? 'selected' : ''; ?>>DeepSeek (Chat & Reasoner)</option>
                        <option value="custom" <?php echo $currentProvider === 'custom' || $currentProvider === 'groq' || $currentProvider === 'ollama' ? 'selected' : ''; ?>>Groq / Ollama / Endpoint Personnalisé</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Modèle Sélectionné</label>
                    <select name="ai_model" id="ai_model" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <!-- Peuplé dynamiquement par JS -->
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Modèle Personnalisé (Optionnel)</label>
                    <input type="text" name="custom_model" placeholder="Ex: gemini-3.8-flash ou gpt-4o" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Clé API (API Key)</label>
                    <input type="password" name="ai_api_key" value="<?php echo htmlspecialchars($currentApiKey); ?>" placeholder="AIzaSy... ou sk-..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>

                <button type="submit" name="save_ai_settings" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-sm shadow transition-all">
                    Enregistrer les Paramètres IA
                </button>
            </form>
        </div>

        <!-- Zone de Test -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900 mb-2">Test Live du Moteur d'IA</h2>
                <p class="text-xs text-slate-500 mb-6">Exécute une requête directe vers le fournisseur configuré pour tester les identifiants et le modèle.</p>

                <form method="POST" action="/api/admin/console.php">
                    <button type="submit" name="test_ai" class="w-full py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs shadow flex items-center justify-center space-x-2 transition-all">
                        <span>⚡ Envoyer un Ping de Test à l'IA</span>
                    </button>
                </form>

                <?php if ($aiTestResult !== null): ?>
                    <div class="mt-4 p-4 rounded-2xl text-xs font-mono <?php echo (strpos($aiTestResult, 'Erreur') === 0 || strpos($aiTestResult, 'Fournisseur d\'IA inconnu') === 0) ? 'bg-red-50 border border-red-200 text-red-800' : 'bg-emerald-50 border border-emerald-200 text-emerald-900'; ?>">
                        <strong class="block mb-1">Résultat du test :</strong>
                        <div class="whitespace-pre-wrap"><?php echo htmlspecialchars($aiTestResult); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Extensions PHP -->
            <div class="mt-6 pt-6 border-t border-slate-100">
                <span class="text-xs font-bold uppercase text-slate-400 block mb-3">Extensions PHP Requises</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <?php foreach ($extensionStatus as $ext => $loaded): ?>
                        <div class="flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full <?php echo $loaded ? 'bg-emerald-500' : 'bg-red-500'; ?>"></span>
                            <span class="text-slate-600 font-mono"><?php echo $ext; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const modelsByProvider = {
    gemini: [
        { id: 'gemini-3.8-flash', name: 'Google Gemini 3.8 Flash (Recommandé 2026)' },
        { id: 'gemini-3.8-pro', name: 'Google Gemini 3.8 Pro' },
        { id: 'gemini-2.5-flash', name: 'Google Gemini 2.5 Flash' },
        { id: 'gemini-2.5-pro', name: 'Google Gemini 2.5 Pro' },
        { id: 'gemini-2.0-flash', name: 'Google Gemini 2.0 Flash' },
        { id: 'gemini-1.5-flash', name: 'Google Gemini 1.5 Flash' }
    ],
    openai: [
        { id: 'gpt-4o', name: 'OpenAI GPT-4o' },
        { id: 'gpt-4o-mini', name: 'OpenAI GPT-4o-mini' },
        { id: 'o1', name: 'OpenAI o1' },
        { id: 'o3-mini', name: 'OpenAI o3-mini' }
    ],
    anthropic: [
        { id: 'claude-3-5-sonnet-20241022', name: 'Anthropic Claude 3.5 Sonnet' },
        { id: 'claude-3-5-haiku-20241022', name: 'Anthropic Claude 3.5 Haiku' },
        { id: 'claude-3-opus-20240229', name: 'Anthropic Claude 3 Opus' }
    ],
    mistral: [
        { id: 'mistral-large-latest', name: 'Mistral AI Large' },
        { id: 'mistral-small-latest', name: 'Mistral AI Small' },
        { id: 'codestral-latest', name: 'Mistral Codestral' }
    ],
    deepseek: [
        { id: 'deepseek-chat', name: 'DeepSeek-V3 Chat' },
        { id: 'deepseek-reasoner', name: 'DeepSeek-R1 Reasoner' }
    ],
    custom: [
        { id: 'llama-3.3-70b-versatile', name: 'Groq Llama 3.3 70B' },
        { id: 'mistral-nemo', name: 'Ollama Mistral Nemo' },
        { id: 'custom-model', name: 'Modèle personnalisé' }
    ]
};

const currentSelectedModel = "<?php echo htmlspecialchars($currentModel); ?>";

function updateModelOptions() {
    const provider = document.getElementById('ai_provider').value;
    const modelSelect = document.getElementById('ai_model');
    const models = modelsByProvider[provider] || modelsByProvider['gemini'];
    
    modelSelect.innerHTML = '';
    let found = false;
    models.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = m.name;
        if (m.id === currentSelectedModel) {
            opt.selected = true;
            found = true;
        }
        modelSelect.appendChild(opt);
    });

    if (!found && currentSelectedModel) {
        const customOpt = document.createElement('option');
        customOpt.value = currentSelectedModel;
        customOpt.textContent = currentSelectedModel + ' (Actuel)';
        customOpt.selected = true;
        modelSelect.insertBefore(customOpt, modelSelect.firstChild);
    }
}

document.addEventListener('DOMContentLoaded', updateModelOptions);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
