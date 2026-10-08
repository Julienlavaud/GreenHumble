import fs from 'fs';
import path from 'path';
import crypto from 'crypto';
import JSZip from 'jszip';

async function buildDolibarrPhpPackage() {
  console.log('🚀 [Dolibarr PHP Strict Visual Replica] Génération du package site-php.zip pour OVH...');

  const zip = new JSZip();

  // 1. Dossier conf/
  const confFolder = zip.folder('conf');
  confFolder?.file('conf.php', `<?php
/**
 * GreenHumble v1.1 — Configuration Dolibarr-like pour OVH
 */
$dolibarr_main_url_root = 'https://climatlocal.com';
$dolibarr_main_db_host = 'localhost';
$dolibarr_main_db_name = 'greenhumble';
$dolibarr_main_db_user = 'root';
$dolibarr_main_db_pass = '';
$dolibarr_main_db_prefix = 'gh_';

define('GH_VERSION', '1.1.0');
define('GH_APP_NAME', 'GreenHumble v1.1 - Audit Greenwashing & Directive Green Claims');
?>`);

  // 2. Dossier sql/
  const sqlFolder = zip.folder('sql');
  const schemaSqlPath = path.resolve('schema.sql');
  if (fs.existsSync(schemaSqlPath)) {
    sqlFolder?.file('schema.sql', fs.readFileSync(schemaSqlPath));
  } else {
    sqlFolder?.file('schema.sql', `-- Base de données GreenHumble v1.1\nCREATE TABLE IF NOT EXISTS gh_audits (id VARCHAR(64) PRIMARY KEY, url VARCHAR(255), company_name VARCHAR(255), score INT, data TEXT, created_at DATETIME);`);
  }

  // 3. Dossier modules/ (IA, Collaborateurs, Style, Édition, Sauvegarde)
  const modulesFolder = zip.folder('modules');

  // Module IA
  const modIa = modulesFolder?.folder('ia');
  modIa?.file('index.php', `<?php
require_once __DIR__ . '/../../conf/conf.php';
$settingsFile = __DIR__ . '/../../data/ia_settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [
  'model' => 'gemini-3.8-flash',
  'temperature' => 0.2,
  'max_tokens' => 4096,
  'system_prompt' => 'Expert en audit de greenwashing et conformité directive Green Claims.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['model'] = $_POST['model'] ?? $settings['model'];
    $settings['temperature'] = floatval($_POST['temperature'] ?? $settings['temperature']);
    $settings['system_prompt'] = $_POST['system_prompt'] ?? $settings['system_prompt'];
    if (!file_exists(dirname($settingsFile))) {
        mkdir(dirname($settingsFile), 0755, true);
    }
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $success = "Paramètres du Moteur IA mis à jour avec succès.";
}
?>
<div class="space-y-6">
  <div class="flex items-center justify-between border-b border-white/10 pb-4">
    <div>
      <h2 class="text-xl font-bold text-white flex items-center gap-2 font-serif">
        <span class="w-2.5 h-2.5 rounded-full bg-[#2A7AE2]"></span>
        Moteur IA & Modèles Gemini
      </h2>
      <p class="text-xs text-[#A0A0A0]">Configuration des modèles d'analyse, températures et prompts système Green Claims</p>
    </div>
  </div>
  <?php if (isset($success)): ?>
    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 p-4 rounded-xl text-xs font-semibold"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <form method="POST" class="space-y-4 bg-[#1E1E1E] p-6 rounded-2xl border border-white/10">
    <div>
      <label class="block text-xs font-bold text-[#A0A0A0] uppercase mb-1.5">Modèle d'IA principal</label>
      <select name="model" class="w-full bg-[#121212] border border-white/10 rounded-xl px-4 py-2.5 text-white text-xs focus:outline-none focus:border-[#2A7AE2]">
        <option value="gemini-3.8-flash" <?= $settings['model'] === 'gemini-3.8-flash' ? 'selected' : '' ?>>Gemini 3.8 Flash (Recommandé - Ultra Rapide)</option>
        <option value="gemini-2.5-flash" <?= $settings['model'] === 'gemini-2.5-flash' ? 'selected' : '' ?>>Gemini 2.5 Flash</option>
        <option value="gemini-2.5-pro" <?= $settings['model'] === 'gemini-2.5-pro' ? 'selected' : '' ?>>Gemini 2.5 Pro</option>
        <option value="groq-llama3" <?= $settings['model'] === 'groq-llama3' ? 'selected' : '' ?>>Groq Llama 3 70B</option>
      </select>
    </div>
    <div>
      <label class="block text-xs font-bold text-[#A0A0A0] uppercase mb-1.5">Température (Créativité : <?= $settings['temperature'] ?>)</label>
      <input type="range" name="temperature" min="0" max="1" step="0.05" value="<?= $settings['temperature'] ?>" class="w-full accent-[#2A7AE2]">
    </div>
    <div>
      <label class="block text-xs font-bold text-[#A0A0A0] uppercase mb-1.5">Prompt Système (Directive Green Claims)</label>
      <textarea name="system_prompt" rows="5" class="w-full bg-[#121212] border border-white/10 rounded-xl px-4 py-2.5 text-white text-xs focus:outline-none focus:border-[#2A7AE2] font-mono"><?= htmlspecialchars($settings['system_prompt']) ?></textarea>
    </div>
    <button type="submit" class="px-5 py-2.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold text-xs rounded-xl transition-all shadow-md cursor-pointer">Enregistrer les paramètres IA</button>
  </form>
</div>`);

  // Module Collaborateurs
  const modCollab = modulesFolder?.folder('collaborateurs');
  modCollab?.file('index.php', `<?php
require_once __DIR__ . '/../../conf/conf.php';
$collabFile = __DIR__ . '/../../data/collaborateurs.json';
$collaborateurs = file_exists($collabFile) ? json_decode(file_get_contents($collabFile), true) : [
  ['id' => 1, 'nom' => 'Julien Lavaud', 'email' => 'jul1lavaud@gmail.com', 'role' => 'Super Admin'],
  ['id' => 2, 'nom' => 'Auditeur ESG', 'email' => 'audit@greenhumble.local', 'role' => 'Analyste Senior']
];
?>
<div class="space-y-6">
  <div class="flex items-center justify-between border-b border-white/10 pb-4">
    <div>
      <h2 class="text-xl font-bold text-white flex items-center gap-2 font-serif">
        <span class="w-2.5 h-2.5 rounded-full bg-[#2A7AE2]"></span>
        Collaborateurs & Rôles
      </h2>
      <p class="text-xs text-[#A0A0A0]">Gestion des comptes d'accès, habilitations et rôles de sécurité</p>
    </div>
  </div>
  <div class="bg-[#1E1E1E] rounded-2xl border border-white/10 overflow-hidden">
    <table class="w-full text-left text-xs text-[#E0E0E0]">
      <thead class="bg-[#121212] text-[#A0A0A0] uppercase tracking-wider font-bold">
        <tr>
          <th class="px-6 py-3.5">Nom</th>
          <th class="px-6 py-3.5">Email</th>
          <th class="px-6 py-3.5">Rôle</th>
          <th class="px-6 py-3.5 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-white/10">
        <?php foreach ($collaborateurs as $c): ?>
        <tr class="hover:bg-white/5 transition-colors">
          <td class="px-6 py-4 font-bold text-white"><?= htmlspecialchars($c['nom']) ?></td>
          <td class="px-6 py-4 text-[#A0A0A0]"><?= htmlspecialchars($c['email']) ?></td>
          <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#2A7AE2]/20 text-[#2A7AE2] border border-[#2A7AE2]/30"><?= htmlspecialchars($c['role']) ?></span></td>
          <td class="px-6 py-4 text-right">
            <button class="text-[#A0A0A0] hover:text-white font-bold cursor-pointer">Modifier</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>`);

  // Module Style
  const modStyle = modulesFolder?.folder('style');
  modStyle?.file('index.php', `<?php
require_once __DIR__ . '/../../conf/conf.php';
$styleFile = __DIR__ . '/../../data/style.json';
$style = file_exists($styleFile) ? json_decode(file_get_contents($styleFile), true) : [
  'theme' => 'dark',
  'primary_color' => '#2A7AE2',
  'slogan' => 'Audit Greenwashing & Directive Green Claims'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $style['slogan'] = $_POST['slogan'] ?? $style['slogan'];
    $style['primary_color'] = $_POST['primary_color'] ?? $style['primary_color'];
    if (!file_exists(dirname($styleFile))) {
        mkdir(dirname($styleFile), 0755, true);
    }
    file_put_contents($styleFile, json_encode($style, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $success = "Style et charte graphique mis à jour avec succès.";
}
?>
<div class="space-y-6">
  <div class="flex items-center justify-between border-b border-white/10 pb-4">
    <div>
      <h2 class="text-xl font-bold text-white flex items-center gap-2 font-serif">
        <span class="w-2.5 h-2.5 rounded-full bg-[#2A7AE2]"></span>
        Style & Thème Graphique
      </h2>
      <p class="text-xs text-[#A0A0A0]">Personnalisation des thèmes, couleurs et slogans institutionnels</p>
    </div>
  </div>
  <?php if (isset($success)): ?>
    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 p-4 rounded-xl text-xs font-semibold"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <form method="POST" class="space-y-4 bg-[#1E1E1E] p-6 rounded-2xl border border-white/10">
    <div>
      <label class="block text-xs font-bold text-[#A0A0A0] uppercase mb-1.5">Slogan Principal</label>
      <input type="text" name="slogan" value="<?= htmlspecialchars($style['slogan']) ?>" class="w-full bg-[#121212] border border-white/10 rounded-xl px-4 py-2.5 text-white text-xs focus:outline-none focus:border-[#2A7AE2]">
    </div>
    <div>
      <label class="block text-xs font-bold text-[#A0A0A0] uppercase mb-1.5">Couleur d'accentuation principale</label>
      <div class="flex items-center gap-3">
        <input type="color" name="primary_color" value="<?= htmlspecialchars($style['primary_color']) ?>" class="w-12 h-12 rounded-xl bg-[#121212] border border-white/10 cursor-pointer">
        <span class="font-mono text-xs text-[#E0E0E0]"><?= htmlspecialchars($style['primary_color']) ?></span>
      </div>
    </div>
    <button type="submit" class="px-5 py-2.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold text-xs rounded-xl transition-all shadow-md cursor-pointer">Enregistrer le style</button>
  </form>
</div>`);

  // Module Édition
  const modEdition = modulesFolder?.folder('edition');
  modEdition?.file('index.php', `<?php
require_once __DIR__ . '/../../conf/conf.php';
$textsFile = __DIR__ . '/../../data/texts.json';
$texts = file_exists($textsFile) ? json_decode(file_get_contents($textsFile), true) : [
  'header_title' => 'GreenHumble Enterprise',
  'footer_copy' => '© 2026 GreenHumble — Tous droits réservés'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texts['header_title'] = $_POST['header_title'] ?? $texts['header_title'];
    $texts['footer_copy'] = $_POST['footer_copy'] ?? $texts['footer_copy'];
    if (!file_exists(dirname($textsFile))) {
        mkdir(dirname($textsFile), 0755, true);
    }
    file_put_contents($textsFile, json_encode($texts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $success = "Textes et libellés mis à jour avec succès.";
}
?>
<div class="space-y-6">
  <div class="flex items-center justify-between border-b border-white/10 pb-4">
    <div>
      <h2 class="text-xl font-bold text-white flex items-center gap-2 font-serif">
        <span class="w-2.5 h-2.5 rounded-full bg-[#2A7AE2]"></span>
        Édition des Textes & Libellés
      </h2>
      <p class="text-xs text-[#A0A0A0]">Modification dynamique des accroches et contenus textuels de l'application</p>
    </div>
  </div>
  <?php if (isset($success)): ?>
    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 p-4 rounded-xl text-xs font-semibold"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <form method="POST" class="space-y-4 bg-[#1E1E1E] p-6 rounded-2xl border border-white/10">
    <div>
      <label class="block text-xs font-bold text-[#A0A0A0] uppercase mb-1.5">Titre de l'en-tête</label>
      <input type="text" name="header_title" value="<?= htmlspecialchars($texts['header_title']) ?>" class="w-full bg-[#121212] border border-white/10 rounded-xl px-4 py-2.5 text-white text-xs focus:outline-none focus:border-[#2A7AE2]">
    </div>
    <div>
      <label class="block text-xs font-bold text-[#A0A0A0] uppercase mb-1.5">Mention du pied de page</label>
      <input type="text" name="footer_copy" value="<?= htmlspecialchars($texts['footer_copy']) ?>" class="w-full bg-[#121212] border border-white/10 rounded-xl px-4 py-2.5 text-white text-xs focus:outline-none focus:border-[#2A7AE2]">
    </div>
    <button type="submit" class="px-5 py-2.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold text-xs rounded-xl transition-all shadow-md cursor-pointer">Enregistrer les textes</button>
  </form>
</div>`);

  // Module Sauvegarde
  const modSauvegarde = modulesFolder?.folder('sauvegarde');
  modSauvegarde?.file('index.php', `<?php
require_once __DIR__ . '/../../conf/conf.php';
?>
<div class="space-y-6">
  <div class="flex items-center justify-between border-b border-white/10 pb-4">
    <div>
      <h2 class="text-xl font-bold text-white flex items-center gap-2 font-serif">
        <span class="w-2.5 h-2.5 rounded-full bg-[#2A7AE2]"></span>
        Sauvegarde & Archive Souveraine
      </h2>
      <p class="text-xs text-[#A0A0A0]">Exportation et restauration complète des données de l'application</p>
    </div>
  </div>
  <div class="bg-[#1E1E1E] p-6 rounded-2xl border border-white/10 space-y-4">
    <p class="text-[#E0E0E0] text-xs leading-relaxed">Générez une archive ZIP complète encryptée et structurée de toutes vos données d'audit, projets, listes de travail et paramètres IA pour une portabilité totale sur OVH.</p>
    <a href="?page=sauvegarde&export=1" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold text-xs rounded-xl transition-all shadow-md cursor-pointer">
      Télécharger l'archive de sauvegarde (.zip)
    </a>
  </div>
</div>`);

  // 4. Dossier htdocs/ (Web root avec index.php et rendu visuel 100% identique à la version React)
  const htdocsFolder = zip.folder('htdocs');
  htdocsFolder?.file('index.php', `<?php
require_once __DIR__ . '/../conf/conf.php';

$page = $_GET['page'] ?? 'audit';
$allowed_pages = ['audit', 'scans', 'projets', 'listes', 'groupes', 'ia', 'collaborateurs', 'style', 'edition', 'sauvegarde'];

if (!in_array($page, $allowed_pages)) {
    $page = 'audit';
}

$isAdminModule = in_array($page, ['ia', 'collaborateurs', 'style', 'edition', 'sauvegarde']);
?>
<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= GH_APP_NAME ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #121212; color: #FFFFFF; }
    </style>
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-[#2A7AE2] selection:text-white">
    <!-- En-tête / Header identique à React -->
    <header class="sticky top-0 z-40 bg-[#121212] text-white shadow-md border-b border-white/10 no-print">
      <div class="max-w-[1850px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 sm:h-18">
          <!-- Logo GreenHumble -->
          <div class="flex items-center space-x-3 cursor-pointer" onclick="window.location.href='?page=audit'">
            <div class="w-9 h-9 rounded-xl bg-[#2A7AE2] flex items-center justify-center text-white font-bold shadow-md">
              <span class="text-lg font-black">G</span>
            </div>
            <div>
              <div class="flex items-center space-x-2">
                <span class="text-xl sm:text-2xl font-black tracking-tight text-white font-serif">GreenHumble</span>
                <span class="hidden xl:inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-[#2A7AE2]/20 text-[#2A7AE2] border border-[#2A7AE2]/30 uppercase tracking-widest">admin</span>
              </div>
            </div>
          </div>

          <!-- Onglets Obligatoires du Viewer : 1. Audit, 2. Scans, 3. Projets, 4. Listes de travail, 5. Groupes -->
          <nav class="hidden lg:flex items-center space-x-1 bg-[#1E1E1E] p-1.5 rounded-2xl border border-white/10">
            <a href="?page=audit" class="flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $page === 'audit' ? 'bg-[#2A7AE2] text-white shadow-md' : 'text-[#E0E0E0] hover:text-[#2A7AE2] hover:bg-white/5' ?>">
              <span>1. Audit</span>
            </a>
            <a href="?page=scans" class="flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $page === 'scans' ? 'bg-[#2A7AE2] text-white shadow-md' : 'text-[#E0E0E0] hover:text-[#2A7AE2] hover:bg-white/5' ?>">
              <span>2. Scans</span>
            </a>
            <a href="?page=projets" class="flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $page === 'projets' ? 'bg-[#2A7AE2] text-white shadow-md' : 'text-[#E0E0E0] hover:text-[#2A7AE2] hover:bg-white/5' ?>">
              <span>3. Projets</span>
            </a>
            <a href="?page=listes" class="flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $page === 'listes' ? 'bg-[#2A7AE2] text-white shadow-md' : 'text-[#E0E0E0] hover:text-[#2A7AE2] hover:bg-white/5' ?>">
              <span>4. Listes de travail</span>
            </a>
            <a href="?page=groupes" class="flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $page === 'groupes' ? 'bg-[#2A7AE2] text-white shadow-md' : 'text-[#E0E0E0] hover:text-[#2A7AE2] hover:bg-white/5' ?>">
              <span>5. Groupes</span>
            </a>
          </nav>

          <!-- Actions : Connexion, Déconnexion, Admin -->
          <div class="flex items-center space-x-2.5">
            <a href="?page=ia" class="flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-[#1E1E1E] text-emerald-400 hover:text-white hover:bg-emerald-600/30 border border-emerald-500/40 shadow-sm">
              <span>Admin</span>
            </a>
            <div class="flex items-center space-x-2 pl-2 border-l border-white/10">
              <div class="w-7 h-7 rounded-lg bg-[#2A7AE2] text-white flex items-center justify-center font-bold text-xs">J</div>
              <span class="text-xs font-semibold text-[#E0E0E0] hidden xl:inline">Julien Lavaud</span>
              <button onclick="alert('Déconnexion réussie')" class="flex items-center space-x-1 px-3 py-1.5 text-xs font-bold text-rose-300 hover:text-white bg-[#1E1E1E] hover:bg-rose-950/60 border border-white/10 hover:border-rose-800 rounded-xl transition-colors cursor-pointer">
                <span>Déconnexion</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Sous-menu Admin si module admin actif -->
    <?php if ($isAdminModule): ?>
    <div class="bg-[#181818] border-b border-white/10 py-3">
      <div class="max-w-[1850px] mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-2 overflow-x-auto text-xs">
        <span class="text-[#A0A0A0] font-bold uppercase tracking-wider mr-2">Console Admin :</span>
        <a href="?page=ia" class="px-3 py-1.5 rounded-xl font-bold transition-all <?= $page === 'ia' ? 'bg-[#2A7AE2] text-white' : 'bg-[#1E1E1E] text-[#E0E0E0] hover:bg-white/5' ?>">1. IA</a>
        <a href="?page=collaborateurs" class="px-3 py-1.5 rounded-xl font-bold transition-all <?= $page === 'collaborateurs' ? 'bg-[#2A7AE2] text-white' : 'bg-[#1E1E1E] text-[#E0E0E0] hover:bg-white/5' ?>">2. Collaborateurs</a>
        <a href="?page=style" class="px-3 py-1.5 rounded-xl font-bold transition-all <?= $page === 'style' ? 'bg-[#2A7AE2] text-white' : 'bg-[#1E1E1E] text-[#E0E0E0] hover:bg-white/5' ?>">3. Style</a>
        <a href="?page=edition" class="px-3 py-1.5 rounded-xl font-bold transition-all <?= $page === 'edition' ? 'bg-[#2A7AE2] text-white' : 'bg-[#1E1E1E] text-[#E0E0E0] hover:bg-white/5' ?>">4. Édition</a>
        <a href="?page=sauvegarde" class="px-3 py-1.5 rounded-xl font-bold transition-all <?= $page === 'sauvegarde' ? 'bg-[#2A7AE2] text-white' : 'bg-[#1E1E1E] text-[#E0E0E0] hover:bg-white/5' ?>">5. Sauvegarde</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Corps principal -->
    <main class="flex-1 max-w-[1850px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php
        if ($isAdminModule) {
            include __DIR__ . '/../modules/' . $page . '/index.php';
        } else {
            ?>
            <div class="space-y-6">
                <!-- En-tête de vue -->
                <div class="bg-[#1E1E1E] border border-white/10 p-8 rounded-3xl shadow-xl flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="space-y-2 text-center md:text-left">
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#2A7AE2]/20 text-[#2A7AE2] border border-[#2A7AE2]/30 uppercase tracking-widest">
                            Module Actif : <?= ucfirst($page) ?>
                        </div>
                        <h2 class="text-3xl font-black text-white font-serif">
                            <?php
                            if ($page === 'audit') echo 'Scanner d\'Audit Greenwashing & Directive Green Claims';
                            elseif ($page === 'scans') echo 'Historique et Consultation des Scans';
                            elseif ($page === 'projets') echo 'Gestion des Dossiers Projets & Clients';
                            elseif ($page === 'listes') echo 'Listes de Travail & Cibles Prioritaires';
                            elseif ($page === 'groupes') echo 'Groupes de Scanners Sectoriels';
                            ?>
                        </h2>
                        <p class="text-sm text-[#A0A0A0] max-w-2xl">
                            <?= $page === 'audit' ? 'Analysez une URL ou un rapport RSE pour détecter instantanément les allégations environnementales trompeuses et générer votre kit de prospection commerciale.' : 'Consultez, filtrez et exportez vos données d\'audit conformes aux exigences réglementaires OVH.' ?>
                        </p>
                    </div>
                    <?php if ($page === 'audit'): ?>
                    <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
                        <input type="text" id="audit-url" placeholder="https://entreprise.com..." class="bg-[#121212] border border-white/10 rounded-xl px-4 py-3 text-white text-xs w-full sm:w-80 focus:outline-none focus:border-[#2A7AE2]">
                        <button onclick="alert('Lancement de l\'audit IA en cours sur l\'URL renseignée...')" class="px-6 py-3 bg-[#2A7AE2] hover:bg-[#1D5BBF] text-white font-bold text-xs rounded-xl transition-all shadow-md cursor-pointer whitespace-nowrap">Lancer l'Audit IA</button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Grille de données / Tableau de démonstration visuelle rigoureuse -->
                <div class="bg-[#1E1E1E] border border-white/10 rounded-3xl p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-base font-bold text-white font-serif">Données enregistrées (<?= ucfirst($page) ?>)</h3>
                        <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-3 py-1 rounded-full">Statut : Opérationnel</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-[#E0E0E0]">
                            <thead class="bg-[#121212] text-[#A0A0A0] uppercase tracking-wider font-bold">
                                <tr>
                                    <th class="px-6 py-3.5">Identifiant / Référence</th>
                                    <th class="px-6 py-3.5">Entreprise / Cible</th>
                                    <th class="px-6 py-3.5">Secteur & Effectifs</th>
                                    <th class="px-6 py-3.5">Score / Conformité</th>
                                    <th class="px-6 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-white">#AUD-2026-001</td>
                                    <td class="px-6 py-4 font-bold text-white">EcoClean Solutions SA</td>
                                    <td class="px-6 py-4 text-[#A0A0A0]">Énergie & CleanTech (50-250 sal.)</td>
                                    <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">85 / 100 (Conforme)</span></td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="alert('Consultation du rapport détaillé')" class="px-3 py-1.5 bg-[#2A7AE2] text-white font-bold rounded-lg hover:bg-[#1D5BBF] cursor-pointer">Consulter</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-white">#AUD-2026-002</td>
                                    <td class="px-6 py-4 font-bold text-white">BioPlast France</td>
                                    <td class="px-6 py-4 text-[#A0A0A0]">Emballages Industriels (250-500 sal.)</td>
                                    <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">52 / 100 (Risque Modéré)</span></td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="alert('Consultation du rapport détaillé')" class="px-3 py-1.5 bg-[#2A7AE2] text-white font-bold rounded-lg hover:bg-[#1D5BBF] cursor-pointer">Consulter</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </main>

    <!-- Pied de page -->
    <footer class="bg-[#121212] border-t border-white/10 py-6 text-center text-xs text-[#A0A0A0]">
        <p><?= GH_APP_NAME ?> — Structure Dolibarr PHP pour hébergement OVH (htdocs/, conf/, modules/, sql/)</p>
    </footer>
</body>
</html>`);

  // Root index.php redirection
  zip.file('index.php', `<?php
header('Location: htdocs/index.php');
exit;
?>`);

  // .htaccess à la racine
  zip.file('.htaccess', `Options -Indexes
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/htdocs/
    RewriteRule ^(.*)$ htdocs/$1 [L]
</IfModule>`);

  const buffer = await zip.generateAsync({
    type: 'nodebuffer',
    compression: 'DEFLATE',
    compressionOptions: { level: 9 }
  });

  const outputZipPath = path.resolve('site-php.zip');
  fs.writeFileSync(outputZipPath, buffer);
  console.log(`✅ [Strict Visual Replica PHP] Archive générée avec succès : ${outputZipPath} (${Math.round(buffer.length / 1024)} Ko)`);

  const publicDir = path.resolve('public');
  if (!fs.existsSync(publicDir)) {
    fs.mkdirSync(publicDir, { recursive: true });
  }
  const publicZipPath = path.join(publicDir, 'site-php.zip');
  fs.writeFileSync(publicZipPath, buffer);
  console.log(`✅ Copié dans public/ : ${publicZipPath}`);
}

buildDolibarrPhpPackage().catch(err => {
  console.error('❌ Erreur lors de la génération du ZIP :', err);
  process.exit(1);
});
