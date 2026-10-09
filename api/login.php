<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/collaborator_auth.php';
$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $identifier = trim((string)($_POST['identifier'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $user = ($identifier !== '' && $password !== '') ? authenticate_user($identifier, $password) : false;
    if (!$user && $identifier !== '' && $password !== '') $user = gh_authenticate_collaborator($identifier, $password);
    if ($user) {
        $destination = (($user['role'] ?? '') === 'admin') ? '/api/admin/index.php' : '/api/collaborateur/index.php';
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
