<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/collaborator_auth.php';
$error = null;
$next = trim((string)($_POST['next'] ?? $_GET['next'] ?? ''));

function gh_login_destination(string $candidate, string $role): ?string {
    $defaults = ['admin' => '/api/dashboard.php', 'collaborator' => '/api/collaborateur/index.php', 'collaborateur' => '/api/collaborateur/index.php', 'pipeline' => '/api/pipeline.php', 'consultant' => '/api/collaborateur/index.php'];
    if (isset($defaults[$candidate])) return $defaults[$candidate];
    if ($candidate === '' || str_contains($candidate, chr(13)) || str_contains($candidate, chr(10)) || str_contains($candidate, chr(92))) return null;
    $parts = parse_url($candidate);
    if (!is_array($parts) || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) return null;
    $path = $parts['path'] ?? '';
    $query = $parts['query'] ?? '';
    $adminOnly = ['/api/admin/index.php', '/api/admin/ai_providers.php'];
    if (in_array($path, $adminOnly, true) && $role !== 'admin') return null;
    if ($path === '/api/audit-report.php') {
        parse_str($query, $params);
        $id = (string)($params['id'] ?? '');
        return preg_match('/^aud_[A-Za-z0-9_-]{8,80}$/', $id) ? $path . '?id=' . rawurlencode($id) : null;
    }
    if ($path === '/api/analyse.php') {
        parse_str($query, $params);
        $id = (string)($params['id'] ?? '');
        $tab = (string)($params['tab'] ?? 'diag');
        $tabs = ['diag', 'missio', 'textes', 'contact', 'kit', 'suivi'];
        if (!ctype_digit($id) || !in_array($tab, $tabs, true)) return null;
        return $path . '?id=' . rawurlencode($id) . '&tab=' . rawurlencode($tab);
    }
    if ($path === '/api/admin/index.php') {
        parse_str($query, $params);
        $tab = (string)($params['tab'] ?? 'scans');
        $tabs = ['audits', 'scans', 'dossiers', 'listes', 'agency', 'team', 'scanner_groups', 'scanner', 'missions', 'legal', 'texts', 'style'];
        return $role === 'admin' && in_array($tab, $tabs, true) ? $path . '?tab=' . rawurlencode($tab) : null;
    }
    $allowed = ['/api/index.php', '/api/dashboard.php', '/api/pipeline.php', '/api/scan.php', '/api/collaborateur/index.php'];
    if (!in_array($path, $allowed, true)) return null;
    return $path;
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $identifier = trim((string)($_POST['identifier'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $user = ($identifier !== '' && $password !== '') ? authenticate_user($identifier, $password) : false;
    if (!$user && $identifier !== '' && $password !== '') $user = gh_authenticate_collaborator($identifier, $password);
    if ($user) {
        $userRole = (string)($user['role'] ?? 'collaborator');
        $destination = gh_login_destination($next, $userRole) ?: (($userRole === 'admin') ? '/api/dashboard.php' : '/api/collaborateur/index.php');
        header('Location: ' . $destination, true, 303);
        exit;
    }
    $error = 'Identifiant ou mot de passe incorrect.';
}
?>
<!doctype html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#f5f8f5">
<title>Connexion — GreenHumble</title>
<link rel="stylesheet" href="/api/assets/css/greenhumble-app.css?v=3">
</head>
<body class="gh-login-page">
<div class="gh-login-layout">
  <aside class="gh-login-sidebar">
    <a class="gh-brand" href="/api/index.php"><span class="gh-brand__mark">G</span><span>GreenHumble<small>Climat Local</small></span></a>
    <div><p class="gh-sidebar__caption">ESPACE PUBLIC</p><a class="gh-sidebar__link" href="/api/index.php"><span class="gh-sidebar__icon">↗</span><span>Pré-diagnostic</span></a></div>
    <div class="gh-login-sidebar__note"><span>GH</span><p>Des signaux à vérifier. Des décisions à éclairer.</p></div>
  </aside>
  <main class="gh-login-main">
    <section class="gh-login-card">
      <p class="gh-eyebrow">Espace professionnel</p>
      <h1>Heureux de vous revoir.</h1>
      <p class="gh-login-intro">Connectez-vous à votre espace collaborateur ou à la console d’administration.</p>
      <?php if ($error !== null): ?><div class="gh-login-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?>
      <form method="post" action="/api/login.php" class="gh-login-form">
        <?php if ($next !== ''): ?><input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?php endif; ?>
        <label for="identifier">E-mail ou identifiant</label>
        <input type="text" id="identifier" name="identifier" required autocomplete="username" autocapitalize="none">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <button type="submit">Se connecter <span aria-hidden="true">→</span></button>
      </form>
      <p class="gh-login-foot">Accès réservé aux personnes autorisées par Climat Local.</p>
    </section>
  </main>
</div>
</body></html>
