<?php
/**
 * Espace collaborateur GreenHumble — première version sécurisée.
 * Les fonctions métier détaillées seront intégrées après validation du parcours.
 */
require_once __DIR__ . '/../includes/auth.php';

$role = get_current_role();
if ($role === 'public') {
    header('Location: /api/login.php');
    exit;
}
if (!in_array($role, ['pipeline', 'collaborator', 'consultant', 'admin'], true)) {
    http_response_code(403);
    exit('Accès refusé.');
}
$page_title = 'Espace collaborateur';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="mx-auto max-w-5xl px-4 py-12 text-slate-100">
  <section class="rounded-3xl border border-white/10 bg-[#1E1E1E] p-8 sm:p-10">
    <p class="text-sm font-semibold uppercase tracking-widest text-emerald-400">GreenHumble · Espace de travail</p>
    <h1 class="mt-3 text-3xl font-bold">Bonjour<?php echo !empty($_SESSION['user_name']) ? ', ' . htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8') : ''; ?>.</h1>
    <p class="mt-3 max-w-2xl text-slate-300">Retrouvez ici les accès utiles pour vos premières analyses. Les résultats automatisés sont des signaux à vérifier, pas des conclusions juridiques.</p>
    <div class="mt-8 grid gap-4 sm:grid-cols-2">
      <a class="rounded-2xl border border-white/10 bg-[#121212] p-6 transition hover:border-emerald-500" href="/api/index.php">
        <h2 class="text-lg font-semibold">Lancer un scan public</h2>
        <p class="mt-2 text-sm text-slate-400">Accéder au scanner GreenHumble et analyser une URL.</p>
        <span class="mt-4 inline-block text-sm font-semibold text-emerald-400">Ouvrir le scanner →</span>
      </a>
      <?php if ($role === 'admin'): ?>
      <a class="rounded-2xl border border-white/10 bg-[#121212] p-6 transition hover:border-emerald-500" href="/api/admin/index.php">
        <h2 class="text-lg font-semibold">Console d’administration</h2>
        <p class="mt-2 text-sm text-slate-400">Accès réservé aux administrateurs.</p>
        <span class="mt-4 inline-block text-sm font-semibold text-emerald-400">Ouvrir la console →</span>
      </a>
      <?php endif; ?>
    </div>
    <div class="mt-8 border-t border-white/10 pt-6">
      <a class="text-sm text-slate-400 underline hover:text-white" href="/api/logout.php">Se déconnecter</a>
    </div>
  </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
