<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cms.php';
require_once __DIR__ . '/app_state.php';
$state = load_app_state();
$userRole = (string)($_SESSION['role'] ?? '');
$isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['admin_logged_in']);
$isAdmin = $isLoggedIn && $userRole === 'admin';
$isCollaborator = $isLoggedIn && in_array($userRole, ['pipeline', 'collaborator', 'consultant'], true);
$userName = trim((string)($_SESSION['user_name'] ?? $_SESSION['username'] ?? ''));
$page_title = isset($page_title) ? (string)$page_title : 'GreenHumble';
$activeTab = isset($activeTab) ? (string)$activeTab : '';
if ($activeTab === '') {
  $currentPage = basename((string)($_SERVER['PHP_SELF'] ?? ''));
  $activeTab = [
    'dashboard.php' => 'dashboard',
    'widget.php' => 'widgets',
    'pipeline.php' => 'pipeline',
    'scan.php' => 'scans',
    'analyse.php' => 'scans',
    'export.php' => 'exports'
  ][$currentPage] ?? '';
}
$adminGroups = [
  'ACTIVITÉ' => ['dashboard' => 'Tableau de bord', 'pipeline' => 'Pipeline', 'scans' => 'Scans'],
  'DOSSIERS' => ['listes' => 'Listes', 'exports' => 'Exports CSV'],
  'ORGANISATION' => ['agency' => 'Organisation'],
  'INTELLIGENCE ARTIFICIELLE' => ['ai_providers' => 'Fournisseurs IA', 'scanner' => 'Prompts & règles'],
  'RÉGLAGES' => ['missions' => 'Catalogue métier', 'widgets' => 'Configurer les widgets', 'legal' => 'Références', 'texts' => 'Dictionnaire', 'style' => 'Textes du site']
];
$adminDirectLinks = [
  'dashboard' => '/admin/dashboard.php',
  'pipeline' => '/admin/pipeline.php',
  'scans' => '/admin/scan.php',
  'exports' => '/admin/export.php',
  'ai_providers' => '/admin/ai_providers.php',
  'widgets' => '/admin/widget.php'
];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#f5f8f5">
<meta name="csrf-token" content="<?= htmlspecialchars(gh_get_csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
<script>
(function () {
  var meta = document.querySelector('meta[name="csrf-token"]');
  var token = meta ? meta.content : '';
  if (!token) return;
  var nativeFetch = window.fetch;
  window.fetch = function (input, init) {
    init = init || {};
    var method = String(init.method || (input && input.method) || 'GET').toUpperCase();
    if (method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS') {
      var url = typeof input === 'string' ? input : (input && input.url) || '';
      var sameOrigin = url.charAt(0) === '/' && url.charAt(1) !== '/' || url.indexOf(location.origin + '/') === 0;
      if (sameOrigin) {
        var headers = new Headers(init.headers || (input && input.headers) || {});
        if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', token);
        init.headers = headers;
        if (!init.credentials) init.credentials = 'same-origin';
      }
    }
    return nativeFetch.call(this, input, init);
  };
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || !form.method || form.method.toLowerCase() !== 'post' || form.querySelector('input[name="csrf_token"]')) return;
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'csrf_token';
    input.value = token;
    form.appendChild(input);
  }, true);
})();
</script>
<title><?= htmlspecialchars($page_title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> — GreenHumble</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/api/assets/css/tailwind-compiled.css?v=1">
<link rel="stylesheet" href="/api/assets/css/styles.css?v=3">
<link rel="stylesheet" href="/api/assets/css/greenhumble-refresh.css?v=3">
<link rel="stylesheet" href="/api/assets/css/greenhumble-app.css?v=5">
<?php if (basename((string)($_SERVER['PHP_SELF'] ?? '')) === 'analyse.php'): ?><link rel="stylesheet" href="/api/assets/css/greenhumble-analyse.css?v=1"><?php endif; ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='7' fill='%23176b4c'/><text x='7' y='17' fill='white' font-size='14' font-family='Arial' font-weight='bold'>G</text></svg>">
</head>
<body data-gh-role="<?= $isAdmin ? 'admin' : ($isCollaborator ? 'collaborator' : 'public') ?>">
<div class="gh-shell">
  <aside class="gh-sidebar">
    <a class="gh-brand" href="/api/index.php"><span class="gh-brand__mark">G</span><span>GreenHumble<small>Climat Local</small></span></a>
    <div class="gh-sidebar__scroll">
      <p class="gh-sidebar__caption">ESPACE</p>
      <nav class="gh-sidebar__nav" aria-label="Navigation principale">
        <a class="gh-sidebar__link <?= !$isAdmin && !$isCollaborator ? 'is-active' : '' ?>" href="/api/index.php"><span class="gh-sidebar__icon">↗</span><span>Pré-diagnostic</span></a>
        <?php if ($isCollaborator): ?><a class="gh-sidebar__link <?= basename($_SERVER['PHP_SELF']) === 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) === 'collaborateur' ? 'is-active' : '' ?>" href="/api/collaborateur/index.php"><span class="gh-sidebar__icon">⌂</span><span>Mon espace</span></a><?php endif; ?>
        <?php if ($isAdmin): ?><a class="gh-sidebar__link" href="/api/collaborateur/index.php"><span class="gh-sidebar__icon">⌂</span><span>Espace collaborateur</span></a><?php endif; ?>
      </nav>
      <?php if ($isAdmin): ?>
        <p class="gh-sidebar__caption gh-sidebar__caption--spaced">ADMINISTRATION</p>
        <nav class="gh-sidebar__nav gh-sidebar__nav--admin" aria-label="Sections d’administration">
          <?php foreach ($adminGroups as $groupLabel => $groupTabs): ?>
            <div class="gh-sidebar__group">
              <p><?= htmlspecialchars($groupLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
              <?php foreach ($groupTabs as $tabId => $tabLabel): ?>
                <a class="gh-sidebar__sublink <?= $activeTab === $tabId ? 'is-active' : '' ?>" href="<?= htmlspecialchars($adminDirectLinks[$tabId] ?? ('/admin/index.php?tab=' . rawurlencode($tabId)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($tabLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
    </div>
    <div class="gh-sidebar__account">
      <?php if ($isLoggedIn): ?>
        <div class="gh-sidebar__identity"><span class="gh-avatar"><?= htmlspecialchars(strtoupper(substr($userName ?: ($isAdmin ? 'A' : 'C'), 0, 1)), ENT_QUOTES, 'UTF-8') ?></span><span><strong><?= htmlspecialchars($userName ?: ($isAdmin ? 'Administration' : 'Collaborateur'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong><small><?= $isAdmin ? 'Administrateur' : 'Collaborateur' ?></small></span></div>
        <a class="gh-sidebar__logout" href="/api/logout.php">Déconnexion</a>
      <?php else: ?>
        <a class="gh-sidebar__login" href="/api/login.php">Connexion à mon espace <span>→</span></a>
        <p>GreenHumble · Climat Local</p>
      <?php endif; ?>
    </div>
  </aside>
  <div class="gh-main-column">
    <div class="gh-topbar"><span class="gh-topbar__title"><?= htmlspecialchars($page_title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span><span class="gh-topbar__status"><i></i><?= $isLoggedIn ? 'Espace sécurisé' : 'Pré-diagnostic public' ?></span></div>
    <div class="gh-content">
