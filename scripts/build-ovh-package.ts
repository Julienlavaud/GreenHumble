import fs from 'fs';
import path from 'path';
import crypto from 'crypto';
import JSZip from 'jszip';

async function addDirectoryToZip(zip: JSZip, rootDir: string, currentDir: string = '') {
  const fullPath = path.join(rootDir, currentDir);
  if (!fs.existsSync(fullPath)) return;

  const entries = fs.readdirSync(fullPath, { withFileTypes: true });
  for (const entry of entries) {
    const entryRelPath = currentDir ? path.join(currentDir, entry.name) : entry.name;
    const entryFullPath = path.join(rootDir, entryRelPath);

    if (
      entry.name === 'node_modules' || 
      entry.name === '.git' || 
      entry.name === '.DS_Store' ||
      entry.name === 'install.lock' ||
      entry.name === 'greenhumble-v1.1-full-ovh.zip'
    ) {
      continue;
    }

    if (entry.isDirectory()) {
      const folderZip = zip.folder(entryRelPath);
      await addDirectoryToZip(zip, rootDir, entryRelPath);
    } else if (entry.isFile()) {
      const fileData = fs.readFileSync(entryFullPath);
      zip.file(entryRelPath, fileData);
    }
  }
}

async function buildPackage() {
  console.log('🚀 [package] Génération de l’archive de déploiement OVH GreenHumble v1.1...');

  const zip = new JSZip();

  // 1. Fichiers racine indispensables
  const rootFiles = [
    'install.php',
    'flush.php',
    '.htaccess',
    'schema.sql'
  ];

  for (const f of rootFiles) {
    const p = path.resolve(f);
    if (fs.existsSync(p)) {
      zip.file(f, fs.readFileSync(p));
      console.log(`  + [root] ${f}`);
    } else {
      console.warn(`  ! [root] Fichier manquant: ${f}`);
    }
  }

  // 2. Dossier api/ (complet)
  console.log('  + Ajout de api/...');
  await addDirectoryToZip(zip, path.resolve('.'), 'api');

  // 3. Dossier admin/ (complet)
  console.log('  + Ajout de admin/...');
  await addDirectoryToZip(zip, path.resolve('.'), 'admin');

  // 4. Dossier data/ (user_style.json, etc.)
  if (fs.existsSync(path.resolve('data'))) {
    console.log('  + Ajout de data/...');
    await addDirectoryToZip(zip, path.resolve('.'), 'data');
  }

  // 5. Dossier dist/ (build statique)
  if (fs.existsSync(path.resolve('dist'))) {
    console.log('  + Ajout de dist/...');
    await addDirectoryToZip(zip, path.resolve('.'), 'dist');
  }

  // 5. Générer le fichier ZIP physique
  const buffer = await zip.generateAsync({
    type: 'nodebuffer',
    compression: 'DEFLATE',
    compressionOptions: { level: 9 }
  });

  const outputZipPath = path.resolve('greenhumble-v1.1-full-ovh.zip');
  fs.writeFileSync(outputZipPath, buffer);
  console.log(`✅ Archive générée avec succès : ${outputZipPath} (${Math.round(buffer.length / 1024)} Ko)`);

  // Sauvegarder également dans public/ pour téléchargement direct via le frontend
  const publicDir = path.resolve('public');
  if (!fs.existsSync(publicDir)) {
    fs.mkdirSync(publicDir, { recursive: true });
  }
  const publicZipPath = path.join(publicDir, 'greenhumble-v1.1-full-ovh.zip');
  fs.writeFileSync(publicZipPath, buffer);
  console.log(`✅ Archive copiée dans public/ : ${publicZipPath}`);

  // Créer le manifeste JSON du package
  const packageManifest = {
    package: "greenhumble-v1.1-full-ovh.zip",
    version: "1.1.0",
    php_target: "PHP 8.2+ (OVH Cloud Web / Mutualisé Pro / Apache 2.4+)",
    created_at: new Date().toISOString(),
    size_bytes: buffer.length,
    size_kb: Math.round(buffer.length / 1024),
    checksum_md5: crypto.createHash('md5').update(buffer).digest('hex'),
    contents: [
      "install.php (Installeur automatique prérequis, .env et dossiers)",
      "flush.php (Purgeur OPcache autonome)",
      ".htaccess (Règles Apache 2.4, Force HTTPS, Anti-cache et masquage .env)",
      "schema.sql (Base de données MySQL avec headcount, turnover, secteurs, audits et litiges)",
      "api/ (API REST complète : router, audit, prospec, scan, analyse, cascade IA Gemini 3.8/2.5/2.0 + Groq)",
      "api/admin/ (Cockpit d'administration simplifié aux couleurs de la charte)",
      "dist/ (Interface React compilée prête pour production)"
    ],
    installation: [
      "1. Téléversez le contenu de l'archive dans le dossier /www ou racine de votre hébergement OVH.",
      "2. Ouvrez votre navigateur sur https://votredomaine.com/install.php",
      "3. Renseignez votre clé GEMINI_API_KEY dans /api/config/.env",
      "4. Accédez à l'administration sur https://votredomaine.com/api/admin"
    ]
  };

  fs.writeFileSync(path.resolve('package-manifest.json'), JSON.stringify(packageManifest, null, 2), 'utf-8');
  fs.writeFileSync(path.join(publicDir, 'package-manifest.json'), JSON.stringify(packageManifest, null, 2), 'utf-8');
  console.log('✅ Manifeste du package créé avec succès.');
}

buildPackage().catch(err => {
  console.error('❌ Erreur lors de la génération du package ZIP :', err);
  process.exit(1);
});
