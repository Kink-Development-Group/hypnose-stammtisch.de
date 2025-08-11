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

writeFileSync(
  join(distDir, "README_DEPLOY.txt"),
  `Deployment Paket\n- Frontend + API\n- Bitte eigene .env außerhalb platzieren\n- install.php nicht enthalten\n`,
);
console.log(
  "Bundle fertig: dist/ enthält Frontend + Backend API (TS build script).",
);
