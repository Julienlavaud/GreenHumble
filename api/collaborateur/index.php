<?php
/**
 * Cockpit commercial collaborateur GreenHumble.
 * Les scans restent des signaux de qualification à vérifier humainement.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$role = get_current_role();
if (!in_array($role, ['pipeline', 'collaborator', 'consultant', 'admin'], true)) {
    header('Location: /api/login.php');
    exit;
}

function gh_collab_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$storageFile = dirname(__DIR__, 2) . '/data/stored_audits.json';
$audits = [];
if (is_file($storageFile)) {
    $decoded = json_decode((string)@file_get_contents($storageFile), true);
    if (is_array($decoded)) $audits = array_slice($decoded, 0, 50);
}
$total = count($audits);
$recentLeads = [];
try {
    $db = get_db_connection();
    $stmt = $db->prepare(
        "SELECT c.name AS company_name, c.domain, cc.name AS contact_name, cc.email, cc.phone, cc.marketing_opt_in, cc.created_at
         FROM cl_company_contacts cc
         INNER JOIN cl_companies c ON c.id = cc.company_id
         WHERE cc.source = ?
         ORDER BY cc.created_at DESC
         LIMIT 30"
    );
    $stmt->execute(['scan_public_diagnostic_request']);
    $recentLeads = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('GreenHumble: lecture des demandes commerciales impossible (' . get_class($e) . ').');
}
$page_title = 'Cockpit commercial GreenHumble';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="mx-auto max-w-7xl px-4 py-10 text-slate-100">
  <section class="rounded-3xl border border-white/10 bg-[#1E1E1E] p-6 sm:p-9">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-400">GreenHumble · Espace de travail</p>
        <h1 class="mt-2 text-3xl font-bold">Cockpit commercial</h1>
        <p class="mt-2 text-slate-300">Bonjour<?= !empty($_SESSION['user_name']) ? ', ' . gh_collab_h($_SESSION['user_name']) : '' ?>. Qualifiez les signaux, puis préparez une proposition adaptée à l’entreprise.</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <a class="rounded-xl bg-emerald-700 px-4 py-3 font-semibold hover:bg-emerald-600" href="/api/index.php">+ Lancer un scan</a>
        <?php if ($role === 'admin'): ?><a class="rounded-xl border border-white/15 px-4 py-3 font-semibold hover:bg-white/5" href="/api/admin/index.php">Administration</a><?php endif; ?>
        <a class="rounded-xl border border-white/15 px-4 py-3 font-semibold hover:bg-white/5" href="/api/logout.php">Déconnexion</a>
      </div>
    </div>
    <div class="mt-8 grid gap-4 sm:grid-cols-3">
      <div class="rounded-2xl border border-white/10 bg-[#121212] p-5"><p class="text-sm text-slate-400">Audits récents disponibles</p><p class="mt-2 text-3xl font-bold"><?= (int)$total ?></p><p class="mt-1 text-xs text-slate-500">50 maximum affichés</p></div>
      <div class="rounded-2xl border border-white/10 bg-[#121212] p-5"><p class="text-sm text-slate-400">Méthode de qualification</p><p class="mt-2 text-lg font-semibold">Preuve → risque → besoin</p><p class="mt-1 text-xs text-slate-500">Pas de conclusion juridique automatique</p></div>
      <div class="rounded-2xl border border-white/10 bg-[#121212] p-5"><p class="text-sm text-slate-400">Prochaine action</p><p class="mt-2 text-lg font-semibold">Vérifier les justificatifs</p><p class="mt-1 text-xs text-slate-500">Puis valider la recommandation commerciale</p></div>
    </div>
  </section>

  <section class="mt-8">
    <div class="mb-4">
      <h2 class="text-2xl font-bold">Demandes de diagnostic public</h2>
      <p class="mt-1 text-sm text-slate-400">Contacts transmis volontairement via le formulaire après le scan. L’accord marketing reste distinct et facultatif.</p>
    </div>
    <?php if (!$recentLeads): ?>
      <div class="rounded-2xl border border-dashed border-white/20 bg-[#1E1E1E] p-6 text-sm text-slate-400">Aucune demande de diagnostic enregistrée pour le moment.</div>
    <?php else: ?>
      <div class="grid gap-3">
      <?php foreach ($recentLeads as $lead): ?>
        <article class="rounded-2xl border border-white/10 bg-[#1E1E1E] p-4 sm:p-5">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <h3 class="font-bold"><?= gh_collab_h($lead['company_name'] ?? 'Entreprise') ?></h3>
              <p class="mt-1 text-sm text-slate-300"><?= gh_collab_h($lead['contact_name'] ?? '') ?></p>
              <p class="text-sm text-slate-400"><?= gh_collab_h($lead['email'] ?? '') ?><?= !empty($lead['phone']) ? ' · ' . gh_collab_h($lead['phone']) : '' ?></p>
              <p class="mt-1 break-all text-xs text-emerald-400"><?= gh_collab_h($lead['domain'] ?? '') ?></p>
            </div>
            <span class="rounded-full border border-white/15 px-3 py-1 text-xs <?= !empty($lead['marketing_opt_in']) ? 'text-emerald-300' : 'text-slate-400' ?>">
              <?= !empty($lead['marketing_opt_in']) ? 'Marketing accepté' : 'Diagnostic uniquement' ?>
            </span>
          </div>
          <p class="mt-2 text-xs text-slate-500"><?= gh_collab_h($lead['created_at'] ?? '') ?></p>
        </article>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="mt-8">
    <div class="mb-4 flex items-end justify-between gap-4">
      <div><h2 class="text-2xl font-bold">Derniers audits</h2><p class="mt-1 text-sm text-slate-400">Les données affichées proviennent des audits enregistrés sur ce serveur.</p></div>
      <a class="text-sm font-semibold text-emerald-400 underline" href="/api/index.php">Nouvelle analyse</a>
    </div>
    <?php if (!$audits): ?>
      <div class="rounded-2xl border border-dashed border-white/20 bg-[#1E1E1E] p-10 text-center">
        <h3 class="text-lg font-semibold">Aucun audit enregistré pour le moment</h3>
        <p class="mx-auto mt-2 max-w-xl text-sm text-slate-400">Lancez un scan de démonstration sur un site autorisé. Une fois l’analyse validée et sauvegardée, elle apparaîtra ici.</p>
        <a class="mt-5 inline-block rounded-xl bg-emerald-700 px-5 py-3 font-semibold" href="/api/index.php">Lancer le premier scan</a>
      </div>
    <?php else: ?>
      <div class="grid gap-4">
      <?php foreach ($audits as $audit):
        if (!is_array($audit)) continue;
        $url = (string)($audit['url'] ?? '');
        $host = parse_url($url, PHP_URL_HOST) ?: $url;
        $claims = is_array($audit['detectedClaims'] ?? null) ? $audit['detectedClaims'] : [];
        $summary = $audit['clientSummary']['riskVerdict'] ?? $audit['clientSummary']['headline'] ?? 'Synthèse non disponible';
        $score = $audit['riskScore'] ?? null;
        $services = $audit['commercialProspectingKit']['recommendedServices'] ?? $audit['commercialProspectingKit']['services'] ?? [];
        if (!is_array($services)) $services = [];
      ?>
        <article class="rounded-2xl border border-white/10 bg-[#1E1E1E] p-5 sm:p-6">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <p class="text-xs uppercase tracking-wider text-slate-500"><?= gh_collab_h($audit['analyzedAt'] ?? '') ?></p>
              <h3 class="mt-1 break-words text-xl font-bold"><?= gh_collab_h($audit['companyName'] ?? $host) ?></h3>
              <a class="mt-1 inline-block break-all text-sm text-emerald-400 underline" href="<?= gh_collab_h($url) ?>" target="_blank" rel="noopener noreferrer"><?= gh_collab_h($host) ?></a>
            </div>
            <div class="flex shrink-0 gap-2">
              <span class="rounded-full border border-white/15 px-3 py-1 text-sm"><?= $score !== null && is_numeric($score) ? 'Score ' . gh_collab_h($score) . '/100' : 'Score à vérifier' ?></span>
              <span class="rounded-full bg-amber-500/10 px-3 py-1 text-sm text-amber-300"><?= count($claims) ?> signal(aux)</span>
            </div>
          </div>
          <p class="mt-4 text-sm leading-6 text-slate-300"><?= gh_collab_h($summary) ?></p>
          <?php if ($claims): ?>
            <div class="mt-4"><p class="text-sm font-semibold">Signaux à vérifier</p><ul class="mt-2 grid gap-2 sm:grid-cols-2">
              <?php foreach (array_slice($claims, 0, 4) as $claim):
                $claimText = is_array($claim) ? ($claim['claim'] ?? $claim['text'] ?? $claim['phrase'] ?? 'Signal détecté') : $claim;
              ?><li class="rounded-xl border border-white/10 bg-[#121212] p-3 text-sm text-slate-300"><?= gh_collab_h($claimText) ?></li><?php endforeach; ?>
            </ul></div>
          <?php endif; ?>
          <?php if ($services): ?>
            <div class="mt-4"><p class="text-sm font-semibold">Pistes commerciales proposées par l’analyse</p><ul class="mt-2 flex flex-wrap gap-2">
              <?php foreach (array_slice($services, 0, 3) as $service):
                $serviceName = is_array($service) ? ($service['serviceTitle'] ?? $service['title'] ?? $service['name'] ?? '') : $service;
                if ($serviceName === '') continue;
              ?><li class="rounded-full border border-emerald-700/50 bg-emerald-900/20 px-3 py-1 text-sm text-emerald-200"><?= gh_collab_h($serviceName) ?></li><?php endforeach; ?>
            </ul></div>
          <?php endif; ?>
          <p class="mt-4 border-t border-white/10 pt-3 text-xs text-slate-500">À confirmer par un collaborateur avant toute conclusion juridique ou proposition client.</p>
        </article>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
