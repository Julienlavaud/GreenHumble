import fs from 'fs';
import path from 'path';

/**
 * Script react2static GreenHumble v1.1
 * 
 * 1. Vérifie le build statique Vite (/dist)
 * 2. Génère les bridges PHP (inclusion automatique des bundles JS/CSS compilés)
 * 3. Prépare le package statique autonome pour hébergement Apache/OVH
 */
async function runReact2Static() {
  console.log('🚀 [react2static] Initialisation de l’export statique GreenHumble...');

  const distDir = path.resolve('dist');
  const assetsDir = path.join(distDir, 'assets');

  if (!fs.existsSync(distDir)) {
    console.log('⚠️ [react2static] Le dossier /dist n’existe pas encore. Lancez d’abord : npm run build');
    return;
  }

  // Scanner les fichiers JS et CSS générés par Vite
  let jsBundle = '';
  let cssBundle = '';

  if (fs.existsSync(assetsDir)) {
    const files = fs.readdirSync(assetsDir);
    for (const file of files) {
      if (file.endsWith('.js') && !file.includes('polyfill')) {
        jsBundle = `assets/${file}`;
      }
      if (file.endsWith('.css')) {
        cssBundle = `assets/${file}`;
      }
    }
  }

  console.log(`📦 [react2static] JS Bundle détecté : ${jsBundle || 'index.js'}`);
  console.log(`🎨 [react2static] CSS Bundle détecté : ${cssBundle || 'styles.css'}`);

  // 1. Générer le fichier bridge PHP d'inclusion statique
  const phpBridgeContent = `<?php
/**
 * Bridge Statique React ➔ PHP (Généré par react2static)
 * Permet d'inclure instantanément l'interface React compilée dans n'importe quel fichier PHP
 */
$reactStaticJs = '${jsBundle}';
$reactStaticCss = '${cssBundle}';
$reactStaticVersion = '${Date.now()}';

function render_greenhumble_react_app($rootId = 'root') {
    global $reactStaticJs, $reactStaticCss, $reactStaticVersion;
    $v = floor(time() / 60) * 60;
    $html = '';
    if (!empty($reactStaticCss)) {
        $html .= '<link rel="stylesheet" href="/dist/' . $reactStaticCss . '?v=' . $v . '">' . PHP_EOL;
    }
    $html .= '<div id="' . htmlspecialchars($rootId) . '"></div>' . PHP_EOL;
    if (!empty($reactStaticJs)) {
        $html .= '<script type="module" src="/dist/' . $reactStaticJs . '?v=' . $v . '"></script>' . PHP_EOL;
    }
    return $html;
}
?>`;

  const phpBridgePath = path.resolve('api/includes/react_static_bridge.php');
  fs.writeFileSync(phpBridgePath, phpBridgeContent, 'utf-8');
  console.log(`✅ [react2static] Bridge PHP généré : ${phpBridgePath}`);

  // 2. Générer le manifeste de déploiement statique
  const manifest = {
    generator: "react2static-greenhumble",
    version: "1.0.0",
    generatedAt: new Date().toISOString(),
    bundles: {
      js: jsBundle,
      css: cssBundle
    },
    relativeBase: "./",
    instructions: [
      "Copier le dossier /dist sur votre serveur web OVH",
      "Dans votre fichier PHP, ajouter : require_once __DIR__ . '/api/includes/react_static_bridge.php';",
      "Afficher l'application avec : echo render_greenhumble_react_app();"
    ]
  };

  const manifestPath = path.join(distDir, 'react2static-manifest.json');
  fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2), 'utf-8');
  console.log(`✅ [react2static] Manifeste statique généré : ${manifestPath}`);
  console.log('🎉 [react2static] Export statique terminé avec succès !');
}

runReact2Static().catch((err) => {
  console.error('❌ Erreur react2static:', err);
  process.exit(1);
});
