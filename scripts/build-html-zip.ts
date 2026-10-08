import fs from 'fs';
import path from 'path';
import JSZip from 'jszip';
import { execSync } from 'child_process';

async function buildHtmlZip() {
  console.log('🚀 [html-zip] Compilation du build statique HTML/JS...');
  
  // 1. Lancer le build Vite avec base relative
  execSync('npx vite build --base=./', { stdio: 'inherit' });

  const distDir = path.resolve('dist');
  if (!fs.existsSync(distDir)) {
    throw new Error('Le dossier /dist n’a pas été généré.');
  }

  const zip = new JSZip();

  // Fonction récursive pour ajouter dist/ dans le zip
  function addDirToZip(currentDir: string, zipFolder: JSZip) {
    const entries = fs.readdirSync(currentDir, { withFileTypes: true });
    for (const entry of entries) {
      const fullPath = path.join(currentDir, entry.name);
      if (entry.isDirectory()) {
        const subFolder = zipFolder.folder(entry.name);
        if (subFolder) {
          addDirToZip(fullPath, subFolder);
        }
      } else if (entry.isFile()) {
        const data = fs.readFileSync(fullPath);
        zipFolder.file(entry.name, data);
      }
    }
  }

  addDirToZip(distDir, zip);

  // Générer le ZIP
  const buffer = await zip.generateAsync({
    type: 'nodebuffer',
    compression: 'DEFLATE',
    compressionOptions: { level: 9 }
  });

  const zipFilename = 'greenhumble-html-static.zip';
  const outputPath = path.resolve(zipFilename);
  fs.writeFileSync(outputPath, buffer);
  console.log(`✅ [html-zip] Archive HTML statique générée : ${outputPath} (${Math.round(buffer.length / 1024)} Ko)`);

  // Copier dans public/ pour téléchargement direct
  const publicDir = path.resolve('public');
  if (!fs.existsSync(publicDir)) {
    fs.mkdirSync(publicDir, { recursive: true });
  }
  const publicPath = path.join(publicDir, zipFilename);
  fs.writeFileSync(publicPath, buffer);
  console.log(`✅ [html-zip] Copié dans public/ : ${publicPath}`);
}

buildHtmlZip().catch(err => {
  console.error('❌ Erreur génération ZIP HTML statique :', err);
  process.exit(1);
});
