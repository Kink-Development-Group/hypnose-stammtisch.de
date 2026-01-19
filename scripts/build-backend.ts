#!/usr/bin/env node
/**
 * Kombiniert Frontend-Build (dist/) mit Backend-Strukturen für Deployment.
 * TypeScript Version.
 */
import {
  copyFileSync,
  Dirent,
  existsSync,
  mkdirSync,
  readdirSync,
  writeFileSync,
} from "fs";
import { join } from "path";

const root = process.cwd();
const distDir = join(root, "dist");
const backendDir = join(root, "backend");
const targetApiDir = join(distDir, "api");

function ensureDir(p: string) {
  if (!existsSync(p)) mkdirSync(p, { recursive: true });
}

function copyTree(
  src: string,
  dest: string,
  filter?: (name: string, isDir: boolean) => boolean,
) {
  if (!existsSync(src)) return;
  ensureDir(dest);
  const entries: Dirent[] = readdirSync(src, { withFileTypes: true });
  for (const entry of entries) {
    const s = join(src, entry.name);
    const d = join(dest, entry.name);
    if (filter && !filter(entry.name, entry.isDirectory())) continue;
    if (entry.isDirectory()) copyTree(s, d, filter);
    else copyFileSync(s, d);
  }
}

if (!existsSync(distDir)) {
  console.error("dist/ fehlt. Erst frontend bauen.");
  process.exit(1);
}

// API kopieren (ohne install.php)
copyTree(
  join(backendDir, "api"),
  targetApiDir,
  (name) => name !== "install.php",
);
// vendor/src/config kopieren
["vendor", "src", "config"].forEach((dir) =>
  copyTree(join(backendDir, dir), join(distDir, dir)),
);

// uploads Verzeichnisstruktur mit .htaccess kopieren
const uploadsDir = join(backendDir, "uploads");
if (existsSync(uploadsDir)) {
  const targetUploads = join(distDir, "uploads");
  ensureDir(targetUploads);
  ensureDir(join(targetUploads, "public"));
  // .htaccess für Upload-Schutz kopieren
  const uploadsHtaccess = join(uploadsDir, ".htaccess");
  if (existsSync(uploadsHtaccess)) {
    copyFileSync(uploadsHtaccess, join(targetUploads, ".htaccess"));
  }
}

// migrations kopieren (nur konsolidierte Baseline + Runner) + Schutzdatei
const migrationsDir = join(backendDir, "migrations");
if (existsSync(migrationsDir)) {
  const targetMig = join(distDir, "migrations");
  ensureDir(targetMig);

  const migrationEntries = readdirSync(migrationsDir, {
    withFileTypes: true,
  });

  for (const entry of migrationEntries) {
    if (!entry.isFile()) {
      continue;
    }

    const filename = entry.name;
    const sourcePath = join(migrationsDir, filename);
    const targetPath = join(targetMig, filename);

    if (filename.endsWith(".sql")) {
      copyFileSync(sourcePath, targetPath);
    } else if (filename === "migrate.php" || filename === "README.md") {
      copyFileSync(sourcePath, targetPath);
    }
  }
  // .htaccess zum Schutz (falls Apache, verhindert Download der SQL Files)
  try {
    writeFileSync(
      join(targetMig, ".htaccess"),
      "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n",
    );
  } catch {}
}

// .env Datei aus backend übernehmen (wenn vorhanden)
const backendEnv = join(backendDir, ".env");
if (existsSync(backendEnv)) {
  copyFileSync(backendEnv, join(distDir, ".env"));
}

// .htaccess für SPA-Routing aus public/ kopieren
const publicHtaccess = join(root, "public", ".htaccess");
if (existsSync(publicHtaccess)) {
  copyFileSync(publicHtaccess, join(distDir, ".htaccess"));
  console.log(".htaccess für SPA-Routing kopiert.");
}

// setup.php für Web-Setup mit kopieren
const backendSetup = join(backendDir, "setup.php");
if (existsSync(backendSetup)) {
  copyFileSync(backendSetup, join(distDir, "setup.php"));
}

writeFileSync(
  join(distDir, "README_DEPLOY.txt"),
  `Deployment Paket\n- Frontend + API + backend .env (übernommen)\n- Prüfen Sie die .env auf Produktionswerte bevor Sie deployen!\n- install.php nicht enthalten\n`,
);
console.log(
  "Bundle fertig: dist/ enthält Frontend + Backend API (TS build script).",
);
