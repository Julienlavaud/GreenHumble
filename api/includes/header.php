<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/cms.php';
require_once __DIR__ . '/app_state.php';
$state = load_app_state();
$agency = $state['agency'] ?? [];
$userRole = (string)($_SESSION['role'] ?? '');
$isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['admin_logged_in']);
$isAdmin = $isLoggedIn && $userRole === 'admin';
$isCollaborator = $isLoggedIn && in_array($userRole, ['pipeline', 'collaborator', 'consultant'], true);
$userName = trim((string)($_SESSION['user_name'] ?? $_SESSION['username'] ?? ''));
$page_title = isset($page_title) ? (string)$page_title : 'GreenHumble';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#f5f8f5">
<title><?= htmlspecialchars($page_title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> — GreenHumble</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/api/assets/css/styles.css?v=2">
<link rel="stylesheet" href="/api/assets/css/greenhumble-refresh.css?v=2">
<link rel="stylesheet" href="/api/assets/css/greenhumble-app.css?v=2">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='7' fill='%23176b4c'/><text x='7' y='17' fill='white' font-size='14' font-family='Arial' font-weight='bold'>G</text></svg>">
<script>
tailwind.config={theme:{extend:{colors:{brand:{bg:'#f5f8f5',primary:'#176b4c',primaryHover:'#0e4c37',secondary:'#b7e7c3',text:'#17352c',muted:'#64766e',card:'#fff'}}}}};
</script>
</head>
<body data-gh-role="<?= $isAdmin ? 'admin' : ($isCollaborator ? 'collaborator' : 'public') ?>">
<div class="gh-page flex flex-col flex-grow" data-gh-role="<?= $isAdmin ? 'admin' : ($isCollaborator ? 'collaborator' : 'public') ?>">
<header class="gh-header">
  <div class="gh-header__inner">
    <a class="gh-brand" href="/api/index.php"><span class="gh-brand__mark">G</span><span>GreenHumble<small>Climat Local</small></span></a>
    <nav class="gh-nav" aria-label="Navigation principale">
      <a href="/api/index.php">Pré-diagnostic</a>
      <?php if ($isCollaborator): ?><a class="gh-nav__primary" href="/api/collaborateur/index.php">Mon espace</a><?php endif; ?>
      <?php if ($isAdmin): ?><a class="gh-nav__primary" href="/api/admin/index.php">Administration</a><?php endif; ?>
      <?php if (!$isLoggedIn): ?><a class="gh-nav__primary" href="/api/login.php">Connexion</a>
      <?php else: ?><span class="gh-header__user"><?= htmlspecialchars($userName ?: ($isAdmin ? 'Administration' : 'Collaborateur'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span><a href="/api/logout.php">Déconnexion</a><?php endif; ?>
    </nav>
  </div>
</header>
<div class="flex-grow">
