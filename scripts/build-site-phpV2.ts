import fs from "fs-extra";
import path from "path";
import JSZip from "jszip";

async function buildPHP() {
  const src = path.resolve("./");
  const zipPath = path.resolve("./site-php.zip");

  const zip = new JSZip();

  const includeDirs = [
    "htdocs",
    "modules",
    "conf",
    "assets",
    "api",
    "admin",
    "data",
    "sql",
    "scripts",
    "public"
  ];

  const includeFiles = [
    "index.php",
    ".htaccess",
    "install.php",
    "schema.sql"
  ];

  async function addDirToZip(folder: JSZip, dirPath: string) {
    if (!fs.existsSync(dirPath)) return;
    const entries = fs.readdirSync(dirPath, { withFileTypes: true });
    for (const entry of entries) {
      if (entry.name === 'node_modules' || entry.name === '.git' || entry.name === 'site-php.zip') continue;
      const fullPath = path.join(dirPath, entry.name);
      if (entry.isDirectory()) {
        const subFolder = folder.folder(entry.name);
        if (subFolder) {
          await addDirToZip(subFolder, fullPath);
        }
      } else if (entry.isFile()) {
        folder.file(entry.name, fs.readFileSync(fullPath));
      }
    }
  }

  for (const dir of includeDirs) {
    const dirPath = path.join(src, dir);
    if (fs.existsSync(dirPath)) {
      const subFolder = zip.folder(dir);
      if (subFolder) {
        await addDirToZip(subFolder, dirPath);
      }
    }
  }

  for (const file of includeFiles) {
    const filePath = path.join(src, file);
    if (fs.existsSync(filePath)) {
      zip.file(file, fs.readFileSync(filePath));
    }
  }

  const buffer = await zip.generateAsync({
    type: "nodebuffer",
    compression: "DEFLATE",
    compressionOptions: { level: 9 }
  });

  fs.writeFileSync(zipPath, buffer);
  console.log("✔ site-php.zip généré avec succès !");
}

buildPHP().catch(err => {
  console.error("❌ Erreur:", err);
  process.exit(1);
});
