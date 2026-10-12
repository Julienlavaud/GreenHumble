<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/app_state.php';
require_once __DIR__ . '/../includes/gemini.php';
require_once __DIR__ . '/../includes/groq.php';
require_once __DIR__ . '/../includes/mistral.php';
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || ($_SESSION['role'] ?? '') !== 'admin') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Session administrateur requise.']);
        exit;
    }
    header('Location: /api/login.php?next=' . rawurlencode('/api/admin/ai_providers.php'), true, 303);
    exit;
}
$defs = [
    'gemini' => [
        'label' => 'Google Gemini',
        'model' => 'gemini-flash-latest',
        'models' => [
            'gemini-flash-latest' => 'Gemini Flash — dernière version',
            'gemini-flash-lite-latest' => 'Gemini Flash-Lite — rapide et économique',
            'gemini-pro-latest' => 'Gemini Pro — raisonnement avancé',
        ],
        'key' => 'geminiApiKey'
    ],
    'mistral' => [
        'label' => 'Mistral AI',
        'model' => 'mistral-large-latest',
        'models' => [
            'mistral-large-latest' => 'Mistral Large — dernière version',
            'mistral-small-latest' => 'Mistral Small — rapide et économique',
        ],
        'key' => 'mistralApiKey'
    ],
    'groq' => [
        'label' => 'Groq',
        'model' => 'openai/gpt-oss-120b',
        'models' => [
            'openai/gpt-oss-120b' => 'GPT-OSS 120B — qualité maximale',
            'openai/gpt-oss-20b' => 'GPT-OSS 20B — rapide et économique',
            'qwen/qwen3.8-27b' => 'Qwen 3.8 27B',
        ],
        'key' => 'groqApiKey'
    ],
];
$state = load_app_state();
if (!isset($state['scanVars']) || !is_array($state['scanVars'])) $state['scanVars'] = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $in = json_decode(file_get_contents('php://input'), true);
    if (!is_array($in)) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'JSON invalide']); exit; }
    $provider = strtolower((string)($in['provider'] ?? ''));
    $action = $in['action'] ?? 'save';
    if ($action === 'default') {
        if (!isset($defs[$provider])) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Fournisseur invalide']); exit; }
        $state['scanVars']['aiProvider'] = $provider;
        if (!save_app_state($state)) { http_response_code(500); echo json_encode(['success' => false, 'error' => 'Sauvegarde impossible']); exit; }
        echo json_encode(['success' => true, 'message' => 'Fournisseur principal enregistré.']); exit;
    }
    if (!isset($defs[$provider])) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Fournisseur invalide']); exit; }
    if ($action === 'test') {
        $key = trim((string)($state['scanVars'][$defs[$provider]['key']] ?? ''));
        if ($key === '') { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Enregistrez d’abord une clé API.']); exit; }
        $model = trim((string)($in['model'] ?? ($state['scanVars'][$provider . 'Model'] ?? $defs[$provider]['model'])));
        if (!array_key_exists($model, $defs[$provider]['models'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Modèle non disponible. Choisis un modèle proposé et réessaie.']);
            exit;
        }
        if ($provider === 'gemini') $reply = call_gemini($model, ['contents' => [['parts' => [['text' => 'Réponds uniquement OK']]]], 'generationConfig' => ['temperature' => 0]], $key);
        elseif ($provider === 'mistral') $reply = call_mistral(['model' => $model, 'messages' => [['role' => 'user', 'content' => 'Réponds uniquement OK']], 'temperature' => 0], $key);
        else $reply = call_groq(['model' => $model, 'messages' => [['role' => 'user', 'content' => 'Réponds uniquement OK']], 'temperature' => 0], $key);
        $ok = is_string($reply) && trim($reply) !== '' && stripos($reply, 'Erreur') !== 0 && stripos($reply, 'HTTP ') === false;
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Connexion fournisseur réussie.']);
            exit;
        }

        // Keep provider diagnostics useful while never returning request headers, keys, or raw bodies.
        $replyText = is_string($reply) ? $reply : '';
        $httpStatus = 0;
        if (preg_match('/HTTP\\s*([0-9]{3})/i', $replyText, $matches)) $httpStatus = (int)$matches[1];
        $diagnosticCode = 'provider_error';
        if (in_array($httpStatus, [401, 403], true)) {
            $diagnosticCode = 'provider_key_rejected';
            $message = 'Le fournisseur a refusé l’authentification. Vérifie la clé enregistrée et les droits du projet.';
        } elseif ($httpStatus === 404) {
            $diagnosticCode = 'provider_model_unavailable';
            $message = 'Le modèle ou l’endpoint est indisponible pour ce fournisseur. Vérifie le modèle sélectionné.';
        } elseif ($httpStatus === 429) {
            $diagnosticCode = 'provider_quota';
            $message = 'Le fournisseur a atteint une limite de quota ou de débit. Vérifie les quotas et réessaie plus tard.';
        } elseif ($httpStatus >= 500) {
            $diagnosticCode = 'provider_unavailable';
            $message = 'Le fournisseur rencontre une erreur temporaire. Réessaie dans quelques instants.';
        } elseif ($httpStatus === 0 && preg_match('/timeout|timed out|resolve|connect|SSL|network/i', $replyText)) {
            $diagnosticCode = 'server_network_error';
            $message = 'Le serveur OVH n’a pas pu joindre le fournisseur. Vérifie les connexions sortantes et le certificat TLS.';
        } elseif ($httpStatus === 400) {
            $diagnosticCode = 'provider_request_rejected';
            $message = 'Le fournisseur a refusé la requête. Vérifie le modèle et sa disponibilité pour cette clé.';
        } else {
            $message = 'Le fournisseur n’a pas répondu correctement. Vérifie la configuration et les journaux PHP côté OVH.';
        }
        error_log('GreenHumble provider test failed: ' . $provider . '; status=' . $httpStatus . '; code=' . $diagnosticCode . '; key omitted');
        http_response_code(502);
        echo json_encode(['success' => false, 'diagnosticCode' => $diagnosticCode, 'message' => $message]);
        exit;
    }
    $model = trim((string)($in['model'] ?? $defs[$provider]['model']));
    if (!array_key_exists($model, $defs[$provider]['models'])) { http_response_code(400); echo json_encode(['success' => false, 'error' => 'Modèle non autorisé']); exit; }
    $newKey = trim((string)($in['apiKey'] ?? ''));
    $state['scanVars'][$provider . 'Model'] = $model;
    if ($newKey !== '') $state['scanVars'][$defs[$provider]['key']] = $newKey;
    if (!save_app_state($state)) { http_response_code(500); echo json_encode(['success' => false, 'error' => 'Sauvegarde impossible']); exit; }
    echo json_encode(['success' => true, 'message' => 'Configuration sauvegardée. La clé ne sera pas renvoyée.', 'configured' => !empty($state['scanVars'][$defs[$provider]['key']])]); exit;
}
$state = load_app_state();
$vars = $state['scanVars'] ?? [];
$active = $vars['aiProvider'] ?? 'gemini';
$page_title = 'Fournisseurs IA';
$activeTab = 'ai_providers';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="gh-workspace gh-provider-page">
  <div class="gh-provider-top"><div><p class="gh-eyebrow">Configuration système</p><h1>Fournisseurs d’intelligence artificielle</h1><p>Configurez séparément les clés, modèles et fournisseur principal.</p></div><span class="gh-provider-badge">Console administrateur</span></div>
  <div class="gh-provider-layout"><section>
    <?php foreach ($defs as $p => $d): $configured = !empty($vars[$d['key']]); ?>
    <article class="gh-provider-card"><div class="gh-provider-card__head"><div><h2><?= htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($p === 'gemini' ? 'Analyse Google' : ($p === 'mistral' ? 'Modèles Mistral AI' : 'Inférence Groq'), ENT_QUOTES, 'UTF-8') ?></p></div><span class="gh-provider-status <?= $configured ? 'is-configured' : '' ?>"><?= $configured ? 'Clé configurée' : 'À configurer' ?></span></div>
      <div class="gh-provider-fields"><div class="gh-provider-field"><label for="key-<?= $p ?>">Clé API</label><input id="key-<?= $p ?>" type="password" autocomplete="new-password" placeholder="<?= $configured ? 'Clé enregistrée — saisir pour remplacer' : 'Collez votre clé API' ?>"><small>La clé enregistrée n’est jamais affichée.</small></div>
      <div class="gh-provider-field"><label for="model-<?= $p ?>">Modèle</label><?php $configuredModel = (string)($vars[$p . 'Model'] ?? ''); $modelOutdated = $configuredModel !== '' && !array_key_exists($configuredModel, $d['models']); $selectedModel = $modelOutdated ? $d['model'] : ($configuredModel ?: $d['model']); ?><select id="model-<?= $p ?>"><?php foreach ($d['models'] as $modelId => $modelLabel): ?><option value="<?= htmlspecialchars($modelId, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedModel === $modelId ? 'selected' : '' ?>><?= htmlspecialchars($modelLabel, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select><?php if ($modelOutdated): ?><small class="text-amber-700">Le modèle enregistré (<?= htmlspecialchars($configuredModel, ENT_QUOTES, 'UTF-8') ?>) n’est plus proposé. Le modèle actuel sera utilisé après enregistrement.</small><?php endif; ?></div></div>
      <div class="gh-provider-actions"><button class="gh-button gh-button--primary" onclick="saveProvider('<?= $p ?>')">Enregistrer</button><button class="gh-button gh-button--secondary" onclick="testProvider('<?= $p ?>')">Tester la connexion</button></div><div class="gh-provider-result" id="result-<?= $p ?>" role="status" aria-live="polite"></div>
    </article><?php endforeach; ?>
    <article class="gh-provider-card"><h2>Fournisseur utilisé par défaut</h2><p>Ce fournisseur sera utilisé pour les nouveaux audits.</p><div class="gh-provider-field"><label for="active-provider">Fournisseur principal</label><select id="active-provider"><?php foreach ($defs as $p => $d): ?><option value="<?= $p ?>" <?= $active === $p ? 'selected' : '' ?>><?= htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div><div class="gh-provider-actions"><button class="gh-button gh-button--primary" onclick="saveDefault()">Enregistrer le fournisseur principal</button></div><div class="gh-provider-result" id="result-default" role="status" aria-live="polite"></div></article>
  </section><aside><article class="gh-provider-panel"><h3>État des fournisseurs</h3><?php foreach ($defs as $p => $d): ?><p><strong><?= htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8') ?></strong><br><span class="gh-provider-status <?= !empty($vars[$d['key']]) ? 'is-configured' : '' ?>"><?= !empty($vars[$d['key']]) ? 'Clé présente' : 'Clé absente' ?></span></p><?php endforeach; ?></article>
  <article class="gh-provider-panel"><h3>Sécurité</h3><p>Les clés ne sont jamais renvoyées au navigateur après sauvegarde. Ne les partagez pas par e-mail.</p></article><article class="gh-provider-panel"><h3>À savoir</h3><p>Un test réussi vérifie l’accès fournisseur, pas l’exactitude juridique d’un audit.</p></article></aside></div>
  <p class="gh-provider-note">Configuration enregistrée dans l’état applicatif serveur.</p>
</main>
<script>
async function api(payload) {
  const response = await fetch('/api/admin/ai_providers.php', {method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(payload)});
  let data;
  try { data = await response.json(); } catch (_) { throw new Error('Réponse serveur invalide.'); }
  if (!response.ok || !data.success) throw new Error(data.error || data.message || 'Erreur serveur');
  return data;
}
async function saveProvider(provider) {
  const el = document.getElementById('result-' + provider); el.textContent = 'Enregistrement…';
  try { const data = await api({action:'save',provider,apiKey:document.getElementById('key-' + provider).value,model:document.getElementById('model-' + provider).value}); document.getElementById('key-' + provider).value = ''; el.textContent = data.message; setTimeout(() => location.reload(), 700); }
  catch (error) { el.textContent = error.message; }
}
async function testProvider(provider) {
  const el = document.getElementById('result-' + provider); el.textContent = 'Test en cours…';
  try { const data = await api({action:'test',provider,model:document.getElementById('model-' + provider).value}); el.textContent = data.message; el.dataset.state = data.success ? 'success' : 'error'; }
  catch (error) { el.textContent = error.message; el.dataset.state = 'error'; }
}
async function saveDefault() {
  const el = document.getElementById('result-default'); el.textContent = 'Enregistrement…';
  try { const data = await api({action:'default',provider:document.getElementById('active-provider').value}); el.textContent = data.message; el.dataset.state = 'success'; }
  catch (error) { el.textContent = error.message; el.dataset.state = 'error'; }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
